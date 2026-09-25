<?php
require_once '../config/conexion.php';
$pdo = getPDO();

$busqueda = trim($_GET['q'] ?? '');
$sql = "SELECT * FROM tipos_incidente";
$params = [];

if ($busqueda !== '') {
    $sql .= " WHERE nombre LIKE ? OR nivel_riesgo LIKE ? OR descripcion LIKE ?";
    $params = ["%$busqueda%", "%$busqueda%", "%$busqueda%"];
}
$sql .= " ORDER BY FIELD(nivel_riesgo, 'CRITICO','ALTO','MEDIO','BAJO'), nombre";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$tipos = $stmt->fetchAll();

$titulo = 'Tipos de Incidente';
require_once '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h2 class="mb-0"><i class="bi bi-tags"></i> Tipos de Incidente</h2>
    <a href="crear.php" class="btn btn-success">
        <i class="bi bi-plus-lg"></i> Nuevo tipo
    </a>
</div>

<?php if (isset($_GET['ok'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        Operación realizada correctamente.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if (isset($_GET['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <?= htmlspecialchars($_GET['error']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<form method="get" class="mb-3">
    <div class="input-group">
        <input type="text" name="q" class="form-control"
               placeholder="Buscar por nombre, nivel o descripción..."
               value="<?= htmlspecialchars($busqueda) ?>">
        <button class="btn btn-outline-primary" type="submit">
            <i class="bi bi-search"></i> Buscar
        </button>
        <?php if ($busqueda): ?>
            <a href="index.php" class="btn btn-outline-secondary">Limpiar</a>
        <?php endif; ?>
    </div>
</form>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle mb-0">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Nivel de riesgo</th>
                    <th>Descripción</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($tipos)): ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">
                            No se encontraron registros.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($tipos as $t): ?>
                        <?php
                        $badge = match ($t['nivel_riesgo']) {
                            'CRITICO' => 'danger',
                            'ALTO'    => 'warning text-dark',
                            'MEDIO'   => 'info text-dark',
                            default   => 'secondary'
                        };
                        ?>
                        <tr>
                            <td><?= (int)$t['id_tipo_incidente'] ?></td>
                            <td class="fw-medium"><?= htmlspecialchars($t['nombre']) ?></td>
                            <td>
                                <span class="badge bg-<?= $badge ?>">
                                    <?= htmlspecialchars($t['nivel_riesgo']) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($t['descripcion'] ?? '—') ?></td>
                            <td class="text-end text-nowrap">
                                <a href="editar.php?id=<?= (int)$t['id_tipo_incidente'] ?>"
                                   class="btn btn-sm btn-outline-primary" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="eliminar.php?id=<?= (int)$t['id_tipo_incidente'] ?>"
                                   class="btn btn-sm btn-outline-danger" title="Eliminar"
                                   onclick="return confirm('¿Eliminar este tipo de incidente?\nSolo se podrá si no tiene incidentes asociados.');">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
