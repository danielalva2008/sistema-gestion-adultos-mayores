import os

from flask import Flask, jsonify, send_from_directory
from flask_cors import CORS

from db_mysql import DatabaseUnavailableError
from habitaciones import habitaciones_bp

BASE_DIR = os.path.abspath(os.path.dirname(__file__))
HTML_DIR = os.path.join(BASE_DIR, "html")

app = Flask(__name__, static_folder="static", static_url_path="/static")
CORS(app)
app.register_blueprint(habitaciones_bp)


# ---- Vistas HTML (AdminLTE 4) ----
@app.route("/")
@app.route("/index3.html")
def dashboard():
    return send_from_directory(HTML_DIR, "index3.html")


@app.route("/tables/data.html")
def listado():
    return send_from_directory(HTML_DIR, "tables/data.html")


@app.route("/crud.html")
def crud():
    return send_from_directory(HTML_DIR, "crud.html")


# ---- Manejo global de MySQL caído -> HTTP 503 ----
@app.errorhandler(DatabaseUnavailableError)
def handle_db_unavailable(e):
    return jsonify({"ok": False, "error": str(e)}), 503


if __name__ == "__main__":
    app.run(host="127.0.0.1", port=5000, debug=True)
