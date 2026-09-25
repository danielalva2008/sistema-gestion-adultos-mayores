<?php
/**
 * Plantilla HTML común (cabecera/pie) del módulo Actividades.
 * Interfaz responsiva con Bootstrap 5 (Word §8: "puede usarse Bootstrap").
 */

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

/** Ruta base del módulo para enlaces (funciona en http://localhost/.../Admin/actividades/). */
function base_url(): string
{
    $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    // Si estamos dentro de /acciones, subir un nivel.
    if (str_ends_with($dir, '/acciones')) {
        $dir = dirname($dir);
    }
    return rtrim($dir, '/');
}

function cabecera(string $titulo, string $activo = ''): void
{
    $base = base_url();
    ?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titulo) ?> · Actividades</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
</head>
<body class="bg-body-tertiary">
<nav class="navbar navbar-expand-lg bg-primary" data-bs-theme="dark">
    <div class="container">
        <a class="navbar-brand fw-semibold" href="<?= e($base) ?>/index.php">
            <i class="bi bi-calendar2-week"></i> Actividades · Residencia
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="nav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link <?= $activo === 'listado' ? 'active' : '' ?>" href="<?= e($base) ?>/index.php">Listado</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $activo === 'nueva' ? 'active' : '' ?>" href="<?= e($base) ?>/form.php">Nueva actividad</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $activo === 'reporte' ? 'active' : '' ?>" href="<?= e($base) ?>/reporte.php">Reporte</a>
                </li>
            </ul>
        </div>
    </div>
</nav>
<main class="container my-4">
    <?php mostrar_flash(); ?>
    <?php
}

function mostrar_flash(): void
{
    $mapa = ['exito' => 'success', 'error' => 'danger', 'alerta' => 'warning'];
    foreach (obtener_flash() as $m) {
        $clase = $mapa[$m['tipo']] ?? 'secondary';
        echo '<div class="alert alert-' . $clase . ' alert-dismissible fade show" role="alert">'
            . e($m['texto'])
            . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>'
            . '</div>';
    }
}

function pie(): void
{
    ?>
</main>
<footer class="container text-center text-body-secondary py-4 small border-top">
    Módulo Actividades y Participaciones · Grupo 6 · Sistema de Gestión de Residencia para Adultos Mayores
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
    <?php
}
