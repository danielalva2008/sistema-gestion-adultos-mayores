"""Conexión a MySQL con PyMySQL y función query() parametrizada.

Uso en los módulos de los demás integrantes:
    from db_mysql import query

    filas = query("SELECT * FROM habitaciones WHERE piso = %s", (1,))        # lista de dicts
    fila  = query("SELECT * FROM habitaciones WHERE id_habitacion = %s", (3,), fetchone=True)  # dict o None
    res   = query("INSERT INTO habitaciones (...) VALUES (%s, ...)", (...), commit=True)
    # con commit=True devuelve {"rowcount": N, "lastrowid": ID}

Siempre se usan parámetros %s: NUNCA concatenar valores dentro del SQL.
Si MySQL no responde se lanza DatabaseUnavailableError y app.py responde HTTP 503.
"""
import logging

import pymysql
from pymysql.cursors import DictCursor

from config import Config

log = logging.getLogger(__name__)


class DatabaseUnavailableError(Exception):
    """MySQL no está disponible (servidor apagado, credenciales o red)."""

    def __init__(self, message="Base de datos MySQL no disponible"):
        super().__init__(message)


def get_mysql_connection():
    try:
        return pymysql.connect(
            host=Config.MYSQL_HOST,
            port=Config.MYSQL_PORT,
            user=Config.MYSQL_USER,
            password=Config.MYSQL_PASSWORD,
            database=Config.MYSQL_DB,
            charset="utf8mb4",
            cursorclass=DictCursor,
            connect_timeout=5,
        )
    except (pymysql.MySQLError, OSError) as exc:
        # Se registra el tipo de fallo (sin credenciales ni datos personales).
        log.error("No se pudo conectar a MySQL: %s", exc)
        raise DatabaseUnavailableError("Base de datos MySQL no disponible") from exc


def query(sql, params=None, fetchone=False, commit=False):
    conn = get_mysql_connection()
    try:
        with conn.cursor() as cur:
            cur.execute(sql, params)
            if commit:
                conn.commit()
                return {"rowcount": cur.rowcount, "lastrowid": cur.lastrowid}
            return cur.fetchone() if fetchone else cur.fetchall()
    except Exception:
        if commit:
            conn.rollback()
        raise
    finally:
        conn.close()
