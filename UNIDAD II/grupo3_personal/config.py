"""Configuración de ambos motores desde .env, sin credenciales en el código."""
import os
from pathlib import Path
from dotenv import load_dotenv

# ======================================================================
# LECTURA DE VARIABLES DE ENTORNO
# Se localiza .env junto a app.py para poder iniciar desde VS Code o desde
# la terminal. .env contiene secretos y no se incluye en la entrega.
# ======================================================================
BASE_DIR = Path(__file__).resolve().parent
load_dotenv(BASE_DIR / '.env')

# ======================================================================
# PARÁMETROS DE LOS DOS MOTORES
# MYSQL_* identifica el origen operativo. SQLSERVER_* identifica la copia
# de destino. Cada motor usa su propio usuario con permisos limitados.
# ======================================================================
class Config:
    MYSQL_HOST = os.getenv('MYSQL_HOST', '127.0.0.1')
    MYSQL_PORT = int(os.getenv('MYSQL_PORT', '3306'))
    MYSQL_DATABASE = os.getenv('MYSQL_DATABASE', 'sistema_residencia')
    MYSQL_USER = os.getenv('MYSQL_USER', 'residencia_app')
    MYSQL_PASSWORD = os.getenv('MYSQL_PASSWORD', '')
    SQLSERVER_DRIVER = os.getenv('SQLSERVER_DRIVER', 'auto')
    SQLSERVER_SERVER = os.getenv('SQLSERVER_SERVER', '').strip()
    SQLSERVER_HOST = os.getenv('SQLSERVER_HOST', '127.0.0.1')
    SQLSERVER_PORT = int(os.getenv('SQLSERVER_PORT', '1433'))
    SQLSERVER_DATABASE = os.getenv('SQLSERVER_DATABASE', 'residencia_sqlserver')
    SQLSERVER_USER = os.getenv('SQLSERVER_USER', 'residencia_sql')
    SQLSERVER_PASSWORD = os.getenv('SQLSERVER_PASSWORD', '')
    SQLSERVER_TRUST_CERTIFICATE = os.getenv('SQLSERVER_TRUST_CERTIFICATE', 'yes')
    MAX_CONTENT_LENGTH = 32 * 1024
