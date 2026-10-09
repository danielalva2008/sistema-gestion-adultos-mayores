<?php
/**
 * Conexión PDO del módulo Actividades (Grupo 6).
 *
 * Requisito de rúbrica (Word §8) y G6 §12.1:
 *   - PDO con consultas preparadas (nunca concatenación).
 *   - Usuario de aplicación residencia_app (NUNCA root).
 *   - EMULATE_PREPARES = false para que los placeholders sean reales.
 *
 * Nota de coordinación (P-04): en main existe Admin/Config/Conexion.php,
 * pero usa mysqli + root + SQL por concatenación, incompatible con los
 * requisitos técnicos (transacciones con FOR UPDATE y preparadas reales).
 * Por eso el Grupo 6 provee su propia conexión PDO. Ver README §Decisiones.
 */

declare(strict_types=1);

// Fuente única de hora (RN-26 / DEC-G6-11): toda "ahora" se calcula en PHP.
date_default_timezone_set('America/Lima');

const DB_HOST = 'localhost';
const DB_NAME = 'sistema_residencia';
const DB_USER = 'residencia_app';
const DB_PASS = 'residencia_app_2024'; // definido en database.sql (CREATE USER)
const DB_CHARSET = 'utf8mb4';

/**
 * Devuelve una única instancia PDO por request (patrón singleton simple).
 */
function conexion(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET);

    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    return $pdo;
}
