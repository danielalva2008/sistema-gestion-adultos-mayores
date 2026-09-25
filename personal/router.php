<?php
// Router de desarrollo: expone únicamente las entradas públicas del módulo.
declare(strict_types=1);

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($path === '/style.css') {
    header('Content-Type: text/css; charset=utf-8');
    readfile(__DIR__ . '/style.css');
    return;
}
$routes = [
    '/' => 'index.php',
    '/index.php' => 'index.php',
    '/crear.php' => 'crear.php',
    '/editar.php' => 'editar.php',
    '/inactivar.php' => 'inactivar.php',
    '/guardar.php' => 'guardar.php',
    '/actualizar.php' => 'actualizar.php',
];
if (isset($routes[$path])) {
    require __DIR__ . '/' . $routes[$path];
    return;
}
http_response_code(404);
echo 'Página no encontrada.';
