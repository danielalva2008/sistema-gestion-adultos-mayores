<?php
/**
 * Acción POST: reactivar una actividad cancelada (RN-13B).
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
$res = $servicio->reactivar($id);

flash($res['ok'] ? 'exito' : 'error',
    $res['ok'] ? 'Actividad reactivada. Vuelve a estar PROGRAMADA.' : $res['error']);
redirigir($base . '/ver.php?id=' . $id);
