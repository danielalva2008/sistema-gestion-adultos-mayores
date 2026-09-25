<?php
require_once '../config/conexion.php';
$pdo = getPDO();
$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM tipos_incidente WHERE id_tipo_incidente = ?");
$stmt->execute([$id]);
$tipo = $stmt->fetch();

if (!$tipo) {
    header('Location: index.php?error=' . urlencode('Tipo de incidente no encontrado.'));
    exit;
}

$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre      = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $nivel       = $_POST['nivel_riesgo'] ?? '';

    if ($nombre === '') {
        $errores[] = 'El nombre es obligatorio.';
    }
    if (!in_array($nivel, ['BAJO', 'MEDIO', 'ALTO', 'CRITICO'], true)) {
        $errores[] = 'Nivel de riesgo inválido.';
    }

    if (empty($errores)) {
        try {
            $stmt = $pdo->prepare(
                "UPDATE tipos_incidente SET nombre = ?, descripcion = ?, nivel_riesgo = ? WHERE id_tipo_incidente = ?"
            );
            $stmt->execute([$nombre, $descripcion !== '' ? $descripcion : null, $nivel, $id]);
            header('Location: index.php?ok=1');
            exit;
        } catch (PDOException $e) {
            if ((int)$e->getCode() === 23000) {
                $errores[] = 'Ya existe un tipo con ese nombre.';
            } else {
                $errores[] = 'Error al actualizar: ' . $e->getMessage();
            }
        }
    }
} else {
    $_POST = $tipo;
}

$titulo = 'Editar Tipo de Incidente';
require_once '../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-7">
        <h2 class="mb-3">
            <i class="bi bi-pencil"></i> Editar Tipo #<?= $id ?>
        </h2>

        <?php if ($errores): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errores as $e): ?>
                        <li><?= htmlspecialchars($e) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" class="card shadow-sm p-4">
            <div class="mb-3">
                <label class="form-label">Nombre <span class="text-danger">*</span></label>
                <input type="text" name="nombre" class="form-control" required maxlength="80"
                       value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Nivel de riesgo <span class="text-danger">*</span></label>
                <select name="nivel_riesgo" class="form-select" required>
                    <?php foreach (['BAJO', 'MEDIO', 'ALTO', 'CRITICO'] as $n): ?>
                        <option value="<?= $n ?>"
                            <?= ($_POST['nivel_riesgo'] ?? '') === $n ? 'selected' : '' ?>>
                            <?= $n ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Descripción</label>
                <textarea name="descripcion" class="form-control" rows="3"
                          maxlength="255"><?= htmlspecialchars($_POST['descripcion'] ?? '') ?></textarea>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg"></i> Actualizar
                </button>
                <a href="index.php" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
