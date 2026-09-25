<?php

require_once __DIR__ . '/../config/conexion.php';

$id = $_POST['id_personal'];
$nombres = $_POST['nombres'];
$apellidos = $_POST['apellidos'];
$cargo = $_POST['cargo'];
$especialidad = $_POST['especialidad'];
$telefono = $_POST['telefono'];
$email = $_POST['email'];
$estado = $_POST['estado'];

$sql = "UPDATE personal
        SET nombres = ?,
            apellidos = ?,
            cargo = ?,
            especialidad = ?,
            telefono = ?,
            email = ?,
            estado = ?
        WHERE id_personal = ?";

$stmt = $conexion->prepare($sql);

$stmt->bind_param(
    "sssssssi",
    $nombres,
    $apellidos,
    $cargo,
    $especialidad,
    $telefono,
    $email,
    $estado,
    $id
);

if ($stmt->execute()) {
    header("Location: index.php");
    exit;
}

echo "Error al actualizar: " . $conexion->error;