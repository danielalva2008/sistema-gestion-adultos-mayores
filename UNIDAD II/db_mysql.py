"""Conexiones cortas con PyMySQL y consultas parametrizadas con %s."""
from contextlib import contextmanager
import pymysql
from flask import current_app


# ======================================================================
# ABRIR CONEXIÓN A MYSQL
# Devuelve filas como diccionarios para convertirlas a JSON. Usa UTF-8
# y la zona horaria de Lima. La aplicación conecta como residencia_app.
# ======================================================================
def connect():
    c = current_app.config
    return pymysql.connect(host=c['MYSQL_HOST'], port=c['MYSQL_PORT'],
        user=c['MYSQL_USER'], password=c['MYSQL_PASSWORD'], database=c['MYSQL_DATABASE'],
        charset='utf8mb4', cursorclass=pymysql.cursors.DictCursor,
        autocommit=False, init_command="SET time_zone='-05:00'", connect_timeout=5, read_timeout=15, write_timeout=15)


# ======================================================================
# TRANSACCIONES
# Al completar el bloque se confirma con commit. Ante una excepción se
# aplica rollback y siempre se cierra la conexión para liberar recursos.
# ======================================================================
@contextmanager
def transaction():
    conn = connect()
    try:
        yield conn
        conn.commit()
    except Exception:
        conn.rollback()
        raise
    finally:
        conn.close()


# ======================================================================
# CONSULTAS DE LECTURA REUTILIZABLES
# Recibe SQL y parámetros separados. one=True obtiene una sola fila.
# Si se entrega una conexión existente, comparte su transacción.
# ======================================================================
def query(sql, params=(), *, one=False, connection=None):
    own = connection is None
    conn = connection if connection is not None else connect()
    try:
        with conn.cursor() as cur:
            cur.execute(sql, params)
            return cur.fetchone() if one else cur.fetchall()
    finally:
        if own:
            conn.close()
