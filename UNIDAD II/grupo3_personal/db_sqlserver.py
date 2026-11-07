"""Destino SQL Server con el mismo código en Windows y Linux."""
from pathlib import Path
import os
import pyodbc
from flask import current_app

# ======================================================================
# VALORES Y DRIVER ODBC
# No se comparten rutas absolutas entre sistemas. Windows busca el driver
# instalado; Linux también puede usar su copia local en .local/odbc.
# No se instala ni se cambia ningún driver del sistema desde la aplicación.
# ======================================================================
def odbc_value(value):
    return '{' + str(value).replace('}', '}}') + '}'

def seleccionar_driver(configurado='auto'):
    configured = configurado.strip()
    installed = pyodbc.drivers()
    if configured and configured.lower() != 'auto':
        if configured in installed or Path(configured).is_file():
            return configured
        raise pyodbc.InterfaceError('IM002', 'El driver configurado no existe en este equipo. Usa SQLSERVER_DRIVER=auto o instala el ODBC Driver 17/18.')
    for driver in ('ODBC Driver 18 for SQL Server', 'ODBC Driver 17 for SQL Server'):
        if driver in installed:
            return driver
    if os.name != 'nt':
        folder = Path(__file__).resolve().parent / '.local/odbc/opt/microsoft/msodbcsql18/lib64'
        drivers = sorted(folder.glob('libmsodbcsql-*.so.*'))
        if drivers: return str(drivers[-1])
    raise pyodbc.InterfaceError('IM002', 'Falta instalar Microsoft ODBC Driver 17 o 18 para SQL Server en este equipo.')

# ======================================================================
# CONEXIÓN INDEPENDIENTE AL DESTINO
# Solo usa SQLSERVER_* de .env. La conexión a MySQL 8 permanece separada.
# No inicia Docker, no crea bases y no modifica credenciales ni permisos.
# ======================================================================
def connect():
    c = current_app.config
    server = c.get('SQLSERVER_SERVER') or f"{c['SQLSERVER_HOST']},{c['SQLSERVER_PORT']}"
    values = {'DRIVER': seleccionar_driver(c['SQLSERVER_DRIVER']),
        'SERVER': server, 'DATABASE': c['SQLSERVER_DATABASE'],
        'UID': c['SQLSERVER_USER'], 'PWD': c['SQLSERVER_PASSWORD'],
        'Encrypt': 'yes', 'TrustServerCertificate': c['SQLSERVER_TRUST_CERTIFICATE']}
    conn = pyodbc.connect(';'.join(k+'='+odbc_value(v) for k,v in values.items()),
                          timeout=5, autocommit=False)
    conn.timeout = 30
    return conn
