<?php
/**
 * Acción POST: crear o editar una actividad (HU-01 / HU-06).
 * Patrón PRG: siempre termina en redirect.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../logica/Validador.php';
require_once __DIR__ . '/../logica/ActividadServicio.php';

$base = rtrim(str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME']))), '/');
$urlForm    = $base . '/form.php';
$urlListado = $base . '/index.php';

exigir_post_csrf($urlForm);

$id = isset($_POST['id_actividad']) ? id_valido($_POST['id_actividad']) : null;
$esEdicion = $id !== null;

// Validación de formato (RN-01..RN-05). Devuelve TODOS los errores de formato (RN-30).
$val = Validador::actividad($_POST);

if ($val['errores']) {
    // Repoblar el formulario con los datos y errores (CA-01.7).
    $_SESSION['form_errores'] = $val['errores'];
    $_SESSION['form_datos']   = $_POST;
    redirigir($esEdicion ? $urlForm . '?id=' . $id : $urlForm);
}

$servicio = new ActividadServicio(conexion());
$res = $esEdicion
    ? $servicio->editar($id, $val['datos'])
    : $servicio->crear($val['datos']);

if (!$res['ok']) {
    // Error de negocio: se muestra el primero (RN-30) y se conservan los datos.
    $_SESSION['form_datos'] = $_POST;
    flash('error', $res['error']);
    redirigir($esEdicion ? $urlForm . '?id=' . $id : $urlForm);
}

$idFinal = $res['id'];
flash('exito', $esEdicion ? 'Actividad actualizada correctamente.' : 'Actividad programada correctamente.'); // MSG-02 / MSG-01
redirigir($base . '/ver.php?id=' . $idFinal);
