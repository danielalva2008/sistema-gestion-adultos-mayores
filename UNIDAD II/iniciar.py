"""Arranque común: Python crea un entorno PROPIO en Windows o Linux.
No inicia, detiene, importa ni elimina bases. No administra Docker ni MySQL 8.
Uso: python iniciar.py [--sin-navegador] [--diagnostico]
"""
import argparse
import hashlib
import os
from pathlib import Path
import shutil
import socket
import subprocess
import sys
import threading
import time
import venv
import webbrowser
import urllib.request
import urllib.error

BASE = Path(__file__).resolve().parent

def preparar_entorno():
    """Crear/usar .venv para ESTE sistema e instalar dependencias si cambiaron."""
    if sys.version_info < (3, 10):
        raise RuntimeError('Se necesita Python 3.10 o posterior.')
    folder = BASE / '.venv'
    interpreter = folder / ('Scripts/python.exe' if os.name == 'nt' else 'bin/python')
    compatible = interpreter.is_file()
    if compatible:
        try:
            probe = subprocess.run([str(interpreter), '-c', 'import sys; assert sys.version_info >= (3, 10)'], capture_output=True, timeout=10)
            compatible = probe.returncode == 0
        except (OSError, subprocess.SubprocessError):
            compatible = False
    creado = not compatible
    if creado:
        if folder.exists():
            saved = BASE / '.local' / ('venv_otro_sistema_' + str(time.time_ns()))
            saved.parent.mkdir(exist_ok=True)
            folder.rename(saved)
        print('Creando el entorno de Python de esta computadora...', flush=True)
        venv.EnvBuilder(with_pip=True).create(folder)
    requirements = BASE / 'requirements.txt'
    digest = hashlib.sha256(requirements.read_bytes()).hexdigest()
    stamp = BASE / '.local' / 'dependencias.sha256'
    if creado or not stamp.exists() or stamp.read_text().strip() != digest:
        print('Comprobando e instalando dependencias...', flush=True)
        subprocess.run([str(interpreter), '-m', 'pip', 'install', '-r', str(requirements)], check=True)
        stamp.parent.mkdir(exist_ok=True)
        stamp.write_text(digest)
    return interpreter

def abrir_cuando_responda(port):
    """Abrir el navegador solo cuando el servidor de ESTA ejecución escuche."""
    for _ in range(40):
        try:
            with socket.create_connection(('127.0.0.1', port), timeout=.3):
                webbrowser.open(f'http://127.0.0.1:{port}/')
                return
        except OSError:
            time.sleep(.25)

def ejecutar():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--sin-navegador', action='store_true')
    parser.add_argument('--diagnostico', action='store_true')
    args = parser.parse_args()
    os.chdir(BASE)
    interpreter = preparar_entorno()
    # Reejecutar el lanzador con su entorno para no usar paquetes globales.
    if Path(sys.prefix).resolve() != (BASE / '.venv').resolve():
        return subprocess.call([str(interpreter), str(BASE / 'iniciar.py'), *sys.argv[1:]])
    if not (BASE / '.env').is_file():
        shutil.copyfile(BASE / '.env.example', BASE / '.env')
        if os.name != 'nt': (BASE / '.env').chmod(0o600)
        print('Se creó .env. Configura las credenciales de MySQL 8 y SQL Server de este equipo y vuelve a iniciar.')
        return 1
    from dotenv import load_dotenv
    load_dotenv(BASE / '.env')
    from diagnostico import comprobar
    estado = comprobar()
    if args.diagnostico:
        return 0 if estado['mysql'] and estado['sqlserver'] else 1
    port = int(os.getenv('FLASK_PORT', '5000'))
    # Nunca se cierra un proceso ajeno que ya esté usando el puerto.
    with socket.socket() as probe:
        if probe.connect_ex(('127.0.0.1', port)) == 0:
            propia = False
            try:
                with urllib.request.urlopen(f'http://127.0.0.1:{port}/', timeout=2) as respuesta:
                    propia = respuesta.headers.get('X-Application-Name') == 'grupo3_personal'
            except (OSError, urllib.error.URLError):
                pass
            if propia:
                print(f'Personal ya está funcionando en http://127.0.0.1:{port}/. No se inicia otra copia.')
                if not args.sin_navegador: webbrowser.open(f'http://127.0.0.1:{port}/')
                return 0
            print(f'Otro servicio ocupa el puerto {port}. No se detuvo ni modificó. Cambia FLASK_PORT en .env para iniciar Personal.')
            return 1
    if not args.sin_navegador:
        threading.Thread(target=abrir_cuando_responda, args=(port,), daemon=True).start()
    print(f'Abre http://127.0.0.1:{port}/. Mantén esta terminal abierta.', flush=True)
    # Las páginas siguen disponibles con mensajes claros aunque falte una base.
    from app import create_app
    create_app().run(host='127.0.0.1', port=port, debug=False, use_reloader=False)
    return 0

if __name__ == '__main__':
    try:
        raise SystemExit(ejecutar())
    except KeyboardInterrupt:
        print('Aplicación detenida. Las bases de datos no se modificaron.')
    except (OSError, ValueError, subprocess.SubprocessError, ImportError, RuntimeError) as error:
        print('No se pudo iniciar:', error)
        print('Revisa Python, las dependencias y .env. No se cambió ningún servicio de bases de datos.')
        raise SystemExit(1)
