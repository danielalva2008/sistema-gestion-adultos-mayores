<?php
// Todos los cambios pasan por las validaciones y el token del controlador.
declare(strict_types=1);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php', true, 303);
    exit;
}
$_POST['accion'] = 'guardar';
if (isset($_POST['id_personal']) && !isset($_POST['id'])) {
    $_POST['id'] = $_POST['id_personal'];
}
require __DIR__ . '/index.php';
