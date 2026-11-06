import pyodbc
from flask import current_app


def escape(value):
    return "{" + str(value).replace("}", "}}") + "}"


def connect():
    c = current_app.config
    values = {"DRIVER": c["SQLSERVER_DRIVER"], "SERVER": c["SQLSERVER_HOST"],
        "DATABASE": c["SQLSERVER_DATABASE"], "UID": c["SQLSERVER_USER"],
        "PWD": c["SQLSERVER_PASSWORD"], "Encrypt": c["SQLSERVER_ENCRYPT"],
        "TrustServerCertificate": c["SQLSERVER_TRUST_CERTIFICATE"]}
    return pyodbc.connect(";".join(k + "=" + escape(v) for k, v in values.items()),
                          timeout=5, autocommit=False)
