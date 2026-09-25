<?php

require_once __DIR__ . '/../modelos/Usuario.php';

header('Content-Type: application/json; charset=utf-8');

$usuarioModelo = new Usuario();

function responder(bool $ok, string $mensaje = '', array $datos = []): void
{
    echo json_encode(
        array_merge(
            [
                'ok' => $ok,
                'mensaje' => $mensaje
            ],
            $datos
        ),
        JSON_UNESCAPED_UNICODE
    );

    exit;
}

function longitudTexto(string $texto): int
{
    return function_exists('mb_strlen')
        ? mb_strlen($texto, 'UTF-8')
        : strlen($texto);
}

$accion = $_POST['accion'] ?? $_GET['accion'] ?? '';

try {

    switch ($accion) {

        /*
         * HU-03
         * Precarga los datos actuales del usuario.
         */
        case 'obtener':

            $idUsuario = filter_input(
                INPUT_GET,
                'id_usuario',
                FILTER_VALIDATE_INT
            );

            if (!$idUsuario) {
                responder(
                    false,
                    'No se encontró el usuario solicitado.'
                );
            }

            $usuario = $usuarioModelo->buscarPorId($idUsuario);

            if (!$usuario) {
                responder(
                    false,
                    'No se encontró el usuario solicitado.'
                );
            }

            responder(
                true,
                '',
                [
                    'usuario' => $usuario
                ]
            );

            break;


        /*
         * HU-03
         * Carga solamente los roles activos permitidos.
         */
        case 'roles':

            $roles = $usuarioModelo->listarRolesActivos();

            responder(
                true,
                '',
                [
                    'roles' => $roles
                ]
            );

            break;


        /*
         * HU-03
         * Actualización del usuario.
         */
        case 'actualizar':

            $idUsuario = filter_input(
                INPUT_POST,
                'id_usuario',
                FILTER_VALIDATE_INT
            );

            $idRol = filter_input(
                INPUT_POST,
                'id_rol',
                FILTER_VALIDATE_INT
            );

            $username = trim($_POST['username'] ?? '');
            $nombres = trim($_POST['nombres'] ?? '');
            $apellidos = trim($_POST['apellidos'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $estado = trim($_POST['estado'] ?? '');

            // La contraseña NO se recorta.
            // Vacía significa conservar el hash actual.
            $nuevaPassword = $_POST['nueva_password'] ?? '';

            /*
             * Validación de campos obligatorios.
             */
            if (
                !$idUsuario ||
                !$idRol ||
                $username === '' ||
                $nombres === '' ||
                $apellidos === '' ||
                $estado === ''
            ) {
                responder(
                    false,
                    'Complete los campos obligatorios.'
                );
            }

            /*
             * Comprobar que el usuario realmente exista.
             */
            $usuarioActual = $usuarioModelo->buscarPorId($idUsuario);

            if (!$usuarioActual) {
                responder(
                    false,
                    'No se encontró el usuario solicitado.'
                );
            }

            /*
             * Longitudes definidas por database.sql.
             */
            if (
                longitudTexto($username) > 50 ||
                longitudTexto($nombres) > 100 ||
                longitudTexto($apellidos) > 100
            ) {
                responder(
                    false,
                    'No se pudo completar la operación. Intente nuevamente.'
                );
            }

            /*
             * Username único,
             * excluyendo al propio usuario.
             */
            if ($usuarioModelo->usernameExiste($username, $idUsuario)) {
                responder(
                    false,
                    'El username ya está registrado.'
                );
            }

            /*
             * Email:
             * vacío = NULL.
             */
            if ($email === '') {

                $email = null;

            } else {

                if (longitudTexto($email) > 120) {
                    responder(
                        false,
                        'Ingrese un email válido.'
                    );
                }

                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    responder(
                        false,
                        'Ingrese un email válido.'
                    );
                }

                if ($usuarioModelo->emailExiste($email, $idUsuario)) {
                    responder(
                        false,
                        'El email ya está registrado.'
                    );
                }
            }

            /*
             * El rol debe existir y estar ACTIVO.
             */
            if (!$usuarioModelo->rolActivoExiste($idRol)) {
                responder(
                    false,
                    'Seleccione un rol válido.'
                );
            }

            /*
             * Únicos estados permitidos.
             */
            if (!in_array(
                $estado,
                ['ACTIVO', 'INACTIVO'],
                true
            )) {
                responder(
                    false,
                    'Seleccione un estado válido.'
                );
            }

            /*
             * Actualización.
             *
             * nueva_password vacía:
             * conserva el hash anterior.
             *
             * nueva_password con valor:
             * Usuario.php genera hash nuevo.
             */
            $actualizado = $usuarioModelo->actualizar(
                $idUsuario,
                $idRol,
                $username,
                $nombres,
                $apellidos,
                $email,
                $estado,
                $nuevaPassword
            );

            if (!$actualizado) {
                responder(
                    false,
                    'No se pudo completar la operación. Intente nuevamente.'
                );
            }

            responder(
                true,
                'Usuario actualizado correctamente.'
            );

            break;


        default:

            responder(
                false,
                'No se pudo completar la operación. Intente nuevamente.'
            );
    }

} catch (Throwable $e) {

    if ($accion === 'roles') {

        responder(
            false,
            'No se pudo cargar la lista de roles.'
        );
    }

    responder(
        false,
        'No se pudo completar la operación. Intente nuevamente.'
    );
}