<?php
/**
 * P5 · Reporte de participación (HU-04). Vista + indicadores, solo lectura.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/plantilla.php';
require_once __DIR__ . '/logica/ConsultaRepositorio.php';

$pdo      = conexion();
$consRepo = new ConsultaRepositorio($pdo);
$base     = base_url();

$actividadTxt = trim((string)($_GET['actividad'] ?? ''));
$desde = trim((string)($_GET['desde'] ?? ''));
$hasta = trim((string)($_GET['hasta'] ?? ''));

// Validación de rango (MSG-E25).
$fechaOk = static function (string $f): bool {
    $d = DateTime::createFromFormat('!Y-m-d', $f);
    return $d !== false && $d->format('Y-m-d') === $f;
};
if ($desde !== '' && $hasta !== '' && $fechaOk($desde) && $fechaOk($hasta) && $desde > $hasta) {
    flash('alerta', 'La fecha "desde" no puede ser posterior a la fecha "hasta".'); // MSG-E25
    $desde = $hasta = '';
}

$filtros = ['actividad' => $actividadTxt, 'desde' => $desde, 'hasta' => $hasta];

$indicadores = $consRepo->vistaParticipacion($filtros);   // vista agregada (una fila por actividad)
$detalle     = $consRepo->detalleParticipacion($filtros); // detalle nominal por residente

function pct(?float $v): string
{
    return $v === null ? '—' : number_format($v, 1) . '%';
}

cabecera('Reporte de participación', 'reporte');
?>

<h1 class="h3 mb-3">Reporte de participación</h1>

<form class="card card-body mb-4" method="get" action="<?= e($base) ?>/reporte.php">
    <div class="row g-3">
        <div class="col-md-5">
            <label class="form-label">Actividad</label>
            <input type="text" name="actividad" class="form-control" value="<?= e($actividadTxt) ?>" maxlength="120" placeholder="Nombre de la actividad">
        </div>
        <div class="col-md-3">
            <label class="form-label">Desde</label>
            <input type="date" name="desde" class="form-control" value="<?= e($desde) ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label">Hasta</label>
            <input type="date" name="hasta" class="form-control" value="<?= e($hasta) ?>">
        </div>
        <div class="col-md-1 d-flex align-items-end">
            <button class="btn btn-outline-primary w-100"><i class="bi bi-search"></i></button>
        </div>
    </div>
</form>

<div class="card mb-4">
    <div class="card-header">Indicadores por actividad</div>
    <div class="table-responsive">
        <table class="table table-striped mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Actividad</th><th>Fecha</th>
                    <th class="text-center">Inscritos</th>
                    <th class="text-center">Asistieron</th>
                    <th class="text-center">No asistieron</th>
                    <th class="text-center">Pendientes</th>
                    <th class="text-center">% Ocupación</th>
                    <th class="text-center">% Asistencia</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$indicadores): ?>
                <tr><td colspan="8" class="text-center text-body-secondary py-3">Sin datos para el filtro.</td></tr>
            <?php else: foreach ($indicadores as $r): ?>
                <tr>
                    <td><?= e($r['nombre']) ?></td>
                    <td><?= e($r['fecha']) ?></td>
                    <td class="text-center"><?= (int)$r['inscritos'] ?></td>
                    <td class="text-center"><?= (int)$r['asistieron'] ?></td>
                    <td class="text-center"><?= (int)$r['no_asistieron'] ?></td>
                    <td class="text-center"><?= (int)$r['pendientes'] ?></td>
                    <td class="text-center"><?= number_format((float)$r['pct_ocupacion'], 1) ?>%</td>
                    <td class="text-center"><?= pct($r['pct_asistencia'] === null ? null : (float)$r['pct_asistencia']) ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-header">Detalle (vista <code>vw_participacion_actividades</code>)</div>
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead class="table-light">
                <tr><th>Actividad</th><th>Fecha</th><th>Código</th><th>Residente</th><th>Asistencia</th><th>Observaciones</th></tr>
            </thead>
            <tbody>
            <?php if (!$detalle): ?>
                <tr><td colspan="6" class="text-center text-body-secondary py-3">Sin registros.</td></tr>
            <?php else: foreach ($detalle as $d):
                $mapa = ['ASISTIO' => 'success', 'NO_ASISTIO' => 'danger', 'INSCRITO' => 'secondary'];
            ?>
                <tr>
                    <td><?= e($d['actividad']) ?></td>
                    <td><?= e($d['fecha']) ?></td>
                    <td><?= e($d['codigo_residente']) ?></td>
                    <td><?= e($d['residente']) ?></td>
                    <td><span class="badge text-bg-<?= $mapa[$d['asistencia']] ?? 'secondary' ?>"><?= e($d['asistencia']) ?></span></td>
                    <td><?= e($d['observacion'] ?? '') ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php pie(); ?>
