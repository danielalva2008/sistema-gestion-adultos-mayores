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

    /**
     * Comprueba si un usuario existe.
     */
    public function existeUsuario(int $idUsuario): bool
    {
        $sql = "SELECT 1
                FROM usuarios
                WHERE id_usuario = :id_usuario
                LIMIT 1";

        $stmt = $this->conexion->prepare($sql);

        $stmt->execute([
            ':id_usuario' => $idUsuario
        ]);

        return (bool) $stmt->fetchColumn();
    }

    /**
     * Cambia el estado de un usuario.
     *
     * Estados permitidos por el proyecto:
     * ACTIVO / INACTIVO
     */
    public function cambiarEstado(int $idUsuario, string $estado): bool
    {
        $sql = "UPDATE usuarios
                SET estado = :estado
                WHERE id_usuario = :id_usuario";

        $stmt = $this->conexion->prepare($sql);

        return $stmt->execute([
            ':estado' => $estado,
            ':id_usuario' => $idUsuario
        ]);
    }
}