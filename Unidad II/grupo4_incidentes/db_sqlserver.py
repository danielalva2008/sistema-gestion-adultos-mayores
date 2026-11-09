import pyodbc
from config import Config

def get_connection():
    conn_str = (
        f"DRIVER={{{Config.SQLSERVER_DRIVER}}};"
        f"SERVER={Config.SQLSERVER_SERVER};"
        f"DATABASE={Config.SQLSERVER_DATABASE};"
        f"UID={Config.SQLSERVER_USER};"
        f"PWD={Config.SQLSERVER_PASSWORD};"
        "TrustServerCertificate=yes;"
    )
    return pyodbc.connect(conn_str)

def query(sql, params=None, fetchone=False):
    conn = get_connection()
    try:
        cur = conn.cursor()
        cur.execute(sql, params or [])
        columns = [c[0] for c in cur.description] if cur.description else []
        rows = cur.fetchall()
        data = [dict(zip(columns, row)) for row in rows]
        return data[0] if fetchone and data else (data if not fetchone else None)
    finally:
        conn.close()