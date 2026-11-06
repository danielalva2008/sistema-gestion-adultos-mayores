"""Configuración del proyecto. Lee las variables del archivo .env (no versionado).

Los demás integrantes la importan así:  from config import Config
NO cambiar los nombres de los atributos: otros módulos dependen de ellos.
"""
import os

from dotenv import load_dotenv

load_dotenv(os.path.join(os.path.dirname(os.path.abspath(__file__)), ".env"))


class Config:
    # MySQL (origen)
    MYSQL_HOST = os.getenv("MYSQL_HOST", "localhost")
    MYSQL_PORT = int(os.getenv("MYSQL_PORT", "3306"))
    MYSQL_USER = os.getenv("MYSQL_USER", "residencia_app")
    MYSQL_PASSWORD = os.getenv("MYSQL_PASSWORD", "")
    MYSQL_DB = os.getenv("MYSQL_DB", "sistema_residencia")

    # SQL Server (destino de la transferencia)
    SQLSERVER_DRIVER = os.getenv("SQLSERVER_DRIVER", "{ODBC Driver 17 for SQL Server}")
    SQLSERVER_SERVER = os.getenv("SQLSERVER_SERVER", "localhost,1433")
    SQLSERVER_DB = os.getenv("SQLSERVER_DB", "residencia_sqlserver")
    SQLSERVER_USER = os.getenv("SQLSERVER_USER", "residencia_sql")
    SQLSERVER_PASSWORD = os.getenv("SQLSERVER_PASSWORD", "")
