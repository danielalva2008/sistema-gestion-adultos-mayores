
"""Conexión a SQL Server para el módulo de transferencia."""
import pyodbc
from config import Config


class SQLServerUnavailableError(Exception):
    """No fue posible establecer la conexión con SQL Server."""


def get_sqlserver_connection():
    conn_str = (
        f"DRIVER={Config.SQLSERVER_DRIVER};"
        f"SERVER={Config.SQLSERVER_SERVER};"
        f"DATABASE={Config.SQLSERVER_DB};"
        "Trusted_Connection=yes;"
        "TrustServerCertificate=yes;"
        "Connection Timeout=5;"
    )

    try:
        return pyodbc.connect(conn_str, autocommit=False)
    except pyodbc.Error as exc:
        raise SQLServerUnavailableError(
            "Base de datos SQL Server no disponible"
        ) from exc
