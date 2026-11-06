from flask import jsonify, request
from pymysql.err import IntegrityError, OperationalError

from db_mysql import DatabaseUnavailableError, query
from . import habitaciones_bp


ESTADOS = ("DISPONIBLE", "OCUPADA", "MANTENIMIENTO")
SELECT = """
SELECT h.id_habitacion, h.numero, h.piso, h.capacidad, h.estado,
       h.observaciones, COALESCE(r.total, 0) AS residentes_asignados
FROM habitaciones AS h
LEFT JOIN (
    SELECT id_habitacion, COUNT(*) AS total
    FROM residentes
    WHERE id_habitacion IS NOT NULL
    GROUP BY id_habitacion
) AS r ON r.id_habitacion = h.id_habitacion
"""


def error(mensaje, codigo):
    return jsonify(ok=False, error=mensaje), codigo


@habitaciones_bp.errorhandler(DatabaseUnavailableError)
@habitaciones_bp.errorhandler(OperationalError)
def base_no_disponible(exc):
    return error("Base de datos MySQL no disponible", 503)


def buscar(id_habitacion):
    return query(SELECT + " WHERE h.id_habitacion = %s",
                 (id_habitacion,), fetchone=True)


def no_existe():
    return error("No existe la habitación solicitada", 404)


def datos_habitacion():
    datos = request.get_json(silent=True)
    if not isinstance(datos, dict):
        raise ValueError("Envía un objeto JSON válido")
    numero = datos.get("numero")
    if not isinstance(numero, str) or not 1 <= len(numero.strip()) <= 10:
        raise ValueError("El número debe tener entre 1 y 10 caracteres")
    for campo in ("piso", "capacidad"):
        valor = datos.get(campo)
        if type(valor) is not int or not 1 <= valor <= 2147483647:
            raise ValueError(f"{campo} debe ser un entero positivo válido")
    estado = datos.get("estado", "DISPONIBLE")
    if estado not in ESTADOS:
        raise ValueError("Estado inválido")
    observaciones = datos.get("observaciones")
    if observaciones is not None and (
        not isinstance(observaciones, str) or len(observaciones) > 255
    ):
        raise ValueError("Las observaciones deben tener hasta 255 caracteres")
    return numero.strip(), datos["piso"], datos["capacidad"], estado, observaciones


@habitaciones_bp.get("", strict_slashes=False)
def listar_habitaciones():
    condiciones = []
    params = []
    estado = request.args.get("estado", "").strip()
    if estado:
        if estado not in ESTADOS:
            return error("Estado inválido", 400)
        condiciones.append("h.estado = %s")
        params.append(estado)
    for campo in ("piso", "capacidad"):
        valor = request.args.get(campo, "").strip()
        if valor:
            try:
                entero = int(valor)
                if not 1 <= entero <= 2147483647:
                    raise ValueError
            except ValueError:
                return error(f"{campo} debe ser un entero positivo válido", 400)
            condiciones.append(f"h.{campo} = %s")
            params.append(entero)
    texto = request.args.get("q", "").strip()
    if texto:
        condiciones.append("(h.numero LIKE %s OR COALESCE(h.observaciones, '') LIKE %s)")
        params.extend((f"%{texto}%", f"%{texto}%"))
    where = " WHERE " + " AND ".join(condiciones) if condiciones else ""
    filas = query(SELECT + where + " ORDER BY h.piso, h.numero", tuple(params))
    return jsonify(ok=True, total=len(filas), data=filas)


@habitaciones_bp.get("/<int:id_habitacion>")
def obtener_habitacion(id_habitacion):
    fila = buscar(id_habitacion)
    return jsonify(ok=True, data=fila) if fila else no_existe()


@habitaciones_bp.post("", strict_slashes=False)
def crear_habitacion():
    try:
        datos = datos_habitacion()
    except ValueError as exc:
        return error(str(exc), 400)
    try:
        resultado = query(
            """INSERT INTO habitaciones (numero, piso, capacidad, estado, observaciones)
               VALUES (%s, %s, %s, %s, %s)""", datos, commit=True,
        )
    except IntegrityError as exc:
        if exc.args[0] == 1062:
            return error(f"Ya existe la habitación {datos[0]}", 409)
        raise
    return jsonify(ok=True, data=buscar(resultado["lastrowid"])), 201


@habitaciones_bp.put("/<int:id_habitacion>")
def actualizar_habitacion(id_habitacion):
    actual = buscar(id_habitacion)
    if not actual:
        return no_existe()
    try:
        datos = datos_habitacion()
    except ValueError as exc:
        return error(str(exc), 400)
    try:
        query(
            """UPDATE habitaciones AS h
               SET numero = %s, piso = %s, capacidad = %s, estado = %s, observaciones = %s
               WHERE h.id_habitacion = %s
               AND (h.capacidad <= %s OR %s >= (
                   SELECT COUNT(*) FROM residentes WHERE id_habitacion = %s
               ))""",
            (*datos, id_habitacion, datos[2], datos[2], id_habitacion), commit=True,
        )
    except IntegrityError as exc:
        if exc.args[0] == 1062:
            return error(f"Ya existe la habitación {datos[0]}", 409)
        raise
    fila = buscar(id_habitacion)
    if not fila:
        return no_existe()
    if datos[2] < fila["capacidad"] and datos[2] < fila["residentes_asignados"]:
        return error("La capacidad no puede ser menor a los residentes actualmente asignados", 409)
    return jsonify(ok=True, data=fila)


@habitaciones_bp.delete("/<int:id_habitacion>")
def eliminar_habitacion(id_habitacion):
    mensaje = "No se puede eliminar una habitación con residentes asignados"
    try:
        resultado = query(
            """DELETE FROM habitaciones WHERE id_habitacion = %s
               AND NOT EXISTS (SELECT 1 FROM residentes WHERE id_habitacion = %s)""",
            (id_habitacion, id_habitacion), commit=True,
        )
    except IntegrityError as exc:
        if exc.args[0] == 1451:
            return error(mensaje, 409)
        raise
    if resultado["rowcount"]:
        return "", 204
    return error(mensaje, 409) if buscar(id_habitacion) else no_existe()
