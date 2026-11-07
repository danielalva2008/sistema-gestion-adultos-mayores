"""Diagnóstico de solo lectura. No imprime contraseñas ni datos personales."""
import sys

def comprobar():
    from app import create_app
    import db_mysql
    import db_sqlserver
    app = create_app()
    estado = {'mysql': False, 'sqlserver': False}
    with app.app_context():
        try:
            row = db_mysql.query('SELECT VERSION() AS version', one=True)
            if not str(row['version']).startswith('8.') or 'mariadb' in str(row['version']).lower():
                raise ValueError('Se requiere MySQL 8 como motor de origen.')
            columns = db_mysql.query('SHOW COLUMNS FROM personal')
            required = {'id_personal','codigo_personal','nombres','apellidos','cargo','especialidad','telefono','email','fecha_ingreso','estado'}
            if not required.issubset({r['Field'] for r in columns}):
                raise ValueError('La tabla personal no tiene los campos del Grupo 3.')
            for table in ('residentes','actividades','incidentes'):
                db_mysql.query('SELECT 1 FROM ' + table + ' LIMIT 1')
            print('OK: MySQL 8 conectado; tabla personal y tablas de apoyo disponibles. Motor:', row['version'])
            estado['mysql'] = True
        except Exception as error:
            code = error.args[0] if error.args else type(error).__name__
            print(f'PENDIENTE: MySQL 8 ({code}). Comprueba el servicio MySQL 8 y revisa MYSQL_* en .env. No importes el SQL si la base ya existe.')
        try:
            conn = db_sqlserver.connect()
            try:
                conn.cursor().execute('SELECT TOP (0) id_personal, fecha_transferencia FROM dbo.personal')
            finally: conn.close()
            print('OK: SQL Server conectado; dbo.personal disponible.')
            estado['sqlserver'] = True
        except Exception as error:
            code = error.args[0] if error.args else type(error).__name__
            print(f'PENDIENTE: SQL Server ({code}). Revisa el servicio, el ODBC Driver 17/18 y SQLSERVER_* en .env. MySQL 8 puede funcionar por separado.')
    return estado

if __name__ == '__main__':
    result = comprobar()
    raise SystemExit(0 if all(result.values()) else 1)
