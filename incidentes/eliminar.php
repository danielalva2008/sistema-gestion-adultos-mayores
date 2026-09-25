<?php
require_once '../config/conexion.php';
$pdo = getPDO();
$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: index.php?error=' . urlencode('ID inválido.'));
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM incidentes WHERE id_incidente = ?");
    $stmt->execute([$id]);

    if ($stmt->rowCount() === 0) {
        header('Location: index.php?error=' . urlencode('Incidente no encontrado.'));
    } else {
        header('Location: index.php?ok=1');
    }
} catch (PDOException $e) {
    header('Location: index.php?error=' . urlencode('Error al eliminar: ' . $e->getMessage()));
}
exit;
