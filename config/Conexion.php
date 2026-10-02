<?php
declare(strict_types=1);

function conexion(): PDO
{
    static $db = null;
    if ($db instanceof PDO) {
        return $db;
    }
    $archivo = __DIR__.'/database.local.php';
    if (!is_file($archivo)) {
        throw new RuntimeException('Falta configurar la conexión: copia config/database.example.php como config/database.local.php y completa tus datos.');
    }
    $config = require $archivo;
    foreach (['host', 'port', 'database', 'user', 'password'] as $campo) {
        if (!is_array($config) || !isset($config[$campo]) || !is_string($config[$campo])) {
            throw new RuntimeException('La configuración de la base de datos está incompleta. Revisa config/database.local.php.');
        }
    }
    $db = new PDO(
        'mysql:host='.$config['host'].';port='.$config['port'].';dbname='.$config['database'].';charset=utf8mb4',
        $config['user'],
        $config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
         PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
         PDO::ATTR_EMULATE_PREPARES => false]
    );
    return $db;
}
