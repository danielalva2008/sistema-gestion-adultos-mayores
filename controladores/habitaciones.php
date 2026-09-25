<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/bootstrap.php';
require_once __DIR__.'/../config/Conexion.php';
require_once __DIR__.'/../modelos/Habitacion.php';
$errores = []; $error = ''; $fatal = false; $filas = []; $todas = [];
$accion = entrada($_GET, 'accion', 'listar');
if (!in_array($accion, ['listar','crear','editar','eliminar'], true)) { $accion = 'listar'; }
$id = filter_var(entrada($_GET, 'id'), FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]) ?: null;
$datos = ['numero'=>'', 'piso'=>'1', 'capacidad'=>'1', 'estado'=>'DISPONIBLE', 'observaciones'=>''];
$q = entrada($_GET, 'q'); $estado = entrada($_GET, 'estado'); $piso = entrada($_GET, 'piso');
$exito = $_SESSION['exito'] ?? ''; unset($_SESSION['exito']);
try {
    $modelo = new Habitacion(conexion());
    if (in_array($accion, ['editar','eliminar'], true)) {
        if (!$id) { throw new DomainException('Identificador de habitación inválido.'); }
        $datos = $modelo->obtener($id);
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!hash_equals($_SESSION['csrf'], entrada($_POST, 'csrf'))) {
            http_response_code(403); throw new DomainException('La sesión del formulario venció. Recarga la página e inténtalo de nuevo.');
        }
        if ($accion === 'crear' || $accion === 'editar') {
            foreach (['numero','piso','capacidad','estado','observaciones'] as $campo) { $datos[$campo] = entrada($_POST, $campo); }
            $errores = Habitacion::validar($datos);
            if (!$errores) {
                $modelo->guardar($accion === 'editar' ? $id : null, $datos);
                volver($accion === 'crear' ? 'Habitación registrada correctamente.' : 'Habitación actualizada correctamente.');
            }
            http_response_code(422);
        } elseif ($accion === 'eliminar') {
            $modelo->eliminar($id);
            volver('Habitación eliminada correctamente.');
        } else { http_response_code(405); throw new DomainException('Operación no permitida.'); }
    }
    if ($accion === 'listar') {
        $filas = $modelo->listar($q, $estado, $piso);
        $todas = $modelo->listar();
    }
} catch (DomainException $e) {
    $error = $e->getMessage();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' && in_array($accion, ['editar', 'eliminar'], true)) { $fatal = true; }
    if (http_response_code() < 400) { http_response_code(422); }
} catch (PDOException $e) {
    if (($e->errorInfo[1] ?? 0) === 1062) { $errores['numero'] = 'Ya existe una habitación con ese número.'; http_response_code(422); }
    else { error_log((string)$e); $fatal=true; $error='No se pudo completar la operación. Revisa la conexión y las tablas de sistema_residencia; vuelve a intentarlo.'; http_response_code(503); }
} catch (RuntimeException $e) {
    $fatal=true; $error=$e->getMessage(); http_response_code(503);
}
$titulos = ['listar'=>'Habitaciones', 'crear'=>'Nueva habitación', 'editar'=>'Editar habitación', 'eliminar'=>'Eliminar habitación'];
$titulo = $titulos[$accion];
