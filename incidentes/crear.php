<?php
require_once '../config/conexion.php';
$pdo = getPDO();

$residentes = $pdo->query(
    "SELECT id_residente, codigo_residente, CONCAT(nombres, ' ', apellidos) AS nombre
     FROM residentes WHERE estado = 'ACTIVO' ORDER BY apellidos, nombres"
)->fetchAll();

$tipos = $pdo->query(
    "SELECT id_tipo_incidente, nombre, nivel_riesgo FROM tipos_incidente ORDER BY nombre"
)->fetchAll();

$personal = $pdo->query(
    "SELECT id_personal, CONCAT(nombres, ' ', apellidos) AS nombre
     FROM personal WHERE estado = 'ACTIVO' ORDER BY apellidos, nombres"
)->fetchAll();

$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_residente = (int)($_POST['id_residente'] ?? 0);
    $id_tipo      = (int)($_POST['id_tipo_incidente'] ?? 0);
    $id_personal  = ($_POST['id_personal_reporta'] ?? '') !== ''
        ? (int)$_POST['id_personal_reporta']
        : null;
    $fecha_hora   = trim($_POST['fecha_hora'] ?? '');
    $lugar        = trim($_POST['lugar'] ?? '');
    $descripcion  = trim($_POST['descripcion'] ?? '');
    $accion       = trim($_POST['accion_realizada'] ?? '');
    $requiere     = isset($_POST['requiere_seguimiento']) ? 1 : 0;
    $estado       = $_POST['estado'] ?? 'REGISTRADO';
    $fecha_cierre = trim($_POST['fecha_cierre'] ?? '');

    // Validaciones
    if ($id_residente <= 0) {
        $errores[] = 'Debe seleccionar un residente.';
    }
    if ($id_tipo <= 0) {
        $errores[] = 'Debe seleccionar un tipo de incidente.';
    }
    if ($fecha_hora === '') {
        $errores[] = 'La fecha y hora son obligatorias.';
    }
    if ($lugar === '') {
        $errores[] = 'El lugar es obligatorio.';
    }
    if ($descripcion === '') {
        $errores[] = 'La descripción es obligatoria.';
    }
    if (!in_array($estado, ['REGISTRADO', 'EN_SEGUIMIENTO', 'CERRADO'], true)) {
        $errores[] = 'Estado inválido.';
    }

    // REGLA DE NEGOCIO: fecha_cierre solo cuando estado = CERRADO
    if ($estado === 'CERRADO') {
        if ($fecha_cierre === '') {
            $errores[] = 'Si el estado es CERRADO debe indicar la fecha de cierre.';
        }
    } else {
        $fecha_cierre = null;
    }

    // Convertir datetime-local a formato MySQL
    if ($fecha_hora !== '') {
        $fecha_hora = str_replace('T', ' ', $fecha_hora);
        if (strlen($fecha_hora) === 16) {
            $fecha_hora .= ':00';
        }
    }
    if ($fecha_cierre !== null && $fecha_cierre !== '') {
        $fecha_cierre = str_replace('T', ' ', $fecha_cierre);
        if (strlen($fecha_cierre) === 16) {
            $fecha_cierre .= ':00';
        }
    }

    if (empty($errores)) {
        try {
            $stmt = $pdo->prepare(
                "INSERT INTO incidentes
                 (id_residente, id_tipo_incidente, id_personal_reporta, fecha_hora, lugar,
                  descripcion, accion_realizada, requiere_seguimiento, estado, fecha_cierre)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $id_residente,
                $id_tipo,
                $id_personal,
                $fecha_hora,
                $lugar,
                $descripcion,
                $accion !== '' ? $accion : null,
                $requiere,
                $estado,
                $fecha_cierre
            ]);
            header('Location: index.php?ok=1');
            exit;
        } catch (PDOException $e) {
            $errores[] = 'Error al guardar: ' . $e->getMessage();
        }
    }
}

$titulo = 'Registrar Incidente';
require_once '../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-9">
        <h2 class="mb-3"><i class="bi bi-plus-circle"></i> Registrar nuevo incidente</h2>

        <?php if ($errores): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errores as $e): ?>
                        <li><?= htmlspecialchars($e) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" class="card shadow-sm p-4" id="formIncidente">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Residente <span class="text-danger">*</span></label>
                    <select name="id_residente" class="form-select" required>
                        <option value="">Seleccione...</option>
                        <?php foreach ($residentes as $r): ?>
                            <option value="<?= (int)$r['id_residente'] ?>"
                                <?= (int)($_POST['id_residente'] ?? 0) === (int)$r['id_residente'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($r['codigo_residente'] . ' — ' . $r['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Tipo de incidente <span class="text-danger">*</span></label>
                    <select name="id_tipo_incidente" class="form-select" required>
                        <option value="">Seleccione...</option>
                        <?php foreach ($tipos as $t): ?>
                            <option value="<?= (int)$t['id_tipo_incidente'] ?>"
                                <?= (int)($_POST['id_tipo_incidente'] ?? 0) === (int)$t['id_tipo_incidente'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($t['nombre'] . ' (' . $t['nivel_riesgo'] . ')') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Personal que reporta</label>
                    <select name="id_personal_reporta" class="form-select">
                        <option value="">— Opcional —</option>
                        <?php foreach ($personal as $p): ?>
                            <option value="<?= (int)$p['id_personal'] ?>"
                                <?= (int)($_POST['id_personal_reporta'] ?? 0) === (int)$p['id_personal'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($p['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Fecha y hora <span class="text-danger">*</span></label>
                    <input type="datetime-local" name="fecha_hora" class="form-control" required
                           value="<?= htmlspecialchars($_POST['fecha_hora'] ?? date('Y-m-d\TH:i')) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Lugar <span class="text-danger">*</span></label>
                    <input type="text" name="lugar" class="form-control" required maxlength="150"
                           value="<?= htmlspecialchars($_POST['lugar'] ?? '') ?>"
                           placeholder="Ej: Pasillo del segundo piso">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Estado <span class="text-danger">*</span></label>
                    <select name="estado" id="estado" class="form-select" required onchange="toggleFechaCierre()">
                        <?php foreach (['REGISTRADO', 'EN_SEGUIMIENTO', 'CERRADO'] as $e): ?>
                            <option value="<?= $e ?>"
                                <?= ($_POST['estado'] ?? 'REGISTRADO') === $e ? 'selected' : '' ?>>
                                <?= $e ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6" id="div_fecha_cierre" style="display:none;">
                    <label class="form-label">Fecha de cierre <span class="text-danger">*</span></label>
                    <input type="datetime-local" name="fecha_cierre" id="fecha_cierre" class="form-control"
                           value="<?= htmlspecialchars($_POST['fecha_cierre'] ?? '') ?>">
                    <div class="form-text">Solo se registra cuando el estado es CERRADO.</div>
                </div>
                <div class="col-12">
                    <label class="form-label">Descripción <span class="text-danger">*</span></label>
                    <textarea name="descripcion" class="form-control" rows="3" required
                              placeholder="Describa lo ocurrido..."><?= htmlspecialchars($_POST['descripcion'] ?? '') ?></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label">Acción realizada</label>
                    <textarea name="accion_realizada" class="form-control" rows="2"
                              placeholder="Qué se hizo para atender el incidente..."><?= htmlspecialchars($_POST['accion_realizada'] ?? '') ?></textarea>
                </div>
                <div class="col-12">
                    <div class="form-check">
                        <input type="checkbox" name="requiere_seguimiento" class="form-check-input" id="req"
                               <?= isset($_POST['requiere_seguimiento']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="req">Requiere seguimiento</label>
                    </div>
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-check-lg"></i> Guardar incidente
                </button>
                <a href="index.php" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<script>
function toggleFechaCierre() {
    const estado = document.getElementById('estado').value;
    const div = document.getElementById('div_fecha_cierre');
    const input = document.getElementById('fecha_cierre');
    if (estado === 'CERRADO') {
        div.style.display = 'block';
        input.required = true;
    } else {
        div.style.display = 'none';
        input.required = false;
        input.value = '';
    }
}
document.addEventListener('DOMContentLoaded', toggleFechaCierre);
</script>

<?php require_once '../includes/footer.php'; ?>
