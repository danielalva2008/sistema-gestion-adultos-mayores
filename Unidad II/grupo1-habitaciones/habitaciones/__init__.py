from flask import Blueprint

# El Blueprint se define ANTES de importar los submódulos, que lo importan con:
#     from . import habitaciones_bp
habitaciones_bp = Blueprint('habitaciones', __name__, url_prefix='/api/habitaciones')

# Importaciones al final, SIN try/except: si un módulo falla, el error debe verse.
from . import kpis_grafico, routes, transferencia  # noqa: E402,F401
