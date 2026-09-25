<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($titulo ?? 'Módulo Incidentes') ?> - Residencia Adultos Mayores</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; }
        .navbar-brand { font-weight: 600; }
        .table th { white-space: nowrap; }
        .badge { font-weight: 500; }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm mb-4">
    <div class="container">
        <a class="navbar-brand" href="../incidentes/index.php">
            <i class="bi bi-heart-pulse-fill"></i> Sistema Residencia
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMain">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?= str_contains($_SERVER['PHP_SELF'] ?? '', '/incidentes/') ? 'active' : '' ?>"
                       href="../incidentes/index.php">
                        <i class="bi bi-exclamation-triangle"></i> Incidentes
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= str_contains($_SERVER['PHP_SELF'] ?? '', '/tipos/') ? 'active' : '' ?>"
                       href="../tipos/index.php">
                        <i class="bi bi-tags"></i> Tipos de Incidente
                    </a>
                </li>
            </ul>
            <span class="navbar-text text-white-50 small">Grupo 5 — Módulo Incidentes</span>
        </div>
    </div>
</nav>
<main class="container pb-5">
