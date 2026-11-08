"""Reglas del formulario y filtros del grupo 3."""
import re
from datetime import date
from zoneinfo import ZoneInfo
from datetime import datetime
from werkzeug.exceptions import BadRequest


# ======================================================================
# VALIDACIÓN DE FECHAS
# Exige AAAA-MM-DD y comprueba que el día exista. Por ejemplo, rechaza
# 2025-02-30 aunque tenga la forma de una fecha.
# ======================================================================
def fecha_iso(value, field):
    if not isinstance(value, str) or not re.fullmatch(r'\d{4}-\d{2}-\d{2}', value):
        raise BadRequest(f'{field}: usa AAAA-MM-DD.')
    try:
        return date.fromisoformat(value)
    except ValueError:
        raise BadRequest(f'{field}: fecha no válida.')


# ======================================================================
# FILTROS COMBINADOS
# Solo los nombres de columna conocidos forman el SQL. Los valores van
# en la lista params y se aplican con %s. Las fechas son inclusivas y todos
# los filtros se combinan con AND. Los vacíos se omiten.
# ======================================================================
def filtros(args):
    clauses, params = [], []
    for field in ('cargo', 'estado', 'especialidad'):
        value = args.get(field, '').strip()
        if value:
            if len(value) > 100:
                raise BadRequest(f'{field}: filtro demasiado largo.')
            if field == 'estado' and value not in ('ACTIVO', 'INACTIVO'):
                raise BadRequest('Estado no válido.')
            clauses.append(f'p.{field} = %s')
            params.append(value)
    start, end = None, None
    for key, op in (('fecha_desde', '>='), ('fecha_hasta', '<=')):
        value = args.get(key, '').strip()
        if value:
            parsed = fecha_iso(value, key)
            if key == 'fecha_desde': start = parsed
            else: end = parsed
            clauses.append(f'p.fecha_ingreso {op} %s')
            params.append(parsed)
    if start and end and start > end:
        raise BadRequest('La fecha desde no puede superar la fecha hasta.')
    text = args.get('q', '').strip()
    if text:
        if len(text) > 100:
            raise BadRequest('Búsqueda demasiado larga.')
        # Parámetros, nunca concatenación de valores en SQL.
        clauses.append('(p.nombres LIKE %s OR p.apellidos LIKE %s OR p.codigo_personal LIKE %s)')
        params.extend(['%'+text+'%'] * 3)
    return (' WHERE ' + ' AND '.join(clauses) if clauses else ''), params


# ======================================================================
# VALIDACIÓN DEL FORMULARIO
# Rechaza campos desconocidos, revisa tipos, obligatorios y longitudes.
# También comprueba estado, teléfono, correo y fecha no futura.
# Devuelve un diccionario limpio para INSERT y UPDATE.
# ======================================================================
def validar_personal(data):
    if not isinstance(data, dict):
        raise BadRequest('Envía un objeto JSON.')
    limits = {'codigo_personal':20,'nombres':100,'apellidos':100,'cargo':80,
              'especialidad':100,'telefono':20,'email':120,'estado':8}
    required = {'codigo_personal','nombres','apellidos','cargo'}
    allowed = set(limits) | {'fecha_ingreso'}
    if set(data) - allowed:
        raise BadRequest('El formulario contiene campos desconocidos.')
    clean = {}
    for field, limit in limits.items():
        value = data.get(field, 'ACTIVO' if field == 'estado' else '')
        if value is None: value = ''
        if not isinstance(value, str):
            raise BadRequest(f'{field}: debe ser texto.')
        value = value.strip()
        if field in required and not value:
            raise BadRequest(f'{field}: campo obligatorio.')
        if len(value) > limit:
            raise BadRequest(f'{field}: máximo {limit} caracteres.')
        clean[field] = value or None
    if clean['estado'] not in ('ACTIVO','INACTIVO'):
        raise BadRequest('El estado debe ser ACTIVO o INACTIVO.')
    if clean['telefono'] and not re.fullmatch(r'[0-9]{1,20}', clean['telefono']):
        raise BadRequest('El teléfono solo admite dígitos, máximo 20.')
    if clean['email'] and not re.fullmatch(r'[^\s@]+@[^\s@]+\.[^\s@]+', clean['email']):
        raise BadRequest('El correo no tiene un formato válido.')
    clean['fecha_ingreso'] = fecha_iso(data.get('fecha_ingreso'), 'fecha_ingreso')
    if clean['fecha_ingreso'] > datetime.now(ZoneInfo('America/Lima')).date():
        raise BadRequest('La fecha de ingreso no puede ser futura.')
    return clean
