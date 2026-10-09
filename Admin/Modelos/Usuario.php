<?php

require_once __DIR__ . '/../Config/ConexionPDO.php';

/**
 * Modelo compartido del módulo Usuarios.
 *
 * Las operaciones del CRUD se añadirán historia por historia.
 */
class Usuario
{
    private PDO $conexion;

    public function __construct()
    {
        $this->conexion = obtenerConexionPDO();
    }
}