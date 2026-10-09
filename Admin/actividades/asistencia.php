<?php
/**
 * P4 · Registro de asistencia (HU-03). Una fila por inscrito, guardar todo.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/plantilla.php';
require_once __DIR__ . '/logica/ActividadRepositorio.php';
require_once __DIR__ . '/logica/ParticipacionRepositorio.php';

$pdo      = conexion();
$actRepo  = new ActividadRepositorio($pdo);
$partRepo = new ParticipacionRepositorio($pdo);
$base     = base_url();

$id = id_valido($_GET['id'] ?? null);
if ($id === null) { redirigir($base . '/index.php'); }

$act = $actRepo->porId($id);
if ($act === null) {
    flash('error', 'La actividad no existe o fue eliminada.'); // MSG-E09
    redirigir($base . '/index.php');
}

$temporal  = estado_temporal($act['fecha'], $act['hora_inicio'], $act['hora_fin']);
$cancelada = esta_cancelada($act);
$habilitado = !$cancelada && in_array($temporal, [T_EN_CURSO, T_FINALIZADA], true);

$inscritos = $partRepo->inscritosDeActividad($id);

cabecera('Asistencia: ' . $act['nombre']);
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Asistencia · <?= e($act['nombre']) ?></h1>
    <a href="<?= e($base) ?>/ver.php?id=<?= $id ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Volver</a>
</div>

<p class="text-body-secondary">
    <?= e($act['fecha']) ?> · <?= e(substr($act['hora_inicio'],0,5)) ?>–<?= e(substr($act['hora_fin'],0,5)) ?> · <?= e($act['lugar']) ?>
</p>

<?php if (!$habilitado): ?>
    <div class="alert alert-warning">
        <i class="bi bi-clock-history"></i> La asistencia solo puede registrarse desde el inicio de la actividad
        (y siempre que no esté cancelada).
    </div>
<?php elseif (!$inscritos): ?>
    <div class="alert alert-info">No hay residentes inscritos en esta actividad.</div>
<?php else: ?>
    <form method="post" action="<?= e($base) ?>/acciones/asistencia.php" class="card">
        <?= csrf_campo() ?>
        <input type="hidden" name="id_actividad" value="<?= $id ?>">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr><th>Código</th><th>Residente</th><th class="text-center">Asistió</th><th class="text-center">No asistió</th><th class="text-center">Sin marcar</th></tr>
                </thead>
                <tbody>
                <?php foreach ($inscritos as $p):
                    $idp = (int)$p['id_participacion'];
                    $a   = $p['asistencia'];
                    $yaMarcada = $a !== ASIST_PENDIENTE;
                ?>
                    <tr>
                        <td><?= e($p['codigo_residente']) ?></td>
                        <td>
                            <?= e($p['apellidos'] . ', ' . $p['nombres']) ?>
                            <?php if ($p['estado_residente'] !== 'ACTIVO'): ?>
                                <span class="badge text-bg-warning"><?= e($p['estado_residente']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <input class="form-check-input" type="radio" name="asistencia[<?= $idp ?>]" value="ASISTIO" <?= $a === ASIST_ASISTIO ? 'checked' : '' ?>>
                        </td>
                        <td class="text-center">
                            <input class="form-check-input" type="radio" name="asistencia[<?= $idp ?>]" value="NO_ASISTIO" <?= $a === ASIST_NO_ASISTIO ? 'checked' : '' ?>>
                        </td>
                        <td class="text-center">
                            <input class="form-check-input" type="radio" name="asistencia[<?= $idp ?>]" value=""
                                   <?= $a === ASIST_PENDIENTE ? 'checked' : '' ?> <?= $yaMarcada ? 'disabled' : '' ?>>
                            <?php if ($yaMarcada): ?>
                                <div class="small text-body-secondary">no reversible</div>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="card-body">
            <button class="btn btn-primary"><i class="bi bi-save"></i> Guardar todo</button>
            <span class="text-body-secondary ms-2 small">
                Las filas sin marcar quedan pendientes. Una asistencia ya registrada no puede volver a pendiente.
            </span>
        </div>
    </form>
<?php endif; ?>

<?php pie(); ?>
