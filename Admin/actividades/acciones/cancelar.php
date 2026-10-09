<?php
/**
 * Acción POST: cancelar una actividad (baja lógica, Variante B / HU-07).
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../logica/ActividadServicio.php';

$base = rtrim(str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME']))), '/');
$urlListado = $base . '/index.php';

exigir_post_csrf($urlListado);

$id = id_valido($_POST['id_actividad'] ?? null);
if ($id === null) {
    flash('error', 'La actividad no existe o fue eliminada.'); // MSG-E09
    redirigir($urlListado);
}

$servicio = new ActividadServicio(conexion());
$res = $servicio->cancelar($id);

if ($res['ok']) {
    flash('exito', 'Actividad cancelada. Sus inscripciones se conservan como historial.'); // MSG-03B
    redirigir($base . '/ver.php?id=' . $id);
}

flash('error', $res['error']);
redirigir($urlListado);
