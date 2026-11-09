"""
db_sqlserver.py - Conexion a SQL Server (destino) con pyodbc.

Se usa solo en la transferencia MySQL -> SQL Server. La cadena de conexion
se arma con las variables de .env y nunca usa el usuario 'sa'.
"""
import config

# pyodbc se importa de forma diferida (dentro de get_connection) para que el
# resto de la app (dashboard, KPIs, CRUD, listado) funcione aunque el driver
# ODBC o SQL Server todavia no esten instalados. Solo la transferencia lo usa.


def build_conn_str():
    s = config.SQLSERVER
    if s.get("trusted"):
        # Autenticacion de Windows (sin puerto: memoria compartida/named pipes)
        return (
            f"DRIVER={{{s['driver']}}};"
            f"SERVER={s['host']};"
            f"DATABASE={s['database']};"
            f"Trusted_Connection=yes;TrustServerCertificate=yes"
        )
    # Autenticacion de SQL Server (usuario/clave por TCP, como pide el plan)
    return (
        f"DRIVER={{{s['driver']}}};"
        f"SERVER={s['host']},{s['port']};"
        f"DATABASE={s['database']};"
        f"UID={s['user']};PWD={s['password']};"
        f"TrustServerCertificate=yes"
    )


def get_connection():
    """Abre una conexion a SQL Server. autocommit=False para usar transaccion."""
    import pyodbc  # import diferido: solo se necesita al transferir
    return pyodbc.connect(build_conn_str(), autocommit=False)
