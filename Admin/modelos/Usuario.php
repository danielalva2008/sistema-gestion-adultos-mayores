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

    public function usernameExiste(string $username, ?int $idUsuario = null): bool
    {
        $sql = "SELECT 1 FROM usuarios
                WHERE username = :username";
        $parametros = ['username' => $username];

        if ($idUsuario !== null) {
            $sql .= " AND id_usuario <> :id_usuario";
            $parametros['id_usuario'] = $idUsuario;
        }

        $sql .= " LIMIT 1";

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute($parametros);

        return $consulta->fetchColumn() !== false;
    }

    public function emailExiste(string $email, ?int $idUsuario = null): bool
    {
        $sql = "SELECT 1 FROM usuarios
                WHERE email = :email";
        $parametros = ['email' => $email];

        if ($idUsuario !== null) {
            $sql .= " AND id_usuario <> :id_usuario";
            $parametros['id_usuario'] = $idUsuario;
        }

        $sql .= " LIMIT 1";

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute($parametros);

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

    public function listar(string $texto = '', string $estado = 'TODOS'): array
    {
        $texto = trim($texto);
        $estado = strtoupper(trim($estado));

        if (!in_array($estado, ['TODOS', 'ACTIVO', 'INACTIVO'], true)) {
            throw new InvalidArgumentException('Estado de filtro no válido.');
        }

        $sql = "
            SELECT
                u.id_usuario,
                u.username,
                u.nombres,
                u.apellidos,
                u.email,
                r.nombre AS rol,
                u.estado,
                u.fecha_creacion
            FROM usuarios u
            INNER JOIN roles r ON u.id_rol = r.id_rol
            WHERE 1 = 1
        ";

        $parametros = [];

        if ($texto !== '') {
            $sql .= "
                AND (
                    u.nombres LIKE :texto_nombres
                    OR u.apellidos LIKE :texto_apellidos
                    OR u.username LIKE :texto_username
                )
            ";

            $patron = '%' . $texto . '%';
            $parametros[':texto_nombres'] = $patron;
            $parametros[':texto_apellidos'] = $patron;
            $parametros[':texto_username'] = $patron;
        }

        if ($estado !== 'TODOS') {
            $sql .= " AND u.estado = :estado";
            $parametros[':estado'] = $estado;
        }

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute($parametros);

        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }

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

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute(['id_usuario' => $idUsuario]);

        $usuario = $consulta->fetch(PDO::FETCH_ASSOC);

        return $usuario ?: null;
    }

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
            'id_usuario' => $idUsuario,
            'id_rol' => $idRol,
            'username' => $username,
            'nombres' => $nombres,
            'apellidos' => $apellidos,
            'email' => $email,
            'estado' => $estado
        ];

        if ($nuevaPassword !== '') {
            $sql = "
                UPDATE usuarios
                SET id_rol = :id_rol,
                    username = :username,
                    nombres = :nombres,
                    apellidos = :apellidos,
                    email = :email,
                    estado = :estado,
                    password_hash = :password_hash
                WHERE id_usuario = :id_usuario
            ";

            $parametros['password_hash'] =
                password_hash($nuevaPassword, PASSWORD_DEFAULT);
        } else {
            $sql = "
                UPDATE usuarios
                SET id_rol = :id_rol,
                    username = :username,
                    nombres = :nombres,
                    apellidos = :apellidos,
                    email = :email,
                    estado = :estado
                WHERE id_usuario = :id_usuario
            ";
        }

        $consulta = $this->conexion->prepare($sql);

        return $consulta->execute($parametros);
    }
}
