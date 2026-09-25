<?php
/**
 * Acción POST: anular una inscripción (HU-08).
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../logica/ParticipacionServicio.php';

$base = rtrim(str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME']))), '/');
$urlListado = $base . '/index.php';

$idActividad = id_valido($_POST['id_actividad'] ?? null);
$urlVer = $idActividad !== null ? $base . '/ver.php?id=' . $idActividad : $urlListado;

exigir_post_csrf($urlVer);

$idParticipacion = id_valido($_POST['id_participacion'] ?? null);
if ($idActividad === null || $idParticipacion === null) {
    flash('error', 'Datos de asistencia no válidos. No se guardó ningún cambio.'); // MSG-E16
    redirigir($urlVer);
}

$servicio = new ParticipacionServicio(conexion());
$res = $servicio->anular($idActividad, $idParticipacion);

flash($res['ok'] ? 'exito' : 'error',
    $res['ok'] ? 'Inscripción anulada. Se liberó un cupo.' : $res['error']); // MSG-05
redirigir($urlVer);
