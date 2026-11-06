from contextlib import closing
from datetime import timezone
from time import perf_counter
import pyodbc
from flask import Blueprint, jsonify
from db_mysql import query
from db_sqlserver import connect

transfer_bp = Blueprint("transferencia", __name__, url_prefix="/api/personal")
COLUMNS = ("id_personal", "codigo_personal", "nombres", "apellidos", "cargo", "especialidad",
           "telefono", "email", "fecha_ingreso", "estado")
MERGE = """MERGE dbo.personal WITH (HOLDLOCK) AS d
USING (VALUES (?,?,?,?,?,?,?,?,?,?)) AS s
(id_personal,codigo_personal,nombres,apellidos,cargo,especialidad,telefono,email,fecha_ingreso,estado)
ON d.id_personal=s.id_personal
WHEN MATCHED THEN UPDATE SET codigo_personal=s.codigo_personal,nombres=s.nombres,
apellidos=s.apellidos,cargo=s.cargo,especialidad=s.especialidad,telefono=s.telefono,
email=s.email,fecha_ingreso=s.fecha_ingreso,estado=s.estado,fecha_transferencia=SYSUTCDATETIME()
WHEN NOT MATCHED THEN INSERT
(id_personal,codigo_personal,nombres,apellidos,cargo,especialidad,telefono,email,fecha_ingreso,estado)
VALUES (s.id_personal,s.codigo_personal,s.nombres,s.apellidos,s.cargo,s.especialidad,s.telefono,
s.email,s.fecha_ingreso,s.estado)
OUTPUT $action;"""


@transfer_bp.post("/transferir")
def transfer():
    start = perf_counter()
    rows = query("SELECT " + ",".join(COLUMNS) + " FROM personal ORDER BY id_personal")
    # Los errores de conexión se traducen a 503 en la aplicación.
    with closing(connect()) as conn:
        try:
            cur = conn.cursor()
            cur.execute("SET XACT_ABORT ON")
            counts = {"INSERT": 0, "UPDATE": 0}
            for row in rows:
                cur.execute(MERGE, tuple(row[c] for c in COLUMNS))
                counts[cur.fetchone()[0]] += 1
            # Una tabla de control conserva la fecha incluso al transferir cero filas.
            cur.execute("SELECT SYSUTCDATETIME()")
            at = cur.fetchone()[0]
            cur.execute("""UPDATE dbo.transferencias_personal WITH (UPDLOCK,SERIALIZABLE)
                SET transferido_el=?,leidos=?,insertados=?,actualizados=? WHERE id=1;
                IF @@ROWCOUNT=0 INSERT INTO dbo.transferencias_personal
                (id,transferido_el,leidos,insertados,actualizados) VALUES (1,?,?,?,?);""",
                at,len(rows),counts["INSERT"],counts["UPDATE"],at,len(rows),counts["INSERT"],counts["UPDATE"])
            conn.commit()
        except pyodbc.Error:
            conn.rollback()
            return jsonify(ok=False, error="La transferencia falló y se revirtió por completo. Revise la tabla y sus restricciones en SQL Server."), 500
    return jsonify(ok=True, leidos=len(rows), insertados=counts["INSERT"], actualizados=counts["UPDATE"],
                   duracion_ms=round((perf_counter()-start)*1000), transferido_el=at.replace(tzinfo=timezone.utc))


@transfer_bp.get("/transferencia/resumen")
def summary():
    source = query("SELECT COUNT(*) AS total FROM personal")[0]["total"]
    with closing(connect()) as conn:
        cur = conn.cursor()
        cur.execute("SELECT COUNT(*) FROM dbo.personal")
        target = cur.fetchone()[0]
        cur.execute("SELECT transferido_el FROM dbo.transferencias_personal WHERE id=1")
        row = cur.fetchone()
    return jsonify(ok=True, data={"mysql": source, "sqlserver": target, "ultima_transferencia": row[0].replace(tzinfo=timezone.utc) if row else None})
