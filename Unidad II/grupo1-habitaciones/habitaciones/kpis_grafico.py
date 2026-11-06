# Reservado para Integrante 2: GET /kpis y GET /grafico
# from . import habitaciones_bp

"""
Integrante 2 - Backend de KPIs
Sistema de Gestion de Residencia
Modulo: Habitaciones
"""

from flask import jsonify

from db_mysql import query
from . import habitaciones_bp


# ==================================================
# CONSULTA SQL: INDICADORES DE HABITACIONES
# ==================================================

SQL_KPIS = """
SELECT
    h.total_habitaciones,
    h.capacidad_instalada,
    h.camas_operativas,
    r.residentes_ocupando,

    ROUND(
        COALESCE(
            r.residentes_ocupando * 100.0 /
            NULLIF(h.camas_operativas, 0),
            0
        ),
        1
    ) AS tasa_ocupacion,

    CAST(h.camas_operativas AS SIGNED)
    - CAST(r.residentes_ocupando AS SIGNED)
    AS camas_libres

FROM (
    SELECT
        COUNT(*) AS total_habitaciones,

        CAST(
            COALESCE(SUM(capacidad), 0)
            AS UNSIGNED
        ) AS capacidad_instalada,

        CAST(
            COALESCE(
                SUM(
                    CASE
                        WHEN estado <> 'MANTENIMIENTO'
                        THEN capacidad
                        ELSE 0
                    END
                ),
                0
            ) AS UNSIGNED
        ) AS camas_operativas

    FROM habitaciones
) AS h

CROSS JOIN (
    SELECT COUNT(*) AS residentes_ocupando
    FROM residentes
    WHERE estado = 'ACTIVO'
      AND id_habitacion IS NOT NULL
) AS r
"""


# ==================================================
# CONSULTA SQL: HABITACIONES POR ESTADO
# ==================================================

SQL_ESTADOS = """
SELECT
    estado,
    COUNT(*) AS total
FROM habitaciones
GROUP BY estado
"""


# ==================================================
# ENDPOINT: GET /api/habitaciones/kpis
# ==================================================

@habitaciones_bp.route("/kpis", methods=["GET"])
def obtener_kpis():
    """Obtiene los indicadores calculados en MySQL."""

    resultado = query(SQL_KPIS, fetchone=True)
    estados = query(SQL_ESTADOS)

    por_estado = {
        "DISPONIBLE": 0,
        "OCUPADA": 0,
        "MANTENIMIENTO": 0,
    }

    for fila in estados:
        por_estado[fila["estado"]] = int(fila["total"])

    data = {
        "total_habitaciones": int(
            resultado["total_habitaciones"]
        ),
        "capacidad_instalada": int(
            resultado["capacidad_instalada"]
        ),
        "camas_operativas": int(
            resultado["camas_operativas"]
        ),
        "residentes_ocupando": int(
            resultado["residentes_ocupando"]
        ),
        "tasa_ocupacion": float(
            resultado["tasa_ocupacion"]
        ),
        "camas_libres": int(
            resultado["camas_libres"]
        ),
        "por_estado": por_estado,
        "formulas": {
            "total_habitaciones":
                "COUNT(*) de habitaciones",
            "capacidad_instalada":
                "SUM(capacidad)",
            "camas_operativas":
                "SUM(capacidad) de habitaciones "
                "distintas de MANTENIMIENTO",
            "tasa_ocupacion":
                "Residentes ACTIVOS con habitacion "
                "/ camas operativas x 100",
            "camas_libres":
                "Camas operativas - residentes "
                "ACTIVOS con habitacion",
            "por_estado":
                "COUNT(*) agrupado por estado",
        },
    }

    return jsonify({
        "ok": True,
        "data": data,
    }), 200


# ==================================================
# INTEGRANTE 2 - GRAFICA COMPARATIVA POR PISO
# ==================================================

SQL_GRAFICO = """
SELECT
    h.piso,

    CAST(
        COALESCE(SUM(h.capacidad), 0)
        AS UNSIGNED
    ) AS capacidad_camas,

    CAST(
        COALESCE(SUM(r.total_res), 0)
        AS UNSIGNED
    ) AS residentes_asignados

FROM habitaciones AS h

LEFT JOIN (
    SELECT
        id_habitacion,
        COUNT(*) AS total_res
    FROM residentes
    WHERE estado = 'ACTIVO'
      AND id_habitacion IS NOT NULL
    GROUP BY id_habitacion
) AS r
    ON h.id_habitacion = r.id_habitacion

GROUP BY h.piso
ORDER BY h.piso ASC
"""


# ==================================================
# ENDPOINT: GET /api/habitaciones/grafico
# ==================================================

@habitaciones_bp.route("/grafico", methods=["GET"])
def obtener_grafico():
    """Devuelve los datos de la grafica por piso."""

    filas = query(SQL_GRAFICO)

    datos = [
        {
            "piso": int(fila["piso"]),
            "etiqueta": f"Piso {fila['piso']}",
            "capacidad_camas": int(fila["capacidad_camas"]),
            "residentes_asignados": int(
                fila["residentes_asignados"]
            ),
        }
        for fila in filas
    ]

    return jsonify({
        "ok": True,
        "total": len(datos),
        "categorias": [
            fila["etiqueta"] for fila in datos
        ],
        "series": [
            {
                "nombre": "Capacidad de camas",
                "datos": [
                    fila["capacidad_camas"]
                    for fila in datos
                ],
            },
            {
                "nombre": "Residentes asignados",
                "datos": [
                    fila["residentes_asignados"]
                    for fila in datos
                ],
            },
        ],
        "data": datos,
    }), 200
