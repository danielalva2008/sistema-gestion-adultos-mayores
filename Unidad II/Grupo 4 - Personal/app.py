import os
import logging
from datetime import date, datetime
from decimal import Decimal
import pymysql
import pyodbc
from flask import Flask, jsonify, render_template, request
from flask.json.provider import DefaultJSONProvider
from werkzeug.exceptions import HTTPException
from config import Config


class JSONProvider(DefaultJSONProvider):
    @staticmethod
    def default(value):
        if isinstance(value, (date, datetime)):
            return value.isoformat()
        if isinstance(value, Decimal):
            return float(value)
        return DefaultJSONProvider.default(value)


def create_app(test_config=None):
    logging.getLogger("werkzeug").setLevel(logging.ERROR)
    app = Flask(__name__, template_folder="html")
    app.json = JSONProvider(app)
    app.config.from_object(Config)
    if test_config:
        app.config.update(test_config)
    from modulo.routes import bp
    from modulo.transferencia import transfer_bp
    app.register_blueprint(bp)
    app.register_blueprint(transfer_bp)

    @app.before_request
    def same_origin():
        if request.method in ("POST", "PUT", "DELETE"):
            origin = request.headers.get("Origin")
            if origin and origin != request.host_url.rstrip("/"):
                return jsonify(ok=False, error="Origen de la solicitud no permitido"), 403

    @app.after_request
    def headers(response):
        response.headers["X-Content-Type-Options"] = "nosniff"
        response.headers["X-Frame-Options"] = "DENY"
        response.headers["Referrer-Policy"] = "same-origin"
        if request.path.startswith("/api/"):
            response.headers["Cache-Control"] = "no-store"
        return response

    @app.get("/")
    @app.get("/index3.html")
    def dashboard():
        return render_template("index3.html", active="dashboard", title="Dashboard")

    @app.get("/tables/data.html")
    def listing():
        return render_template("tables/data.html", active="listado", title="Listado de personal")

    @app.get("/crud.html")
    def maintenance():
        return render_template("crud.html", active="crud", title="Mantenimiento de personal")

    @app.errorhandler(pymysql.IntegrityError)
    def integrity(error):
        return jsonify(ok=False, error="El código ya existe o el registro tiene relaciones que impiden la operación"), 409

    @app.errorhandler(pymysql.OperationalError)
    @app.errorhandler(pyodbc.InterfaceError)
    @app.errorhandler(pyodbc.OperationalError)
    def unavailable(error):
        return jsonify(ok=False, error="Base de datos no disponible. Revise la conexión y las credenciales locales."), 503

    @app.errorhandler(HTTPException)
    def http_error(error):
        return jsonify(ok=False, error=error.description), error.code

    @app.errorhandler(Exception)
    def unexpected(error):
        # Solo se registra la clase, nunca SQL, credenciales o información personal.
        app.logger.error("Error interno: %s", type(error).__name__)
        return jsonify(ok=False, error="No se pudo completar la operación"), 500

    return app


app = create_app()
if __name__ == "__main__":
    app.run(host="127.0.0.1", port=int(os.getenv("PORT", "5004")), debug=False)
