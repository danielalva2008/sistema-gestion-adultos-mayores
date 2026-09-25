<?php

declare(strict_types=1);

session_start(['cookie_httponly' => true, 'cookie_samesite' => 'Lax']);
require __DIR__ . '/config.php';
require __DIR__ . '/model.php';

function h(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function scalar_input(array $source, string $key, string $default = ''): string
{
    return is_string($source[$key] ?? null) ? $source[$key] : $default;
}

$_SESSION['csrf'] ??= bin2hex(random_bytes(32));
$errors = [];
$rows = [];
$db = null;
$fatal = false;
$impact = null;
$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

$search = trim(scalar_input($_GET, 'q'));
$state = scalar_input($_GET, 'estado');
$action = scalar_input($_GET, 'accion', 'lista');
$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: null;
$data = array_fill_keys(array_keys(FIELDS), '');
$data['estado'] = 'ACTIVO';
$data['fecha_ingreso'] = date('Y-m-d');

try {
    $db = database();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = scalar_input($_POST, 'accion');
        $rawId = scalar_input($_POST, 'id');
        $id = filter_var($rawId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: null;

        if (!hash_equals($_SESSION['csrf'], scalar_input($_POST, 'csrf'))) {
            http_response_code(403);
            throw new DomainException('La sesión del formulario venció. Recarga la página e inténtalo de nuevo.');
        }

        if ($action === 'guardar') {
            [$data, $errors] = validate_personal($_POST);
            if ($rawId !== '' && $id === null) {
                $errors[] = 'Identificador inválido.';
            }
            $action = $id ? 'editar' : 'nuevo';
            if (!$errors) {
                personal_save($db, $data, $id);
                $_SESSION['flash'] = $id ? 'Datos actualizados correctamente.' : 'Trabajador registrado correctamente.';
                // Redirigir evita repetir el envío al recargar la página.
                header('Location: index.php', true, 303);
                exit;
            }
        } elseif ($action === 'inactivar' && $id) {
            personal_inactivate($db, $id);
            $_SESSION['flash'] = 'Trabajador inactivado. Sus actividades e incidentes conservan el historial.';
            header('Location: index.php', true, 303);
            exit;
        } else {
            http_response_code(400);
            throw new DomainException('Operación inválida.');
        }
    } elseif (in_array($action, ['editar', 'baja'], true)) {
        $found = $id ? personal_find($db, $id) : null;
        if (!$found) {
            http_response_code(404);
            $action = 'lista';
            throw new DomainException('No se encontró al trabajador.');
        }
        $data = $found;
        if ($action === 'baja') {
            $impact = personal_impact($db, $id);
        }
    }
} catch (DomainException $exception) {
    $errors[] = $exception->getMessage();
} catch (PDOException $exception) {
    error_log('Personal: ' . $exception->getMessage());
    if (($exception->errorInfo[1] ?? null) === 1062) {
        $errors[] = 'El código de personal ya está registrado.';
    } else {
        http_response_code(503);
        $fatal = true;
        $errors[] = 'No se pudo acceder a la base de datos. Verifica que MySQL esté iniciado y que la conexión esté configurada.';
    }
}

if ($db && !$fatal) {
    try {
        $rows = personal_list($db, $search, $state);
    } catch (PDOException $exception) {
        error_log($exception->getMessage());
        $fatal = true;
        http_response_code(503);
        $errors[] = 'No se pudo cargar el directorio de personal.';
    }
}

require __DIR__ . '/view.php';
