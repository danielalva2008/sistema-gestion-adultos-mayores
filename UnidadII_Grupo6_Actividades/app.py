"""
app.py - Crea la aplicacion Flask, registra el Blueprint del modulo
Actividades y sirve las paginas del front (dashboard, listado y CRUD).

Ejecutar:
    python app.py
Luego abrir:  http://127.0.0.1:5000/
"""
import os
import pymysql
from flask import Flask, send_from_directory, jsonify
from flask_cors import CORS

import config
from modulo.routes import bp as actividades_bp

BASE_DIR = os.path.dirname(os.path.abspath(__file__))
HTML_DIR = os.path.join(BASE_DIR, "html")
STATIC_DIR = os.path.join(BASE_DIR, "static")


def create_app():
    # static_url_path=/static sirve la carpeta static/ (copia de dist/ AdminLTE)
    app = Flask(__name__, static_folder=STATIC_DIR, static_url_path="/static")
    CORS(app)  # permite abrir el HTML desde otro puerto si hace falta

    # API del modulo
    app.register_blueprint(actividades_bp)

    # ---- Paginas del front ----
    @app.get("/")
    def dashboard():
        return send_from_directory(HTML_DIR, "index3.html")

    @app.get("/listado")
    def listado():
        return send_from_directory(os.path.join(HTML_DIR, "tables"), "data.html")

    @app.get("/crud")
    def crud():
        return send_from_directory(HTML_DIR, "crud.html")

    # Si MySQL (origen) no esta disponible, responder 503 en JSON en vez de
    # un error 500 crudo ("503 base de datos no disponible").
    @app.errorhandler(pymysql.MySQLError)
    def mysql_no_disponible(ex):
        return jsonify({"ok": False,
                        "error": f"Base de datos MySQL no disponible: {ex}"}), 503

    return app


app = create_app()

if __name__ == "__main__":
    app.run(host=config.FLASK["host"],
            port=config.FLASK["port"],
            debug=config.FLASK["debug"])
