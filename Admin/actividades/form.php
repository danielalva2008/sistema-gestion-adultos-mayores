<?php
/**
 * P2 · Formulario de actividad (crear/editar) — HU-01 / HU-06.
 * Precarga datos en edición y repuebla ante errores (CA-01.7 / CA-06.5).
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/plantilla.php';
require_once __DIR__ . '/logica/ActividadRepositorio.php';
require_once __DIR__ . '/logica/ConsultaRepositorio.php';
require_once __DIR__ . '/logica/Validador.php';

$pdo      = conexion();
$actRepo  = new ActividadRepositorio($pdo);
$consRepo = new ConsultaRepositorio($pdo);
$base     = base_url();

$id = id_valido($_GET['id'] ?? null);
$esEdicion = $id !== null;

$soloLectura = false;
$actividad = null;

if ($esEdicion) {
    $actividad = $actRepo->porId($id);
    if ($actividad === null) {
        flash('error', 'La actividad no existe o fue eliminada.'); // MSG-E09
        redirigir($base . '/index.php');
    }
    // RN-09: solo se editan PROGRAMADA no canceladas; el resto es solo lectura (CA-06.1).
    $temporal = estado_temporal($actividad['fecha'], $actividad['hora_inicio'], $actividad['hora_fin']);
    if (esta_cancelada($actividad) || $temporal !== T_PROGRAMADA) {
        $soloLectura = true;
    }
}

// Errores/datos previos (repoblado tras un POST fallido).
$errores = $_SESSION['form_errores'] ?? [];
$previos = $_SESSION['form_datos'] ?? [];
unset($_SESSION['form_errores'], $_SESSION['form_datos']);

/** Devuelve el valor a mostrar: 1) datos previos del POST, 2) actividad en BD, 3) vacío. */
function campo(string $nombre, array $previos, ?array $actividad, string $default = ''): string
{
    if (array_key_exists($nombre, $previos)) {
        return (string)$previos[$nombre];
    }
    if ($actividad !== null && array_key_exists($nombre, $actividad)) {
        // Horas a formato H:i para el input type=time.
        $v = (string)$actividad[$nombre];
        if (in_array($nombre, ['hora_inicio', 'hora_fin'], true)) {
            return substr($v, 0, 5);
        }
        return $v;
    }
    return $default;
}

$personal = $consRepo->personalActivo();

// En edición, si el responsable actual ya no está ACTIVO, añadirlo al combo para no perderlo.
$respActual = campo('id_personal', $previos, $actividad);
if ($esEdicion && $actividad['id_personal'] !== null) {
    $ids = array_column($personal, 'id_personal');
    if (!in_array((int)$actividad['id_personal'], array_map('intval', $ids), true)) {
        $p = $consRepo->personalPorId((int)$actividad['id_personal']);
        if ($p !== null) {
            $p['cargo'] = ($p['cargo'] ?? '') . ' (INACTIVO)';
            $personal[] = $p;
        }
    }
}

cabecera($esEdicion ? 'Editar actividad' : 'Nueva actividad', $esEdicion ? '' : 'nueva');
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0"><?= $esEdicion ? 'Editar actividad' : 'Programar nueva actividad' ?></h1>
    <a href="<?= e($base) ?>/index.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Volver</a>
</div>

<?php if ($soloLectura): ?>
    <div class="alert alert-warning">
        <i class="bi bi-lock"></i> Solo pueden modificarse actividades programadas (no iniciadas ni canceladas).
        Esta actividad se muestra en modo solo lectura.
    </div>
<?php endif; ?>

<form method="post" action="<?= e($base) ?>/acciones/guardar.php" class="card card-body" novalidate>
    <?= csrf_campo() ?>
    <?php if ($esEdicion): ?>
        <input type="hidden" name="id_actividad" value="<?= (int)$id ?>">
    <?php endif; ?>

    <div class="row g-3">
        <div class="col-md-12">
            <label class="form-label">Nombre <span class="text-danger">*</span></label>
            <input type="text" name="nombre" maxlength="100" required
                   class="form-control <?= isset($errores['nombre']) ? 'is-invalid' : '' ?>"
                   value="<?= e(campo('nombre', $previos, $actividad)) ?>" <?= $soloLectura ? 'disabled' : '' ?>>
            <?php if (isset($errores['nombre'])): ?><div class="invalid-feedback"><?= e($errores['nombre']) ?></div><?php endif; ?>
        </div>

        <div class="col-md-4">
            <label class="form-label">Fecha <span class="text-danger">*</span></label>
            <input type="date" name="fecha" required
                   class="form-control <?= isset($errores['fecha']) ? 'is-invalid' : '' ?>"
                   value="<?= e(campo('fecha', $previos, $actividad)) ?>" <?= $soloLectura ? 'disabled' : '' ?>>
            <?php if (isset($errores['fecha'])): ?><div class="invalid-feedback"><?= e($errores['fecha']) ?></div><?php endif; ?>
        </div>
        <div class="col-md-2">
            <label class="form-label">Hora inicio <span class="text-danger">*</span></label>
            <input type="time" name="hora_inicio" required
                   class="form-control <?= isset($errores['hora_inicio']) ? 'is-invalid' : '' ?>"
                   value="<?= e(campo('hora_inicio', $previos, $actividad)) ?>" <?= $soloLectura ? 'disabled' : '' ?>>
            <?php if (isset($errores['hora_inicio'])): ?><div class="invalid-feedback"><?= e($errores['hora_inicio']) ?></div><?php endif; ?>
        </div>
        <div class="col-md-2">
            <label class="form-label">Hora fin <span class="text-danger">*</span></label>
            <input type="time" name="hora_fin" required
                   class="form-control <?= isset($errores['hora_fin']) ? 'is-invalid' : '' ?>"
                   value="<?= e(campo('hora_fin', $previos, $actividad)) ?>" <?= $soloLectura ? 'disabled' : '' ?>>
            <?php if (isset($errores['hora_fin'])): ?><div class="invalid-feedback"><?= e($errores['hora_fin']) ?></div><?php endif; ?>
        </div>
        <div class="col-md-2">
            <label class="form-label">Cupo <span class="text-danger">*</span></label>
            <input type="number" name="cupo" min="1" step="1" required
                   class="form-control <?= isset($errores['cupo']) ? 'is-invalid' : '' ?>"
                   value="<?= e(campo('cupo', $previos, $actividad)) ?>" <?= $soloLectura ? 'disabled' : '' ?>>
            <?php if (isset($errores['cupo'])): ?><div class="invalid-feedback"><?= e($errores['cupo']) ?></div><?php endif; ?>
        </div>

        <div class="col-md-8">
            <label class="form-label">Lugar <span class="text-danger">*</span></label>
            <input type="text" name="lugar" maxlength="80" required
                   class="form-control <?= isset($errores['lugar']) ? 'is-invalid' : '' ?>"
                   value="<?= e(campo('lugar', $previos, $actividad)) ?>" <?= $soloLectura ? 'disabled' : '' ?>>
            <?php if (isset($errores['lugar'])): ?><div class="invalid-feedback"><?= e($errores['lugar']) ?></div><?php endif; ?>
        </div>
        <div class="col-md-4">
            <label class="form-label">Responsable <span class="text-danger">*</span></label>
            <select name="id_personal" required
                    class="form-select <?= isset($errores['id_personal']) ? 'is-invalid' : '' ?>" <?= $soloLectura ? 'disabled' : '' ?>>
                <option value="">— Seleccione —</option>
                <?php foreach ($personal as $p): ?>
                    <option value="<?= (int)$p['id_personal'] ?>" <?= (string)$respActual === (string)$p['id_personal'] ? 'selected' : '' ?>>
                        <?= e($p['codigo_personal'] . ' – ' . $p['apellidos'] . ', ' . $p['nombres'] . ' – ' . $p['cargo']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($errores['id_personal'])): ?><div class="invalid-feedback"><?= e($errores['id_personal']) ?></div><?php endif; ?>
        </div>

        <div class="col-12">
            <label class="form-label">Descripción</label>
            <textarea name="descripcion" maxlength="255" rows="2"
                      class="form-control <?= isset($errores['descripcion']) ? 'is-invalid' : '' ?>" <?= $soloLectura ? 'disabled' : '' ?>><?= e(campo('descripcion', $previos, $actividad)) ?></textarea>
            <?php if (isset($errores['descripcion'])): ?><div class="invalid-feedback"><?= e($errores['descripcion']) ?></div><?php endif; ?>
        </div>
    </div>

    <?php if (!$soloLectura): ?>
    <div class="mt-4">
        <button class="btn btn-primary"><i class="bi bi-save"></i> <?= $esEdicion ? 'Guardar cambios' : 'Programar actividad' ?></button>
        <a href="<?= e($base) ?>/index.php" class="btn btn-outline-secondary">Cancelar</a>
    </div>
    <?php else: ?>
    <div class="mt-4">
        <a href="<?= e($base) ?>/ver.php?id=<?= (int)$id ?>" class="btn btn-outline-secondary">Ver detalle</a>
    </div>
    <?php endif; ?>
</form>

<?php pie(); ?>
