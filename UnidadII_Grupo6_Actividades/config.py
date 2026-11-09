"""
config.py - Lectura centralizada de las variables de entorno (.env).

Todas las credenciales viven en .env (no versionado). Aqui solo se leen
y se exponen como diccionarios para MySQL y SQL Server.
"""
import os
from dotenv import load_dotenv

# Carga el archivo .env ubicado junto a este modulo
load_dotenv(os.path.join(os.path.dirname(__file__), ".env"))


def _int(name, default):
    try:
        return int(os.getenv(name, default))
    except (TypeError, ValueError):
        return int(default)


# ---- Origen: MySQL ----
MYSQL = {
    "host": os.getenv("MYSQL_HOST", "localhost"),
    "port": _int("MYSQL_PORT", 3306),
    "database": os.getenv("MYSQL_DB", "sistema_residencia"),
    "user": os.getenv("MYSQL_USER", "residencia_app"),
    "password": os.getenv("MYSQL_PASSWORD", ""),
}

# ---- Destino: SQL Server ----
SQLSERVER = {
    "driver": os.getenv("SQLSERVER_DRIVER", "ODBC Driver 17 for SQL Server"),
    "host": os.getenv("SQLSERVER_HOST", "localhost"),
    "port": _int("SQLSERVER_PORT", 1433),
    "database": os.getenv("SQLSERVER_DB", "residencia_sqlserver"),
    "user": os.getenv("SQLSERVER_USER", ""),
    "password": os.getenv("SQLSERVER_PASSWORD", ""),
    # Si TRUSTED=1 se usa autenticacion de Windows (sin usuario/clave) y el
    # servidor se indica sin puerto (memoria compartida/named pipes).
    "trusted": os.getenv("SQLSERVER_TRUSTED", "0") == "1",
}

# ---- Flask ----
FLASK = {
    "host": os.getenv("FLASK_HOST", "127.0.0.1"),
    "port": _int("FLASK_PORT", 5000),
    "debug": os.getenv("FLASK_DEBUG", "1") == "1",
}
