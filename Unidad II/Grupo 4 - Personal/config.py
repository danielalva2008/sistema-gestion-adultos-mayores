import os
from pathlib import Path
from dotenv import load_dotenv

ROOT = Path(__file__).resolve().parent
load_dotenv(ROOT / ".env")

class Config:
    MYSQL_HOST = os.getenv("MYSQL_HOST", "127.0.0.1")
    MYSQL_PORT = int(os.getenv("MYSQL_PORT", "3306"))
    MYSQL_DATABASE = os.getenv("MYSQL_DATABASE", "sistema_residencia")
    MYSQL_USER = os.getenv("MYSQL_USER", "residencia_app")
    MYSQL_PASSWORD = os.getenv("MYSQL_PASSWORD", "")
    MYSQL_SOCKET = os.getenv("MYSQL_SOCKET") or None
    SQLSERVER_DRIVER = os.getenv("SQLSERVER_DRIVER", "ODBC Driver 18 for SQL Server")
    SQLSERVER_HOST = os.getenv("SQLSERVER_HOST", "localhost,1433")
    SQLSERVER_DATABASE = os.getenv("SQLSERVER_DATABASE", "residencia_sqlserver")
    SQLSERVER_USER = os.getenv("SQLSERVER_USER", "residencia_sql")
    SQLSERVER_PASSWORD = os.getenv("SQLSERVER_PASSWORD", "")
    SQLSERVER_ENCRYPT = os.getenv("SQLSERVER_ENCRYPT", "yes")
    SQLSERVER_TRUST_CERTIFICATE = os.getenv("SQLSERVER_TRUST_CERTIFICATE", "yes")
    MAX_CONTENT_LENGTH = 16 * 1024
