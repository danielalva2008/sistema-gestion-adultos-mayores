<?php
require_once '../config/conexion.php';
$pdo = getPDO();
$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: index.php?error=' . urlencode('ID inválido.'));
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM tipos_incidente WHERE id_tipo_incidente = ?");
    $stmt->execute([$id]);

    if ($stmt->rowCount() === 0) {
        header('Location: index.php?error=' . urlencode('Tipo no encontrado.'));
    } else {
        header('Location: index.php?ok=1');
    }
} catch (PDOException $e) {
    $msg = ((int)$e->getCode() === 23000)
        ? 'No se puede eliminar: existen incidentes asociados a este tipo.'
        : $e->getMessage();
    header('Location: index.php?error=' . urlencode($msg));
}
exit;
