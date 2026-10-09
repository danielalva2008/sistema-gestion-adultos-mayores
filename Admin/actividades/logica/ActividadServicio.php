<?php
/**
 * Reglas de negocio y transacciones de `actividades` (Grupo 6).
 * Aplica RN-06..RN-13 y el orden de validación de RN-30.
 * Devuelve resultados estructurados: ['ok'=>bool, 'id'=>?int, 'error'=>?string].
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/ActividadRepositorio.php';
require_once __DIR__ . '/ParticipacionRepositorio.php';
require_once __DIR__ . '/ConsultaRepositorio.php';

class ActividadServicio
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

    private static function ok(?int $id = null): array
    {
        return ['ok' => true, 'id' => $id, 'error' => null];
    }

    private static function error(string $msg): array
    {
        return ['ok' => false, 'id' => null, 'error' => $msg];
    }

    private static function inicio(array $d): string
    {
        return $d['fecha'] . ' ' . $d['hora_inicio']; // hora ya es H:i:00
    }

    /**
     * Crear actividad (HU-01 / §8.1). $d ya viene normalizado por Validador.
     */
    public function crear(array $d): array
    {
        // RN-06: inicio > ahora.
        if (self::inicio($d) <= ahora()) {
            return self::error('La actividad debe programarse para una fecha y hora futuras.'); // MSG-E06
        }
        // RN-07: responsable existe y ACTIVO.
        $resp = $this->consRepo->personalPorId((int)$d['id_personal']);
        if ($resp === null || $resp['estado'] !== 'ACTIVO') {
            return self::error('El responsable seleccionado no existe o no está activo.'); // MSG-E07
        }

        try {
            $this->pdo->beginTransaction();

            // RN-08: solapamiento del responsable (id_excluir = 0 al crear).
            $conf = $this->actRepo->solapamientoResponsable(
                (int)$d['id_personal'], $d['fecha'], $d['hora_inicio'], $d['hora_fin'], 0
            );
            if ($conf !== null) {
                $this->pdo->rollBack();
                return self::error(sprintf(
                    'El responsable ya tiene asignada "%s" de %s a %s ese día.',
                    $conf['nombre'], substr($conf['hora_inicio'], 0, 5), substr($conf['hora_fin'], 0, 5)
                )); // MSG-E08
            }

            $id = $this->actRepo->crear($d);
            $this->pdo->commit();
            return self::ok($id);
        } catch (PDOException $e) {
            if ($this->pdo->inTransaction()) { $this->pdo->rollBack(); }
            return self::error($this->traducir($e));
        }
    }

    /**
     * Editar actividad (HU-06 / §8.2).
     */
    public function editar(int $id, array $d): array
    {
        // RN-07 fuera de la transacción (solo lectura de estado del responsable).
        $resp = $this->consRepo->personalPorId((int)$d['id_personal']);
        if ($resp === null || $resp['estado'] !== 'ACTIVO') {
            return self::error('El responsable seleccionado no existe o no está activo.'); // MSG-E07
        }

        try {
            $this->pdo->beginTransaction();

            $act = $this->actRepo->porIdBloqueando($id);
            if ($act === null) {
                $this->pdo->rollBack();
                return self::error('La actividad no existe o fue eliminada.'); // MSG-E09
            }

            // RN-09: solo PROGRAMADA (temporal) y no cancelada. Estado leído de la BD.
            if (esta_cancelada($act) ||
                estado_temporal($act['fecha'], $act['hora_inicio'], $act['hora_fin']) !== T_PROGRAMADA) {
                $this->pdo->rollBack();
                return self::error('Solo pueden modificarse actividades programadas (no iniciadas ni canceladas).'); // MSG-E17
            }

            // RN-10: nuevo inicio > ahora.
            if (self::inicio($d) <= ahora()) {
                $this->pdo->rollBack();
                return self::error('La actividad debe programarse para una fecha y hora futuras.'); // MSG-E06
            }

            // RN-08: solapamiento del responsable excluyendo esta actividad.
            $conf = $this->actRepo->solapamientoResponsable(
                (int)$d['id_personal'], $d['fecha'], $d['hora_inicio'], $d['hora_fin'], $id
            );
            if ($conf !== null) {
                $this->pdo->rollBack();
                return self::error(sprintf(
                    'El responsable ya tiene asignada "%s" de %s a %s ese día.',
                    $conf['nombre'], substr($conf['hora_inicio'], 0, 5), substr($conf['hora_fin'], 0, 5)
                )); // MSG-E08
            }

            // RN-11: si el cupo cambió, no puede ser menor que los inscritos (valor de BD).
            $inscritos = $this->partRepo->contarInscritos($id);
            if ((int)$d['cupo'] !== (int)$act['cupo'] && (int)$d['cupo'] < $inscritos) {
                $this->pdo->rollBack();
                return self::error("El cupo no puede ser menor que los {$inscritos} residentes ya inscritos."); // MSG-E18
            }

            // RN-12: si fecha/horas cambiaron, revalidar solapamientos e ingresos de inscritos.
            $cambioHorario = $d['fecha'] !== $act['fecha']
                || $d['hora_inicio'] !== $act['hora_inicio']
                || $d['hora_fin'] !== $act['hora_fin'];
            if ($cambioHorario) {
                $sol = $this->partRepo->inscritosSolapadosTrasCambio($id, $d['fecha'], $d['hora_inicio'], $d['hora_fin']);
                $ing = $this->partRepo->inscritosConIngresoPosterior($id, $d['fecha']);
                if ($sol || $ing) {
                    $this->pdo->rollBack();
                    $nombres = [];
                    foreach ($sol as $r) { $nombres[] = $r['codigo_residente']; }
                    foreach ($ing as $r) { $nombres[] = $r['codigo_residente']; }
                    $nombres = array_values(array_unique($nombres));
                    return self::error('El nuevo horario genera conflictos para: ' . implode(', ', $nombres) . '.'); // MSG-E19
                }
            }

            $this->actRepo->actualizar($id, $d);
            $this->pdo->commit();
            return self::ok($id);
        } catch (PDOException $e) {
            if ($this->pdo->inTransaction()) { $this->pdo->rollBack(); }
            return self::error($this->traducir($e));
        }
    }

    /**
     * Cancelar (baja lógica, Variante B / HU-07 / §8.3).
     */
    public function cancelar(int $id): array
    {
        try {
            $this->pdo->beginTransaction();
            $act = $this->actRepo->porIdBloqueando($id);
            if ($act === null) {
                $this->pdo->rollBack();
                return self::error('La actividad no existe o fue eliminada.'); // MSG-E09
            }
            // Solo se cancela una PROGRAMADA no cancelada.
            if (esta_cancelada($act) ||
                estado_temporal($act['fecha'], $act['hora_inicio'], $act['hora_fin']) !== T_PROGRAMADA) {
                $this->pdo->rollBack();
                return self::error('Solo pueden modificarse actividades programadas (no iniciadas ni canceladas).'); // MSG-E17
            }
            $this->actRepo->cancelar($id);
            $this->pdo->commit();
            return self::ok($id);
        } catch (PDOException $e) {
            if ($this->pdo->inTransaction()) { $this->pdo->rollBack(); }
            return self::error($this->traducir($e));
        }
    }

    /**
     * Reactivar una actividad cancelada (RN-13B).
     * Solo si su estado temporal sigue siendo PROGRAMADA y revalida RN-08/RN-12.
     */
    public function reactivar(int $id): array
    {
        try {
            $this->pdo->beginTransaction();
            $act = $this->actRepo->porIdBloqueando($id);
            if ($act === null) {
                $this->pdo->rollBack();
                return self::error('La actividad no existe o fue eliminada.'); // MSG-E09
            }
            if (!esta_cancelada($act)) {
                $this->pdo->rollBack();
                return self::error('Solo se pueden reactivar actividades canceladas.');
            }
            if (estado_temporal($act['fecha'], $act['hora_inicio'], $act['hora_fin']) !== T_PROGRAMADA) {
                $this->pdo->rollBack();
                return self::error('No se puede reactivar: la actividad ya no está en el futuro.'); // MSG-E17 (variante)
            }
            // RN-07 responsable (si lo tiene).
            if ($act['id_personal'] !== null) {
                $resp = $this->consRepo->personalPorId((int)$act['id_personal']);
                if ($resp === null || $resp['estado'] !== 'ACTIVO') {
                    $this->pdo->rollBack();
                    return self::error('No se puede reactivar: el responsable no está activo.'); // MSG-E07
                }
                // RN-08 solapamiento del responsable (excluyéndose a sí misma).
                $conf = $this->actRepo->solapamientoResponsable(
                    (int)$act['id_personal'], $act['fecha'], $act['hora_inicio'], $act['hora_fin'], $id
                );
                if ($conf !== null) {
                    $this->pdo->rollBack();
                    return self::error('No se puede reactivar: el responsable tiene un horario en conflicto.'); // MSG-E08 (variante)
                }
            }
            $this->actRepo->reactivar($id);
            $this->pdo->commit();
            return self::ok($id);
        } catch (PDOException $e) {
            if ($this->pdo->inTransaction()) { $this->pdo->rollBack(); }
            return self::error($this->traducir($e));
        }
    }

    /** Traducción de errores de BD (§12.3). No expone detalles al usuario. */
    private function traducir(PDOException $e): string
    {
        $codigo = $e->errorInfo[1] ?? 0;
        switch ($codigo) {
            case 1062: return 'El residente ya está inscrito en esta actividad.'; // MSG-E12
            case 1451: return 'No se puede eliminar: la actividad tiene participaciones registradas.'; // MSG-E20
            case 1452: return 'La actividad no existe o fue eliminada.'; // MSG-E09/E07/E22
            case 1406: return 'Uno de los campos supera la longitud permitida.'; // MSG-E02
            case 3819:
            case 4025: return 'La fecha, hora o cupo ingresado no cumple una restricción.'; // MSG-E04/E05
            default:
                error_log('[Actividades][BD] ' . $e->getMessage());
                return 'Ocurrió un error inesperado. Intente nuevamente.'; // MSG-E99
        }
    }
}
