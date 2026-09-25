<?php

declare(strict_types=1);

function database(): PDO
{
    $socket = getenv('PERSONAL_DB_SOCKET');
    $name = getenv('PERSONAL_DB_NAME') ?: 'sistema_residencia';
    $host = getenv('PERSONAL_DB_HOST') ?: '127.0.0.1';
    $port = getenv('PERSONAL_DB_PORT') ?: '3306';

    $dsn = $socket
        ? "mysql:unix_socket=$socket;dbname=$name;charset=utf8mb4"
        : "mysql:host=$host;port=$port;dbname=$name;charset=utf8mb4";

    return new PDO(
        $dsn,
        getenv('PERSONAL_DB_USER') ?: 'residencia_app',
        getenv('PERSONAL_DB_PASSWORD') ?: 'Residencia2026*',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
}
