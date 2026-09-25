<?php
declare(strict_types=1);
session_name('residencia_grupo3');
session_start(['cookie_httponly' => true, 'cookie_samesite' => 'Lax', 'use_strict_mode' => true]);
header('Content-Type: text/html; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: default-src 'self'; style-src 'self'; img-src 'self' data:; form-action 'self'; frame-ancestors 'none'; base-uri 'self'");
header('Cache-Control: no-store');
$_SESSION['csrf'] ??= bin2hex(random_bytes(32));
function e(mixed $value): string { return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function entrada(array $source, string $key, string $default = ''): string {
    return isset($source[$key]) && is_string($source[$key]) ? trim($source[$key]) : $default;
}
function csrf(): string { return '<input type="hidden" name="csrf" value="'.e($_SESSION['csrf']).'">'; }
function volver(string $mensaje): never {
    $_SESSION['exito'] = $mensaje;
    header('Location: habitaciones.php', true, 303);
    exit;
}
