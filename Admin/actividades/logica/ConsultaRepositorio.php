<?php
/**
 * Lecturas de apoyo: combos (personal/residentes), datos puntuales y
 * la vista de participación con sus indicadores (HU-04 / §8.7).
 * Tablas ajenas (residentes, personal) y la vista se leen SOLO en modo lectura.
 *
 * La vista oficial vw_participacion_actividades es AGREGADA (una fila por
 * actividad) y ya trae: id_actividad, nombre, fecha, hora_inicio, hora_fin,
 * lugar, cupo, estado, responsable, inscritos, asistieron, no_asistieron,
 * cupos_disponibles.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../includes/helpers.php';

class ConsultaRepositorio
{
    public function __construct(private PDO $pdo) {}

    /* ---------- Combos ---------- */

    /** Personal ACTIVO para el combo de responsable (RN-07 / CA-01.5). */
    public function personalActivo(): array
    {
        $sql = 'SELECT id_personal, codigo_personal, nombres, apellidos, cargo
                FROM personal
                WHERE estado = :activo
                ORDER BY apellidos, nombres';
        $st = $this->pdo->prepare($sql);
        $st->execute([':activo' => 'ACTIVO']);
        return $st->fetchAll();
    }

    /** Residentes ACTIVO no inscritos aún en la actividad (CA-02.2). */
    public function residentesInscribibles(int $idActividad): array
    {
        $sql = 'SELECT r.id_residente, r.codigo_residente, r.nombres, r.apellidos
                FROM residentes r
                WHERE r.estado = :activo
                  AND r.id_residente NOT IN (
                        SELECT p.id_residente FROM participaciones p WHERE p.id_actividad = :a
                  )
                ORDER BY r.apellidos, r.nombres';
        $st = $this->pdo->prepare($sql);
        $st->execute([':activo' => 'ACTIVO', ':a' => $idActividad]);
        return $st->fetchAll();
    }

    /* ---------- Datos puntuales para revalidación en servidor ---------- */

    /** Personal por id (para revalidar responsable, RN-07). */
    public function personalPorId(int $id): ?array
    {
        $st = $this->pdo->prepare(
            'SELECT id_personal, codigo_personal, nombres, apellidos, cargo, estado
             FROM personal WHERE id_personal = :id'
        );
        $st->execute([':id' => $id]);
        $f = $st->fetch();
        return $f === false ? null : $f;
    }

    /** Residente por id (para revalidar inscripción, RN-15/RN-19). */
    public function residentePorId(int $id): ?array
    {
        $st = $this->pdo->prepare(
            'SELECT id_residente, codigo_residente, nombres, apellidos, estado, fecha_ingreso
             FROM residentes WHERE id_residente = :id'
        );
        $st->execute([':id' => $id]);
        $f = $st->fetch();
        return $f === false ? null : $f;
    }

    /* ---------- Vista de participación (HU-04) ---------- */

    /**
     * Consulta vw_participacion_actividades (una fila por actividad) con filtros (V-06)
     * y calcula los porcentajes en PHP (§8.7).
     *
     * @param array $f Filtros: actividad (texto), desde, hasta
     */
    public function vistaParticipacion(array $f): array
    {
        $where  = ['1 = 1'];
        $params = [];

        if (($f['actividad'] ?? '') !== '') {
            $where[] = 'v.nombre LIKE :act';
            $params[':act'] = '%' . addcslashes($f['actividad'], '%_\\') . '%';
        }
        if (($f['desde'] ?? '') !== '') {
            $where[] = 'v.fecha >= :desde';
            $params[':desde'] = $f['desde'];
        }
        if (($f['hasta'] ?? '') !== '') {
            $where[] = 'v.fecha <= :hasta';
            $params[':hasta'] = $f['hasta'];
        }

        $sql = 'SELECT v.id_actividad, v.nombre, v.fecha, v.hora_inicio, v.hora_fin,
                       v.lugar, v.cupo, v.estado, v.responsable,
                       v.inscritos, v.asistieron, v.no_asistieron, v.cupos_disponibles
                FROM vw_participacion_actividades v
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY v.fecha DESC, v.nombre';
        $st = $this->pdo->prepare($sql);
        $st->execute($params);

        $filas = $st->fetchAll();
        foreach ($filas as &$r) {
            $inscritos     = (int)$r['inscritos'];
            $asistieron    = (int)$r['asistieron'];
            $no_asistieron = (int)$r['no_asistieron'];
            $cupo          = (int)$r['cupo'];

            $r['pendientes']    = $inscritos - $asistieron - $no_asistieron;
            // % Ocupación (cupo nunca es 0 por CHECK).
            $r['pct_ocupacion'] = round($inscritos / $cupo * 100, 1);
            // % Asistencia: los pendientes NO cuentan; denominador 0 => null ("—").
            $den = $asistieron + $no_asistieron;
            $r['pct_asistencia'] = $den === 0 ? null : round($asistieron / $den * 100, 1);
        }
        unset($r);

        return $filas;
    }

    /**
     * Detalle por residente (desde tablas base) para el reporte.
     * La vista es agregada, por eso el detalle nominal se arma aquí.
     *
     * @param array $f Filtros: actividad (texto), desde, hasta
     */
    public function detalleParticipacion(array $f): array
    {
        $where  = ['1 = 1'];
        $params = [];

        if (($f['actividad'] ?? '') !== '') {
            $where[] = 'a.nombre LIKE :act';
            $params[':act'] = '%' . addcslashes($f['actividad'], '%_\\') . '%';
        }
        if (($f['desde'] ?? '') !== '') {
            $where[] = 'a.fecha >= :desde';
            $params[':desde'] = $f['desde'];
        }
        if (($f['hasta'] ?? '') !== '') {
            $where[] = 'a.fecha <= :hasta';
            $params[':hasta'] = $f['hasta'];
        }

        $sql = 'SELECT a.nombre AS actividad, a.fecha, r.codigo_residente,
                       CONCAT(r.nombres, " ", r.apellidos) AS residente,
                       p.asistencia, p.observacion
                FROM participaciones p
                JOIN actividades a ON a.id_actividad = p.id_actividad
                JOIN residentes r  ON r.id_residente = p.id_residente
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY a.fecha DESC, a.nombre, r.apellidos';
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }
}
