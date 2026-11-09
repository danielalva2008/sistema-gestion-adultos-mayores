"""
transferencia.py - Transferencia MySQL -> SQL Server del modulo Actividades.

POST /api/actividades/transferir
  1. Lee todas las actividades de MySQL.
  2. Abre SQL Server con pyodbc y una transaccion.
  3. Aplica MERGE por la llave primaria (id_actividad): actualiza si existe,
     inserta si no existe -> operacion idempotente, no duplica datos.
  4. commit; ante cualquier error rollback y respuesta 500.
  5. Responde con conteos y fecha de la transferencia.

GET /api/actividades/transferencia/resumen
  Cantidad de registros en MySQL y en SQL Server + fecha de la ultima
  transferencia. Si SQL Server no esta disponible responde 503.
"""
import time
import datetime
from flask import jsonify

import db_mysql as mysql
import db_sqlserver as sqlserver


# MERGE idempotente por id_actividad. Incluye 'estado' (ENUM en MySQL ->
# NVARCHAR con CHECK en SQL Server).
MERGE_SQL = """
MERGE dbo.actividades AS d
USING (VALUES (?,?,?,?,?,?,?,?,?,?)) AS s
      (id_actividad, id_personal, nombre, descripcion, fecha,
       hora_inicio, hora_fin, lugar, cupo, estado)
   ON d.id_actividad = s.id_actividad
 WHEN MATCHED THEN UPDATE SET
       d.id_personal = s.id_personal,
       d.nombre      = s.nombre,
       d.descripcion = s.descripcion,
       d.fecha       = s.fecha,
       d.hora_inicio = s.hora_inicio,
       d.hora_fin    = s.hora_fin,
       d.lugar       = s.lugar,
       d.cupo        = s.cupo,
       d.estado      = s.estado,
       d.fecha_transferencia = SYSDATETIME()
 WHEN NOT MATCHED THEN INSERT
       (id_actividad, id_personal, nombre, descripcion, fecha,
        hora_inicio, hora_fin, lugar, cupo, estado)
      VALUES (s.id_actividad, s.id_personal, s.nombre, s.descripcion, s.fecha,
              s.hora_inicio, s.hora_fin, s.lugar, s.cupo, s.estado);
"""


def _td_a_time(value):
    """Convierte timedelta (TIME de MySQL) a datetime.time para pyodbc."""
    if isinstance(value, datetime.timedelta):
        total = int(value.total_seconds())
        return datetime.time(total // 3600, (total % 3600) // 60, total % 60)
    return value


def transferir_actividades():
    inicio = time.time()

    # 1) Leer todo desde MySQL
    filas = mysql.query(
        "SELECT id_actividad, id_personal, nombre, descripcion, fecha, "
        "       hora_inicio, hora_fin, lugar, cupo, estado "
        "FROM actividades ORDER BY id_actividad")
    leidos = len(filas)

    # 2) Conectar a SQL Server y aplicar el MERGE dentro de una transaccion
    try:
        conn = sqlserver.get_connection()
    except Exception as ex:  # SQL Server no disponible
        return jsonify({"ok": False,
                        "error": f"SQL Server no disponible: {ex}"}), 503

    insertados = actualizados = 0
    try:
        cur = conn.cursor()
        for f in filas:
            # Cuantos existian antes para distinguir insert vs update
            cur.execute(
                "SELECT COUNT(*) FROM dbo.actividades WHERE id_actividad = ?",
                f["id_actividad"])
            existia = cur.fetchone()[0]

            cur.execute(MERGE_SQL, (
                f["id_actividad"], f["id_personal"], f["nombre"],
                f["descripcion"], f["fecha"],
                _td_a_time(f["hora_inicio"]), _td_a_time(f["hora_fin"]),
                f["lugar"], f["cupo"], f["estado"]))

            if existia:
                actualizados += 1
            else:
                insertados += 1

        conn.commit()
    except Exception as ex:
        conn.rollback()
        conn.close()
        return jsonify({"ok": False,
                        "error": f"Error en la transferencia: {ex}"}), 500

    conn.close()
    duracion_ms = int((time.time() - inicio) * 1000)
    return jsonify({
        "ok": True,
        "leidos": leidos,
        "insertados": insertados,
        "actualizados": actualizados,
        "duracion_ms": duracion_ms,
        "transferido_el": datetime.datetime.now().strftime("%Y-%m-%dT%H:%M:%S"),
    }), 200


def resumen_transferencia():
    # Conteo en MySQL (origen)
    r = mysql.query_one("SELECT COUNT(*) AS n FROM actividades")
    total_mysql = int(r["n"])

    # Conteo y ultima fecha en SQL Server (destino)
    try:
        conn = sqlserver.get_connection()
    except Exception as ex:
        return jsonify({"ok": False,
                        "error": f"SQL Server no disponible: {ex}"}), 503
    try:
        cur = conn.cursor()
        cur.execute("SELECT COUNT(*), MAX(fecha_transferencia) "
                    "FROM dbo.actividades")
        total_sql, ultima = cur.fetchone()
    except Exception as ex:
        conn.close()
        return jsonify({"ok": False,
                        "error": f"No se pudo leer el destino: {ex}"}), 503
    conn.close()

    return jsonify({
        "ok": True,
        "mysql": total_mysql,
        "sqlserver": int(total_sql or 0),
        "ultima_transferencia": ultima.strftime("%Y-%m-%dT%H:%M:%S")
                                if ultima else None,
    }), 200
