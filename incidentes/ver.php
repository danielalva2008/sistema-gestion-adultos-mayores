<?php
require_once '../config/conexion.php';
$pdo = getPDO();
$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare(
    "SELECT i.*,
            CONCAT(r.nombres, ' ', r.apellidos) AS residente,
            r.codigo_residente,
            r.telefono AS telefono_residente,
            ti.nombre AS tipo_nombre,
            ti.nivel_riesgo,
            ti.descripcion AS tipo_descripcion,
            CONCAT(p.nombres, ' ', p.apellidos) AS personal_reporta,
            p.cargo AS personal_cargo
     FROM incidentes i
     INNER JOIN residentes r ON i.id_residente = r.id_residente
     INNER JOIN tipos_incidente ti ON i.id_tipo_incidente = ti.id_tipo_incidente
     LEFT JOIN personal p ON i.id_personal_reporta = p.id_personal
     WHERE i.id_incidente = ?"
);
$stmt->execute([$id]);
$i = $stmt->fetch();

if (!$i) {
    header('Location: index.php?error=' . urlencode('Incidente no encontrado.'));
    exit;
}

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

$titulo = 'Detalle del Incidente';
require_once '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h2 class="mb-0">
        <i class="bi bi-eye"></i> Incidente #<?= (int)$i['id_incidente'] ?>
    </h2>
    <div class="d-flex gap-2">
        <a href="editar.php?id=<?= (int)$i['id_incidente'] ?>" class="btn btn-primary">
            <i class="bi bi-pencil"></i> Editar
        </a>
        <a href="index.php" class="btn btn-outline-secondary">Volver al listado</a>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header bg-white fw-semibold">
                Información del incidente
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4 text-muted">Fecha y hora</dt>
                    <dd class="col-sm-8"><?= date('d/m/Y H:i', strtotime($i['fecha_hora'])) ?></dd>

                    <dt class="col-sm-4 text-muted">Lugar</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars($i['lugar']) ?></dd>

                    <dt class="col-sm-4 text-muted">Estado</dt>
                    <dd class="col-sm-8">
                        <span class="badge bg-<?= $badgeEstado ?>"><?= htmlspecialchars($i['estado']) ?></span>
                    </dd>

                    <dt class="col-sm-4 text-muted">Requiere seguimiento</dt>
                    <dd class="col-sm-8">
                        <?= $i['requiere_seguimiento'] ? '<span class="badge bg-warning text-dark">Sí</span>' : 'No' ?>
                    </dd>

                    <?php if ($i['fecha_cierre']): ?>
                        <dt class="col-sm-4 text-muted">Fecha de cierre</dt>
                        <dd class="col-sm-8"><?= date('d/m/Y H:i', strtotime($i['fecha_cierre'])) ?></dd>
                    <?php endif; ?>

                    <dt class="col-sm-4 text-muted">Descripción</dt>
                    <dd class="col-sm-8"><?= nl2br(htmlspecialchars($i['descripcion'])) ?></dd>

                    <dt class="col-sm-4 text-muted">Acción realizada</dt>
                    <dd class="col-sm-8">
                        <?= $i['accion_realizada']
                            ? nl2br(htmlspecialchars($i['accion_realizada']))
                            : '<span class="text-muted">—</span>' ?>
                    </dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold">Residente</div>
            <div class="card-body">
                <p class="mb-1">
                    <strong><?= htmlspecialchars($i['residente']) ?></strong>
                </p>
                <p class="mb-1 text-muted small">
                    Código: <?= htmlspecialchars($i['codigo_residente']) ?>
                </p>
                <?php if ($i['telefono_residente']): ?>
                    <p class="mb-0 small">
                        <i class="bi bi-telephone"></i>
                        <?= htmlspecialchars($i['telefono_residente']) ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>

        <div class="card shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold">Tipo de incidente</div>
            <div class="card-body">
                <p class="mb-1">
                    <strong><?= htmlspecialchars($i['tipo_nombre']) ?></strong>
                </p>
                <p class="mb-1">
                    <span class="badge bg-<?= $badgeRiesgo ?>">
                        <?= htmlspecialchars($i['nivel_riesgo']) ?>
                    </span>
                </p>
                <?php if ($i['tipo_descripcion']): ?>
                    <p class="mb-0 small text-muted">
                        <?= htmlspecialchars($i['tipo_descripcion']) ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-white fw-semibold">Reportado por</div>
            <div class="card-body">
                <?php if ($i['personal_reporta']): ?>
                    <p class="mb-1"><strong><?= htmlspecialchars($i['personal_reporta']) ?></strong></p>
                    <p class="mb-0 small text-muted"><?= htmlspecialchars($i['personal_cargo'] ?? '') ?></p>
                <?php else: ?>
                    <p class="mb-0 text-muted">No registrado</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
