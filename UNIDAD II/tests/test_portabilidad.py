"""Regresiones de configuración portable y aislamiento entre los dos motores.
Las pruebas de driver simulan su registro en Windows; no sustituyen una prueba
real en Windows con MySQL 8, Python y ODBC instalados.
"""
import hashlib
import os
from pathlib import Path
import subprocess
import tempfile
import unittest
from unittest.mock import patch
import pyodbc
import db_sqlserver
import iniciar
from app import create_app

class Drivers(unittest.TestCase):
    def test_windows_prefiere_driver_18_instalado(self):
        with patch('db_sqlserver.pyodbc.drivers', return_value=['ODBC Driver 17 for SQL Server', 'ODBC Driver 18 for SQL Server']):
            self.assertEqual(db_sqlserver.seleccionar_driver('auto'), 'ODBC Driver 18 for SQL Server')

    def test_windows_acepta_driver_17(self):
        with patch('db_sqlserver.pyodbc.drivers', return_value=['ODBC Driver 17 for SQL Server']):
            self.assertEqual(db_sqlserver.seleccionar_driver('auto'), 'ODBC Driver 17 for SQL Server')

    def test_windows_no_usa_ruta_linux_si_falta_driver(self):
        with patch('db_sqlserver.pyodbc.drivers', return_value=[]), patch('db_sqlserver.os.name', 'nt'):
            with self.assertRaises(pyodbc.InterfaceError):
                db_sqlserver.seleccionar_driver('auto')

    def test_ruta_de_otro_equipo_produce_error_claro(self):
        with patch('db_sqlserver.pyodbc.drivers', return_value=[]):
            with self.assertRaises(pyodbc.InterfaceError):
                db_sqlserver.seleccionar_driver('/ruta/que/no/existe/odbc.so')

class Lanzador(unittest.TestCase):
    def test_entorno_copiado_de_otro_sistema_se_aparta_y_reinstala(self):
        # Incluso con un sello previo idéntico, el entorno nuevo necesita pip.
        with tempfile.TemporaryDirectory(prefix='personal con espacios ') as temp:
            base = Path(temp)
            otro = 'bin/python' if os.name == 'nt' else 'Scripts/python.exe'
            actual = 'Scripts/python.exe' if os.name == 'nt' else 'bin/python'
            (base/'.venv'/otro).parent.mkdir(parents=True)
            (base/'.venv'/otro).write_text('entorno de otro sistema')
            (base/'.venv/no_borrar.txt').write_text('conservar')
            (base/'.local').mkdir()
            req = b'Flask>=3.1,<4\n'
            (base/'requirements.txt').write_bytes(req)
            (base/'.local/dependencias.sha256').write_text(hashlib.sha256(req).hexdigest())
            def crear(folder):
                exe = Path(folder)/actual
                exe.parent.mkdir(parents=True)
                exe.write_text('python del equipo')
            with patch.object(iniciar, 'BASE', base), patch('iniciar.venv.EnvBuilder') as builder, patch('iniciar.subprocess.run') as run:
                builder.return_value.create.side_effect = crear
                run.return_value = subprocess.CompletedProcess([], 0)
                result = iniciar.preparar_entorno()
                self.assertEqual(result, base/'.venv'/actual)
                self.assertTrue(any('pip' in call.args[0] for call in run.call_args_list))
                saved = list((base/'.local').glob('venv_otro_sistema_*'))
                self.assertEqual(len(saved), 1)
                self.assertEqual((saved[0]/'no_borrar.txt').read_text(), 'conservar')

class PuertoAjeno(unittest.TestCase):
    def test_no_cierra_ni_abre_otro_servicio(self):
        from unittest.mock import MagicMock
        socket_local = MagicMock()
        socket_local.__enter__.return_value.connect_ex.return_value = 0
        respuesta = MagicMock()
        respuesta.__enter__.return_value.headers = {'X-Application-Name': 'otro-servicio'}
        with patch('iniciar.preparar_entorno'), patch('iniciar.sys.prefix', str(iniciar.BASE/'.venv')), patch('iniciar.sys.argv', ['iniciar.py', '--sin-navegador']), patch('diagnostico.comprobar', return_value={'mysql': True, 'sqlserver': True}), patch('iniciar.socket.socket', return_value=socket_local), patch('iniciar.urllib.request.urlopen', return_value=respuesta), patch('iniciar.webbrowser.open') as abrir, patch('iniciar.subprocess.call') as proceso:
            self.assertEqual(iniciar.ejecutar(), 1)
            abrir.assert_not_called()
            proceso.assert_not_called()

class Aislamiento(unittest.TestCase):
    def test_fallo_del_destino_no_rompe_consulta_de_mysql8(self):
        c = create_app({'TESTING': True}).test_client()
        with patch('db_sqlserver.connect', side_effect=pyodbc.OperationalError('08001', 'Destino no disponible')):
            r = c.get('/api/personal/transferencia/resumen')
            self.assertEqual(r.status_code, 503)
            self.assertEqual(set(r.json), {'ok', 'error'})
            source = c.get('/api/personal')
            self.assertEqual(source.status_code, 200)
            self.assertTrue(source.json['ok'])
            self.assertGreaterEqual(source.json['total'], 15)

if __name__ == '__main__':
    unittest.main(verbosity=2)
