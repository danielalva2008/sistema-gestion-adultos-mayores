#!/usr/bin/env bash
# Entorno académico aislado: usa los ejecutables instalados de XAMPP.
set -euo pipefail
module_dir="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
xampp_dir="${XAMPP_DIR:-/opt/lampp}"
runtime_dir="$module_dir/.local"
mode="${1:-serve}"
if [[ "$mode" != serve && "$mode" != test ]]; then
    echo 'Uso: bash personal/dev.sh [serve|test]' >&2
    exit 1
fi
for executable in bin/php bin/mysql bin/mysql_install_db sbin/mysqld; do
    [[ -x "$xampp_dir/$executable" ]] || { echo "No se encuentra $xampp_dir/$executable" >&2; exit 1; }
done
mkdir -p "$runtime_dir"
exec 9>"$runtime_dir/entorno.lock"
flock -n 9 || { echo 'El entorno de Personal ya está iniciado. Detén la otra terminal con Ctrl+C.' >&2; exit 1; }
if [[ ! -d "$runtime_dir/mysql/mysql" ]]; then
    "$xampp_dir/bin/mysql_install_db" --no-defaults --basedir="$xampp_dir" --datadir="$runtime_dir/mysql" >"$runtime_dir/install.log" 2>&1
fi
socket_dir="$(mktemp -d /tmp/residencia-personal.XXXXXX)"
db_pid=''
cleanup() {
    if [[ -n "$db_pid" ]] && kill -0 "$db_pid" 2>/dev/null; then
        kill "$db_pid"
        wait "$db_pid" || true
    fi
    rm -f "$socket_dir/mysql.sock" "$socket_dir/mysql.pid"
    rmdir "$socket_dir" 2>/dev/null || true
}
trap cleanup EXIT
trap 'exit 130' INT TERM
"$xampp_dir/sbin/mysqld" --no-defaults --basedir="$xampp_dir" --datadir="$runtime_dir/mysql" \
    --socket="$socket_dir/mysql.sock" --pid-file="$socket_dir/mysql.pid" \
    --skip-networking --log-error="$runtime_dir/mysql.log" &
db_pid=$!
mysql_args=(--no-defaults --socket="$socket_dir/mysql.sock" --user="$(id -un)")
ready=false
for attempt in {1..50}; do
    if "$xampp_dir/bin/mysql" "${mysql_args[@]}" -e 'SELECT 1' >/dev/null 2>&1; then ready=true; break; fi
    if ! kill -0 "$db_pid" 2>/dev/null; then break; fi
    sleep 0.2
done
if [[ "$ready" != true ]]; then
    echo "No se pudo iniciar la base local. Revisa $runtime_dir/mysql.log" >&2
    exit 1
fi
exists="$("$xampp_dir/bin/mysql" "${mysql_args[@]}" -Nse "SELECT COUNT(*) FROM information_schema.schemata WHERE schema_name='sistema_residencia'")"
if [[ "$exists" == 0 ]]; then
    echo 'Preparando los datos ficticios para la primera ejecución...'
    "$xampp_dir/bin/mysql" "${mysql_args[@]}" < "$module_dir/database-inicial.sql"
fi
export PERSONAL_DB_SOCKET="$socket_dir/mysql.sock"
if [[ "$mode" == test ]]; then
    "$xampp_dir/bin/php" "$module_dir/test.php"
else
    echo 'Personal listo en http://127.0.0.1:8084'
    echo 'Deja esta terminal abierta. Ctrl+C detiene el entorno y conserva los datos.'
    "$xampp_dir/bin/php" -S 127.0.0.1:8084 -t "$module_dir" "$module_dir/router.php"
fi
