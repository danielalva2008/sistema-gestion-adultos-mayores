"""API REST de Personal. Los seis KPIs se calculan en MySQL."""
from flask import Blueprint, jsonify, request
from werkzeug.exceptions import NotFound, Conflict
from db_mysql import query, transaction
from modulo.validacion import filtros, validar_personal

personal = Blueprint('personal', __name__)
CAMPOS = ('codigo_personal','nombres','apellidos','cargo','especialidad',
          'telefono','email','fecha_ingreso','estado')
COLUMNAS = 'p.id_personal, ' + ', '.join('p.'+c for c in CAMPOS)


# ======================================================================
# FORMATO JSON
# Las consultas y el CRUD responden con ok, total y data. En listados,
# total es la cantidad de filas; en respuestas individuales, total vale 1.
# DELETE responde 204 sin cuerpo. Los errores usan ok=false y error.
# ======================================================================
# LISTADO CON FILTROS
# Obtiene la tarjeta de filtros por query string, construye el WHERE seguro
# y devuelve registros ordenados por apellidos y nombres.
# ======================================================================
@personal.get('')
def listar():
    where, params = filtros(request.args)
    rows = query('SELECT '+COLUMNAS+' FROM personal p'+where+' ORDER BY p.apellidos, p.nombres', params)
    return jsonify(ok=True, total=len(rows), data=rows)


# ======================================================================
# OPCIONES PARA LOS CONTROLES
# Lee cargos y especialidades existentes. Las listas del frontend reflejan
# la base real y no un conjunto de opciones escrito a mano.
# ======================================================================
@personal.get('/catalogos')
def catalogos():
    cargos = query('SELECT DISTINCT cargo FROM personal ORDER BY cargo')
    especialidades = query("SELECT DISTINCT especialidad FROM personal WHERE especialidad IS NOT NULL AND especialidad <> '' ORDER BY especialidad")
    return jsonify(ok=True, total=1, data={'cargos':[r['cargo'] for r in cargos],
        'especialidades':[r['especialidad'] for r in especialidades]})


# ======================================================================
# CONSULTA DE UN TRABAJADOR
# Busca por la llave primaria. El formulario usa estos datos para editar.
# Si no encuentra el ID, responde 404.
# ======================================================================
@personal.get('/<int:id_personal>')
def obtener(id_personal):
    row = query('SELECT '+COLUMNAS+' FROM personal p WHERE p.id_personal=%s', (id_personal,), one=True)
    if not row: raise NotFound('No existe ese trabajador.')
    return jsonify(ok=True, total=1, data=row)


# ======================================================================
# REGISTRO DE PERSONAL
# Valida todos los campos antes de insertar. MySQL protege el código único.
# El commit guarda la fila y la respuesta 201 devuelve el nuevo ID.
# ======================================================================
@personal.post('')
def crear():
    data = validar_personal(request.get_json(silent=True))
    with transaction() as conn:
        with conn.cursor() as cur:
            cur.execute('INSERT INTO personal ('+', '.join(CAMPOS)+') VALUES ('+', '.join(['%s']*len(CAMPOS))+')',
                        [data[c] for c in CAMPOS])
            id_personal = cur.lastrowid
    return jsonify(ok=True, total=1, data={'id_personal':id_personal, **data}), 201


# ======================================================================
# ACTUALIZACIÓN DE PERSONAL
# Recibe el formulario completo. Bloquea la fila durante la transacción
# para actualizar un registro existente y responde 200.
# ======================================================================
@personal.put('/<int:id_personal>')
def actualizar(id_personal):
    data = validar_personal(request.get_json(silent=True))
    with transaction() as conn:
        row = query('SELECT id_personal FROM personal WHERE id_personal=%s FOR UPDATE',
                    (id_personal,), one=True, connection=conn)
        if not row: raise NotFound('No existe ese trabajador.')
        with conn.cursor() as cur:
            cur.execute('UPDATE personal SET '+', '.join(c+'=%s' for c in CAMPOS)+' WHERE id_personal=%s',
                        [data[c] for c in CAMPOS]+[id_personal])
    return jsonify(ok=True, total=1, data={'id_personal':id_personal, **data})


# ======================================================================
# ELIMINACIÓN Y REGLA DE NEGOCIO
# Consulta actividades e incidentes antes de borrar. Si tiene referencias
# responde 409 y recomienda INACTIVO; así no pierde sus vínculos por SET NULL.
# ======================================================================
@personal.delete('/<int:id_personal>')
def eliminar(id_personal):
    with transaction() as conn:
        row = query('SELECT id_personal FROM personal WHERE id_personal=%s FOR UPDATE',
                    (id_personal,), one=True, connection=conn)
        if not row: raise NotFound('No existe ese trabajador.')
        refs = query('''SELECT
            (SELECT COUNT(*) FROM actividades WHERE id_personal_responsable=%s) AS actividades,
            (SELECT COUNT(*) FROM incidentes WHERE id_personal_reporta=%s) AS incidentes''',
            (id_personal,id_personal), one=True, connection=conn)
        if refs['actividades'] or refs['incidentes']:
            raise Conflict('Tiene actividades o incidentes asociados. Cambia su estado a INACTIVO en lugar de eliminarlo.')
        with conn.cursor() as cur: cur.execute('DELETE FROM personal WHERE id_personal=%s', (id_personal,))
    return '', 204


# ======================================================================
# CÁLCULO DE LOS SEIS INDICADORES
# La CTE seleccion contiene la cohorte filtrada por fecha de ingreso. Los
# COUNT, AVG, razones y redondeos se resuelven en SQL. NULLIF evita dividir
# por cero y COALESCE convierte el resultado vacío en 0.
# ======================================================================
@personal.get('/kpis')
def kpis():
    where, params = filtros(request.args)
    # La cohorte se filtra por fecha_ingreso de personal. La carga incluye todas
    # las actividades/incidentes de esa cohorte; no usa su fecha de ocurrencia.
    sql = '''WITH seleccion AS (SELECT p.* FROM personal p {where})
    SELECT
      (SELECT COUNT(*) FROM seleccion WHERE estado='ACTIVO') AS personal_activo,
      (SELECT COALESCE(ROUND(AVG(TIMESTAMPDIFF(MONTH,fecha_ingreso,CURDATE()))/12,1),0) FROM seleccion) AS antiguedad_promedio,
      COALESCE(ROUND((SELECT COUNT(*) FROM residentes WHERE estado='ACTIVO') /
         NULLIF((SELECT COUNT(*) FROM seleccion WHERE cargo='Cuidador'),0),1),0) AS residentes_por_cuidador,
      (SELECT COALESCE(ROUND(COUNT(*)/NULLIF(COUNT(DISTINCT a.id_personal_responsable),0),1),0)
       FROM actividades a INNER JOIN seleccion p ON p.id_personal=a.id_personal_responsable) AS actividades_por_responsable,
      (SELECT COALESCE(ROUND(COUNT(*)/NULLIF(COUNT(DISTINCT i.id_personal_reporta),0),1),0)
       FROM incidentes i INNER JOIN seleccion p ON p.id_personal=i.id_personal_reporta) AS incidentes_por_persona,
      (SELECT COUNT(DISTINCT cargo) FROM seleccion) AS total_cargos
    '''.format(where=where)
    with transaction() as conn:
        data = query(sql, params, one=True, connection=conn)
        data['distribucion_cargo'] = query('SELECT p.cargo, COUNT(*) AS cantidad FROM personal p'+where+
            ' GROUP BY p.cargo ORDER BY cantidad DESC, p.cargo', params, connection=conn)
    return jsonify(ok=True, total=1, data=data)


# ======================================================================
# DATOS DE LA GRÁFICA COMPARATIVA
# Cuenta actividades e incidentes por persona antes de unirlos a personal.
# Luego suma por cargo: esta preagrupación evita multiplicar conteos.
# LEFT JOIN conserva los cargos que tienen cero actividades o incidentes.
# ======================================================================
@personal.get('/grafico')
def grafico():
    where, params = filtros(request.args)
    # Preagregar evita multiplicar actividades por incidentes en un JOIN doble.
    rows = query('''SELECT p.cargo, COALESCE(SUM(a.total),0) AS actividades,
        COALESCE(SUM(i.total),0) AS incidentes
        FROM personal p
        LEFT JOIN (SELECT id_personal_responsable, COUNT(*) AS total FROM actividades
          GROUP BY id_personal_responsable) a ON a.id_personal_responsable=p.id_personal
        LEFT JOIN (SELECT id_personal_reporta, COUNT(*) AS total FROM incidentes
          GROUP BY id_personal_reporta) i ON i.id_personal_reporta=p.id_personal
        '''+where+' GROUP BY p.cargo ORDER BY p.cargo', params)
    return jsonify(ok=True, total=len(rows), data=rows)

# Registrar estas rutas una sola vez al cargar el módulo.
from modulo.transferencia import registrar_transferencia
registrar_transferencia(personal)
