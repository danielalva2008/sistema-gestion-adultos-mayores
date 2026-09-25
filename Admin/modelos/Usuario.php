<?php

require_once __DIR__ . '/../Config/ConexionPDO.php';

/**
 * Modelo compartido del módulo Usuarios.
 */
class Usuario
{
    private PDO $conexion;

    public function __construct()
    {
        $this->conexion = obtenerConexionPDO();
    }

    /**
     * Lista usuarios y permite aplicar búsqueda por texto
     * y filtro por estado.
     */
    public function listar(string $texto = '', string $estado = 'TODOS'): array
    {
        $texto = trim($texto);
        $estado = strtoupper(trim($estado));

        // Estados permitidos para el filtro.
        $estadosPermitidos = ['TODOS', 'ACTIVO', 'INACTIVO'];

        if (!in_array($estado, $estadosPermitidos, true)) {
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
            INNER JOIN roles r
                ON u.id_rol = r.id_rol
            WHERE 1 = 1
        ";

        $parametros = [];

        // Búsqueda por nombres, apellidos o username.
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

        // Filtro por estado.
        if ($estado !== 'TODOS') {
            $sql .= " AND u.estado = :estado";
            $parametros[':estado'] = $estado;
        }

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute($parametros);

        return $consulta->fetchAll();
    }
}