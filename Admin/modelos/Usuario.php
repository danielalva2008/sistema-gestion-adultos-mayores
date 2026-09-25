<?php

require_once __DIR__ . '/../Config/ConexionPDO.php';

class Usuario
{
    private PDO $conexion;

    public function __construct()
    {
        $this->conexion = obtenerConexionPDO();
    }

    public function obtenerRolesActivos(): array
    {
        $sql = "SELECT id_rol, nombre
                FROM roles
                WHERE estado = 'ACTIVO'
                ORDER BY nombre";

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute();

        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }

    public function usernameExiste(string $username): bool
    {
        $sql = "SELECT 1 FROM usuarios
                WHERE username = :username
                LIMIT 1";

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute(['username' => $username]);

        return $consulta->fetchColumn() !== false;
    }

    public function emailExiste(string $email): bool
    {
        $sql = "SELECT 1 FROM usuarios
                WHERE email = :email
                LIMIT 1";

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute(['email' => $email]);

        return $consulta->fetchColumn() !== false;
    }

    public function rolActivo(int $idRol): bool
    {
        $sql = "SELECT 1 FROM roles
                WHERE id_rol = :id_rol
                  AND estado = 'ACTIVO'
                LIMIT 1";

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute(['id_rol' => $idRol]);

        return $consulta->fetchColumn() !== false;
    }

    public function registrar(array $datos): bool
    {
        $sql = "INSERT INTO usuarios
                    (id_rol, username, password_hash, nombres, apellidos, email)
                VALUES
                    (:id_rol, :username, :password_hash, :nombres, :apellidos, :email)";

        $consulta = $this->conexion->prepare($sql);
        $email = trim((string) ($datos['email'] ?? ''));

        return $consulta->execute([
            'id_rol' => $datos['id_rol'],
            'username' => $datos['username'],
            'password_hash' => password_hash($datos['password'], PASSWORD_DEFAULT),
            'nombres' => $datos['nombres'],
            'apellidos' => $datos['apellidos'],
            'email' => $email === '' ? null : $email
        ]);
    }
}