<?php
/**
 * Acceso a datos de la tabla `participaciones` (Grupo 6).
 * Solo SQL preparado (RN-28). Sin HTML ni reglas de negocio.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../includes/helpers.php';

class ParticipacionRepositorio
{
    public function __construct(private PDO $pdo) {}

    /** Nº de inscritos de una actividad (RN-17). */
    public function contarInscritos(int $idActividad): int
    {
        $st = $this->pdo->prepare('SELECT COUNT(*) FROM participaciones WHERE id_actividad = :a');
        $st->execute([':a' => $idActividad]);
        return (int)$st->fetchColumn();
    }

    /** ¿El residente ya está inscrito en la actividad? (RN-16). */
    public function existeInscripcion(int $idActividad, int $idResidente): bool
    {
        $st = $this->pdo->prepare(
            'SELECT 1 FROM participaciones WHERE id_actividad = :a AND id_residente = :r LIMIT 1'
        );
        $st->execute([':a' => $idActividad, ':r' => $idResidente]);
        return $st->fetchColumn() !== false;
    }

    /** Inserta una inscripción con asistencia pendiente (valor DEFAULT 'INSCRITO', V-02). */
    public function inscribir(int $idActividad, int $idResidente, ?string $observacion = null): int
    {
        $sql = 'INSERT INTO participaciones (id_actividad, id_residente, asistencia, observacion)
                VALUES (:a, :r, :asis, :obs)';
        $st = $this->pdo->prepare($sql);
        $st->execute([
            ':a'    => $idActividad,
            ':r'    => $idResidente,
            ':asis' => ASIST_PENDIENTE,
            ':obs'  => $observacion,
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    /** Lee una participación por id dentro de una actividad (RN-23). */
    public function porIdEnActividad(int $idParticipacion, int $idActividad): ?array
    {
        $st = $this->pdo->prepare(
            'SELECT * FROM participaciones WHERE id_participacion = :p AND id_actividad = :a'
        );
        $st->execute([':p' => $idParticipacion, ':a' => $idActividad]);
        $fila = $st->fetch();
        return $fila === false ? null : $fila;
    }

    /** Borra físicamente una inscripción (HU-08 / RN-20). */
    public function anular(int $idParticipacion, int $idActividad): void
    {
        $st = $this->pdo->prepare(
            'DELETE FROM participaciones WHERE id_participacion = :p AND id_actividad = :a'
        );
        $st->execute([':p' => $idParticipacion, ':a' => $idActividad]);
    }

    /** Mapa id_participacion => asistencia de una actividad (validación de lote, RN-23). */
    public function idsValidos(int $idActividad): array
    {
        $st = $this->pdo->prepare(
            'SELECT id_participacion, asistencia FROM participaciones WHERE id_actividad = :a'
        );
        $st->execute([':a' => $idActividad]);
        $mapa = [];
        foreach ($st->fetchAll() as $f) {
            $mapa[(int)$f['id_participacion']] = $f['asistencia'];
        }
        return $mapa;
    }

    /** Actualiza la asistencia de una participación (RN-22). */
    public function actualizarAsistencia(int $idParticipacion, int $idActividad, string $valor): void
    {
        $st = $this->pdo->prepare(
            'UPDATE participaciones SET asistencia = :v
             WHERE id_participacion = :p AND id_actividad = :a'
        );
        $st->execute([':v' => $valor, ':p' => $idParticipacion, ':a' => $idActividad]);
    }

    /**
     * Lista los inscritos de una actividad con datos del residente.
     * Para pantallas de detalle (P3) y asistencia (P4).
     */
    public function inscritosDeActividad(int $idActividad): array
    {
        $sql = 'SELECT p.id_participacion, p.id_residente, p.asistencia, p.observacion,
                       r.codigo_residente, r.nombres, r.apellidos, r.estado AS estado_residente
                FROM participaciones p
                JOIN residentes r ON r.id_residente = p.id_residente
                WHERE p.id_actividad = :a
                ORDER BY r.apellidos, r.nombres';
        $st = $this->pdo->prepare($sql);
        $st->execute([':a' => $idActividad]);
        return $st->fetchAll();
    }

    /**
     * Solapamiento de un residente el mismo día (RN-18 / Q-SOL-RESIDENTE).
     * Excluye la actividad indicada y las canceladas.
     */
    public function solapamientoResidente(
        int $idResidente, string $fecha, string $hi, string $hf, int $idActividadExcluir
    ): ?array {
        $sql = 'SELECT a.id_actividad, a.nombre, a.hora_inicio, a.hora_fin
                FROM participaciones p
                JOIN actividades a ON a.id_actividad = p.id_actividad
                WHERE p.id_residente = :res
                  AND a.fecha = :fecha
                  AND a.hora_inicio < :fin
                  AND a.hora_fin > :ini
                  AND a.id_actividad <> :act
                  AND a.estado <> :cancelada
                LIMIT 1';
        $st = $this->pdo->prepare($sql);
        $st->execute([
            ':res'       => $idResidente,
            ':fecha'     => $fecha,
            ':fin'       => $hf,
            ':ini'       => $hi,
            ':act'       => $idActividadExcluir,
            ':cancelada' => ESTADO_CANCELADA,
        ]);
        $fila = $st->fetch();
        return $fila === false ? null : $fila;
    }

    /**
     * Inscritos que quedarían solapados si la actividad cambia a este horario (RN-12 / Q-SOL-INSCRITOS).
     * @return array filas con residente y actividad_conflicto
     */
    public function inscritosSolapadosTrasCambio(
        int $idActividad, string $fecha, string $hi, string $hf
    ): array {
        $sql = 'SELECT DISTINCT r.id_residente, r.codigo_residente,
                       CONCAT(r.nombres, " ", r.apellidos) AS residente,
                       a2.nombre AS actividad_conflicto
                FROM participaciones p1
                JOIN participaciones p2 ON p2.id_residente = p1.id_residente
                                       AND p2.id_actividad <> p1.id_actividad
                JOIN actividades a2 ON a2.id_actividad = p2.id_actividad
                JOIN residentes r  ON r.id_residente = p1.id_residente
                WHERE p1.id_actividad = :act
                  AND a2.fecha = :fecha
                  AND a2.hora_inicio < :fin
                  AND a2.hora_fin > :ini
                  AND a2.estado <> :cancelada';
        $st = $this->pdo->prepare($sql);
        $st->execute([
            ':act'       => $idActividad,
            ':fecha'     => $fecha,
            ':fin'       => $hf,
            ':ini'       => $hi,
            ':cancelada' => ESTADO_CANCELADA,
        ]);
        return $st->fetchAll();
    }

    /**
     * Inscritos cuya fecha_ingreso es posterior a la nueva fecha (RN-12/RN-19 / Q-INGRESO-INSCRITOS).
     */
    public function inscritosConIngresoPosterior(int $idActividad, string $fecha): array
    {
        $sql = 'SELECT r.id_residente, r.codigo_residente,
                       CONCAT(r.nombres, " ", r.apellidos) AS residente
                FROM participaciones p
                JOIN residentes r ON r.id_residente = p.id_residente
                WHERE p.id_actividad = :act
                  AND r.fecha_ingreso > :fecha';
        $st = $this->pdo->prepare($sql);
        $st->execute([':act' => $idActividad, ':fecha' => $fecha]);
        return $st->fetchAll();
    }
}
