<?php
declare(strict_types=1);
final class Habitacion
{
    public const ESTADOS = ['DISPONIBLE', 'OCUPADA', 'MANTENIMIENTO'];
    public function __construct(private PDO $db) {}

    public function listar(string $numero = '', string $estado = '', string $piso = ''): array
    {
        $sql = "SELECT h.*, (SELECT COUNT(*) FROM residentes r WHERE r.id_habitacion=h.id_habitacion AND r.estado='ACTIVO') AS activos FROM habitaciones h WHERE 1=1";
        $params = [];
        if ($numero !== '') { $sql .= ' AND h.numero LIKE ?'; $params[] = '%'.$numero.'%'; }
        if ($estado !== '') { $sql .= ' AND h.estado=?'; $params[] = $estado; }
        if ($piso !== '') { $sql .= ' AND h.piso=?'; $params[] = $piso; }
        $stmt = $this->db->prepare($sql.' ORDER BY h.piso, h.numero');
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    public function obtener(int $id): array
    {
        $stmt = $this->db->prepare('SELECT * FROM habitaciones WHERE id_habitacion=?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) { throw new DomainException('La habitación solicitada ya no existe.'); }
        $row['activos'] = $this->activos($id);
        return $row;
    }
    private function activos(int $id): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM residentes WHERE id_habitacion=? AND estado='ACTIVO'");
        $stmt->execute([$id]);
        return (int)$stmt->fetchColumn();
    }
    public static function validar(array $data): array
    {
        $errors = [];
        if ($data['numero'] === '' || mb_strlen($data['numero']) > 10) {
            $errors['numero'] = 'Escribe un número o código de habitación de 1 a 10 caracteres.';
        }
        foreach (['piso', 'capacidad'] as $campo) {
            if (filter_var($data[$campo], FILTER_VALIDATE_INT, ['options'=>['min_range'=>1, 'max_range'=>2147483647]]) === false) {
                $errors[$campo] = ucfirst($campo).' debe ser un número entero mayor que 0.';
            }
        }
        if (!in_array($data['estado'], self::ESTADOS, true)) { $errors['estado'] = 'Selecciona un estado válido.'; }
        if (mb_strlen($data['observaciones']) > 255) { $errors['observaciones'] = 'Las observaciones admiten hasta 255 caracteres.'; }
        return $errors;
    }
    // Bloquear padre e hijos mantiene la revisión y la escritura en una misma transacción.
    private function bloquear(int $id): array
    {
        $stmt = $this->db->prepare('SELECT * FROM habitaciones WHERE id_habitacion=? FOR UPDATE');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) { throw new DomainException('La habitación solicitada ya no existe.'); }
        $stmt = $this->db->prepare('SELECT estado FROM residentes WHERE id_habitacion=? FOR UPDATE');
        $stmt->execute([$id]);
        $row['activos'] = count(array_filter($stmt->fetchAll(PDO::FETCH_COLUMN), fn($estado) => $estado === 'ACTIVO'));
        return $row;
    }
    public function guardar(?int $id, array $data): void
    {
        $errors = self::validar($data);
        if ($errors) { throw new DomainException(implode(' ', $errors)); }
        $this->db->beginTransaction();
        try {
            if ($id !== null) {
                $actual = $this->bloquear($id);
                if ((int)$data['capacidad'] < $actual['activos']) {
                    throw new DomainException('La capacidad no puede ser menor que los '.$actual['activos'].' residentes activos asignados.');
                }
            }
            $values = [$data['numero'], (int)$data['piso'], (int)$data['capacidad'], $data['estado'], $data['observaciones'] === '' ? null : $data['observaciones']];
            if ($id === null) {
                $stmt = $this->db->prepare('INSERT INTO habitaciones (numero,piso,capacidad,estado,observaciones) VALUES (?,?,?,?,?)');
            } else {
                $stmt = $this->db->prepare('UPDATE habitaciones SET numero=?,piso=?,capacidad=?,estado=?,observaciones=? WHERE id_habitacion=?');
                $values[] = $id;
            }
            $stmt->execute($values);
            $this->db->commit();
        } catch (Throwable $e) { $this->db->rollBack(); throw $e; }
    }
    public function eliminar(int $id): void
    {
        $this->db->beginTransaction();
        try {
            $actual = $this->bloquear($id);
            if ($actual['estado'] === 'OCUPADA' || $actual['activos'] > 0) {
                throw new DomainException('No se puede eliminar: la habitación está OCUPADA o tiene residentes activos asignados.');
            }
            $stmt = $this->db->prepare('DELETE FROM habitaciones WHERE id_habitacion=?');
            $stmt->execute([$id]);
            $this->db->commit();
        } catch (Throwable $e) { $this->db->rollBack(); throw $e; }
    }
}
