<?php

require_once __DIR__ . '/../config/conexion.php';

$id = $_GET['id'] ?? 0;

$stmt = $conexion->prepare(
    "UPDATE personal
     SET estado = 'INACTIVO'
     WHERE id_personal = ?"
);

$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    header("Location: index.php");
    exit;
}

echo "Error al inactivar: " . $conexion->error;