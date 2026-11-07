"""Pruebas contra MySQL y SQL Server configurados en .env.
Ejecutar: .venv/bin/python -m unittest discover -s tests -v
Usar en la copia de clase: se crean/eliminan registros temporales y se transfiere personal.
"""
import json
import unittest
from datetime import date
from unittest.mock import patch, MagicMock
from uuid import uuid4
import pyodbc
from app import create_app

class Integracion(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        cls.app=create_app({'TESTING':True})
        cls.client=cls.app.test_client()
        listado=cls.client.get('/api/personal')
        if listado.status_code != 200: raise RuntimeError('MySQL 8 no está disponible para las pruebas.')
        cls.total_origen=listado.json['total']
        cls.activos_origen=sum(x['estado']=='ACTIVO' for x in listado.json['data'])

    def payload(self):
        return {'codigo_personal':'T'+uuid4().hex[:15], 'nombres':'Prueba',
            'apellidos':'Temporal', 'cargo':'Prueba', 'especialidad':'',
            'telefono':'999111222','email':'prueba@example.com',
            'fecha_ingreso':'2025-01-01','estado':'ACTIVO'}

    def test_01_kpis_del_script(self):
        r=self.client.get('/api/personal/kpis')
        self.assertEqual(r.status_code,200)
        d=r.json['data']
        self.assertEqual(d['personal_activo'],self.activos_origen)
        self.assertGreaterEqual(d['personal_activo'],15)
        self.assertEqual(d['residentes_por_cuidador'],6.7)
        self.assertEqual(d['actividades_por_responsable'],3)
        self.assertEqual(d['incidentes_por_persona'],3)
        cargos={x['cargo']:x['cantidad'] for x in d['distribucion_cargo']}
        self.assertEqual(cargos['Enfermera'],4)
        self.assertEqual(cargos['Cuidador'],3)

    def test_02_grafico_sin_multiplicar_joins(self):
        r=self.client.get('/api/personal/grafico')
        self.assertEqual(r.status_code,200)
        rows={x['cargo']:x for x in r.json['data']}
        self.assertEqual(rows['Enfermera']['incidentes'],10)
        self.assertEqual(rows['Psicólogo']['actividades'],3)
        self.assertEqual(rows['Psicólogo']['incidentes'],2)
        self.assertEqual(sum(x['actividades'] for x in rows.values()),12)
        self.assertEqual(sum(x['incidentes'] for x in rows.values()),12)

    def test_03_filtros_y_limites_inclusivos(self):
        r=self.client.get('/api/personal?cargo=Enfermera&estado=ACTIVO')
        self.assertEqual(r.json['total'],4)
        r=self.client.get('/api/personal?fecha_desde=2025-01-01&fecha_hasta=2025-12-31')
        self.assertEqual(r.json['total'],6)
        r=self.client.get('/api/personal?fecha_desde=2025-01-13&fecha_hasta=2025-01-13')
        self.assertEqual(r.json['total'],1)
        self.assertEqual(r.json['data'][0]['codigo_personal'],'PER007')
        self.assertEqual(self.client.get('/api/personal?q=PER015').json['total'],1)
        self.assertEqual(self.client.get('/api/personal',query_string={'q':"' OR 1=1 --"}).json['total'],0)

    def test_04_fecha_invalida_y_cero_denominador(self):
        for path in ['/api/personal?fecha_desde=2025-02-30',
                     '/api/personal?fecha_desde=2025-12-31&fecha_hasta=2025-01-01',
                     '/api/personal?estado=OTRO','/api/personal/kpis?fecha_desde=2025-2-01']:
            r=self.client.get(path);self.assertEqual(r.status_code,400);self.assertFalse(r.json['ok'])
        r=self.client.get('/api/personal/kpis?fecha_desde=1900-01-01&fecha_hasta=1900-01-02')
        self.assertEqual(r.status_code,200)
        for k in ('personal_activo','antiguedad_promedio','residentes_por_cuidador','actividades_por_responsable','incidentes_por_persona'):
            self.assertEqual(r.json['data'][k],0)

    def test_05_crud_y_codigo_unico(self):
        d=self.payload()
        r=self.client.post('/api/personal',json=d)
        self.assertEqual(r.status_code,201)
        self.assertEqual(r.json['total'],1)
        id=r.json['data']['id_personal']
        try:
            self.assertEqual(self.client.get('/api/personal/'+str(id)).json['data']['codigo_personal'],d['codigo_personal'])
            self.assertEqual(self.client.post('/api/personal',json=d).status_code,409)
            d['estado']='INACTIVO'
            r=self.client.put('/api/personal/'+str(id),json=d)
            self.assertEqual(r.status_code,200)
            self.assertEqual(self.client.get('/api/personal/'+str(id)).json['data']['estado'],'INACTIVO')
        finally:
            self.assertEqual(self.client.delete('/api/personal/'+str(id)).status_code,204)
        self.assertEqual(self.client.get('/api/personal/'+str(id)).status_code,404)
        self.assertEqual(self.client.delete('/api/personal/'+str(id)).status_code,404)

    def test_06_no_eliminar_personal_con_referencias(self):
        for id in (1,4):
            r=self.client.delete('/api/personal/'+str(id))
            self.assertEqual(r.status_code,409)
            self.assertIn('INACTIVO',r.json['error'])
            self.assertEqual(self.client.get('/api/personal/'+str(id)).status_code,200)

    def test_07_validaciones_formulario(self):
        for field,value in [('email','sin-arroba'),('telefono','999ABC'),
                            ('fecha_ingreso','9999-01-01'),('estado','OTRO'),
                            ('nombres',''),('cargo','a'*81),('nombres',123)]:
            d=self.payload();d[field]=value
            r=self.client.post('/api/personal',json=d)
            self.assertEqual(r.status_code,400,(field,r.json))
        for data in [None,[],{'id_personal':1}]:
            self.assertEqual(self.client.post('/api/personal',json=data).status_code,400)

    def test_08_catalogos_y_paginas(self):
        r=self.client.get('/api/personal/catalogos')
        self.assertEqual(r.status_code,200);self.assertIn('Enfermera',r.json['data']['cargos'])
        for path in ['/','/index3.html','/tables/data.html','/crud.html']:
            r=self.client.get(path);self.assertEqual(r.status_code,200)
            self.assertIn(b'Gesti\xc3\xb3n de Personal',r.data)
            self.assertIn(b'css/adminlte.css',r.data)

    def test_09_transferencia_repetida(self):
        inicial=self.client.get('/api/personal/transferencia/resumen').json['data']['sqlserver']
        first=self.client.post('/api/personal/transferir')
        self.assertEqual(first.status_code,200,first.json)
        self.assertEqual(first.json['leidos'],self.total_origen)
        self.assertEqual(first.json['insertados']+first.json['actualizados'],self.total_origen)
        second=self.client.post('/api/personal/transferir')
        self.assertEqual(second.status_code,200,second.json)
        self.assertEqual(second.json['insertados'],0)
        self.assertEqual(second.json['actualizados'],self.total_origen)
        r=self.client.get('/api/personal/transferencia/resumen')
        self.assertEqual(r.status_code,200)
        self.assertEqual(r.json['data']['mysql'],self.total_origen)
        self.assertEqual(r.json['data']['sqlserver'],inicial+first.json['insertados'])
        self.assertGreaterEqual(r.json['data']['sqlserver'],self.total_origen)
        self.assertTrue(r.json['data']['ultima_transferencia'])

    def test_10_rollback_ante_error_de_destino(self):
        conn=MagicMock()
        conn.cursor.return_value.execute.side_effect=pyodbc.IntegrityError('23000','Conflicto simulado')
        with patch('modulo.transferencia.db_sqlserver.connect',return_value=conn):
            r=self.client.post('/api/personal/transferir')
        self.assertEqual(r.status_code,500)
        self.assertFalse(r.json['ok'])
        conn.rollback.assert_called_once();conn.commit.assert_not_called();conn.close.assert_called_once()

    def test_12_contrato_json(self):
        for path in ['/api/personal', '/api/personal/1', '/api/personal/kpis',
                     '/api/personal/grafico', '/api/personal/catalogos',
                     '/api/personal/transferencia/resumen']:
            r = self.client.get(path)
            self.assertEqual(r.status_code, 200)
            self.assertTrue(r.json['ok'])
            self.assertIn('data', r.json)
            self.assertIsInstance(r.json['total'], int)
            if isinstance(r.json['data'], list):
                self.assertEqual(r.json['total'], len(r.json['data']))
            else:
                self.assertEqual(r.json['total'], 1)
        r = self.client.get('/api/personal/9999999')
        self.assertEqual(r.status_code, 404)
        self.assertEqual(set(r.json), {'ok', 'error'})

    def test_11_destino_no_disponible(self):
        with patch('modulo.transferencia.db_sqlserver.connect',side_effect=pyodbc.OperationalError('08001','Sin conexión')):
            r=self.client.post('/api/personal/transferir')
        self.assertEqual(r.status_code,503)
        self.assertFalse(r.json['ok'])

if __name__=='__main__':unittest.main(verbosity=2)
