<?php
/**
 * Acción POST: registrar/corregir asistencia por lote (HU-03).
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../logica/ParticipacionServicio.php';

$base = rtrim(str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME']))), '/');
$urlListado = $base . '/index.php';

$idActividad = id_valido($_POST['id_actividad'] ?? null);
$urlAsis = $idActividad !== null ? $base . '/asistencia.php?id=' . $idActividad : $urlListado;

exigir_post_csrf($urlAsis);

if ($idActividad === null) {
    flash('error', 'La actividad no existe o fue eliminada.'); // MSG-E09
    redirigir($urlListado);
}

// asistencia[id_participacion] = 'ASISTIO' | 'NO_ASISTIO' | ''
$entradas = $_POST['asistencia'] ?? [];
if (!is_array($entradas)) { $entradas = []; }

$servicio = new ParticipacionServicio(conexion());
$res = $servicio->registrarAsistencia($idActividad, $entradas);

if ($res['ok']) {
    flash('exito', sprintf('Asistencia guardada: %d registradas, %d pendientes.', $res['marcadas'], $res['pendientes'])); // MSG-06
} else {
    flash('error', $res['error']);
}
redirigir($urlAsis);
