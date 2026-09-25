<?php
/**
 * P1 · Listado de actividades (HU-05).
 * Búsqueda por nombre/lugar, filtros por fecha, responsable y estado; alertas.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/plantilla.php';
require_once __DIR__ . '/logica/ActividadRepositorio.php';
require_once __DIR__ . '/logica/ConsultaRepositorio.php';

$pdo      = conexion();
$actRepo  = new ActividadRepositorio($pdo);
$consRepo = new ConsultaRepositorio($pdo);

$base = base_url();

// --- Filtros (GET) ---
$q     = trim((string)($_GET['q'] ?? ''));
$desde = trim((string)($_GET['desde'] ?? ''));
$hasta = trim((string)($_GET['hasta'] ?? ''));
$resp  = id_valido($_GET['id_responsable'] ?? null);
$estado = (string)($_GET['estado'] ?? '');

// Validación de rango de fechas (MSG-E25).
$errorRango = false;
if ($desde !== '' && $hasta !== '' && Validador_fecha_ok($desde) && Validador_fecha_ok($hasta) && $desde > $hasta) {
    $errorRango = true;
    flash('alerta', 'La fecha "desde" no puede ser posterior a la fecha "hasta".'); // MSG-E25
}

$filtros = [
    'q'               => $q,
    'desde'           => $errorRango ? '' : $desde,
    'hasta'           => $errorRango ? '' : $hasta,
    'id_responsable'  => $resp,
    'estado_temporal' => $estado,
];

$actividades = $actRepo->listar($filtros, ahora());
$personal    = $consRepo->personalActivo();

/** Helper local: valida fecha Y-m-d sin cargar el Validador completo. */
function Validador_fecha_ok(string $f): bool
{
    $d = DateTime::createFromFormat('!Y-m-d', $f);
    return $d !== false && $d->format('Y-m-d') === $f;
}

/** Badge de color según estado calculado. */
function badge_estado(string $estado): string
{
    $mapa = [
        'PROGRAMADA' => 'primary',
        'EN_CURSO'   => 'success',
        'FINALIZADA' => 'secondary',
        'CANCELADA'  => 'danger',
    ];
    $color = $mapa[$estado] ?? 'secondary';
    return '<span class="badge text-bg-' . $color . '">' . e($estado) . '</span>';
}

cabecera('Listado de actividades', 'listado');
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Actividades</h1>
    <a href="<?= e($base) ?>/form.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Nueva actividad</a>
</div>

<form class="card card-body mb-4" method="get" action="<?= e($base) ?>/index.php">
    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label">Buscar (nombre o lugar)</label>
            <input type="text" name="q" class="form-control" value="<?= e($q) ?>" maxlength="120" placeholder="Ej. Gimnasia, Sala…">
        </div>
        <div class="col-md-2">
            <label class="form-label">Desde</label>
            <input type="date" name="desde" class="form-control" value="<?= e($desde) ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label">Hasta</label>
            <input type="date" name="hasta" class="form-control" value="<?= e($hasta) ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label">Responsable</label>
            <select name="id_responsable" class="form-select">
                <option value="">Todos</option>
                <?php foreach ($personal as $p): ?>
                    <option value="<?= (int)$p['id_personal'] ?>" <?= $resp === (int)$p['id_personal'] ? 'selected' : '' ?>>
                        <?= e($p['apellidos'] . ', ' . $p['nombres']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label">Estado</label>
            <select name="estado" class="form-select">
                <?php
                $opciones = ['' => 'Todas', 'PROGRAMADA' => 'Programada', 'EN_CURSO' => 'En curso',
                             'FINALIZADA' => 'Finalizada', 'CANCELADA' => 'Cancelada'];
                foreach ($opciones as $val => $txt): ?>
                    <option value="<?= e($val) ?>" <?= $estado === $val ? 'selected' : '' ?>><?= e($txt) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="mt-3">
        <button class="btn btn-outline-primary"><i class="bi bi-search"></i> Filtrar</button>
        <a href="<?= e($base) ?>/index.php" class="btn btn-outline-secondary">Limpiar</a>
    </div>
</form>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Fecha</th>
                    <th>Horario</th>
                    <th>Actividad</th>
                    <th>Lugar</th>
                    <th>Responsable</th>
                    <th class="text-center">Inscritos/Cupo</th>
                    <th>Estado</th>
                    <th>Alertas</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$actividades): ?>
                <tr><td colspan="9" class="text-center text-body-secondary py-4">No hay actividades que coincidan con el filtro.</td></tr>
            <?php else: foreach ($actividades as $a):
                $estadoCalc = $a['estado_calculado'];
                $inscritos  = (int)$a['inscritos'];
                $cupo       = (int)$a['cupo'];
                $sobrecupo  = $inscritos > $cupo;

                // Alertas (CA-05.4 / MSG-A01..A05).
                $alertas = [];
                if ($a['id_personal'] === null) {
                    $alertas[] = ['warning', 'Sin responsable'];
                } elseif ($a['estado_responsable'] !== null && $a['estado_responsable'] !== 'ACTIVO') {
                    $alertas[] = ['warning', 'Responsable inactivo'];
                }
                if ($sobrecupo) { $alertas[] = ['danger', 'Sobrecupo']; }
                if ($estadoCalc === 'FINALIZADA' && (int)$a['pendientes'] > 0) {
                    $alertas[] = ['info', 'Asistencia incompleta'];
                }
                if ((int)$a['inscritos_no_activos'] > 0) {
                    $alertas[] = ['warning', 'Residente no activo inscrito'];
                }

                $responsable = $a['id_personal'] === null
                    ? '<span class="text-body-secondary fst-italic">Sin responsable</span>'
                    : e($a['resp_apellidos'] . ', ' . $a['resp_nombres']);
            ?>
                <tr>
                    <td><?= e($a['fecha']) ?></td>
                    <td class="text-nowrap"><?= e(substr($a['hora_inicio'], 0, 5)) ?>–<?= e(substr($a['hora_fin'], 0, 5)) ?></td>
                    <td>
                        <a href="<?= e($base) ?>/ver.php?id=<?= (int)$a['id_actividad'] ?>" class="fw-semibold text-decoration-none">
                            <?= e($a['nombre']) ?>
                        </a>
                    </td>
                    <td><?= e($a['lugar']) ?></td>
                    <td><?= $responsable ?></td>
                    <td class="text-center">
                        <span class="<?= $sobrecupo ? 'text-danger fw-bold' : '' ?>"><?= $inscritos ?></span> / <?= $cupo ?>
                    </td>
                    <td><?= badge_estado($estadoCalc) ?></td>
                    <td>
                        <?php foreach ($alertas as [$color, $txt]): ?>
                            <span class="badge text-bg-<?= $color ?> mb-1"><?= e($txt) ?></span>
                        <?php endforeach; ?>
                    </td>
                    <td class="text-end text-nowrap">
                        <a href="<?= e($base) ?>/ver.php?id=<?= (int)$a['id_actividad'] ?>" class="btn btn-sm btn-outline-secondary" title="Ver"><i class="bi bi-eye"></i></a>
                        <?php if ($estadoCalc === 'PROGRAMADA'): ?>
                            <a href="<?= e($base) ?>/form.php?id=<?= (int)$a['id_actividad'] ?>" class="btn btn-sm btn-outline-primary" title="Editar"><i class="bi bi-pencil"></i></a>
                        <?php endif; ?>
                        <?php if ($estadoCalc === 'EN_CURSO' || $estadoCalc === 'FINALIZADA'): ?>
                            <a href="<?= e($base) ?>/asistencia.php?id=<?= (int)$a['id_actividad'] ?>" class="btn btn-sm btn-outline-success" title="Asistencia"><i class="bi bi-clipboard-check"></i></a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php pie(); ?>
