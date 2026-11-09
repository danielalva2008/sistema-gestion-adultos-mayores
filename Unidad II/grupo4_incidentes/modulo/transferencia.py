"""Transferencia MySQL -> SQL Server (MERGE por id_incidente)."""
import time
from datetime import datetime
 
import pymysql
import pyodbc
from flask import Blueprint, jsonify
 
import db_mysql
import db_sqlserver
 
bp = Blueprint("transferencia", __name__)
 
COLUMNAS = (
    "id_incidente", "id_residente", "id_tipo_incidente", "id_personal_reporta",
    "fecha_hora", "lugar", "descripcion", "accion_realizada",
    "requiere_seguimiento", "estado", "fecha_cierre",
)
 
SQL_ORIGEN = f"SELECT {', '.join(COLUMNAS)} FROM incidentes ORDER BY id_incidente"
 
# Un MERGE por fila; OUTPUT $action indica si fue INSERT o UPDATE.
SQL_MERGE = """
MERGE dbo.incidentes AS destino
USING (SELECT ? AS id_incidente, ? AS id_residente, ? AS id_tipo_incidente,
              ? AS id_personal_reporta, ? AS fecha_hora, ? AS lugar,
              ? AS descripcion, ? AS accion_realizada,
              ? AS requiere_seguimiento, ? AS estado, ? AS fecha_cierre,
              ? AS fecha_transferencia) AS origen
ON destino.id_incidente = origen.id_incidente
WHEN MATCHED THEN UPDATE SET
    id_residente         = origen.id_residente,
    id_tipo_incidente    = origen.id_tipo_incidente,
    id_personal_reporta  = origen.id_personal_reporta,
    fecha_hora           = origen.fecha_hora,
    lugar                = origen.lugar,
    descripcion          = origen.descripcion,
    accion_realizada     = origen.accion_realizada,
    requiere_seguimiento = origen.requiere_seguimiento,
    estado               = origen.estado,
    fecha_cierre         = origen.fecha_cierre,
    fecha_transferencia  = origen.fecha_transferencia
WHEN NOT MATCHED THEN INSERT
    (id_incidente, id_residente, id_tipo_incidente, id_personal_reporta,
     fecha_hora, lugar, descripcion, accion_realizada, requiere_seguimiento,
     estado, fecha_cierre, fecha_transferencia)
VALUES
    (origen.id_incidente, origen.id_residente, origen.id_tipo_incidente,
     origen.id_personal_reporta, origen.fecha_hora, origen.lugar,
     origen.descripcion, origen.accion_realizada, origen.requiere_seguimiento,
     origen.estado, origen.fecha_cierre, origen.fecha_transferencia)
OUTPUT $action;
"""
 
ERRORES_CONEXION = (
    pymysql.err.OperationalError,
    pymysql.err.InterfaceError,
    pyodbc.OperationalError,
    pyodbc.InterfaceError,
)
 
 
def _json_error(mensaje, codigo):
    return jsonify({"ok": False, "error": mensaje}), codigo
 
 
@bp.post("/transferir")
def transferir():
    inicio = time.perf_counter()
 
    # 1) Leer todo de MySQL
    try:
        filas = db_mysql.query(SQL_ORIGEN)
    except ERRORES_CONEXION:
        return _json_error("MySQL no está disponible", 503)
 
    # 2) Escribir en SQL Server dentro de una sola transacción
    try:
        conn = db_sqlserver.get_connection()
    except ERRORES_CONEXION:
        return _json_error("SQL Server no está disponible", 503)
 
    ahora = datetime.now()
    insertados = actualizados = 0
    try:
        conn.autocommit = False
        cur = conn.cursor()
        for f in filas:
            valores = [f[c] for c in COLUMNAS]
            # BIT: 0/1 -> bool
            valores[COLUMNAS.index("requiere_seguimiento")] = bool(f["requiere_seguimiento"])
            valores.append(ahora)
            cur.execute(SQL_MERGE, valores)
            accion = cur.fetchone()[0]
            if accion == "INSERT":
                insertados += 1
            else:
                actualizados += 1
        conn.commit()
    except Exception as e:
        conn.rollback()
        return _json_error(f"Transferencia revertida (rollback): {e}", 500)
    finally:
        conn.close()
 
    return jsonify({
        "ok": True,
        "leidos": len(filas),
        "insertados": insertados,
        "actualizados": actualizados,
        "duracion_ms": round((time.perf_counter() - inicio) * 1000),
        "transferido_el": ahora.strftime("%Y-%m-%d %H:%M:%S"),
    })
 
 
@bp.get("/transferencia/resumen")
def resumen():
    try:
        mysql_total = db_mysql.query(
            "SELECT COUNT(*) AS n FROM incidentes", fetchone=True)["n"]
    except ERRORES_CONEXION:
        return _json_error("MySQL no está disponible", 503)
 
    try:
        fila = db_sqlserver.query(
            "SELECT COUNT(*) AS n, MAX(fecha_transferencia) AS ultima "
            "FROM dbo.incidentes", fetchone=True)
    except ERRORES_CONEXION:
        return _json_error("SQL Server no está disponible", 503)
 
    ultima = fila["ultima"]
    return jsonify({
        "ok": True,
        "mysql_total": mysql_total,
        "sqlserver_total": fila["n"],
        "ultima_transferencia": ultima.strftime("%Y-%m-%d %H:%M:%S") if ultima else None,
    })