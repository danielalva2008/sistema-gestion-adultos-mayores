<?php

require_once __DIR__ . '/../modelos/Usuario.php';

header('Content-Type: application/json; charset=utf-8');

function responderJson(int $codigo, array $respuesta): void
{
    http_response_code($codigo);
    echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);
    exit;
}

function longitudTexto(string $texto): int
{
    return function_exists('mb_strlen')
        ? mb_strlen($texto, 'UTF-8')
        : strlen($texto);
}

function textoPost(string $campo): string
{
    $valor = $_POST[$campo] ?? '';
    return is_string($valor) ? trim($valor) : '';
}

$metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if (!in_array($metodo, ['GET', 'POST'], true)) {
    responderJson(405, [
        'ok' => false,
        'mensaje' => 'Solicitud no válida.'
    ]);
}

$accionRaw = $metodo === 'GET'
    ? ($_GET['accion'] ?? '')
    : ($_POST['accion'] ?? '');

if (!is_string($accionRaw)) {
    responderJson(400, [
        'ok' => false,
        'mensaje' => 'Acción no válida.'
    ]);
}

$accion = trim($accionRaw);

try {
    if ($metodo === 'GET' && $accion === 'roles') {
        $modelo = new Usuario();

        responderJson(200, [
            'ok' => true,
            'exito' => true,
            'roles' => $modelo->obtenerRolesActivos()
        ]);
    }

    if ($metodo === 'GET' && $accion === 'listar') {
        $textoRaw = $_GET['texto'] ?? '';
        $estadoRaw = $_GET['estado'] ?? 'TODOS';

        if (!is_string($textoRaw) || !is_string($estadoRaw)) {
            responderJson(400, [
                'ok' => false,
                'mensaje' => 'Solicitud no válida.'
            ]);
        }

        $textoBusqueda = trim($textoRaw);
        $estadoBusqueda = strtoupper(trim($estadoRaw));

        if (!in_array($estadoBusqueda, ['TODOS', 'ACTIVO', 'INACTIVO'], true)) {
            responderJson(400, [
                'ok' => false,
                'mensaje' => 'Seleccione un estado válido.'
            ]);
        }

        $modelo = new Usuario();
        $usuarios = $modelo->listar($textoBusqueda, $estadoBusqueda);

        responderJson(200, [
            'ok' => true,
            'datos' => $usuarios,
            'mensaje' => empty($usuarios)
                ? 'No se encontraron usuarios con los criterios indicados.'
                : ''
        ]);
    }

    if ($metodo === 'GET' && $accion === 'obtener') {
        $idRaw = $_GET['id_usuario'] ?? null;
        $idUsuario = filter_var($idRaw, FILTER_VALIDATE_INT);

        if ($idUsuario === false || $idUsuario === null || $idUsuario <= 0) {
            responderJson(404, [
                'ok' => false,
                'mensaje' => 'No se encontró el usuario solicitado.'
            ]);
        }

        $modelo = new Usuario();
        $usuario = $modelo->buscarPorId($idUsuario);

        if (!$usuario) {
            responderJson(404, [
                'ok' => false,
                'mensaje' => 'No se encontró el usuario solicitado.'
            ]);
        }

        responderJson(200, [
            'ok' => true,
            'mensaje' => '',
            'usuario' => $usuario
        ]);
    }

    if ($metodo === 'POST' && $accion === 'registrar') {
        $nombres = textoPost('nombres');
        $apellidos = textoPost('apellidos');
        $email = textoPost('email');
        $username = textoPost('username');
        $idRolTexto = textoPost('id_rol');

        $passwordRecibida = $_POST['password'] ?? '';
        $password = is_string($passwordRecibida) ? $passwordRecibida : '';

        if (trim($password) === '') {
            responderJson(400, [
                'ok' => false,
                'exito' => false,
                'mensaje' => 'La contraseña es obligatoria al registrar un usuario.'
            ]);
        }

        if ($nombres === '' || $apellidos === '' || $username === '') {
            responderJson(400, [
                'ok' => false,
                'exito' => false,
                'mensaje' => 'Complete los campos obligatorios.'
            ]);
        }

        $idRol = filter_var($idRolTexto, FILTER_VALIDATE_INT);

        if ($idRol === false || $idRol < 1) {
            responderJson(400, [
                'ok' => false,
                'exito' => false,
                'mensaje' => 'Seleccione un rol válido.'
            ]);
        }

        if (longitudTexto($username) > 50) {
            responderJson(400, [
                'ok' => false,
                'exito' => false,
                'mensaje' => 'El username no puede tener más de 50 caracteres.'
            ]);
        }

        if (longitudTexto($nombres) > 100 || longitudTexto($apellidos) > 100) {
            responderJson(400, [
                'ok' => false,
                'exito' => false,
                'mensaje' => 'Nombres y apellidos no pueden superar los 100 caracteres.'
            ]);
        }

        if (longitudTexto($email) > 120) {
            responderJson(400, [
                'ok' => false,
                'exito' => false,
                'mensaje' => 'El email no puede tener más de 120 caracteres.'
            ]);
        }

        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            responderJson(400, [
                'ok' => false,
                'exito' => false,
                'mensaje' => 'Ingrese un email válido.'
            ]);
        }

        $modelo = new Usuario();

        if ($modelo->usernameExiste($username)) {
            responderJson(400, [
                'ok' => false,
                'exito' => false,
                'mensaje' => 'El username ya está registrado.'
            ]);
        }

        if ($email !== '' && $modelo->emailExiste($email)) {
            responderJson(400, [
                'ok' => false,
                'exito' => false,
                'mensaje' => 'El email ya está registrado.'
            ]);
        }

        if (!$modelo->rolActivo($idRol)) {
            responderJson(400, [
                'ok' => false,
                'exito' => false,
                'mensaje' => 'Seleccione un rol válido.'
            ]);
        }

        $registrado = $modelo->registrar([
            'nombres' => $nombres,
            'apellidos' => $apellidos,
            'email' => $email,
            'username' => $username,
            'password' => $password,
            'id_rol' => $idRol
        ]);

        if (!$registrado) {
            responderJson(500, [
                'ok' => false,
                'exito' => false,
                'mensaje' => 'No se pudo completar la operación. Intente nuevamente.'
            ]);
        }

        responderJson(200, [
            'ok' => true,
            'exito' => true,
            'mensaje' => 'Usuario registrado correctamente.'
        ]);
    }

    if ($metodo === 'POST' && $accion === 'actualizar') {
        $idRaw = $_POST['id_usuario'] ?? null;
        $idRolRaw = $_POST['id_rol'] ?? null;

        $idUsuario = filter_var($idRaw, FILTER_VALIDATE_INT);
        $idRol = filter_var($idRolRaw, FILTER_VALIDATE_INT);

        $username = textoPost('username');
        $nombres = textoPost('nombres');
        $apellidos = textoPost('apellidos');
        $email = textoPost('email');
        $estado = strtoupper(textoPost('estado'));

        $passwordRaw = $_POST['nueva_password'] ?? '';
        $nuevaPassword = is_string($passwordRaw) ? $passwordRaw : '';

        if (
            $idUsuario === false || $idUsuario === null || $idUsuario <= 0 ||
            $idRol === false || $idRol === null || $idRol <= 0 ||
            $username === '' || $nombres === '' ||
            $apellidos === '' || $estado === ''
        ) {
            responderJson(400, [
                'ok' => false,
                'mensaje' => 'Complete los campos obligatorios.'
            ]);
        }

        if (
            longitudTexto($username) > 50 ||
            longitudTexto($nombres) > 100 ||
            longitudTexto($apellidos) > 100
        ) {
            responderJson(400, [
                'ok' => false,
                'mensaje' => 'Revise la longitud de los campos ingresados.'
            ]);
        }

        if ($email !== '' && longitudTexto($email) > 120) {
            responderJson(400, [
                'ok' => false,
                'mensaje' => 'Ingrese un email válido.'
            ]);
        }

        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            responderJson(400, [
                'ok' => false,
                'mensaje' => 'Ingrese un email válido.'
            ]);
        }

        if (!in_array($estado, ['ACTIVO', 'INACTIVO'], true)) {
            responderJson(400, [
                'ok' => false,
                'mensaje' => 'Seleccione un estado válido.'
            ]);
        }

        $modelo = new Usuario();
        $usuarioActual = $modelo->buscarPorId($idUsuario);

        if (!$usuarioActual) {
            responderJson(404, [
                'ok' => false,
                'mensaje' => 'No se encontró el usuario solicitado.'
            ]);
        }

        if ($modelo->usernameExiste($username, $idUsuario)) {
            responderJson(400, [
                'ok' => false,
                'mensaje' => 'El username ya está registrado.'
            ]);
        }

        if ($email !== '' && $modelo->emailExiste($email, $idUsuario)) {
            responderJson(400, [
                'ok' => false,
                'mensaje' => 'El email ya está registrado.'
            ]);
        }

        if (!$modelo->rolActivo($idRol)) {
            responderJson(400, [
                'ok' => false,
                'mensaje' => 'Seleccione un rol válido.'
            ]);
        }

        $actualizado = $modelo->actualizar(
            $idUsuario,
            $idRol,
            $username,
            $nombres,
            $apellidos,
            $email === '' ? null : $email,
            $estado,
            $nuevaPassword
        );

        if (!$actualizado) {
            responderJson(500, [
                'ok' => false,
                'mensaje' => 'No se pudo completar la operación. Intente nuevamente.'
            ]);
        }

        responderJson(200, [
            'ok' => true,
            'mensaje' => 'Usuario actualizado correctamente.'
        ]);
    }

    responderJson(400, [
        'ok' => false,
        'mensaje' => 'Acción no válida.'
    ]);

} catch (Throwable $error) {
    error_log($error->getMessage());

    responderJson(500, [
        'ok' => false,
        'mensaje' => 'No se pudo completar la operación. Intente nuevamente.'
    ]);
}
