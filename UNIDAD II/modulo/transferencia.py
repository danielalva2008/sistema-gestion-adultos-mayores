"""Transferencia idempotente: MERGE por PK, transacción y rollback."""
from collections import Counter
from datetime import datetime
from time import perf_counter
from zoneinfo import ZoneInfo
from flask import jsonify, current_app
import pyodbc
from db_mysql import query
import db_sqlserver


# ======================================================================
# COLUMNAS QUE SE COPIAN
# La llave primaria se conserva desde MySQL. fecha_transferencia no viene
# del origen: SQL Server la genera al insertar y la actualiza en el MERGE.
# ======================================================================
CAMPOS = ('id_personal','codigo_personal','nombres','apellidos','cargo','especialidad',
          'telefono','email','fecha_ingreso','estado')

# ======================================================================
# MERGE SIN DUPLICADOS
# Compara id_personal. Si ya existe, actualiza; si no, inserta. Los signos
# ? son parámetros de pyodbc. OUTPUT $action indica INSERT o UPDATE para
# contar los resultados reales. HOLDLOCK protege la coincidencia de la PK.
# ======================================================================
MERGE = """
MERGE dbo.personal WITH (HOLDLOCK) AS d
USING (VALUES (?,?,?,?,?,?,?,?,?,?)) AS s
(id_personal,codigo_personal,nombres,apellidos,cargo,especialidad,telefono,email,fecha_ingreso,estado)
ON d.id_personal=s.id_personal
WHEN MATCHED THEN UPDATE SET
  d.codigo_personal=s.codigo_personal, d.nombres=s.nombres, d.apellidos=s.apellidos,
  d.cargo=s.cargo, d.especialidad=s.especialidad, d.telefono=s.telefono,
  d.email=s.email, d.fecha_ingreso=s.fecha_ingreso, d.estado=s.estado,
  d.fecha_transferencia=SYSDATETIME()
WHEN NOT MATCHED THEN INSERT
  (id_personal,codigo_personal,nombres,apellidos,cargo,especialidad,telefono,email,fecha_ingreso,estado)
  VALUES (s.id_personal,s.codigo_personal,s.nombres,s.apellidos,s.cargo,s.especialidad,
          s.telefono,s.email,s.fecha_ingreso,s.estado)
OUTPUT $action;
"""

def registrar_transferencia(bp):

    # ======================================================================
    # EJECUTAR LA TRANSFERENCIA
    # Lee toda la tabla, sin los filtros visibles del dashboard. Procesa las
    # filas en una única transacción del destino. Si una fila falla, rollback
    # deshace el intento completo. Solo se hace commit al terminar.
    # ======================================================================
    @bp.post('/transferir')
    def transferir():
        start = perf_counter()
        rows = query('SELECT '+', '.join(CAMPOS)+' FROM personal ORDER BY id_personal')
        conn = db_sqlserver.connect()  # Fallo de conexión: manejador 503.
        counts = Counter()
        try:
            cur = conn.cursor()
            cur.execute('SET XACT_ABORT ON;')
            for row in rows:
                result = cur.execute(MERGE, *[row[c] for c in CAMPOS]).fetchone()
                counts[result[0]] += 1
            conn.commit()
        except Exception:
            # Ante cualquier fallo posterior a la conexión, revertir el intento.
            conn.rollback()
            current_app.logger.warning('Transferencia cancelada: rollback aplicado.')
            return jsonify(ok=False, error='La transferencia falló. Se deshicieron todos los cambios del intento.'), 500
        finally:
            conn.close()
        return jsonify(ok=True, leidos=len(rows), insertados=counts['INSERT'],
            actualizados=counts['UPDATE'], duracion_ms=round((perf_counter()-start)*1000),
            transferido_el=datetime.now(ZoneInfo('America/Lima')).isoformat(timespec='seconds'))


    # ======================================================================
    # RESUMEN DEL ORIGEN Y DESTINO
    # Consulta los conteos de ambas bases y MAX(fecha_transferencia).
    # El frontend lo actualiza después de pulsar Transferir.
    # ======================================================================
    @bp.get('/transferencia/resumen')
    def resumen():
        source = query('SELECT COUNT(*) AS total FROM personal', one=True)['total']
        try:
            conn = db_sqlserver.connect()
        except pyodbc.Error:
            return jsonify(ok=False, error='SQL Server no está disponible. MySQL 8 sigue funcionando; revisa el destino y el driver ODBC.'), 503
        try:
            row = conn.cursor().execute('SELECT COUNT(*), MAX(fecha_transferencia) FROM dbo.personal').fetchone()
            return jsonify(ok=True, total=1, data={'mysql':source, 'sqlserver':row[0],
                'ultima_transferencia':row[1].isoformat() if row[1] else None})
        finally:
            conn.close()
