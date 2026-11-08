#!/usr/bin/env bash
# Consultas y transferencia. CRUD completo en coleccion.json.
set -euo pipefail
BASE_URL="${BASE_URL:-http://127.0.0.1:5000}"
curl --fail-with-body "$BASE_URL/api/personal/kpis"
curl --fail-with-body "$BASE_URL/api/personal/grafico"
curl --fail-with-body "$BASE_URL/api/personal?cargo=Enfermera&estado=ACTIVO"
curl --fail-with-body "$BASE_URL/api/personal?fecha_desde=2025-01-01&fecha_hasta=2025-12-31"
curl --fail-with-body -X POST "$BASE_URL/api/personal/transferir"
curl --fail-with-body -X POST "$BASE_URL/api/personal/transferir"
curl --fail-with-body "$BASE_URL/api/personal/transferencia/resumen"
