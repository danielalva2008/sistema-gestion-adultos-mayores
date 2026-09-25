<?php

$servidor = "localhost";
$usuario = "residencia_app";
$password = "residencia123";
$base_datos = "sistema_residencia";
echo "Longitud de contraseña: " . strlen($password);
exit;
$conexion = new mysqli($servidor, $usuario, $password, $base_datos);

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

$conexion->set_charset("utf8mb4");
?>