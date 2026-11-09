"""
db_mysql.py - Conexion a MySQL (origen) y ayudantes de consulta.

- Toda consulta es parametrizada con %s (nunca se concatenan valores).
- query()    -> lista de dicts (SELECT que devuelve varias filas)
- query_one()-> un dict o None
- execute()  -> ejecuta INSERT/UPDATE/DELETE y devuelve (lastrowid, rowcount)
"""
import pymysql
from pymysql.cursors import DictCursor
import config


def get_connection():
    """Abre una conexion a MySQL usando las credenciales de .env."""
    return pymysql.connect(
        host=config.MYSQL["host"],
        port=config.MYSQL["port"],
        user=config.MYSQL["user"],
        password=config.MYSQL["password"],
        database=config.MYSQL["database"],
        charset="utf8mb4",
        cursorclass=DictCursor,
        autocommit=True,
    )


def query(sql, params=None):
    """Devuelve todas las filas de un SELECT como lista de diccionarios."""
    conn = get_connection()
    try:
        with conn.cursor() as cur:
            cur.execute(sql, params or ())
            return cur.fetchall()
    finally:
        conn.close()


def query_one(sql, params=None):
    """Devuelve la primera fila de un SELECT como diccionario, o None."""
    conn = get_connection()
    try:
        with conn.cursor() as cur:
            cur.execute(sql, params or ())
            return cur.fetchone()
    finally:
        conn.close()


def execute(sql, params=None):
    """Ejecuta INSERT/UPDATE/DELETE. Devuelve (lastrowid, rowcount)."""
    conn = get_connection()
    try:
        with conn.cursor() as cur:
            cur.execute(sql, params or ())
            return cur.lastrowid, cur.rowcount
    finally:
        conn.close()
