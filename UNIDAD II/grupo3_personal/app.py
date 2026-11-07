"""Punto de entrada: python app.py. También admite Flask desde VS Code."""
import os
from datetime import date, datetime
from decimal import Decimal
import pymysql
import pyodbc
from flask import Flask, jsonify, render_template
from flask.json.provider import DefaultJSONProvider
from werkzeug.exceptions import HTTPException
from config import Config


# ======================================================================
# FORMATO DE RESPUESTAS JSON
# MySQL puede devolver fechas y números Decimal. Este proveedor los convierte
# a fechas ISO y números que JavaScript puede mostrar sin procesar SQL.
# ======================================================================
class JSONProvider(DefaultJSONProvider):
    @staticmethod
    def default(value):
        if isinstance(value, (date, datetime)): return value.isoformat()
        if isinstance(value, Decimal): return float(value)
        return DefaultJSONProvider.default(value)


# ======================================================================
# CREACIÓN DE LA APLICACIÓN
# Carga la configuración de .env, registra el Blueprint del módulo y define
# las tres páginas. La misma API sirve al navegador, Postman y curl.
# ======================================================================
def create_app(test_config=None):
    app = Flask(__name__, template_folder='html', static_folder='static')
    app.json = JSONProvider(app)
    app.config.from_object(Config)
    if test_config: app.config.update(test_config)
    from modulo.routes import personal
    app.register_blueprint(personal, url_prefix='/api/personal')


    # ======================================================================
    # PÁGINAS DE LA INTERFAZ
    # Flask renderiza las vistas de html/. Todas heredan base.html para compartir
    # el menú, los recursos de AdminLTE y los mensajes de la aplicación.
    # ======================================================================
    @app.get('/')
    @app.get('/index3.html')
    def dashboard():
        return render_template('index3.html', page='dashboard', title='Dashboard de personal')

    @app.get('/tables/data.html')
    def listado():
        return render_template('tables/data.html', page='listado', title='Listado de personal')

    @app.get('/crud.html')
    def mantenimiento():
        return render_template('crud.html', page='crud', title='Mantenimiento de personal')


    # ======================================================================
    # ERRORES CONTROLADOS Y SEGURIDAD
    # Se responde siempre con ok=false y un mensaje en JSON. Los conflictos
    # son 409 y los servicios no disponibles son 503. No se exponen consultas
    # ni credenciales y no se registran datos personales en los logs.
    # ======================================================================
    @app.errorhandler(HTTPException)
    def http_error(error):
        return jsonify(ok=False, error=error.description), error.code

    @app.errorhandler(pymysql.err.IntegrityError)
    def integrity_error(error):
        if error.args[0] == 1062:
            message = 'Ya existe un trabajador con ese código de personal.'
        else:
            message = 'La operación entra en conflicto con registros relacionados.'
        return jsonify(ok=False, error=message), 409

    @app.errorhandler(pymysql.err.OperationalError)
    @app.errorhandler(pymysql.err.InterfaceError)
    def mysql_error(error):
        app.logger.warning('MySQL no disponible (%s).', type(error).__name__)
        return jsonify(ok=False, error='MySQL no está disponible. Revisa el servicio y la configuración .env.'), 503

    @app.errorhandler(pyodbc.Error)
    def sqlserver_error(error):
        app.logger.warning('SQL Server no disponible (%s).', type(error).__name__)
        return jsonify(ok=False, error='SQL Server no está disponible. Revisa el driver, el servicio y .env.'), 503

    @app.errorhandler(Exception)
    def unexpected(error):
        # No se registran consultas, contraseñas, nombres, correos ni teléfonos.
        app.logger.error('Error interno (%s).', type(error).__name__)
        return jsonify(ok=False, error='No se pudo completar la operación.'), 500


    # ======================================================================
    # CABECERAS DE RESPUESTA
    # Evita que el navegador interprete un contenido como otro tipo de archivo
    # y que almacene respuestas de la API con datos de personal.
    # ======================================================================
    @app.after_request
    def headers(response):
        response.headers['X-Content-Type-Options'] = 'nosniff'
        response.headers['X-Application-Name'] = 'grupo3_personal'
        if response.mimetype == 'application/json': response.headers['Cache-Control'] = 'no-store'
        return response
    return app


# ======================================================================
# EJECUCIÓN LOCAL
# Este bloque solo se ejecuta al iniciar python app.py. El servidor escucha
# en esta computadora; el puerto y la depuración se configuran con .env.
# ======================================================================
if __name__ == '__main__':
    create_app().run(host='127.0.0.1', port=int(os.getenv('FLASK_PORT','5000')),
                     debug=os.getenv('FLASK_DEBUG','0') == '1')
