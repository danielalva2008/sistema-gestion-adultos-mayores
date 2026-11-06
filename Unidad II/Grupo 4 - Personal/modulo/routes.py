import re
from datetime import date
from flask import Blueprint, jsonify, request
from werkzeug.exceptions import BadRequest, NotFound, Conflict
from db_mysql import connect, query

bp = Blueprint("personal", __name__, url_prefix="/api/personal")
FIELDS = {"codigo_personal": 20, "nombres": 100, "apellidos": 100, "cargo": 80,
          "especialidad": 100, "telefono": 20, "email": 120, "fecha_ingreso": 10, "estado": 8}


def iso_date(value):
    if not isinstance(value, str) or not re.fullmatch(r"\d{4}-\d{2}-\d{2}", value):
        raise BadRequest("La fecha debe tener formato AAAA-MM-DD")
    try:
        parsed = date.fromisoformat(value)
    except ValueError:
        raise BadRequest("Fecha inválida")
    if parsed.year < 1000:
        raise BadRequest("La fecha debe ser posterior al año 999")
    return parsed


def filters():
    terms, values = [], []
    for field in ("cargo", "estado", "especialidad"):
        value = request.args.get(field, "").strip()
        if value:
            if len(value) > FIELDS[field]:
                raise BadRequest("Filtro demasiado largo")
            if field == "estado" and value not in ("ACTIVO", "INACTIVO"):
                raise BadRequest("Estado inválido")
            terms.append(f"p.{field} = %s")
            values.append(value)
    lower, upper = request.args.get("fecha_desde", ""), request.args.get("fecha_hasta", "")
    if lower:
        iso_date(lower); terms.append("p.fecha_ingreso >= %s"); values.append(lower)
    if upper:
        iso_date(upper); terms.append("p.fecha_ingreso <= %s"); values.append(upper)
    if lower and upper and lower > upper:
        raise BadRequest("La fecha inicial no puede ser posterior a la final")
    q = request.args.get("q", "").strip()
    if q:
        if len(q) > 100:
            raise BadRequest("La búsqueda admite hasta 100 caracteres")
        # Escapar comodines: una búsqueda textual no amplía el conjunto de resultados.
        q = q.replace("!", "!!").replace("%", "!%").replace("_", "!_")
        terms.append("(p.nombres LIKE %s ESCAPE '!' OR p.apellidos LIKE %s ESCAPE '!' OR p.codigo_personal LIKE %s ESCAPE '!')")
        values.extend([f"%{q}%"] * 3)
    return (" WHERE " + " AND ".join(terms) if terms else ""), values


def validate():
    if not request.is_json:
        raise BadRequest("Envíe un objeto JSON con Content-Type application/json")
    body = request.get_json()
    if not isinstance(body, dict):
        raise BadRequest("Se requiere un objeto JSON")
    if set(body) - set(FIELDS):
        raise BadRequest("El JSON contiene campos no admitidos")
    data = {}
    for field, limit in FIELDS.items():
        value = body.get(field, "ACTIVO" if field == "estado" else "")
        if value is None and field in ("especialidad", "telefono", "email"):
            value = ""
        if not isinstance(value, str):
            raise BadRequest(f"{field} debe ser texto")
        value = value.strip()
        if len(value) > limit:
            raise BadRequest(f"{field} admite hasta {limit} caracteres")
        data[field] = value
    for field in ("codigo_personal", "nombres", "apellidos", "cargo", "fecha_ingreso"):
        if not data[field]:
            raise BadRequest(f"{field} es obligatorio")
    if iso_date(data["fecha_ingreso"]) > date.today():
        raise BadRequest("La fecha de ingreso no puede ser futura")
    if data["estado"] not in ("ACTIVO", "INACTIVO"):
        raise BadRequest("Estado inválido")
    if data["telefono"] and not re.fullmatch(r"[0-9]{1,20}", data["telefono"]):
        raise BadRequest("El teléfono solo admite dígitos, hasta 20")
    if data["email"] and not re.fullmatch(r"[^\s@]+@[^\s@]+\.[^\s@]+", data["email"]):
        raise BadRequest("Formato de correo inválido")
    return [data[f] or None for f in FIELDS]


@bp.get("")
def listing():
    where, values = filters()
    rows = query("SELECT p.* FROM personal p" + where + " ORDER BY p.id_personal", values)
    return jsonify(ok=True, total=len(rows), data=rows)


@bp.get("/catalogos")
def catalogs():
    return jsonify(ok=True, data={
        "cargos": [r["cargo"] for r in query("SELECT DISTINCT cargo FROM personal ORDER BY cargo")],
        "especialidades": [r["especialidad"] for r in query("SELECT DISTINCT especialidad FROM personal WHERE especialidad IS NOT NULL AND especialidad <> '' ORDER BY especialidad")]})


@bp.get("/<int:identifier>")
def one(identifier):
    rows = query("SELECT * FROM personal WHERE id_personal=%s", (identifier,))
    if not rows:
        raise NotFound("No existe el trabajador")
    return jsonify(ok=True, data=rows[0])


@bp.post("")
def create():
    values = validate()
    with connect() as conn:
        with conn.cursor() as cur:
            cur.execute("INSERT INTO personal (" + ",".join(FIELDS) + ") VALUES (" + ",".join(["%s"] * len(FIELDS)) + ")", values)
            identifier = cur.lastrowid
        conn.commit()
    response = jsonify(ok=True, data={"id_personal": identifier})
    response.headers["Location"] = f"/api/personal/{identifier}"
    return response, 201


@bp.put("/<int:identifier>")
def update(identifier):
    values = validate()
    with connect() as conn:
        with conn.cursor() as cur:
            cur.execute("SELECT id_personal FROM personal WHERE id_personal=%s FOR UPDATE", (identifier,))
            if not cur.fetchone():
                raise NotFound("No existe el trabajador")
            cur.execute("UPDATE personal SET " + ",".join(f"{f}=%s" for f in FIELDS) + " WHERE id_personal=%s", values + [identifier])
        conn.commit()
    return jsonify(ok=True, data={"id_personal": identifier})


@bp.delete("/<int:identifier>")
def delete(identifier):
    with connect() as conn:
        with conn.cursor() as cur:
            # El bloqueo del padre impide insertar nuevas referencias durante la comprobación.
            cur.execute("SELECT id_personal FROM personal WHERE id_personal=%s FOR UPDATE", (identifier,))
            if not cur.fetchone():
                raise NotFound("No existe el trabajador")
            cur.execute("SELECT (SELECT COUNT(*) FROM actividades WHERE id_personal_responsable=%s) + (SELECT COUNT(*) FROM incidentes WHERE id_personal_reporta=%s) AS relaciones", (identifier, identifier))
            if cur.fetchone()["relaciones"]:
                raise Conflict("El trabajador tiene actividades o incidentes. Cambie su estado a INACTIVO para conservar el historial.")
            cur.execute("DELETE FROM personal WHERE id_personal=%s", (identifier,))
        conn.commit()
    return "", 204


@bp.get("/kpis")
def kpis():
    where, values = filters()
    data = query("""SELECT COALESCE(SUM(p.estado='ACTIVO'),0) AS personal_activo,
        COUNT(*) AS total_personal,
        ROUND(COALESCE(AVG(TIMESTAMPDIFF(MONTH,p.fecha_ingreso,CURDATE())) / 12,0),1) AS antiguedad_promedio,
        ROUND(COALESCE((SELECT COUNT(*) FROM residentes WHERE estado='ACTIVO') /
            NULLIF(SUM(p.cargo='Cuidador'),0),0),1) AS residentes_por_cuidador
        FROM personal p""" + where, values)[0]
    data["distribucion_cargo"] = query("SELECT p.cargo,COUNT(*) AS total FROM personal p" + where + " GROUP BY p.cargo ORDER BY total DESC,p.cargo", values)
    for table, fk, key in (("actividades", "id_personal_responsable", "actividades_por_responsable"),
                           ("incidentes", "id_personal_reporta", "incidentes_por_persona")):
        numerator = "COUNT(*)" if table == "actividades" else f"COUNT(t.{fk})"
        joins = f" JOIN personal p ON p.id_personal=t.{fk}" if where else ""
        data[key] = query(f"SELECT ROUND(COALESCE({numerator}/NULLIF(COUNT(DISTINCT t.{fk}),0),0),1) AS valor FROM {table} t" + joins + where, values)[0]["valor"]
    return jsonify(ok=True, data=data)


@bp.get("/grafico")
def chart():
    where, values = filters()
    rows = query("""SELECT p.cargo, COALESCE(SUM(a.total),0) AS actividades,
        COALESCE(SUM(i.total),0) AS incidentes FROM personal p
        LEFT JOIN (SELECT id_personal_responsable,COUNT(*) total FROM actividades GROUP BY id_personal_responsable) a
            ON a.id_personal_responsable=p.id_personal
        LEFT JOIN (SELECT id_personal_reporta,COUNT(*) total FROM incidentes GROUP BY id_personal_reporta) i
            ON i.id_personal_reporta=p.id_personal""" + where + " GROUP BY p.cargo ORDER BY p.cargo", values)
    return jsonify(ok=True, total=len(rows), data=rows)
