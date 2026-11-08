#!/usr/bin/env bash
# Lanzador Linux: utiliza el instalador común, sin Docker ni cambios en MySQL 8.
set -euo pipefail
cd -- "$(dirname -- "$0")"
if ! command -v python3 >/dev/null 2>&1; then
  echo "Falta Python 3.10 o posterior. Instálalo y vuelve a ejecutar este archivo."
  exit 1
fi
exec python3 iniciar.py "$@"
