import re
from datetime import datetime
 
import pymysql
from flask import Blueprint, jsonify, request
 
from db_mysql import query, execute
 
bp = Blueprint("incidentes", __name__)
 
ESTADOS = ("REGISTRADO", "EN_SEGUIMIENTO", "CERRADO")
NIVELES = ("BAJO", "MEDIO", "ALTO", "CRITICO")
 
FORMATOS_FECHA_HORA = (
    "%Y-%m-%dT%H:%M",
    "%Y-%m-%dT%H:%M:%S",
    "%Y-%m-%d %H:%M",
    "%Y-%m-%d %H:%M:%S",
)
 
SELECT_INCIDENTES = """
    SELECT i.id_incidente, i.id_residente, i.id_tipo_incidente,
           i.id_personal_reporta, i.fecha_hora, i.lugar, i.descripcion,
           i.accion_realizada, i.requiere_seguimiento, i.estado,
           i.fecha_cierre,
           t.nombre AS tipo, t.nivel_riesgo,
           CONCAT(r.apellidos, ', ', r.nombres) AS residente,
           CONCAT(p.nombres, ' ', p.apellidos) AS personal_reporta
    FROM incidentes i
    INNER JOIN tipos_incidente t ON t.id_tipo_incidente = i.id_tipo_incidente
    INNER JOIN residentes r ON r.id_residente = i.id_residente
    LEFT JOIN personal p ON p.id_personal = i.id_personal_reporta
"""
 
 
# ---------------------------------------------------------------------------
# Errores
# ---------------------------------------------------------------------------
class ApiError(Exception):
    def __init__(self, mensaje, codigo=400):
        super().__init__(mensaje)
        self.mensaje = mensaje
        self.codigo = codigo
 
 
@bp.errorhandler(ApiError)
def _manejar_api_error(e):
    return jsonify({"ok": False, "error": e.mensaje}), e.codigo
 
 
@bp.errorhandler(pymysql.err.IntegrityError)
def _manejar_integridad(e):
    return jsonify({
        "ok": False,
        "error": "La operación viola una regla de integridad de los datos",
    }), 409
 
 
@bp.errorhandler(pymysql.err.OperationalError)
@bp.errorhandler(pymysql.err.InterfaceError)
def _manejar_bd_no_disponible(e):
    return jsonify({"ok": False, "error": "Base de datos no disponible"}), 503
 
 
# ---------------------------------------------------------------------------
# Utilidades de validación
# ---------------------------------------------------------------------------
def _fecha_param(nombre):
    """Lee un parámetro AAAA-MM-DD del query string. Vacío = None."""
    valor = (request.args.get(nombre) or "").strip()
    if not valor:
        return None
    if not re.fullmatch(r"\d{4}-\d{2}-\d{2}", valor):
        raise ApiError(f"{nombre} debe tener formato AAAA-MM-DD")
    try:
        return datetime.strptime(valor, "%Y-%m-%d").date()
    except ValueError:
        raise ApiError(f"{nombre} debe tener formato AAAA-MM-DD")
 
 
def _rango_fechas(alias="i"):
    desde = _fecha_param("fecha_desde")
    hasta = _fecha_param("fecha_hasta")
    if desde and hasta and desde > hasta:
        raise ApiError("fecha_desde no puede ser posterior a fecha_hasta")
    where = "1=1"
    params = []
    if desde:
        where += f" AND {alias}.fecha_hora >= %s"
        params.append(desde)
    if hasta:
        where += f" AND {alias}.fecha_hora < DATE_ADD(%s, INTERVAL 1 DAY)"
        params.append(hasta)
    return where, params
 
 
def _vacio(valor):
    return valor is None or (isinstance(valor, str) and not valor.strip())
 
 
def _entero(valor, nombre, obligatorio=True):
    if _vacio(valor):
        if obligatorio:
            raise ApiError(f"{nombre} es obligatorio")
        return None
    if isinstance(valor, bool):
        raise ApiError(f"{nombre} debe ser un número entero")
    try:
        if isinstance(valor, float) and not valor.is_integer():
            raise ValueError
        n = int(valor)
    except (TypeError, ValueError):
        raise ApiError(f"{nombre} debe ser un número entero")
    if n <= 0:
        raise ApiError(f"{nombre} no es válido")
    return n
 
 
def _texto(valor, nombre, maximo=None, obligatorio=True):
    if _vacio(valor):
        if obligatorio:
            raise ApiError(f"{nombre} es obligatorio")
        return None
    texto = str(valor).strip()
    if maximo and len(texto) > maximo:
        raise ApiError(f"{nombre} no puede superar {maximo} caracteres")
    return texto
 
 
def _fecha_hora(valor, nombre, obligatorio=True):
    if _vacio(valor):
        if obligatorio:
            raise ApiError(f"{nombre} es obligatorio")
        return None
    texto = str(valor).strip()
    for formato in FORMATOS_FECHA_HORA:
        try:
            return datetime.strptime(texto, formato)
        except ValueError:
            continue
    raise ApiError(f"{nombre} debe tener formato AAAA-MM-DD HH:MM")
 
 
def _booleano(valor):
    if _vacio(valor):
        return 0
    if isinstance(valor, bool):
        return 1 if valor else 0
    if str(valor).strip().lower() in ("1", "true", "si", "sí", "on"):
        return 1
    if str(valor).strip().lower() in ("0", "false", "no", "off"):
        return 0
    raise ApiError("requiere_seguimiento debe ser sí o no")
 
 
def _existe(sql, valor, mensaje):
    if not query(sql, (valor,), fetchone=True):
        raise ApiError(mensaje)
 
 
def _serializar(fila):
    salida = {}
    for clave, valor in fila.items():
        if isinstance(valor, datetime):
            salida[clave] = valor.strftime("%Y-%m-%dT%H:%M:%S")
        else:
            salida[clave] = valor
    return salida
 
 
def _validar(data):
    if not isinstance(data, dict):
        raise ApiError("Se esperaba un cuerpo JSON válido")
 
    id_residente = _entero(data.get("id_residente"), "id_residente")
    id_tipo = _entero(data.get("id_tipo_incidente"), "id_tipo_incidente")
    id_personal = _entero(data.get("id_personal_reporta"),
                          "id_personal_reporta", obligatorio=False)
 
    _existe("SELECT 1 AS x FROM residentes WHERE id_residente = %s",
            id_residente, "El residente indicado no existe")
    _existe("SELECT 1 AS x FROM tipos_incidente WHERE id_tipo_incidente = %s",
            id_tipo, "El tipo de incidente indicado no existe")
    if id_personal:
        _existe("SELECT 1 AS x FROM personal WHERE id_personal = %s",
                id_personal, "El personal indicado no existe")
 
    fecha_hora = _fecha_hora(data.get("fecha_hora"), "fecha_hora")
    if fecha_hora > datetime.now():
        raise ApiError("fecha_hora no puede ser futura")
 
    lugar = _texto(data.get("lugar"), "lugar", 150)
    descripcion = _texto(data.get("descripcion"), "descripcion")
    accion = _texto(data.get("accion_realizada"), "accion_realizada",
                    obligatorio=False)
    seguimiento = _booleano(data.get("requiere_seguimiento"))
 
    estado = str(data.get("estado") or "REGISTRADO").strip().upper()
    if estado not in ESTADOS:
        raise ApiError("estado debe ser REGISTRADO, EN_SEGUIMIENTO o CERRADO")
 
    fecha_cierre = _fecha_hora(data.get("fecha_cierre"), "fecha_cierre",
                               obligatorio=False)
    if estado == "CERRADO":
        if fecha_cierre is None:
            raise ApiError("fecha_cierre es obligatoria cuando el estado es CERRADO")
        if fecha_cierre <= fecha_hora:
            raise ApiError("fecha_cierre debe ser posterior a fecha_hora")
    elif fecha_cierre is not None:
        raise ApiError("fecha_cierre debe estar vacía si el estado no es CERRADO")
 
    return {
        "id_residente": id_residente,
        "id_tipo_incidente": id_tipo,
        "id_personal_reporta": id_personal,
        "fecha_hora": fecha_hora,
        "lugar": lugar,
        "descripcion": descripcion,
        "accion_realizada": accion,
        "requiere_seguimiento": seguimiento,
        "estado": estado,
        "fecha_cierre": fecha_cierre,
    }
 
 
def _obtener(id_incidente):
    return query(SELECT_INCIDENTES + " WHERE i.id_incidente = %s",
                 (id_incidente,), fetchone=True)
 
 
# ---------------------------------------------------------------------------
# KPIs y gráfica
# ---------------------------------------------------------------------------
@bp.get("/kpis")
def kpis():
    where, params = _rango_fechas("i")
 
    total = query(
        f"SELECT COUNT(*) AS n FROM incidentes i WHERE {where}",
        params, fetchone=True,
    )["n"]
 
    activos = query(
        "SELECT COUNT(*) AS n FROM residentes WHERE estado = 'ACTIVO'",
        fetchone=True,
    )["n"]
    tasa_por_100 = round((total / activos) * 100, 1) if activos else 0.0
 
    con_seguimiento = query(
        f"SELECT COUNT(*) AS n FROM incidentes i "
        f"WHERE {where} AND i.requiere_seguimiento = 1",
        params, fetchone=True,
    )["n"]
    pct_seguimiento = round((con_seguimiento / total) * 100, 1) if total else 0.0
 
    cerrados = query(
        f"SELECT COUNT(*) AS n FROM incidentes i "
        f"WHERE {where} AND i.estado = 'CERRADO'",
        params, fetchone=True,
    )["n"]
    pct_cerrados = round((cerrados / total) * 100, 1) if total else 0.0
 
    fila_horas = query(
        f"""
        SELECT AVG(TIMESTAMPDIFF(MINUTE, i.fecha_hora, i.fecha_cierre)) / 60.0 AS horas
        FROM incidentes i
        WHERE {where}
          AND i.estado = 'CERRADO'
          AND i.fecha_cierre IS NOT NULL
        """,
        params, fetchone=True,
    )
    horas_cierre = (
        round(float(fila_horas["horas"]), 2)
        if fila_horas and fila_horas["horas"] is not None
        else 0.0
    )
 
    alto_critico = query(
        f"""
        SELECT COUNT(*) AS n
        FROM incidentes i
        INNER JOIN tipos_incidente t ON t.id_tipo_incidente = i.id_tipo_incidente
        WHERE {where}
          AND t.nivel_riesgo IN ('ALTO', 'CRITICO')
        """,
        params, fetchone=True,
    )["n"]
 
    return jsonify({
        "ok": True,
        "data": {
            "total_incidentes": total,
            "tasa_por_100_residentes": tasa_por_100,
            "pct_requiere_seguimiento": pct_seguimiento,
            "pct_cerrados": pct_cerrados,
            "horas_cierre_promedio": horas_cierre,
            "incidentes_alto_critico": alto_critico,
            "residentes_activos": activos,
        },
    })
 
 
@bp.get("/grafico")
def grafico():
    desde = _fecha_param("fecha_desde")
    hasta = _fecha_param("fecha_hasta")
    if desde and hasta and desde > hasta:
        raise ApiError("fecha_desde no puede ser posterior a fecha_hasta")
 
    join_extra = ""
    params = []
    if desde:
        join_extra += " AND i.fecha_hora >= %s"
        params.append(desde)
    if hasta:
        join_extra += " AND i.fecha_hora < DATE_ADD(%s, INTERVAL 1 DAY)"
        params.append(hasta)
 
    filas = query(
        f"""
        SELECT
            t.nombre AS tipo,
            COALESCE(SUM(CASE WHEN i.estado = 'CERRADO' THEN 1 ELSE 0 END), 0) AS cerrados,
            COALESCE(SUM(CASE WHEN i.estado = 'EN_SEGUIMIENTO' THEN 1 ELSE 0 END), 0) AS en_seguimiento
        FROM tipos_incidente t
        LEFT JOIN incidentes i
            ON i.id_tipo_incidente = t.id_tipo_incidente
           {join_extra}
        GROUP BY t.id_tipo_incidente, t.nombre
        ORDER BY t.nombre
        """,
        params,
    )
 
    return jsonify({
        "ok": True,
        "data": {
            "labels": [f["tipo"] for f in filas],
            "series": [
                {"nombre": "Cerrados",
                 "valores": [int(f["cerrados"] or 0) for f in filas]},
                {"nombre": "En seguimiento",
                 "valores": [int(f["en_seguimiento"] or 0) for f in filas]},
            ],
        },
    })
 
 
# ---------------------------------------------------------------------------
# Catálogos
# ---------------------------------------------------------------------------
@bp.get("/catalogos")
def catalogos():
    residentes = query(
        """
        SELECT id_residente,
               CONCAT(apellidos, ', ', nombres) AS nombre,
               estado
        FROM residentes
        ORDER BY apellidos, nombres
        """
    )
    tipos = query(
        """
        SELECT id_tipo_incidente, nombre, nivel_riesgo
        FROM tipos_incidente
        ORDER BY nombre
        """
    )
    personal = query(
        """
        SELECT id_personal,
               CONCAT(apellidos, ', ', nombres) AS nombre,
               cargo
        FROM personal
        ORDER BY apellidos, nombres
        """
    )
    return jsonify({
        "ok": True,
        "data": {
            "residentes": residentes,
            "tipos": tipos,
            "personal": personal,
            "estados": list(ESTADOS),
            "niveles_riesgo": list(NIVELES),
        },
    })
 
 
# ---------------------------------------------------------------------------
# Listado con filtros
# ---------------------------------------------------------------------------
@bp.get("")
@bp.get("/")
def listar():
    where, params = _rango_fechas("i")
 
    tipo = (request.args.get("tipo") or "").strip()
    if tipo:
        where += " AND i.id_tipo_incidente = %s"
        params.append(_entero(tipo, "tipo"))
 
    estado = (request.args.get("estado") or "").strip().upper()
    if estado:
        if estado not in ESTADOS:
            raise ApiError("estado no válido")
        where += " AND i.estado = %s"
        params.append(estado)
 
    nivel = (request.args.get("nivel_riesgo") or "").strip().upper()
    if nivel:
        if nivel not in NIVELES:
            raise ApiError("nivel_riesgo no válido")
        where += " AND t.nivel_riesgo = %s"
        params.append(nivel)
 
    seguimiento = (request.args.get("seguimiento") or "").strip().lower()
    if seguimiento:
        if seguimiento not in ("0", "1", "si", "sí", "no"):
            raise ApiError("seguimiento debe ser 1 o 0")
        where += " AND i.requiere_seguimiento = %s"
        params.append(1 if seguimiento in ("1", "si", "sí") else 0)
 
    q = (request.args.get("q") or "").strip()
    if q:
        where += (" AND (i.lugar LIKE %s"
                  " OR CONCAT(r.nombres, ' ', r.apellidos) LIKE %s)")
        patron = f"%{q}%"
        params.extend([patron, patron])
 
    filas = query(
        SELECT_INCIDENTES
        + f" WHERE {where} ORDER BY i.fecha_hora DESC, i.id_incidente DESC",
        params,
    )
    datos = [_serializar(f) for f in filas]
    return jsonify({"ok": True, "total": len(datos), "data": datos})
 
 
# ---------------------------------------------------------------------------
# CRUD
# ---------------------------------------------------------------------------
@bp.get("/<int:id_incidente>")
def obtener(id_incidente):
    fila = _obtener(id_incidente)
    if not fila:
        raise ApiError("El incidente no existe", 404)
    return jsonify({"ok": True, "data": _serializar(fila)})
 
 
@bp.post("")
@bp.post("/")
def crear():
    d = _validar(request.get_json(silent=True))
    nuevo_id, _ = execute(
        """
        INSERT INTO incidentes
            (id_residente, id_tipo_incidente, id_personal_reporta, fecha_hora,
             lugar, descripcion, accion_realizada, requiere_seguimiento,
             estado, fecha_cierre)
        VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s)
        """,
        (d["id_residente"], d["id_tipo_incidente"], d["id_personal_reporta"],
         d["fecha_hora"], d["lugar"], d["descripcion"], d["accion_realizada"],
         d["requiere_seguimiento"], d["estado"], d["fecha_cierre"]),
    )
    return jsonify({"ok": True, "data": _serializar(_obtener(nuevo_id))}), 201
 
 
@bp.put("/<int:id_incidente>")
def actualizar(id_incidente):
    if not _obtener(id_incidente):
        raise ApiError("El incidente no existe", 404)
    d = _validar(request.get_json(silent=True))
    execute(
        """
        UPDATE incidentes
        SET id_residente = %s, id_tipo_incidente = %s, id_personal_reporta = %s,
            fecha_hora = %s, lugar = %s, descripcion = %s,
            accion_realizada = %s, requiere_seguimiento = %s,
            estado = %s, fecha_cierre = %s
        WHERE id_incidente = %s
        """,
        (d["id_residente"], d["id_tipo_incidente"], d["id_personal_reporta"],
         d["fecha_hora"], d["lugar"], d["descripcion"], d["accion_realizada"],
         d["requiere_seguimiento"], d["estado"], d["fecha_cierre"],
         id_incidente),
    )
    return jsonify({"ok": True, "data": _serializar(_obtener(id_incidente))})
 
 
@bp.delete("/<int:id_incidente>")
def eliminar(id_incidente):
    if not _obtener(id_incidente):
        raise ApiError("El incidente no existe", 404)
    execute("DELETE FROM incidentes WHERE id_incidente = %s", (id_incidente,))
    return "", 204