"""Endpoints para sincronizar habitaciones de MySQL hacia SQL Server."""
import logging
import time
from datetime import datetime

import pyodbc
from flask import jsonify

from . import habitaciones_bp
from db_mysql import query, DatabaseUnavailableError
from db_sqlserver import get_sqlserver_connection, SQLServerUnavailableError

log = logging.getLogger(__name__)

MERGE_HABITACION = """
MERGE dbo.habitaciones AS d
USING (VALUES (?,?,?,?,?,?)) AS s
    (id_habitacion, numero, piso, capacidad, estado, observaciones)
ON d.id_habitacion = s.id_habitacion
WHEN MATCHED THEN UPDATE SET
    d.numero = s.numero, d.piso = s.piso, d.capacidad = s.capacidad,
    d.estado = s.estado, d.observaciones = s.observaciones,
    d.fecha_transferencia = SYSDATETIME()
WHEN NOT MATCHED THEN INSERT
    (id_habitacion, numero, piso, capacidad, estado, observaciones)
    VALUES (s.id_habitacion, s.numero, s.piso, s.capacidad,
            s.estado, s.observaciones)
OUTPUT $action;
"""


@habitaciones_bp.post('/transferir')
def transferir_habitaciones():
    inicio = time.perf_counter()
    try:
        filas = query("""SELECT id_habitacion, numero, piso, capacidad, estado,
                              observaciones FROM habitaciones ORDER BY id_habitacion""")
    except DatabaseUnavailableError:
        return jsonify(ok=False, error="Base de datos MySQL no disponible"), 503
    except Exception:
        log.exception("Error al leer habitaciones desde MySQL")
        return jsonify(ok=False, error="Error al consultar habitaciones"), 500

    try:
        conn = get_sqlserver_connection()
    except SQLServerUnavailableError:
        return jsonify(ok=False, error="Base de datos SQL Server no disponible"), 503

    insertados = actualizados = 0
    try:
        with conn.cursor() as cur:
            for fila in filas:
                params = (fila['id_habitacion'], fila['numero'], fila['piso'],
                          fila['capacidad'], fila['estado'], fila['observaciones'])
                cur.execute(MERGE_HABITACION, params)
                accion = cur.fetchone()[0].upper()
                if accion == 'INSERT':
                    insertados += 1
                elif accion == 'UPDATE':
                    actualizados += 1
                else:
                    raise RuntimeError('Acción MERGE inesperada')
        conn.commit()
        return jsonify(ok=True, leidos=len(filas), insertados=insertados,
                       actualizados=actualizados,
                       duracion_ms=round((time.perf_counter() - inicio) * 1000),
                       transferido_el=datetime.now().isoformat(timespec='seconds')), 200
    except Exception:
        conn.rollback()
        log.exception("Falló la transferencia de habitaciones")
        return jsonify(ok=False, error="Error durante la transferencia a SQL Server"), 500
    finally:
        conn.close()


@habitaciones_bp.get('/transferencia/resumen')
def resumen_transferencia():
    try:
        origen = query('SELECT COUNT(*) AS total FROM habitaciones', fetchone=True)
    except DatabaseUnavailableError:
        return jsonify(ok=False, error="Base de datos MySQL no disponible"), 503
    except Exception:
        log.exception("Error al contar habitaciones en MySQL")
        return jsonify(ok=False, error="Error al consultar MySQL"), 500

    try:
        conn = get_sqlserver_connection()
    except SQLServerUnavailableError:
        return jsonify(ok=False, error="Base de datos SQL Server no disponible"), 503
    try:
        with conn.cursor() as cur:
            cur.execute('SELECT COUNT(*), MAX(fecha_transferencia) FROM dbo.habitaciones')
            total, ultima = cur.fetchone()
        return jsonify(ok=True, mysql_total=int(origen['total']),
                       sqlserver_total=int(total),
                       ultima_transferencia=ultima.isoformat(timespec='seconds') if ultima else None), 200
    except pyodbc.Error:
        log.exception("Error al consultar resumen SQL Server")
        return jsonify(ok=False, error="Error al consultar SQL Server"), 503
    finally:
        conn.close()
