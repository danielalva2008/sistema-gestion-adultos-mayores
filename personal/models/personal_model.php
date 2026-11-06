<?php

declare(strict_types=1);

// Los límites corresponden a las columnas de la tabla personal.
const FIELDS = [
    'codigo_personal' => 20,
    'nombres' => 100,
    'apellidos' => 100,
    'cargo' => 80,
    'especialidad' => 100,
    'telefono' => 20,
    'email' => 120,
    'fecha_ingreso' => 10,
    'estado' => 8,
];

function validate_personal(array $input): array
{
    $data = [];
    $errors = [];

    foreach (FIELDS as $field => $max) {
        $data[$field] = is_string($input[$field] ?? null) ? trim($input[$field]) : '';
        if (str_contains($data[$field], "\0")) {
            $errors[] = "El campo $field contiene caracteres no válidos.";
            $data[$field] = str_replace("\0", '', $data[$field]);
        }
        if (mb_strlen($data[$field]) > $max) {
            $errors[] = "El campo $field admite hasta $max caracteres.";
        }
    }

    foreach (['codigo_personal', 'nombres', 'apellidos', 'cargo', 'fecha_ingreso'] as $field) {
        if ($data[$field] === '') {
            $errors[] = "Completa el campo $field.";
        }
    }

    if ($data['email'] !== '' && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Ingresa un correo válido.';
    }
    if ($data['telefono'] !== '' && (!preg_match('/^\+?[0-9 ()-]{6,20}$/D', $data['telefono']) || preg_match_all('/[0-9]/', $data['telefono']) < 6)) {
        $errors[] = 'Ingresa un teléfono válido de 6 a 20 caracteres.';
    }

    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $data['fecha_ingreso']);
    if (!$date || $date->format('Y-m-d') !== $data['fecha_ingreso'] || $data['fecha_ingreso'] < '1000-01-01') {
        $errors[] = 'La fecha de ingreso no es válida.';
    }
    if (!in_array($data['estado'], ['ACTIVO', 'INACTIVO'], true)) {
        $errors[] = 'El estado no es válido.';
    }

    return [$data, $errors];
}

function personal_find(PDO $db, int $id): ?array
{
    $query = $db->prepare('SELECT * FROM personal WHERE id_personal = ?');
    $query->execute([$id]);
    return $query->fetch() ?: null;
}

function personal_save(PDO $db, array $data, ?int $id): void
{
    // La validación informa al usuario; UNIQUE también protege ante escrituras simultáneas.
    $query = $db->prepare('SELECT id_personal FROM personal WHERE codigo_personal = ? AND id_personal <> ?');
    $query->execute([$data['codigo_personal'], $id ?? 0]);
    if ($query->fetch()) {
        throw new DomainException('El código de personal ya está registrado. Usa otro código.');
    }

    $values = [];
    foreach (FIELDS as $field => $max) {
        $values[] = $data[$field] === '' ? null : $data[$field];
    }

    if ($id !== null) {
        if (!personal_find($db, $id)) {
            throw new DomainException('El trabajador ya no existe.');
        }
        $sql = 'UPDATE personal SET codigo_personal=?, nombres=?, apellidos=?, cargo=?,
                especialidad=?, telefono=?, email=?, fecha_ingreso=?, estado=? WHERE id_personal=?';
        $values[] = $id;
    } else {
        $sql = 'INSERT INTO personal
                (codigo_personal,nombres,apellidos,cargo,especialidad,telefono,email,fecha_ingreso,estado)
                VALUES (?,?,?,?,?,?,?,?,?)';
    }

    $db->prepare($sql)->execute($values);
}

function personal_inactivate(PDO $db, int $id): void
{
    if (!personal_find($db, $id)) {
        throw new DomainException('No se encontró al trabajador.');
    }
    // Baja lógica: nunca se ejecuta DELETE, por lo que las referencias permanecen.
    $db->prepare("UPDATE personal SET estado='INACTIVO' WHERE id_personal=?")->execute([$id]);
}

function personal_impact(PDO $db, int $id): array
{
    $query = $db->prepare(
        'SELECT
            (SELECT COUNT(*) FROM actividades WHERE id_personal_responsable = ?) AS actividades,
            (SELECT COUNT(*) FROM actividades WHERE id_personal_responsable = ?
             AND estado = \'PROGRAMADA\') AS programadas,
            (SELECT COUNT(*) FROM incidentes WHERE id_personal_reporta = ?) AS incidentes'
    );
    $query->execute([$id, $id, $id]);
    return $query->fetch();
}

function personal_list(PDO $db, string $search, string $state): array
{
    $sql = 'SELECT * FROM personal WHERE 1=1';
    $params = [];
    if ($search !== '') {
        // Solo se concatenan fragmentos fijos; los datos se envían como parámetros.
        $sql .= ' AND (codigo_personal LIKE ? OR nombres LIKE ? OR apellidos LIKE ?
                  OR cargo LIKE ? OR especialidad LIKE ?)';
        $params = array_fill(0, 5, '%' . $search . '%');
    }
    if (in_array($state, ['ACTIVO', 'INACTIVO'], true)) {
        $sql .= ' AND estado=?';
        $params[] = $state;
    }
    $query = $db->prepare($sql . ' ORDER BY apellidos,nombres,id_personal');
    $query->execute($params);
    return $query->fetchAll();
}
