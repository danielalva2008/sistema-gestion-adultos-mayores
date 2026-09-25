<?php
/**
 * P3 · Detalle de actividad y participantes (HU-02 inscribir, HU-08 anular).
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/plantilla.php';
require_once __DIR__ . '/logica/ActividadRepositorio.php';
require_once __DIR__ . '/logica/ParticipacionRepositorio.php';
require_once __DIR__ . '/logica/ConsultaRepositorio.php';

$pdo      = conexion();
$actRepo  = new ActividadRepositorio($pdo);
$partRepo = new ParticipacionRepositorio($pdo);
$consRepo = new ConsultaRepositorio($pdo);
$base     = base_url();

$id = id_valido($_GET['id'] ?? null);
if ($id === null) { redirigir($base . '/index.php'); }

$act = $actRepo->porId($id);
if ($act === null) {
    flash('error', 'La actividad no existe o fue eliminada.'); // MSG-E09
    redirigir($base . '/index.php');
}

$temporal   = estado_temporal($act['fecha'], $act['hora_inicio'], $act['hora_fin']);
$cancelada  = esta_cancelada($act);
$estadoCalc = $cancelada ? 'CANCELADA' : $temporal;

$inscritosLista = $partRepo->inscritosDeActividad($id);
$inscritos = count($inscritosLista);
$cupo      = (int)$act['cupo'];
$disponible = max(0, $cupo - $inscritos);
$cupoLleno = $inscritos >= $cupo;

$responsable = null;
if ($act['id_personal'] !== null) {
    $responsable = $consRepo->personalPorId((int)$act['id_personal']);
}

// Matriz de operaciones (§7).
$puedeEditar     = $estadoCalc === 'PROGRAMADA';
$puedeInscribir  = $estadoCalc === 'PROGRAMADA' && !$cupoLleno;
$puedeAnular     = $estadoCalc === 'PROGRAMADA';
$puedeAsistencia = in_array($estadoCalc, ['EN_CURSO', 'FINALIZADA'], true);
$puedeCancelar   = $estadoCalc === 'PROGRAMADA';
$puedeReactivar  = $cancelada && $temporal === 'PROGRAMADA';

$residentes = $puedeInscribir ? $consRepo->residentesInscribibles($id) : [];

function badge_asistencia(string $a): string
{
    $mapa = ['ASISTIO' => 'success', 'NO_ASISTIO' => 'danger', 'INSCRITO' => 'secondary'];
    return '<span class="badge text-bg-' . ($mapa[$a] ?? 'secondary') . '">' . e($a) . '</span>';
}

cabecera('Detalle: ' . $act['nombre']);
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0"><?= e($act['nombre']) ?></h1>
    <a href="<?= e($base) ?>/index.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Volver</a>
</div>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Datos de la actividad</span>
                <?php
                $mapa = ['PROGRAMADA'=>'primary','EN_CURSO'=>'success','FINALIZADA'=>'secondary','CANCELADA'=>'danger'];
                ?>
                <span class="badge text-bg-<?= $mapa[$estadoCalc] ?? 'secondary' ?>"><?= e($estadoCalc) ?></span>
            </div>
            <ul class="list-group list-group-flush">
                <li class="list-group-item"><strong>Fecha:</strong> <?= e($act['fecha']) ?></li>
                <li class="list-group-item"><strong>Horario:</strong> <?= e(substr($act['hora_inicio'],0,5)) ?> – <?= e(substr($act['hora_fin'],0,5)) ?></li>
                <li class="list-group-item"><strong>Lugar:</strong> <?= e($act['lugar']) ?></li>
                <li class="list-group-item">
                    <strong>Responsable:</strong>
                    <?php if ($responsable === null): ?>
                        <span class="text-body-secondary fst-italic">Sin responsable</span>
                    <?php else: ?>
                        <?= e($responsable['apellidos'] . ', ' . $responsable['nombres'] . ' (' . $responsable['cargo'] . ')') ?>
                        <?php if ($responsable['estado'] !== 'ACTIVO'): ?>
                            <span class="badge text-bg-warning">Inactivo</span>
                        <?php endif; ?>
                    <?php endif; ?>
                </li>
                <li class="list-group-item">
                    <strong>Cupo:</strong>
                    <span class="<?= $inscritos > $cupo ? 'text-danger fw-bold' : '' ?>"><?= $inscritos ?></span> / <?= $cupo ?>
                    · Disponible: <strong><?= $disponible ?></strong>
                    <?php if ($inscritos > $cupo): ?><span class="badge text-bg-danger">Sobrecupo</span><?php endif; ?>
                </li>
                <?php if ($act['descripcion']): ?>
                    <li class="list-group-item"><strong>Descripción:</strong> <?= e($act['descripcion']) ?></li>
                <?php endif; ?>
            </ul>
            <div class="card-body d-flex gap-2 flex-wrap">
                <?php if ($puedeEditar): ?>
                    <a href="<?= e($base) ?>/form.php?id=<?= $id ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-pencil"></i> Editar</a>
                <?php endif; ?>
                <?php if ($puedeAsistencia): ?>
                    <a href="<?= e($base) ?>/asistencia.php?id=<?= $id ?>" class="btn btn-outline-success btn-sm"><i class="bi bi-clipboard-check"></i> Asistencia</a>
                <?php endif; ?>
                <?php if ($puedeCancelar): ?>
                    <form method="post" action="<?= e($base) ?>/acciones/cancelar.php" onsubmit="return confirm('¿Cancelar esta actividad? Se conservarán las inscripciones como historial.');">
                        <?= csrf_campo() ?>
                        <input type="hidden" name="id_actividad" value="<?= $id ?>">
                        <button class="btn btn-outline-danger btn-sm"><i class="bi bi-x-circle"></i> Cancelar actividad</button>
                    </form>
                <?php endif; ?>
                <?php if ($puedeReactivar): ?>
                    <form method="post" action="<?= e($base) ?>/acciones/reactivar.php" onsubmit="return confirm('¿Reactivar esta actividad?');">
                        <?= csrf_campo() ?>
                        <input type="hidden" name="id_actividad" value="<?= $id ?>">
                        <button class="btn btn-outline-success btn-sm"><i class="bi bi-arrow-counterclockwise"></i> Reactivar</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">Participantes (<?= $inscritos ?>)</div>
            <div class="card-body">
                <?php if ($puedeInscribir): ?>
                    <form method="post" action="<?= e($base) ?>/acciones/inscribir.php" class="row g-2 mb-3">
                        <?= csrf_campo() ?>
                        <input type="hidden" name="id_actividad" value="<?= $id ?>">
                        <div class="col-md-6">
                            <select name="id_residente" class="form-select" required>
                                <option value="">— Seleccione residente activo —</option>
                                <?php foreach ($residentes as $r): ?>
                                    <option value="<?= (int)$r['id_residente'] ?>">
                                        <?= e($r['codigo_residente'] . ' – ' . $r['apellidos'] . ', ' . $r['nombres']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <input type="text" name="observacion" class="form-control" maxlength="150" placeholder="Observación (opcional)">
                        </div>
                        <div class="col-md-2 d-grid">
                            <button class="btn btn-primary"><i class="bi bi-plus-lg"></i> Inscribir</button>
                        </div>
                    </form>
                <?php elseif ($estadoCalc === 'PROGRAMADA' && $cupoLleno): ?>
                    <div class="alert alert-info mb-3"><i class="bi bi-info-circle"></i> Cupo completo.</div>
                <?php endif; ?>

                <?php if (!$inscritosLista): ?>
                    <p class="text-body-secondary mb-0">Aún no hay residentes inscritos.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr><th>Código</th><th>Residente</th><th>Asistencia</th><th class="text-end">Acción</th></tr>
                            </thead>
                            <tbody>
                            <?php foreach ($inscritosLista as $p): ?>
                                <tr>
                                    <td><?= e($p['codigo_residente']) ?></td>
                                    <td>
                                        <?= e($p['apellidos'] . ', ' . $p['nombres']) ?>
                                        <?php if ($p['estado_residente'] !== 'ACTIVO'): ?>
                                            <span class="badge text-bg-warning"><?= e($p['estado_residente']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= badge_asistencia($p['asistencia']) ?></td>
                                    <td class="text-end">
                                        <?php if ($puedeAnular && $p['asistencia'] === ASIST_PENDIENTE): ?>
                                            <form method="post" action="<?= e($base) ?>/acciones/anular.php" onsubmit="return confirm('¿Anular la inscripción de este residente?');">
                                                <?= csrf_campo() ?>
                                                <input type="hidden" name="id_actividad" value="<?= $id ?>">
                                                <input type="hidden" name="id_participacion" value="<?= (int)$p['id_participacion'] ?>">
                                                <button class="btn btn-sm btn-outline-danger" title="Anular inscripción"><i class="bi bi-person-dash"></i></button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php pie(); ?>
