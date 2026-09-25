<?php

require_once __DIR__ . '/../modelos/Usuario.php';

header('Content-Type: application/json; charset=utf-8');

$accion = $_GET['accion'] ?? $_POST['accion'] ?? '';

try {

    switch ($accion) {

        case 'listar':

            $texto = isset($_GET['texto'])
                ? trim((string) $_GET['texto'])
                : '';

            $estado = isset($_GET['estado'])
                ? strtoupper(trim((string) $_GET['estado']))
                : 'TODOS';

            // Estados admitidos por HU-02.
            $estadosPermitidos = ['TODOS', 'ACTIVO', 'INACTIVO'];

            if (!in_array($estado, $estadosPermitidos, true)) {
                http_response_code(400);

                echo json_encode([
                    'ok' => false,
                    'mensaje' => 'Seleccione un estado válido.'
                ], JSON_UNESCAPED_UNICODE);

                exit;
            }

            $usuario = new Usuario();

            $usuarios = $usuario->listar($texto, $estado);

            echo json_encode([
                'ok' => true,
                'datos' => $usuarios,
                'mensaje' => empty($usuarios)
                    ? 'No se encontraron usuarios con los criterios indicados.'
                    : ''
            ], JSON_UNESCAPED_UNICODE);

            break;

        default:

            http_response_code(400);

            echo json_encode([
                'ok' => false,
                'mensaje' => 'Acción no válida.'
            ], JSON_UNESCAPED_UNICODE);

            break;
    }

} catch (InvalidArgumentException $e) {

    http_response_code(400);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'Seleccione un estado válido.'
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {

    http_response_code(500);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'No se pudo completar la operación. Intente nuevamente.'
    ], JSON_UNESCAPED_UNICODE);
}