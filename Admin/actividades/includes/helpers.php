<?php
/**
 * Utilidades transversales del módulo Actividades (Grupo 6).
 *   - Escape XSS, mensajes flash, token CSRF y "ahora" (RN-26).
 * G6 §12.4 (Seguridad), §12.5 (helpers).
 */

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    // $_SESSION se usa SOLO para flash y CSRF, no para autenticar (DEC-G6-10).
    session_start();
}

require_once __DIR__ . '/../config/conexion.php';

/* ============================================================
 * Constantes del dominio (nombres verificados contra database.sql)
 * ============================================================ */

// Valores de asistencia (participaciones.asistencia). V-02.
// El "pendiente" (inscrito sin asistencia marcada) es el valor DEFAULT 'INSCRITO'.
const ASIST_PENDIENTE  = 'INSCRITO';
const ASIST_ASISTIO    = 'ASISTIO';
const ASIST_NO_ASISTIO = 'NO_ASISTIO';

// Estado de baja lógica (actividades.estado). Variante B / V-01.
const ESTADO_CANCELADA = 'CANCELADA';

// Estados temporales calculados (NO son columnas). DEC-G6-02.
const T_PROGRAMADA = 'PROGRAMADA';
const T_EN_CURSO   = 'EN_CURSO';
const T_FINALIZADA = 'FINALIZADA';

/* ============================================================
 * Salida segura y utilidades varias
 * ============================================================ */

/** Escape de salida contra XSS (G6 §12.4). */
function e(?string $valor): string
{
    return htmlspecialchars($valor ?? '', ENT_QUOTES, 'UTF-8');
}

/** "ahora" en America/Lima como 'Y-m-d H:i:s' (RN-26). Fuente única de hora. */
function ahora(): string
{
    return (new DateTimeImmutable('now', new DateTimeZone('America/Lima')))
        ->format('Y-m-d H:i:s');
}

/* ============================================================
 * Mensajes flash (patrón PRG)
 * ============================================================ */

/** Encola un mensaje flash. $tipo: 'exito' | 'error' | 'alerta'. */
function flash(string $tipo, string $texto): void
{
    $_SESSION['flash'][] = ['tipo' => $tipo, 'texto' => $texto];
}

/** Devuelve y limpia todos los mensajes flash pendientes. */
function obtener_flash(): array
{
    $mensajes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $mensajes;
}

/** Redirección PRG: nunca se escribe en BD por GET. */
function redirigir(string $url): void
{
    header('Location: ' . $url);
    exit;
}

/* ============================================================
 * CSRF (G6 §12.4, MSG-E26)
 * ============================================================ */

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

/** Campo oculto listo para incrustar en un <form> POST. */
function csrf_campo(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

/** Valida el token recibido con hash_equals. */
function csrf_valido(): bool
{
    return isset($_POST['csrf'], $_SESSION['csrf'])
        && is_string($_POST['csrf'])
        && hash_equals($_SESSION['csrf'], $_POST['csrf']);
}

/**
 * Exige POST + CSRF válido en un action. Si falla, avisa y redirige.
 */
function exigir_post_csrf(string $url_fallback): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        redirigir($url_fallback);
    }
    if (!csrf_valido()) {
        flash('error', 'La solicitud expiró o no es válida. Recargue la página e intente de nuevo.'); // MSG-E26
        redirigir($url_fallback);
    }
}

/* ============================================================
 * Validación de enteros de ID (G6 §12.4)
 * ============================================================ */

/** Valida un entero >= 1. Devuelve int o null. */
function id_valido($valor): ?int
{
    $id = filter_var($valor, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    return $id === false ? null : $id;
}

/* ============================================================
 * Estado temporal (Glosario §2, DEC-G6-02)
 * ============================================================ */

/**
 * Calcula el estado temporal de una actividad a partir de fecha/horas y $ahora.
 * No considera la cancelación; eso se evalúa aparte (estado === CANCELADA manda).
 */
function estado_temporal(string $fecha, string $hora_inicio, string $hora_fin, ?string $ahora = null): string
{
    $ahora   = $ahora ?? ahora();
    $inicio  = $fecha . ' ' . substr($hora_inicio, 0, 5) . ':00';
    $fin     = $fecha . ' ' . substr($hora_fin, 0, 5) . ':00';

    if ($ahora < $inicio) {
        return T_PROGRAMADA;
    }
    if ($ahora < $fin) {
        return T_EN_CURSO;
    }
    return T_FINALIZADA;
}

/** ¿La actividad está cancelada? (Variante B). */
function esta_cancelada(array $actividad): bool
{
    return ($actividad['estado'] ?? '') === ESTADO_CANCELADA;
}
