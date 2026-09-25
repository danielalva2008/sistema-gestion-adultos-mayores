<?php

require_once __DIR__ . '/../Config/ConexionPDO.php';

/**
 * Modelo compartido del módulo Usuarios.
 *
 * HU-03: Edición de usuarios.
 */
class Usuario
{
    private PDO $conexion;

    public function __construct()
    {
        $this->conexion = obtenerConexionPDO();
    }

    /**
     * Busca un usuario por su ID.
     * No devuelve password_hash.
     */
    public function buscarPorId(int $idUsuario): ?array
    {
        $sql = "
            SELECT
                id_usuario,
                id_rol,
                username,
                nombres,
                apellidos,
                email,
                estado,
                fecha_creacion
            FROM usuarios
            WHERE id_usuario = :id_usuario
            LIMIT 1
        ";

        $stmt = $this->conexion->prepare($sql);

        $stmt->execute([
            ':id_usuario' => $idUsuario
        ]);

        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        return $usuario ?: null;
    }

    /**
     * Comprueba si el username pertenece a otro usuario.
     */
    public function usernameExiste(
        string $username,
        int $idUsuario
    ): bool {
        $sql = "
            SELECT 1
            FROM usuarios
            WHERE username = :username
              AND id_usuario <> :id_usuario
            LIMIT 1
        ";

        $stmt = $this->conexion->prepare($sql);

        $stmt->execute([
            ':username' => $username,
            ':id_usuario' => $idUsuario
        ]);

        return (bool) $stmt->fetchColumn();
    }

    /**
     * Comprueba si el email pertenece a otro usuario.
     */
    public function emailExiste(
        string $email,
        int $idUsuario
    ): bool {
        $sql = "
            SELECT 1
            FROM usuarios
            WHERE email = :email
              AND id_usuario <> :id_usuario
            LIMIT 1
        ";

        $stmt = $this->conexion->prepare($sql);

        $stmt->execute([
            ':email' => $email,
            ':id_usuario' => $idUsuario
        ]);

        return (bool) $stmt->fetchColumn();
    }

    /**
     * Obtiene los roles activos que pueden asignarse.
     */
    public function listarRolesActivos(): array
    {
        $sql = "
            SELECT
                id_rol,
                nombre
            FROM roles
            WHERE estado = 'ACTIVO'
            ORDER BY nombre ASC
        ";

        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Valida que el rol exista y esté ACTIVO.
     */
    public function rolActivoExiste(int $idRol): bool
    {
        $sql = "
            SELECT 1
            FROM roles
            WHERE id_rol = :id_rol
              AND estado = 'ACTIVO'
            LIMIT 1
        ";

        $stmt = $this->conexion->prepare($sql);

        $stmt->execute([
            ':id_rol' => $idRol
        ]);

        return (bool) $stmt->fetchColumn();
    }

    /**
     * Actualiza los datos del usuario.
     *
     * Si nuevaPassword está vacía:
     * se conserva el password_hash actual.
     *
     * Si tiene contenido:
     * se genera un nuevo hash.
     */
    public function actualizar(
        int $idUsuario,
        int $idRol,
        string $username,
        string $nombres,
        string $apellidos,
        ?string $email,
        string $estado,
        string $nuevaPassword = ''
    ): bool {
        $parametros = [
            ':id_usuario' => $idUsuario,
            ':id_rol' => $idRol,
            ':username' => $username,
            ':nombres' => $nombres,
            ':apellidos' => $apellidos,
            ':email' => $email,
            ':estado' => $estado
        ];

        if ($nuevaPassword !== '') {

            $sql = "
                UPDATE usuarios
                SET
                    id_rol = :id_rol,
                    username = :username,
                    nombres = :nombres,
                    apellidos = :apellidos,
                    email = :email,
                    estado = :estado,
                    password_hash = :password_hash
                WHERE id_usuario = :id_usuario
            ";

            $parametros[':password_hash'] =
                password_hash($nuevaPassword, PASSWORD_DEFAULT);

        } else {

            $sql = "
                UPDATE usuarios
                SET
                    id_rol = :id_rol,
                    username = :username,
                    nombres = :nombres,
                    apellidos = :apellidos,
                    email = :email,
                    estado = :estado
                WHERE id_usuario = :id_usuario
            ";
        }

        $stmt = $this->conexion->prepare($sql);

        return $stmt->execute($parametros);
    }
}