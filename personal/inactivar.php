<?php
// Compatibilidad con los enlaces del aporte inicial del Grupo 4.
declare(strict_types=1);
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    header('Allow: GET');
    exit('Abre el formulario desde el directorio de Personal.');
}
$_GET['accion'] = 'baja';
require __DIR__ . '/index.php';
