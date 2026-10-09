<?php

function obtenerConexionPDO(): PDO
{
    static $conexion = null;

    if ($conexion instanceof PDO) {
        return $conexion;
    }

    $host = 'localhost';
    $baseDatos = 'sistema_residencia';
    $usuario = 'residencia_app';
    $contrasena = 'Residencia2026*';
    $charset = 'utf8mb4';

    $dsn = "mysql:host={$host};dbname={$baseDatos};charset={$charset}";

    $opciones = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ];

    $conexion = new PDO(
        $dsn,
        $usuario,
        $contrasena,
        $opciones
    );

    return $conexion;
}