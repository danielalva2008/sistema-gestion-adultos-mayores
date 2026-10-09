<?php
/**
 * Reglas de negocio y transacciones de `participaciones` (Grupo 6).
 * Inscripción (HU-02), anulación (HU-08) y asistencia (HU-03).
 * Operaciones críticas con SELECT ... FOR UPDATE (RN-17).
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/ActividadRepositorio.php';
require_once __DIR__ . '/ParticipacionRepositorio.php';
require_once __DIR__ . '/ConsultaRepositorio.php';

class ParticipacionServicio
{
    private ActividadRepositorio $actRepo;
    private ParticipacionRepositorio $partRepo;
    private ConsultaRepositorio $consRepo;

    public function __construct(private PDO $pdo)
    {
        $this->actRepo  = new ActividadRepositorio($pdo);
        $this->partRepo = new ParticipacionRepositorio($pdo);
        $this->consRepo = new ConsultaRepositorio($pdo);
    }

    private static function ok(array $extra = []): array
    {
        return array_merge(['ok' => true, 'error' => null], $extra);
    }

    private static function error(string $msg): array
    {
        return ['ok' => false, 'error' => $msg];
    }

    /**
     * Inscribir un residente respetando el cupo (HU-02 / §8.4). Operación crítica.
     */
    public function inscribir(int $idActividad, int $idResidente, ?string $observacion = null): array
    {
        try {
            $this->pdo->beginTransaction();

            // Bloquea la fila de la actividad: serializa las inscripciones de ESTA actividad.
            $act = $this->actRepo->porIdBloqueando($idActividad);
            if ($act === null) {
                $this->pdo->rollBack();
                return self::error('La actividad no existe o fue eliminada.'); // MSG-E09
            }

            // RN-14: PROGRAMADA (temporal) y no cancelada.
            if (esta_cancelada($act) ||
                estado_temporal($act['fecha'], $act['hora_inicio'], $act['hora_fin']) !== T_PROGRAMADA) {
                $this->pdo->rollBack();
                return self::error('Las inscripciones solo se permiten antes de que inicie la actividad.'); // MSG-E10
            }

            // RN-15: residente existe y ACTIVO.
            $res = $this->consRepo->residentePorId($idResidente);
            if ($res === null) {
                $this->pdo->rollBack();
                return self::error('El residente no existe.'); // MSG-E22
            }
            if ($res['estado'] !== 'ACTIVO') {
                $this->pdo->rollBack();
                return self::error('Solo se pueden inscribir residentes en estado ACTIVO.'); // MSG-E23
            }

            // RN-19: fecha de la actividad >= fecha_ingreso del residente.
            if ($act['fecha'] < $res['fecha_ingreso']) {
                $this->pdo->rollBack();
                return self::error('La actividad es anterior a la fecha de ingreso del residente.'); // MSG-E14
            }

            // RN-16: no duplicado.
            if ($this->partRepo->existeInscripcion($idActividad, $idResidente)) {
                $this->pdo->rollBack();
                return self::error('El residente ya está inscrito en esta actividad.'); // MSG-E12
            }

            // RN-17: cupo disponible (conteo bajo bloqueo).
            $inscritos = $this->partRepo->contarInscritos($idActividad);
            if ($inscritos >= (int)$act['cupo']) {
                $this->pdo->rollBack();
                return self::error('No hay cupo disponible en esta actividad.'); // MSG-E11
            }

            // RN-18: solapamiento del residente ese día.
            $conf = $this->partRepo->solapamientoResidente(
                $idResidente, $act['fecha'], $act['hora_inicio'], $act['hora_fin'], $idActividad
            );
            if ($conf !== null) {
                $this->pdo->rollBack();
                return self::error(sprintf(
                    'El residente ya está inscrito en "%s" (%s–%s), que se cruza en horario.',
                    $conf['nombre'], substr($conf['hora_inicio'], 0, 5), substr($conf['hora_fin'], 0, 5)
                )); // MSG-E13
            }

            $this->partRepo->inscribir($idActividad, $idResidente, $observacion);
            $disponible = (int)$act['cupo'] - ($inscritos + 1);
            $this->pdo->commit();

            return self::ok([
                'codigo'     => $res['codigo_residente'],
                'disponible' => max(0, $disponible),
            ]);
        } catch (PDOException $e) {
            if ($this->pdo->inTransaction()) { $this->pdo->rollBack(); }
            $codigo = $e->errorInfo[1] ?? 0;
            if ($codigo === 1062) {
                return self::error('El residente ya está inscrito en esta actividad.'); // MSG-E12
            }
            if ($codigo === 1452) {
                return self::error('El residente no existe.'); // MSG-E22
            }
            error_log('[Actividades][inscribir] ' . $e->getMessage());
            return self::error('Ocurrió un error inesperado. Intente nuevamente.'); // MSG-E99
        }
    }

    /**
     * Anular inscripción (HU-08 / §8.5). Solo PROGRAMADA y asistencia pendiente.
     */
    public function anular(int $idActividad, int $idParticipacion): array
    {
        try {
            $this->pdo->beginTransaction();

            $act = $this->actRepo->porIdBloqueando($idActividad);
            if ($act === null) {
                $this->pdo->rollBack();
                return self::error('La actividad no existe o fue eliminada.'); // MSG-E09
            }
            if (esta_cancelada($act) ||
                estado_temporal($act['fecha'], $act['hora_inicio'], $act['hora_fin']) !== T_PROGRAMADA) {
                $this->pdo->rollBack();
                return self::error('Solo se pueden anular inscripciones sin asistencia en actividades que aún no inician.'); // MSG-E21
            }

            $part = $this->partRepo->porIdEnActividad($idParticipacion, $idActividad);
            if ($part === null) {
                $this->pdo->rollBack();
                return self::error('Datos de asistencia no válidos. No se guardó ningún cambio.'); // MSG-E16
            }
            if ($part['asistencia'] !== ASIST_PENDIENTE) {
                $this->pdo->rollBack();
                return self::error('Solo se pueden anular inscripciones sin asistencia en actividades que aún no inician.'); // MSG-E21
            }

            $this->partRepo->anular($idParticipacion, $idActividad);
            $this->pdo->commit();
            return self::ok();
        } catch (PDOException $e) {
            if ($this->pdo->inTransaction()) { $this->pdo->rollBack(); }
            error_log('[Actividades][anular] ' . $e->getMessage());
            return self::error('Ocurrió un error inesperado. Intente nuevamente.'); // MSG-E99
        }
    }

    /**
     * Registrar/corregir asistencia por lote (HU-03 / §8.6).
     * $entradas: [id_participacion => 'ASISTIO'|'NO_ASISTIO'|''].
     * Todo o nada: un error revierte el lote.
     */
    public function registrarAsistencia(int $idActividad, array $entradas): array
    {
        try {
            $this->pdo->beginTransaction();

            $act = $this->actRepo->porIdBloqueando($idActividad);
            if ($act === null) {
                $this->pdo->rollBack();
                return self::error('La actividad no existe o fue eliminada.'); // MSG-E09
            }

            // RN-21: EN_CURSO o FINALIZADA, no cancelada.
            $temporal = estado_temporal($act['fecha'], $act['hora_inicio'], $act['hora_fin']);
            if (esta_cancelada($act) || $temporal === T_PROGRAMADA) {
                $this->pdo->rollBack();
                return self::error('La asistencia solo puede registrarse desde el inicio de la actividad.'); // MSG-E15
            }

            $validos = $this->partRepo->idsValidos($idActividad); // id => asistencia actual
            $permitidos = [ASIST_ASISTIO, ASIST_NO_ASISTIO];
            $registradas = 0;

            foreach ($entradas as $idpRaw => $valor) {
                $idp = id_valido($idpRaw);
                if ($idp === null || !array_key_exists($idp, $validos)) {
                    $this->pdo->rollBack();
                    return self::error('Datos de asistencia no válidos. No se guardó ningún cambio.'); // MSG-E16
                }

                $valor = is_string($valor) ? trim($valor) : '';

                if ($valor === '') {
                    // RN-22: no se vuelve a pendiente una asistencia ya marcada.
                    if ($validos[$idp] !== ASIST_PENDIENTE) {
                        $this->pdo->rollBack();
                        return self::error('Una asistencia ya registrada no puede volver a quedar pendiente.'); // MSG-E24
                    }
                    continue; // sin marcar y ya pendiente: se ignora.
                }

                if (!in_array($valor, $permitidos, true)) {
                    $this->pdo->rollBack();
                    return self::error('Datos de asistencia no válidos. No se guardó ningún cambio.'); // MSG-E16
                }

                // rowCount() puede ser 0 si el valor no cambió: no es error (pertenencia ya validada).
                $this->partRepo->actualizarAsistencia($idp, $idActividad, $valor);
            }

            // Recontar el resultado final para el mensaje.
            $final = $this->partRepo->idsValidos($idActividad);
            $marcadas = 0; $pendientes = 0;
            foreach ($final as $a) {
                if ($a === ASIST_PENDIENTE) { $pendientes++; } else { $marcadas++; }
            }

            $this->pdo->commit();
            return self::ok(['marcadas' => $marcadas, 'pendientes' => $pendientes]);
        } catch (PDOException $e) {
            if ($this->pdo->inTransaction()) { $this->pdo->rollBack(); }
            error_log('[Actividades][asistencia] ' . $e->getMessage());
            return self::error('Ocurrió un error inesperado. Intente nuevamente.'); // MSG-E99
        }
    }
}
