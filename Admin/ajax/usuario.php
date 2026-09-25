<?php

require_once __DIR__ . '/../modelos/Usuario.php';

header('Content-Type: application/json; charset=utf-8');

/**
 * Envía una respuesta en formato JSON.
 */
function responderJson(bool $ok, string $mensaje, array $datos = []): void
{
    echo json_encode([
        'ok' => $ok,
        'mensaje' => $mensaje,
        'datos' => $datos
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

/*
 * Recibimos la acción enviada por POST.
 */
$accion = $_POST['accion'] ?? '';

switch ($accion) {

    case 'cambiar_estado':

        /*
         * Recibimos los datos.
         */
        $idUsuario = filter_var(
            $_POST['id_usuario'] ?? null,
            FILTER_VALIDATE_INT
        );

        $estado = strtoupper(trim($_POST['estado'] ?? ''));

        /*
         * Validar estado.
         * Solo se permite ACTIVO o INACTIVO.
         */
        if (!in_array($estado, ['ACTIVO', 'INACTIVO'], true)) {
            responderJson(
                false,
                'Seleccione un estado válido.'
            );
        }

        /*
         * Validar id_usuario.
         */
        if ($idUsuario === false || $idUsuario === null || $idUsuario <= 0) {
            responderJson(
                false,
                'No se encontró el usuario solicitado.'
            );
        }

        try {

            $usuario = new Usuario();

            /*
             * Comprobar que el usuario realmente exista.
             */
            if (!$usuario->existeUsuario($idUsuario)) {
                responderJson(
                    false,
                    'No se encontró el usuario solicitado.'
                );
            }

            /*
             * Cambiar estado.
             */
            $resultado = $usuario->cambiarEstado(
                $idUsuario,
                $estado
            );

            if (!$resultado) {
                responderJson(
                    false,
                    'No se pudo completar la operación. Intente nuevamente.'
                );
            }

            /*
             * Mensaje según el nuevo estado.
             */
            $mensaje = $estado === 'INACTIVO'
                ? 'Usuario inactivado correctamente.'
                : 'Usuario reactivado correctamente.';

            responderJson(
                true,
                $mensaje,
                [
                    'id_usuario' => $idUsuario,
                    'estado' => $estado
                ]
            );

        } catch (Throwable $e) {

            /*
             * No mostramos errores técnicos de MySQL al usuario.
             */
            responderJson(
                false,
                'No se pudo completar la operación. Intente nuevamente.'
            );
        }

        break;

    default:

        responderJson(
            false,
            'No se pudo completar la operación. Intente nuevamente.'
        );
}