"""Pruebas de integración sobre bases académicas aisladas.
TEST_MYSQL_DATABASE y TEST_SQLSERVER_DATABASE son obligatorias y deben acabar en _test.
"""
import os
from uuid import uuid4
import pytest
import pyodbc
from app import create_app
from db_mysql import connect
from db_sqlserver import connect as target_connect

@pytest.fixture()
def app():
    source=os.getenv("TEST_MYSQL_DATABASE", "")
    target=os.getenv("TEST_SQLSERVER_DATABASE", "")
    if not source.endswith("_test") or not target.endswith("_test"):
        pytest.skip("Se requieren bases aisladas con sufijo _test")
    return create_app({"TESTING": True,"MYSQL_DATABASE":source,"SQLSERVER_DATABASE":target})

@pytest.fixture()
def client(app):
    return app.test_client()

@pytest.fixture()
def body():
    return {"codigo_personal":"T-"+uuid4().hex[:16], "nombres":"Prueba", "apellidos":"Integracion",
            "cargo":"Analista", "especialidad":"Sistemas", "telefono":"999111222",
            "email":"prueba@example.test", "fecha_ingreso":"2025-01-01", "estado":"ACTIVO"}

@pytest.fixture()
def person(client,body):
    result=client.post('/api/personal',json=body)
    assert result.status_code==201,result.json
    identifier=result.json['data']['id_personal']
    yield identifier
    client.delete('/api/personal/'+str(identifier))


def test_reference_kpis(client):
    result=client.get('/api/personal/kpis')
    assert result.status_code==200
    data=result.json['data']
    assert data['personal_activo']==15
    assert data['residentes_por_cuidador']==6.7
    assert data['actividades_por_responsable']==3
    # El SQL heredado tiene un incidente sin reportante: 11 / 4 = 2.8.
    assert data['incidentes_por_persona']==2.8
    assert sum(x['total'] for x in data['distribucion_cargo'])==15


def test_filters_and_dates(client):
    result=client.get('/api/personal?cargo=Enfermera&estado=ACTIVO')
    assert result.json['total']==4
    assert {r['codigo_personal'] for r in result.json['data']}=={'PER001','PER003','PER007','PER011'}
    result=client.get('/api/personal?fecha_desde=2025-01-01&fecha_hasta=2025-12-31')
    assert result.json['total']==6
    assert client.get('/api/personal?cargo=Enfermera&especialidad=Geriatría&estado=ACTIVO').status_code==200
    assert client.get('/api/personal?q=%25').json['total']==0
    assert client.get('/api/personal?q=%27%20OR%201%3D1--').json['total']==0
    day=client.get('/api/personal/1').json['data']['fecha_ingreso']
    result=client.get('/api/personal?fecha_desde='+day+'&fecha_hasta='+day)
    assert result.status_code==200 and result.json['total']>0
    assert all(r['fecha_ingreso']==day for r in result.json['data'])

@pytest.mark.parametrize('query',['fecha_desde=2026-02-30','fecha_desde=2026-10-01&fecha_hasta=2026-09-01','estado=OTRO','fecha_desde=20250101'])
def test_bad_filters(client,query):
    for endpoint in ('','/kpis','/grafico'):
        assert client.get('/api/personal'+endpoint+'?'+query).status_code==400


def test_zero_division_and_chart(client):
    d=client.get('/api/personal/kpis?cargo=NoExiste').json['data']
    assert d['personal_activo']==d['residentes_por_cuidador']==d['actividades_por_responsable']==d['incidentes_por_persona']==0
    chart=client.get('/api/personal/grafico').json['data']
    assert sum(r['actividades'] for r in chart)==12
    assert sum(r['incidentes'] for r in chart)==11


def test_crud_duplicate_and_unchanged_update(client,body,person):
    url='/api/personal/'+str(person)
    assert client.get(url).json['data']['email']==body['email']
    assert client.post('/api/personal',json=body).status_code==409
    assert client.put(url,json=body).status_code==200
    body['estado']='INACTIVO'
    assert client.put(url,json=body).status_code==200
    assert client.get(url).json['data']['estado']=='INACTIVO'
    result=client.delete(url)
    assert result.status_code==204 and not result.data
    assert client.get(url).status_code==404
    assert client.put(url,json=body).status_code==404
    assert client.delete(url).status_code==404

@pytest.mark.parametrize('field,value',[('email','sin-arroba'),('telefono','123abc'),('fecha_ingreso','2999-01-01'),('fecha_ingreso','2025-02-30'),('cargo',''),('estado','BAJA'),('nombres','x'*101),('nombres',123)])
def test_invalid_body(client,body,field,value):
    body[field]=value
    assert client.post('/api/personal',json=body).status_code==400


def test_invalid_json_and_origin(client):
    assert client.post('/api/personal',json=[]).status_code==400
    assert client.post('/api/personal',data='{}').status_code==400
    assert client.post('/api/personal',data='{',content_type='application/json').status_code==400
    assert client.delete('/api/personal/1',headers={'Origin':'https://externo.example'}).status_code==403


def test_referenced_person_cannot_be_deleted(client):
    before=client.get('/api/personal/1').json['data']
    result=client.delete('/api/personal/1')
    assert result.status_code==409 and 'INACTIVO' in result.json['error']
    assert client.get('/api/personal/1').json['data']==before


def test_catalogs_and_pages(client):
    assert 'Enfermera' in client.get('/api/personal/catalogos').json['data']['cargos']
    for path in ('/','/index3.html','/tables/data.html','/crud.html'):
        assert client.get(path).status_code==200
    assert client.get('/api/personal/999999').status_code==404


def test_transfer_twice_and_update(client,app,body,person):
    first=client.post('/api/personal/transferir')
    assert first.status_code==200,first.json
    assert first.json['leidos']==16
    count_after_first=client.get('/api/personal/transferencia/resumen').json['data']['sqlserver']
    second=client.post('/api/personal/transferir')
    assert second.status_code==200,second.json
    assert second.json['insertados']==0 and second.json['actualizados']==16
    body['estado']='INACTIVO'
    client.put('/api/personal/'+str(person),json=body)
    assert client.post('/api/personal/transferir').status_code==200
    with app.app_context():
        conn=target_connect()
        try:
            assert conn.cursor().execute('SELECT estado FROM dbo.personal WHERE id_personal=?',person).fetchone()[0]=='INACTIVO'
        finally:conn.close()
    summary=client.get('/api/personal/transferencia/resumen')
    assert summary.status_code==200
    assert summary.json['data']['mysql']==16 and summary.json['data']['sqlserver']==count_after_first
    assert summary.json['data']['ultima_transferencia']


def test_transfer_rolls_back(client,app,monkeypatch):
    import modulo.transferencia as transfer
    with app.app_context():
        conn=target_connect()
        cur=conn.cursor();before=[tuple(r) for r in cur.execute('SELECT * FROM dbo.personal ORDER BY id_personal')];conn.close()
    real_connect=transfer.connect
    class FailingConnection:
        def __init__(self):self.conn=real_connect();self.merges=0
        def cursor(self):return self
        def execute(self,sql,*params):
            if sql.startswith('MERGE'):
                self.merges+=1
                if self.merges==2:raise pyodbc.IntegrityError('23000','Fallo de prueba')
            self.current=self.conn.cursor().execute(sql,*params);return self
        def fetchone(self):return self.current.fetchone()
        def rollback(self):return self.conn.rollback()
        def close(self):return self.conn.close()
    monkeypatch.setattr(transfer,'connect',FailingConnection)
    assert client.post('/api/personal/transferir').status_code==500
    with app.app_context():
        conn=target_connect()
        after=[tuple(r) for r in conn.cursor().execute('SELECT * FROM dbo.personal ORDER BY id_personal')];conn.close()
    assert before==after


def test_database_unavailable(client,monkeypatch):
    import modulo.transferencia as transfer
    def fail():raise pyodbc.OperationalError('08001','detalle privado')
    monkeypatch.setattr(transfer,'connect',fail)
    response=client.post('/api/personal/transferir')
    assert response.status_code==503 and 'privado' not in response.json['error']
