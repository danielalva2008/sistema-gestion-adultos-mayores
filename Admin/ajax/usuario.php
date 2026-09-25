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

/* El formulario pedirá los roles activos con:
   Admin/ajax/usuario.php?accion=roles
*/
if ($metodo === 'GET' && ($_GET['accion'] ?? '') === 'roles') {
    try {
        $modelo = new Usuario();

        responderJson(200, [
            'exito' => true,
            'roles' => $modelo->obtenerRolesActivos()
        ]);
    } catch (Throwable $error) {
        error_log($error->getMessage());

        responderJson(500, [
            'exito' => false,
            'mensaje' => 'No se pudo cargar la lista de roles.'
        ]);
    }
}

if ($metodo !== 'POST') {
    responderJson(405, [
        'exito' => false,
        'mensaje' => 'Solicitud no válida.'
    ]);
}

if (($_POST['accion'] ?? '') !== 'registrar') {
    responderJson(400, [
        'exito' => false,
        'mensaje' => 'Acción no válida.'
    ]);
}

$nombres = textoPost('nombres');
$apellidos = textoPost('apellidos');
$email = textoPost('email');
$username = textoPost('username');
$idRolTexto = textoPost('id_rol');

$passwordRecibida = $_POST['password'] ?? '';
$password = is_string($passwordRecibida) ? $passwordRecibida : '';

if (trim($password) === '') {
    responderJson(400, [
        'exito' => false,
        'mensaje' => 'La contraseña es obligatoria al registrar un usuario.'
    ]);
}

if ($nombres === '' || $apellidos === '' || $username === '') {
    responderJson(400, [
        'exito' => false,
        'mensaje' => 'Complete los campos obligatorios.'
    ]);
}

if ($idRolTexto === '') {
    responderJson(400, [
        'exito' => false,
        'mensaje' => 'Seleccione un rol válido.'
    ]);
}

if (longitudTexto($username) > 50) {
    responderJson(400, [
        'exito' => false,
        'mensaje' => 'El username no puede tener más de 50 caracteres.'
    ]);
}

if (longitudTexto($nombres) > 100 || longitudTexto($apellidos) > 100) {
    responderJson(400, [
        'exito' => false,
        'mensaje' => 'Nombres y apellidos no pueden superar los 100 caracteres.'
    ]);
}

if (longitudTexto($email) > 120) {
    responderJson(400, [
        'exito' => false,
        'mensaje' => 'El email no puede tener más de 120 caracteres.'
    ]);
}

if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
    responderJson(400, [
        'exito' => false,
        'mensaje' => 'Ingrese un email válido.'
    ]);
}

$idRol = filter_var($idRolTexto, FILTER_VALIDATE_INT);

if ($idRol === false || $idRol < 1) {
    responderJson(400, [
        'exito' => false,
        'mensaje' => 'Seleccione un rol válido.'
    ]);
}

try {
    $modelo = new Usuario();

    if ($modelo->usernameExiste($username)) {
        responderJson(400, [
            'exito' => false,
            'mensaje' => 'El username ya está registrado.'
        ]);
    }

    if ($email !== '' && $modelo->emailExiste($email)) {
        responderJson(400, [
            'exito' => false,
            'mensaje' => 'El email ya está registrado.'
        ]);
    }

    if (!$modelo->rolActivo($idRol)) {
        responderJson(400, [
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
            'exito' => false,
            'mensaje' => 'No se pudo completar la operación. Intente nuevamente.'
        ]);
    }

    responderJson(200, [
        'exito' => true,
        'mensaje' => 'Usuario registrado correctamente.'
    ]);
} catch (Throwable $error) {
    error_log($error->getMessage());

    responderJson(500, [
        'exito' => false,
        'mensaje' => 'No se pudo completar la operación. Intente nuevamente.'
    ]);
}