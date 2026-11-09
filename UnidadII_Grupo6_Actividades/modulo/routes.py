"""
routes.py - Blueprint del modulo ACTIVIDADES (Grupo 6).

Expone en JSON:
  GET  /api/actividades/kpis                 KPIs del dashboard
  GET  /api/actividades/grafico              series de la grafica comparativa
  GET  /api/actividades                      listado con filtros
  GET  /api/actividades/<id>                 un registro (para editar)
  POST /api/actividades                      crear (CRUD Create)
  PUT  /api/actividades/<id>                 actualizar (CRUD Update)
  DEL  /api/actividades/<id>                 eliminar (CRUD Delete)
  GET  /api/actividades/catalogos            personal responsable (listas del form)
  POST /api/actividades/transferir           copia a SQL Server (boton Transferir)
  GET  /api/actividades/transferencia/resumen  conteos MySQL / SQL Server
  POST /api/actividades/<id>/participaciones  (opcional, puntaje extra) inscribir

Reglas aplicadas (plan Unidad II):
  - Indicadores calculados en SQL (NULLIF evita division entre cero).
  - Consultas parametrizadas (%s); nunca se concatenan valores.
  - Fechas fecha_desde / fecha_hasta inclusivas en formato AAAA-MM-DD.
  - Formato JSON: {ok, total, data} en exito; {ok:false, error} en error.
"""
import datetime
from decimal import Decimal
from flask import Blueprint, request, jsonify

import db_mysql as db
from .transferencia import transferir_actividades, resumen_transferencia

bp = Blueprint("actividades", __name__)

ESTADOS = ("PROGRAMADA", "REALIZADA", "CANCELADA")


# ---------------------------------------------------------------------------
# Ayudantes de respuesta y serializacion
# ---------------------------------------------------------------------------
def ok(data=None, total=None, code=200, **extra):
    body = {"ok": True}
    if total is not None:
        body["total"] = total
    if data is not None:
        body["data"] = data
    body.update(extra)
    return jsonify(body), code


def err(mensaje, code=400):
    return jsonify({"ok": False, "error": mensaje}), code


def _jsonable(value):
    """Convierte tipos de MySQL (date/time/datetime/Decimal) a texto JSON."""
    if isinstance(value, datetime.timedelta):          # columnas TIME
        total = int(value.total_seconds())
        return f"{total // 3600:02d}:{(total % 3600) // 60:02d}:{total % 60:02d}"
    if isinstance(value, (datetime.datetime,)):
        return value.strftime("%Y-%m-%d %H:%M:%S")
    if isinstance(value, datetime.date):
        return value.strftime("%Y-%m-%d")
    if isinstance(value, Decimal):
        return float(value)
    return value


def fila(row):
    """Serializa una fila (dict) de MySQL a tipos JSON."""
    return {k: _jsonable(v) for k, v in row.items()}


# ---------------------------------------------------------------------------
# Validaciones de fechas y rango
# ---------------------------------------------------------------------------
def _fecha_valida(texto):
    try:
        return datetime.datetime.strptime(texto, "%Y-%m-%d").date()
    except (ValueError, TypeError):
        return None


def _rango_fechas():
    """Lee fecha_desde / fecha_hasta del query string.

    Devuelve (clausula_sql, params, error). La clausula filtra a.fecha
    de forma inclusiva. Si hay un formato invalido devuelve error.
    """
    desde = request.args.get("fecha_desde", "").strip()
    hasta = request.args.get("fecha_hasta", "").strip()
    clausula, params = "", []
    if desde:
        if not _fecha_valida(desde):
            return None, None, "fecha_desde invalida (use AAAA-MM-DD)"
        clausula += " AND a.fecha >= %s"
        params.append(desde)
    if hasta:
        if not _fecha_valida(hasta):
            return None, None, "fecha_hasta invalida (use AAAA-MM-DD)"
        clausula += " AND a.fecha <= %s"
        params.append(hasta)
    return clausula, params, None


def _hora_a_segundos(texto):
    """Convierte 'HH:MM' o 'HH:MM:SS' a segundos; None si es invalida."""
    for fmt in ("%H:%M:%S", "%H:%M"):
        try:
            t = datetime.datetime.strptime(texto, fmt).time()
            return t.hour * 3600 + t.minute * 60 + t.second
        except (ValueError, TypeError):
            continue
    return None


# ===========================================================================
# 1) KPIs  ·  GET /api/actividades/kpis
# ===========================================================================
@bp.get("/api/actividades/kpis")
def kpis():
    rango, rparams, e = _rango_fechas()
    if e:
        return err(e, 400)

    # 1. Actividades programadas en el rango
    r = db.query_one(
        f"SELECT COUNT(*) AS n FROM actividades a "
        f"WHERE a.estado='PROGRAMADA'{rango}", rparams)
    programadas = int(r["n"])

    # 2. Total de participaciones de las actividades del rango
    r = db.query_one(
        f"SELECT COUNT(*) AS n FROM participaciones pa "
        f"JOIN actividades a ON a.id_actividad = pa.id_actividad "
        f"WHERE 1=1{rango}", rparams)
    total_part = int(r["n"])

    # 3. Tasa de asistencia (%): ASISTIO / (ASISTIO + NO_ASISTIO) * 100
    r = db.query_one(
        f"SELECT ROUND("
        f"  SUM(CASE WHEN pa.asistencia='ASISTIO' THEN 1 ELSE 0 END) * 100.0 "
        f"  / NULLIF(SUM(CASE WHEN pa.asistencia IN ('ASISTIO','NO_ASISTIO') "
        f"                     THEN 1 ELSE 0 END),0), 1) AS v "
        f"FROM participaciones pa "
        f"JOIN actividades a ON a.id_actividad = pa.id_actividad "
        f"WHERE 1=1{rango}", rparams)
    tasa_asistencia = float(r["v"]) if r["v"] is not None else 0.0

    # 4. Ocupacion de cupo (%): participaciones / SUM(cupo) * 100
    r = db.query_one(
        f"SELECT ROUND("
        f"  (SELECT COUNT(*) FROM participaciones pa "
        f"     JOIN actividades a ON a.id_actividad = pa.id_actividad "
        f"     WHERE 1=1{rango}) * 100.0 "
        f"  / NULLIF((SELECT SUM(cupo) FROM actividades a WHERE 1=1{rango}),0),1) AS v",
        rparams + rparams)
    ocupacion_cupo = float(r["v"]) if r["v"] is not None else 0.0

    # 5. Cobertura de residentes (%): residentes distintos que participan
    #    / residentes ACTIVOS * 100
    r = db.query_one(
        f"SELECT ROUND("
        f"  (SELECT COUNT(DISTINCT pa.id_residente) FROM participaciones pa "
        f"     JOIN actividades a ON a.id_actividad = pa.id_actividad "
        f"     WHERE 1=1{rango}) * 100.0 "
        f"  / NULLIF((SELECT COUNT(*) FROM residentes WHERE estado='ACTIVO'),0),1) AS v",
        rparams)
    cobertura = float(r["v"]) if r["v"] is not None else 0.0

    # 6. Participantes por actividad: participaciones / actividades
    r = db.query_one(
        f"SELECT ROUND("
        f"  (SELECT COUNT(*) FROM participaciones pa "
        f"     JOIN actividades a ON a.id_actividad = pa.id_actividad "
        f"     WHERE 1=1{rango}) * 1.0 "
        f"  / NULLIF((SELECT COUNT(*) FROM actividades a WHERE 1=1{rango}),0),1) AS v",
        rparams + rparams)
    part_por_actividad = float(r["v"]) if r["v"] is not None else 0.0

    data = {
        "actividades_programadas": programadas,
        "total_participaciones": total_part,
        "tasa_asistencia": tasa_asistencia,
        "ocupacion_cupo": ocupacion_cupo,
        "cobertura_residentes": cobertura,
        "participantes_por_actividad": part_por_actividad,
    }
    return ok(data=data)


# ===========================================================================
# 2) Grafica comparativa  ·  GET /api/actividades/grafico
#    Cupo disponible frente a residentes inscritos, por actividad.
# ===========================================================================
@bp.get("/api/actividades/grafico")
def grafico():
    rango, rparams, e = _rango_fechas()
    if e:
        return err(e, 400)
    filas = db.query(
        f"SELECT a.id_actividad, a.nombre, a.cupo, "
        f"       COUNT(pa.id_participacion) AS inscritos "
        f"FROM actividades a "
        f"LEFT JOIN participaciones pa ON pa.id_actividad = a.id_actividad "
        f"WHERE 1=1{rango} "
        f"GROUP BY a.id_actividad, a.nombre, a.cupo "
        f"ORDER BY a.id_actividad", rparams)
    data = {
        "labels": [f["nombre"] for f in filas],
        "cupo": [int(f["cupo"]) for f in filas],
        "inscritos": [int(f["inscritos"]) for f in filas],
    }
    return ok(data=data, total=len(filas))


# ===========================================================================
# 3) Listado con filtros  ·  GET /api/actividades
# ===========================================================================
@bp.get("/api/actividades")
def listar():
    where, params = ["1=1"], []

    estado = request.args.get("estado", "").strip()
    if estado:
        if estado not in ESTADOS:
            return err("estado invalido", 400)
        where.append("a.estado = %s")
        params.append(estado)

    lugar = request.args.get("lugar", "").strip()
    if lugar:
        where.append("a.lugar = %s")
        params.append(lugar)

    responsable = request.args.get("responsable", "").strip()
    if responsable:
        if not responsable.isdigit():
            return err("responsable invalido", 400)
        where.append("a.id_personal = %s")
        params.append(int(responsable))

    rango, rparams, e = _rango_fechas()
    if e:
        return err(e, 400)

    q = request.args.get("q", "").strip()
    if q:
        where.append("(a.nombre LIKE %s OR a.descripcion LIKE %s)")
        like = f"%{q}%"
        params.extend([like, like])

    sql = (
        f"SELECT a.id_actividad, a.nombre, a.descripcion, a.fecha, "
        f"       a.hora_inicio, a.hora_fin, a.lugar, a.cupo, a.id_personal, "
        f"       a.estado, "
        f"       CONCAT(COALESCE(p.nombres,''),' ',COALESCE(p.apellidos,'')) AS responsable, "
        f"       (SELECT COUNT(*) FROM participaciones pa "
        f"          WHERE pa.id_actividad = a.id_actividad) AS inscritos "
        f"FROM actividades a "
        f"LEFT JOIN personal p ON p.id_personal = a.id_personal "
        f"WHERE {' AND '.join(where)}{rango} "
        f"ORDER BY a.fecha, a.hora_inicio"
    )
    filas = [fila(r) for r in db.query(sql, params + rparams)]
    return ok(data=filas, total=len(filas))


# ===========================================================================
# 4) Obtener un registro  ·  GET /api/actividades/<id>
# ===========================================================================
@bp.get("/api/actividades/<int:id_actividad>")
def obtener(id_actividad):
    r = db.query_one(
        "SELECT a.*, "
        "  (SELECT COUNT(*) FROM participaciones pa "
        "     WHERE pa.id_actividad = a.id_actividad) AS inscritos "
        "FROM actividades a WHERE a.id_actividad = %s", (id_actividad,))
    if not r:
        return err("La actividad no existe", 404)
    return ok(data=fila(r))


# ===========================================================================
#   Validacion compartida para Create / Update
# ===========================================================================
def _validar(payload, parcial=False):
    """Valida el cuerpo del formulario. Devuelve (campos, error)."""
    d = payload or {}

    nombre = str(d.get("nombre", "")).strip()
    if not nombre:
        return None, "El nombre es obligatorio"
    if len(nombre) > 100:
        return None, "El nombre no debe exceder 100 caracteres"

    descripcion = d.get("descripcion")
    descripcion = str(descripcion).strip() if descripcion not in (None, "") else None
    if descripcion and len(descripcion) > 255:
        return None, "La descripcion no debe exceder 255 caracteres"

    fecha = str(d.get("fecha", "")).strip()
    if not _fecha_valida(fecha):
        return None, "La fecha es obligatoria y debe tener formato AAAA-MM-DD"

    hi = str(d.get("hora_inicio", "")).strip()
    hf = str(d.get("hora_fin", "")).strip()
    s_hi, s_hf = _hora_a_segundos(hi), _hora_a_segundos(hf)
    if s_hi is None or s_hf is None:
        return None, "hora_inicio y hora_fin son obligatorias (HH:MM)"
    if s_hf <= s_hi:
        return None, "hora_fin debe ser mayor que hora_inicio"

    lugar = str(d.get("lugar", "")).strip()
    if not lugar:
        return None, "El lugar es obligatorio"
    if len(lugar) > 80:
        return None, "El lugar no debe exceder 80 caracteres"

    cupo = d.get("cupo")
    try:
        cupo = int(cupo)
    except (ValueError, TypeError):
        return None, "El cupo debe ser un numero entero"
    if cupo <= 0:
        return None, "El cupo debe ser mayor que 0"

    estado = str(d.get("estado", "PROGRAMADA")).strip() or "PROGRAMADA"
    if estado not in ESTADOS:
        return None, "estado invalido (PROGRAMADA, REALIZADA o CANCELADA)"

    id_personal = d.get("id_personal")
    if id_personal in (None, "", "null"):
        id_personal = None
    else:
        try:
            id_personal = int(id_personal)
        except (ValueError, TypeError):
            return None, "id_personal invalido"
        existe = db.query_one(
            "SELECT 1 FROM personal WHERE id_personal = %s", (id_personal,))
        if not existe:
            return None, "El personal responsable no existe"

    campos = {
        "nombre": nombre, "descripcion": descripcion, "fecha": fecha,
        "hora_inicio": hi, "hora_fin": hf, "lugar": lugar,
        "cupo": cupo, "estado": estado, "id_personal": id_personal,
    }
    return campos, None


# ===========================================================================
# 5) Crear  ·  POST /api/actividades
# ===========================================================================
@bp.post("/api/actividades")
def crear():
    campos, e = _validar(request.get_json(silent=True))
    if e:
        return err(e, 400)
    new_id, _ = db.execute(
        "INSERT INTO actividades "
        "(nombre, descripcion, fecha, hora_inicio, hora_fin, lugar, cupo, "
        " id_personal, estado) "
        "VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s)",
        (campos["nombre"], campos["descripcion"], campos["fecha"],
         campos["hora_inicio"], campos["hora_fin"], campos["lugar"],
         campos["cupo"], campos["id_personal"], campos["estado"]))
    return ok(data={"id_actividad": new_id}, code=201)


# ===========================================================================
# 6) Actualizar  ·  PUT /api/actividades/<id>
# ===========================================================================
@bp.put("/api/actividades/<int:id_actividad>")
def actualizar(id_actividad):
    actual = db.query_one(
        "SELECT id_actividad FROM actividades WHERE id_actividad = %s",
        (id_actividad,))
    if not actual:
        return err("La actividad no existe", 404)

    campos, e = _validar(request.get_json(silent=True))
    if e:
        return err(e, 400)

    # Regla de negocio: el cupo no puede quedar por debajo de los inscritos.
    r = db.query_one(
        "SELECT COUNT(*) AS n FROM participaciones WHERE id_actividad = %s",
        (id_actividad,))
    inscritos = int(r["n"])
    if campos["cupo"] < inscritos:
        return err(
            f"El cupo ({campos['cupo']}) no puede ser menor que los "
            f"inscritos actuales ({inscritos})", 409)

    db.execute(
        "UPDATE actividades SET nombre=%s, descripcion=%s, fecha=%s, "
        "hora_inicio=%s, hora_fin=%s, lugar=%s, cupo=%s, id_personal=%s, "
        "estado=%s WHERE id_actividad=%s",
        (campos["nombre"], campos["descripcion"], campos["fecha"],
         campos["hora_inicio"], campos["hora_fin"], campos["lugar"],
         campos["cupo"], campos["id_personal"], campos["estado"],
         id_actividad))
    return ok(data={"id_actividad": id_actividad})


# ===========================================================================
# 7) Eliminar  ·  DELETE /api/actividades/<id>
#    Al eliminar se borran sus participaciones (ON DELETE CASCADE): se
#    informa cuantas se perderan.
# ===========================================================================
@bp.delete("/api/actividades/<int:id_actividad>")
def eliminar(id_actividad):
    actual = db.query_one(
        "SELECT id_actividad FROM actividades WHERE id_actividad = %s",
        (id_actividad,))
    if not actual:
        return err("La actividad no existe", 404)
    r = db.query_one(
        "SELECT COUNT(*) AS n FROM participaciones WHERE id_actividad = %s",
        (id_actividad,))
    perdidas = int(r["n"])
    db.execute("DELETE FROM actividades WHERE id_actividad = %s",
               (id_actividad,))
    return ok(data={"id_actividad": id_actividad,
                    "participaciones_eliminadas": perdidas}, code=200)


# ===========================================================================
# 8) Catalogos  ·  GET /api/actividades/catalogos
#    Personal responsable para la lista del formulario.
# ===========================================================================
@bp.get("/api/actividades/catalogos")
def catalogos():
    personal = db.query(
        "SELECT id_personal, "
        "       CONCAT(nombres,' ',apellidos,' - ',cargo) AS nombre "
        "FROM personal WHERE estado='ACTIVO' "
        "ORDER BY nombres, apellidos")
    lugares = db.query(
        "SELECT DISTINCT lugar FROM actividades "
        "WHERE lugar IS NOT NULL AND lugar <> '' ORDER BY lugar")
    return ok(data={
        "personal": [fila(p) for p in personal],
        "lugares": [l["lugar"] for l in lugares],
        "estados": list(ESTADOS),
    })


# ===========================================================================
# 9) Transferencia a SQL Server
# ===========================================================================
@bp.post("/api/actividades/transferir")
def transferir():
    return transferir_actividades()


@bp.get("/api/actividades/transferencia/resumen")
def resumen():
    return resumen_transferencia()


# ===========================================================================
# 10) (OPCIONAL - puntaje extra) Inscribir participacion
#     POST /api/actividades/<id>/participaciones
# ===========================================================================
@bp.post("/api/actividades/<int:id_actividad>/participaciones")
def inscribir(id_actividad):
    d = request.get_json(silent=True) or {}
    act = db.query_one(
        "SELECT cupo FROM actividades WHERE id_actividad = %s", (id_actividad,))
    if not act:
        return err("La actividad no existe", 404)

    id_residente = d.get("id_residente")
    try:
        id_residente = int(id_residente)
    except (ValueError, TypeError):
        return err("id_residente es obligatorio", 400)

    res = db.query_one(
        "SELECT estado FROM residentes WHERE id_residente = %s", (id_residente,))
    if not res:
        return err("El residente no existe", 404)
    if res["estado"] != "ACTIVO":
        return err("Solo se pueden inscribir residentes ACTIVOS", 409)

    # No exceder el cupo
    r = db.query_one(
        "SELECT COUNT(*) AS n FROM participaciones WHERE id_actividad = %s",
        (id_actividad,))
    if int(r["n"]) >= int(act["cupo"]):
        return err("La actividad ya alcanzo su cupo maximo", 409)

    # No duplicar inscripcion (UNIQUE id_actividad + id_residente)
    dup = db.query_one(
        "SELECT 1 FROM participaciones "
        "WHERE id_actividad = %s AND id_residente = %s",
        (id_actividad, id_residente))
    if dup:
        return err("El residente ya esta inscrito en esta actividad", 409)

    asistencia = str(d.get("asistencia", "INSCRITO")).strip() or "INSCRITO"
    if asistencia not in ("INSCRITO", "ASISTIO", "NO_ASISTIO"):
        return err("asistencia invalida", 400)

    new_id, _ = db.execute(
        "INSERT INTO participaciones (id_actividad, id_residente, asistencia) "
        "VALUES (%s,%s,%s)", (id_actividad, id_residente, asistencia))
    return ok(data={"id_participacion": new_id}, code=201)
