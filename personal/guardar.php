<?php

require_once __DIR__ . '/../config/conexion.php';

$codigo = $_POST['codigo_personal'];
$nombres = $_POST['nombres'];
$apellidos = $_POST['apellidos'];
$cargo = $_POST['cargo'];
$especialidad = $_POST['especialidad'];
$telefono = $_POST['telefono'];
$email = $_POST['email'];
$fecha_ingreso = $_POST['fecha_ingreso'];

/* Verificar si el código ya existe */
$sql_verificar = "SELECT id_personal
                  FROM personal
                  WHERE codigo_personal = ?";

$stmt_verificar = $conexion->prepare($sql_verificar);
$stmt_verificar->bind_param("s", $codigo);
$stmt_verificar->execute();

$resultado = $stmt_verificar->get_result();

if ($resultado->num_rows > 0) {
    die("Error: el código de personal ya existe. Regrese y use otro código.");
}

/* Insertar el nuevo personal */
$sql = "INSERT INTO personal
        (codigo_personal, nombres, apellidos, cargo, especialidad, telefono, email, fecha_ingreso)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conexion->prepare($sql);

$stmt->bind_param(
    "ssssssss",
    $codigo,
    $nombres,
    $apellidos,
    $cargo,
    $especialidad,
    $telefono,
    $email,
    $fecha_ingreso
);

if ($stmt->execute()) {
    header("Location: index.php");
    exit;
}

echo "Error al registrar: " . $conexion->error;