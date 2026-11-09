import os
from dotenv import load_dotenv

load_dotenv()

class Config:
    MYSQL_HOST = os.getenv("MYSQL_HOST", "localhost")
    MYSQL_PORT = int(os.getenv("MYSQL_PORT", "3306"))
    MYSQL_USER = os.getenv("MYSQL_USER", "residencia_app")
    MYSQL_PASSWORD = os.getenv("MYSQL_PASSWORD", "")
    MYSQL_DATABASE = os.getenv("MYSQL_DATABASE", "sistema_residencia")

    SQLSERVER_DRIVER = os.getenv("SQLSERVER_DRIVER", "ODBC Driver 17 for SQL Server")
    SQLSERVER_SERVER = os.getenv("SQLSERVER_SERVER", "localhost,1433")
    SQLSERVER_DATABASE = os.getenv("SQLSERVER_DATABASE", "residencia_sqlserver")
    SQLSERVER_USER = os.getenv("SQLSERVER_USER", "")
    SQLSERVER_PASSWORD = os.getenv("SQLSERVER_PASSWORD", "")