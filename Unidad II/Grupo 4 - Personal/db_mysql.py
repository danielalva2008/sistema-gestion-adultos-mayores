import pymysql
from flask import current_app


def connect():
    c = current_app.config
    return pymysql.connect(host=c["MYSQL_HOST"], port=c["MYSQL_PORT"],
        user=c["MYSQL_USER"], password=c["MYSQL_PASSWORD"], database=c["MYSQL_DATABASE"],
        unix_socket=c["MYSQL_SOCKET"], charset="utf8mb4",
        cursorclass=pymysql.cursors.DictCursor, autocommit=False,
        connect_timeout=5, read_timeout=15, write_timeout=15)


def query(sql, params=()):
    with connect() as conn:
        with conn.cursor() as cursor:
            cursor.execute(sql, params)
            return cursor.fetchall()
