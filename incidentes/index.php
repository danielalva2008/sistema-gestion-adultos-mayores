<?php
require_once '../config/conexion.php';
$pdo = getPDO();

$filtro_residente = trim($_GET['residente'] ?? '');
$filtro_tipo      = (int)($_GET['tipo'] ?? 0);
$filtro_riesgo    = $_GET['riesgo'] ?? '';
$filtro_estado    = $_GET['estado'] ?? '';

$sql = "SELECT i.*,
               CONCAT(r.nombres, ' ', r.apellidos) AS residente,
               r.codigo_residente,
               ti.nombre AS tipo_nombre,
               ti.nivel_riesgo,
               CONCAT(p.nombres, ' ', p.apellidos) AS personal_reporta
        FROM incidentes i
        INNER JOIN residentes r ON i.id_residente = r.id_residente
        INNER JOIN tipos_incidente ti ON i.id_tipo_incidente = ti.id_tipo_incidente
        LEFT JOIN personal p ON i.id_personal_reporta = p.id_personal
        WHERE 1 = 1";
$params = [];

if ($filtro_residente !== '') {
    $sql .= " AND (r.codigo_residente LIKE ? OR r.nombres LIKE ? OR r.apellidos LIKE ?)";
    $like = '%' . $filtro_residente . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}
if ($filtro_tipo > 0) {
    $sql .= " AND i.id_tipo_incidente = ?";
    $params[] = $filtro_tipo;
}
if (in_array($filtro_riesgo, ['BAJO', 'MEDIO', 'ALTO', 'CRITICO'], true)) {
    $sql .= " AND ti.nivel_riesgo = ?";
    $params[] = $filtro_riesgo;
}
if (in_array($filtro_estado, ['REGISTRADO', 'EN_SEGUIMIENTO', 'CERRADO'], true)) {
    $sql .= " AND i.estado = ?";
    $params[] = $filtro_estado;
}

$sql .= " ORDER BY i.fecha_hora DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$incidentes = $stmt->fetchAll();

$tipos = $pdo->query("SELECT id_tipo_incidente, nombre FROM tipos_incidente ORDER BY nombre")->fetchAll();

$titulo = 'Listado de Incidentes';
require_once '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h2 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Incidentes</h2>
    <a href="crear.php" class="btn btn-success">
        <i class="bi bi-plus-lg"></i> Registrar incidente
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

<form method="get" class="card card-body shadow-sm mb-4">
    <div class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label small mb-1">Residente</label>
            <input type="text" name="residente" class="form-control form-control-sm"
                   placeholder="Código o nombre"
                   value="<?= htmlspecialchars($filtro_residente) ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label small mb-1">Tipo</label>
            <select name="tipo" class="form-select form-select-sm">
                <option value="">Todos</option>
                <?php foreach ($tipos as $t): ?>
                    <option value="<?= (int)$t['id_tipo_incidente'] ?>"
                        <?= $filtro_tipo === (int)$t['id_tipo_incidente'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($t['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small mb-1">Nivel de riesgo</label>
            <select name="riesgo" class="form-select form-select-sm">
                <option value="">Todos</option>
                <?php foreach (['BAJO', 'MEDIO', 'ALTO', 'CRITICO'] as $r): ?>
                    <option value="<?= $r ?>" <?= $filtro_riesgo === $r ? 'selected' : '' ?>><?= $r ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small mb-1">Estado</label>
            <select name="estado" class="form-select form-select-sm">
                <option value="">Todos</option>
                <?php foreach (['REGISTRADO', 'EN_SEGUIMIENTO', 'CERRADO'] as $e): ?>
                    <option value="<?= $e ?>" <?= $filtro_estado === $e ? 'selected' : '' ?>><?= $e ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <button type="submit" class="btn btn-primary btn-sm">
                <i class="bi bi-funnel"></i> Filtrar
            </button>
            <a href="index.php" class="btn btn-outline-secondary btn-sm">Limpiar</a>
        </div>
    </div>
</form>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle mb-0">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Fecha / Hora</th>
                    <th>Residente</th>
                    <th>Tipo</th>
                    <th>Riesgo</th>
                    <th>Lugar</th>
                    <th>Estado</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($incidentes)): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            No se encontraron incidentes con los filtros aplicados.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($incidentes as $i): ?>
                        <?php
                        $badgeEstado = match ($i['estado']) {
                            'CERRADO'        => 'success',
                            'EN_SEGUIMIENTO' => 'warning text-dark',
                            default          => 'secondary'
                        };
                        $badgeRiesgo = match ($i['nivel_riesgo']) {
                            'CRITICO' => 'danger',
                            'ALTO'    => 'warning text-dark',
                            'MEDIO'   => 'info text-dark',
                            default   => 'secondary'
                        };
                        ?>
                        <tr>
                            <td><?= (int)$i['id_incidente'] ?></td>
                            <td class="text-nowrap">
                                <?= date('d/m/Y H:i', strtotime($i['fecha_hora'])) ?>
                            </td>
                            <td>
                                <small class="text-muted d-block"><?= htmlspecialchars($i['codigo_residente']) ?></small>
                                <?= htmlspecialchars($i['residente']) ?>
                            </td>
                            <td><?= htmlspecialchars($i['tipo_nombre']) ?></td>
                            <td>
                                <span class="badge bg-<?= $badgeRiesgo ?>">
                                    <?= htmlspecialchars($i['nivel_riesgo']) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($i['lugar']) ?></td>
                            <td>
                                <span class="badge bg-<?= $badgeEstado ?>">
                                    <?= htmlspecialchars($i['estado']) ?>
                                </span>
                            </td>
                            <td class="text-end text-nowrap">
                                <a href="ver.php?id=<?= (int)$i['id_incidente'] ?>"
                                   class="btn btn-sm btn-outline-info" title="Ver detalle">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="editar.php?id=<?= (int)$i['id_incidente'] ?>"
                                   class="btn btn-sm btn-outline-primary" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="eliminar.php?id=<?= (int)$i['id_incidente'] ?>"
                                   class="btn btn-sm btn-outline-danger" title="Eliminar"
                                   onclick="return confirm('¿Eliminar permanentemente este incidente?');">
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

<p class="text-muted small mt-2">
    Total: <strong><?= count($incidentes) ?></strong> incidente(s)
</p>

<?php require_once '../includes/footer.php'; ?>
