<?php
/**
 * Acceso a datos de la tabla `actividades` (Grupo 6).
 * Solo SQL preparado (RN-28). No contiene HTML ni reglas de negocio.
 * Nombres de columnas verificados contra database.sql oficial:
 *   actividades(id_actividad, nombre, descripcion, fecha, hora_inicio, hora_fin,
 *               lugar, cupo, id_personal, estado)
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../includes/helpers.php';

class ActividadRepositorio
{
    public function __construct(private PDO $pdo) {}

    /** Inserta una actividad y devuelve su id. Nace siempre PROGRAMADA (RN-06). */
    public function crear(array $d): int
    {
        $sql = 'INSERT INTO actividades
                    (nombre, descripcion, fecha, hora_inicio, hora_fin, lugar, cupo, id_personal, estado)
                VALUES
                    (:nombre, :descripcion, :fecha, :hi, :hf, :lugar, :cupo, :resp, :estado)';
        $st = $this->pdo->prepare($sql);
        $st->execute([
            ':nombre'      => $d['nombre'],
            ':descripcion' => $d['descripcion'],
            ':fecha'       => $d['fecha'],
            ':hi'          => $d['hora_inicio'],
            ':hf'          => $d['hora_fin'],
            ':lugar'       => $d['lugar'],
            ':cupo'        => $d['cupo'],
            ':resp'        => $d['id_personal'],
            ':estado'      => 'PROGRAMADA',
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    /** Actualiza los campos editables de una actividad. */
    public function actualizar(int $id, array $d): void
    {
        $sql = 'UPDATE actividades SET
                    nombre      = :nombre,
                    descripcion = :descripcion,
                    fecha       = :fecha,
                    hora_inicio = :hi,
                    hora_fin    = :hf,
                    lugar       = :lugar,
                    cupo        = :cupo,
                    id_personal = :resp
                WHERE id_actividad = :id';
        $st = $this->pdo->prepare($sql);
        $st->execute([
            ':nombre'      => $d['nombre'],
            ':descripcion' => $d['descripcion'],
            ':fecha'       => $d['fecha'],
            ':hi'          => $d['hora_inicio'],
            ':hf'          => $d['hora_fin'],
            ':lugar'       => $d['lugar'],
            ':cupo'        => $d['cupo'],
            ':resp'        => $d['id_personal'],
            ':id'          => $id,
        ]);
    }

    /** Lee una actividad por id (sin bloqueo). Devuelve null si no existe. */
    public function porId(int $id): ?array
    {
        $st = $this->pdo->prepare('SELECT * FROM actividades WHERE id_actividad = :id');
        $st->execute([':id' => $id]);
        $fila = $st->fetch();
        return $fila === false ? null : $fila;
    }

    /**
     * Lee y BLOQUEA la fila de la actividad dentro de una transacción (RN-17).
     * Debe llamarse con una transacción ya iniciada.
     */
    public function porIdBloqueando(int $id): ?array
    {
        $st = $this->pdo->prepare('SELECT * FROM actividades WHERE id_actividad = :id FOR UPDATE');
        $st->execute([':id' => $id]);
        $fila = $st->fetch();
        return $fila === false ? null : $fila;
    }

    /** Cancela (baja lógica, Variante B / RN-13B). */
    public function cancelar(int $id): void
    {
        $st = $this->pdo->prepare('UPDATE actividades SET estado = :estado WHERE id_actividad = :id');
        $st->execute([':estado' => ESTADO_CANCELADA, ':id' => $id]);
    }

    /** Reactiva una actividad cancelada (RN-13B). */
    public function reactivar(int $id): void
    {
        $st = $this->pdo->prepare('UPDATE actividades SET estado = :estado WHERE id_actividad = :id');
        $st->execute([':estado' => 'PROGRAMADA', ':id' => $id]);
    }

    /** Eliminación física (no usada en Variante B; disponible por completitud). */
    public function eliminar(int $id): void
    {
        $st = $this->pdo->prepare('DELETE FROM actividades WHERE id_actividad = :id');
        $st->execute([':id' => $id]);
    }

    /**
     * Solapamiento del responsable el mismo día (RN-08 / Q-SOL-RESP).
     * Excluye la propia actividad ($excluir) y las canceladas (Variante B).
     * Devuelve la actividad en conflicto o null.
     */
    public function solapamientoResponsable(
        int $idResponsable, string $fecha, string $hi, string $hf, int $excluir
    ): ?array {
        $sql = 'SELECT id_actividad, nombre, hora_inicio, hora_fin
                FROM actividades
                WHERE id_personal = :resp
                  AND fecha = :fecha
                  AND hora_inicio < :fin
                  AND hora_fin > :ini
                  AND id_actividad <> :excluir
                  AND estado <> :cancelada
                LIMIT 1';
        $st = $this->pdo->prepare($sql);
        $st->execute([
            ':resp'      => $idResponsable,
            ':fecha'     => $fecha,
            ':fin'       => $hf,
            ':ini'       => $hi,
            ':excluir'   => $excluir,
            ':cancelada' => ESTADO_CANCELADA,
        ]);
        $fila = $st->fetch();
        return $fila === false ? null : $fila;
    }

    /**
     * Listado con filtros (HU-05 / Q-LISTADO). Todos los fragmentos del WHERE
     * son fijos del código; el usuario solo aporta valores parametrizados (RN-28).
     *
     * @param array $f Filtros: q, desde, hasta, id_responsable, estado_temporal
     */
    public function listar(array $f, string $ahora): array
    {
        $where  = ['1 = 1'];
        $params = [':ahora1' => $ahora, ':ahora2' => $ahora];

        if (($f['q'] ?? '') !== '') {
            // LIKE con % y _ tratados como literales (CA-05.3).
            $patron = '%' . addcslashes($f['q'], '%_\\') . '%';
            $where[] = '(a.nombre LIKE :q1 OR a.lugar LIKE :q2)';
            $params[':q1'] = $patron;
            $params[':q2'] = $patron;
        }
        if (($f['desde'] ?? '') !== '') {
            $where[] = 'a.fecha >= :desde';
            $params[':desde'] = $f['desde'];
        }
        if (($f['hasta'] ?? '') !== '') {
            $where[] = 'a.fecha <= :hasta';
            $params[':hasta'] = $f['hasta'];
        }
        if (!empty($f['id_responsable'])) {
            $where[] = 'a.id_personal = :resp';
            $params[':resp'] = $f['id_responsable'];
        }

        // Filtro por estado (lista blanca): temporales + CANCELADA.
        $estado = $f['estado_temporal'] ?? '';
        if ($estado === 'CANCELADA') {
            $where[] = 'a.estado = :cancelada';
            $params[':cancelada'] = ESTADO_CANCELADA;
        } elseif (in_array($estado, [T_PROGRAMADA, T_EN_CURSO, T_FINALIZADA], true)) {
            $where[] = 'a.estado <> :cancelada';
            $params[':cancelada'] = ESTADO_CANCELADA;
            if ($estado === T_PROGRAMADA) {
                $where[] = ':ahoraf < TIMESTAMP(a.fecha, a.hora_inicio)';
            } elseif ($estado === T_EN_CURSO) {
                $where[] = ':ahoraf >= TIMESTAMP(a.fecha, a.hora_inicio) AND :ahorag < TIMESTAMP(a.fecha, a.hora_fin)';
                $params[':ahorag'] = $ahora;
            } else { // FINALIZADA
                $where[] = ':ahoraf >= TIMESTAMP(a.fecha, a.hora_fin)';
            }
            $params[':ahoraf'] = $ahora;
        }

        $sql = 'SELECT a.id_actividad, a.nombre, a.fecha, a.hora_inicio, a.hora_fin,
                       a.lugar, a.cupo, a.estado, a.id_personal,
                       pe.nombres   AS resp_nombres,
                       pe.apellidos AS resp_apellidos,
                       pe.estado    AS estado_responsable,
                       (SELECT COUNT(*) FROM participaciones x WHERE x.id_actividad = a.id_actividad) AS inscritos,
                       (SELECT COUNT(*) FROM participaciones y
                          WHERE y.id_actividad = a.id_actividad AND y.asistencia = :pend) AS pendientes,
                       (SELECT COUNT(*) FROM participaciones z
                          JOIN residentes r ON r.id_residente = z.id_residente
                          WHERE z.id_actividad = a.id_actividad AND r.estado <> :activo) AS inscritos_no_activos,
                       CASE
                         WHEN a.estado = :cancelada2 THEN \'CANCELADA\'
                         WHEN :ahora1 <  TIMESTAMP(a.fecha, a.hora_inicio) THEN \'PROGRAMADA\'
                         WHEN :ahora2 <  TIMESTAMP(a.fecha, a.hora_fin)    THEN \'EN_CURSO\'
                         ELSE \'FINALIZADA\'
                       END AS estado_calculado
                FROM actividades a
                LEFT JOIN personal pe ON pe.id_personal = a.id_personal
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY a.fecha ASC, a.hora_inicio ASC';

        $params[':pend']       = ASIST_PENDIENTE;
        $params[':activo']     = 'ACTIVO';
        $params[':cancelada2'] = ESTADO_CANCELADA;

        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }
}
