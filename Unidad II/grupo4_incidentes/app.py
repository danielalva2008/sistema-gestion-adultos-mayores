from flask import Flask, jsonify, send_from_directory
from flask_cors import CORS
from modulo.routes import bp as incidentes_bp
from modulo.transferencia import bp as transferencia_bp

app = Flask(__name__, static_folder="static", static_url_path="/static")
CORS(app)

app.register_blueprint(incidentes_bp, url_prefix="/api/incidentes")
app.register_blueprint(transferencia_bp, url_prefix="/api/incidentes")

@app.get("/")
def raiz():
    return send_from_directory("html", "index.html")

@app.get("/<path:pagina>.html")
def paginas(pagina):
    return send_from_directory("html", pagina + ".html")

@app.get("/api/health")
def health():
    return jsonify({"ok": True, "modulo": "incidentes"})

if __name__ == "__main__":
    app.run(debug=True, port=5000)