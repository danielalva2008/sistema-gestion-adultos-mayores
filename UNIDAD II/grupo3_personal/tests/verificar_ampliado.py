import sys,json,hashlib,datetime,uuid
from pathlib import Path
ROOT=Path(__file__).resolve().parents[1]
sys.path.insert(0,str(ROOT))
from app import create_app
import db_mysql,db_sqlserver
P=ROOT;app=create_app({'TESTING':True})
if not all(str(app.config[k]).endswith('_pruebas') for k in ('MYSQL_DATABASE','SQLSERVER_DATABASE')):
    raise SystemExit('Selecciona bases de ensayo terminadas en _pruebas mediante MYSQL_DATABASE y SQLSERVER_DATABASE. No se ejecutaron pruebas de escritura.')
c=app.test_client();results=[]
def check(name,condition,detail=''):
 results.append({'caso':name,'correcto':bool(condition),'detalle':detail})
 assert condition,(name,detail)
def call(name,method,path,expected,**kw):
 r=c.open('/api/personal'+path,method=method,**kw)
 check(name,r.status_code==expected,f'HTTP {r.status_code}, esperado {expected}')
 if expected>=400:check(name+' JSON de error',isinstance(r.json,dict) and set(r.json)=={'ok','error'} and r.json['ok'] is False)
 return r
with app.app_context():
 before=db_mysql.query('SELECT * FROM personal ORDER BY id_personal')
 check('Origen MySQL 8 real',db_mysql.query('SELECT VERSION() AS v',one=True)['v'].startswith('8.'))
original=json.dumps(before,default=str,sort_keys=True)
base={'codigo_personal':'V'+uuid.uuid4().hex[:15],'nombres':'Verificacion','apellidos':'Temporal','cargo':'Prueba','fecha_ingreso':'2025-01-01'}
for field,limit in [('codigo_personal',20),('nombres',100),('apellidos',100),('cargo',80),('especialidad',100),('telefono',20),('email',120),('estado',8)]:
 call('Longitud máxima '+field,'POST','',400,json={**base,field:'x'*(limit+1)})
for field in ['codigo_personal','nombres','apellidos','cargo']:
 call('Obligatorio '+field,'POST','',400,json={**base,field:' '})
for field,value in [('telefono','99A1'),('email','sin-arroba'),('fecha_ingreso','9999-01-01'),('fecha_ingreso','2025-02-30'),('fecha_ingreso','2025-2-01'),('estado','OTRO'),('estado',None),('nombres',123)]:
 call('Tipo o formato '+field+' '+str(value),'POST','',400,json={**base,field:value})
call('Campo desconocido','POST','',400,json={**base,'id_personal':55})
call('Objeto JSON obligatorio','POST','',400,json=[])
call('JSON mal formado','POST','',400,data='{"x":',content_type='application/json')
call('PUT inexistente','PUT','/99999999',404,json=base)
call('GET inexistente','GET','/99999999',404)
call('DELETE inexistente','DELETE','/99999999',404)
call('Método no permitido','PATCH','/1',405,json=base)
for q in ['?q='+('a'*101),'?cargo='+('a'*101),'?estado=OTRO','?fecha_desde=2025-12-31&fecha_hasta=2025-01-01']:
 call('Filtro inválido '+q[:35],'GET',q,400)
r=call('Búsqueda con SQL como texto','GET','',200,query_string={'q':"' OR 1=1 --"});check('Sin inyección SQL',r.json['total']==0)
r=call('Filtros vacíos no limitan','GET','?cargo=&estado=&especialidad=&q=',200);check('Total sin filtros',r.json['total']==len(before))
r=call('Filtros combinados exactos','GET','?cargo=Enfermera&estado=ACTIVO',200);check('Cuatro enfermeras activas',r.json['total']==4)
r=call('Fechas inclusivas','GET','?fecha_desde=2025-01-13&fecha_hasta=2025-01-13',200);check('Día límite incluido',r.json['total']==1 and r.json['data'][0]['codigo_personal']=='PER007')
r=call('Crear con estado predeterminado','POST','',201,json=base);identifier=r.json['data']['id_personal']
try:
 check('Estado predeterminado ACTIVO',r.json['data']['estado']=='ACTIVO')
 check('Opcionales vacíos se guardan NULL',all(r.json['data'][x] is None for x in ['telefono','email','especialidad']))
 r=call('Leer temporal','GET','/'+str(identifier),200);check('Fecha ISO en JSON',r.json['data']['fecha_ingreso']=='2025-01-01')
 call('Código duplicado al editar','PUT','/'+str(identifier),409,json={**base,'codigo_personal':'PER001'})
 call('Actualizar temporal','PUT','/'+str(identifier),200,json={**base,'estado':'INACTIVO'})
 check('Actualización persistida',c.get('/api/personal/'+str(identifier)).json['data']['estado']=='INACTIVO')
finally:
 r=call('Eliminar temporal','DELETE','/'+str(identifier),204);check('204 sin cuerpo',r.data==b'')
for path in ['','/kpis','/grafico','/catalogos','/1']:
 r=c.get('/api/personal'+path);check('Cabeceras API '+path,r.headers.get('Cache-Control')=='no-store' and r.headers.get('X-Content-Type-Options')=='nosniff')
for path in ['/','/index3.html','/tables/data.html','/crud.html','/static/css/adminlte.css','/static/js/personal.js','/static/vendor/chart/chart.umd.min.js','/static/vendor/tabulator/tabulator.min.js']:
 check('Recurso local '+path,c.get(path).status_code==200)
# Simular únicamente la configuración de una app de pruebas; no detener servicios.
off=create_app({'TESTING':True,'MYSQL_PORT':1}).test_client();r=off.get('/api/personal');check('Origen no disponible responde 503',r.status_code==503 and set(r.json)=={'ok','error'});check('Pantalla sigue disponible sin origen',off.get('/').status_code==200)
with app.app_context():
 after=db_mysql.query('SELECT * FROM personal ORDER BY id_personal');check('Datos origen conservados',json.dumps(after,default=str,sort_keys=True)==original)
 conn=db_sqlserver.connect()
 try:
  cur=conn.cursor();cur.execute('SELECT id_personal,codigo_personal,nombres,apellidos,cargo,especialidad,telefono,email,fecha_ingreso,estado FROM dbo.personal ORDER BY id_personal')
  cols=[d[0] for d in cur.description];dest=[dict(zip(cols,r)) for r in cur.fetchall()]
  byid={x['id_personal']:x for x in dest}
  check('Cada fila actual coincide en todos sus campos en destino',all(x['id_personal'] in byid and json.dumps(x,default=str,sort_keys=True)==json.dumps(byid[x['id_personal']],default=str,sort_keys=True) for x in after))
  extra_ids=sorted(set(byid)-{x['id_personal'] for x in after})
  check('Destino no duplica claves primarias',len(byid)==len(dest))
  cur.execute("SELECT COUNT(*) FROM sys.identity_columns WHERE object_id=OBJECT_ID('dbo.personal')");check('SQL Server sin IDENTITY',cur.fetchone()[0]==0)
  cur.execute("SELECT COUNT(*) FROM sys.foreign_keys WHERE parent_object_id=OBJECT_ID('dbo.personal')");check('SQL Server sin claves foráneas',cur.fetchone()[0]==0)
  cur.execute('SELECT COUNT(*) FROM dbo.personal WHERE fecha_transferencia IS NULL');check('Todos tienen fecha de transferencia',cur.fetchone()[0]==0)
  cur.execute("SELECT COUNT(*) FROM sys.key_constraints WHERE parent_object_id=OBJECT_ID('dbo.personal') AND type='PK'");check('Llave primaria en destino',cur.fetchone()[0]==1)
 finally:conn.close()
out={'fecha':datetime.datetime.now(datetime.timezone(datetime.timedelta(hours=-5))).isoformat(),'total':len(results),'correctos':sum(r['correcto'] for r in results),'personal_antes':len(before),'personal_despues':len(after),'datos_conservados':True,'filas_destino':len(dest),'ids_solo_en_destino':extra_ids,'nota':'MERGE conserva filas históricas del destino; no sincroniza borrados.','casos':results}
(P/'docs/evidencias/verificacion_completa.json').write_text(json.dumps(out,ensure_ascii=False,indent=2))
print(len(results),'comprobaciones adicionales correctas; origen y destino coinciden campo por campo; datos conservados.')
