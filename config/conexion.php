<?php
/**
 * Conexión PDO - Usuario de aplicación residencia_app
 * Grupo 5 - Módulo Incidentes
 */
declare(strict_types=1);

define('DB_HOST', 'localhost');
define('DB_NAME', 'sistema_residencia');
define('DB_USER', 'residencia_app');
define('DB_PASS', 'Residencia2026*');
define('DB_CHARSET', 'utf8mb4');

function getPDO(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die('<div style="padding:2rem;font-family:sans-serif;color:#b91c1c;">
                <h2>Error de conexión a la base de datos</h2>
                <p>' . htmlspecialchars($e->getMessage()) . '</p>
                <p><strong>Verifica:</strong></p>
                <ul>
                    <li>Que XAMPP (MySQL) esté corriendo</li>
                    <li>Que hayas importado databaseadultomayor.sql</li>
                    <li>Usuario: residencia_app / Contraseña: Residencia2026*</li>
                </ul>
            </div>');
        }
    }
    return $pdo;
}
