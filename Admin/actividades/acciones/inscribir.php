<?php
/**
 * Acción POST: inscribir un residente en una actividad (HU-02).
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../logica/ParticipacionServicio.php';

$base = rtrim(str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME']))), '/');
$urlListado = $base . '/index.php';

$idActividad = id_valido($_POST['id_actividad'] ?? null);
$urlVer = $idActividad !== null ? $base . '/ver.php?id=' . $idActividad : $urlListado;

exigir_post_csrf($urlVer);

if ($idActividad === null) {
    flash('error', 'La actividad no existe o fue eliminada.'); // MSG-E09
    redirigir($urlListado);
}

$idResidente = id_valido($_POST['id_residente'] ?? null);
if ($idResidente === null) {
    flash('error', 'Debe seleccionar un residente válido.');
    redirigir($urlVer);
}

$obs = trim((string)($_POST['observacion'] ?? ''));
if (mb_strlen($obs) > 150) { $obs = mb_substr($obs, 0, 150); }

$servicio = new ParticipacionServicio(conexion());
$res = $servicio->inscribir($idActividad, $idResidente, $obs === '' ? null : $obs);

if ($res['ok']) {
    flash('exito', sprintf('Residente %s inscrito. Cupo disponible: %d.', $res['codigo'], $res['disponible'])); // MSG-04
} else {
    flash('error', $res['error']);
}
redirigir($urlVer);
