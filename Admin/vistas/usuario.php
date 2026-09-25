<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar usuario</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f6fa;
            margin: 0;
            padding: 30px;
        }

        .contenedor {
            max-width: 700px;
            margin: auto;
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }

        h1 {
            margin-top: 0;
        }

        .campo {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        input,
        select {
            width: 100%;
            box-sizing: border-box;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        button {
            padding: 10px 18px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            background: #0d6efd;
            color: white;
        }

        button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        #mensaje {
            display: none;
            margin-bottom: 18px;
            padding: 12px;
            border-radius: 5px;
        }

        .mensaje-ok {
            display: block !important;
            background: #d1e7dd;
            color: #0f5132;
        }

        .mensaje-error {
            display: block !important;
            background: #f8d7da;
            color: #842029;
        }

        .ayuda {
            font-size: 13px;
            color: #666;
            margin-top: 5px;
        }
    </style>
</head>

<body>

<div class="contenedor">

    <h1>Editar usuario</h1>

    <div id="mensaje"></div>

    <form id="formUsuario">

        <input
            type="hidden"
            id="id_usuario"
            name="id_usuario"
        >

        <div class="campo">
            <label for="nombres">
                Nombres *
            </label>

            <input
                type="text"
                id="nombres"
                name="nombres"
                maxlength="100"
                required
            >
        </div>

        <div class="campo">
            <label for="apellidos">
                Apellidos *
            </label>

            <input
                type="text"
                id="apellidos"
                name="apellidos"
                maxlength="100"
                required
            >
        </div>

        <div class="campo">
            <label for="username">
                Username *
            </label>

            <input
                type="text"
                id="username"
                name="username"
                maxlength="50"
                required
            >
        </div>

        <div class="campo">
            <label for="email">
                Email
            </label>

            <input
                type="email"
                id="email"
                name="email"
                maxlength="120"
            >
        </div>

        <div class="campo">
            <label for="nueva_password">
                Nueva contraseña
            </label>

            <input
                type="password"
                id="nueva_password"
                name="nueva_password"
                autocomplete="new-password"
            >

            <div class="ayuda">
                Déjela vacía para conservar la contraseña actual.
            </div>
        </div>

        <div class="campo">
            <label for="id_rol">
                Rol *
            </label>

            <select
                id="id_rol"
                name="id_rol"
                required
            >
                <option value="">
                    Cargando roles...
                </option>
            </select>
        </div>

        <div class="campo">
            <label for="estado">
                Estado *
            </label>

            <select
                id="estado"
                name="estado"
                required
            >
                <option value="ACTIVO">
                    ACTIVO
                </option>

                <option value="INACTIVO">
                    INACTIVO
                </option>
            </select>
        </div>

        <button
            type="submit"
            id="btnGuardar"
        >
            Guardar cambios
        </button>

    </form>

</div>


<script>

const formulario = document.getElementById('formUsuario');
const mensaje = document.getElementById('mensaje');
const btnGuardar = document.getElementById('btnGuardar');

const parametrosURL =
    new URLSearchParams(window.location.search);

const idUsuario =
    parametrosURL.get('id_usuario');


function mostrarMensaje(texto, tipo)
{
    mensaje.textContent = texto;

    mensaje.className =
        tipo === 'ok'
            ? 'mensaje-ok'
            : 'mensaje-error';
}


async function cargarRoles()
{
    try {

        const respuesta = await fetch(
            '../ajax/usuario.php?accion=roles'
        );

        const datos = await respuesta.json();

        if (!datos.ok) {
            mostrarMensaje(
                datos.mensaje ||
                'No se pudo cargar la lista de roles.',
                'error'
            );

            return false;
        }

        const selectRol =
            document.getElementById('id_rol');

        selectRol.innerHTML =
            '<option value="">Seleccione un rol</option>';

        datos.roles.forEach(function(rol) {

            const opcion =
                document.createElement('option');

            opcion.value = rol.id_rol;
            opcion.textContent = rol.nombre;

            selectRol.appendChild(opcion);
        });

        return true;

    } catch (error) {

        mostrarMensaje(
            'No se pudo cargar la lista de roles.',
            'error'
        );

        return false;
    }
}


async function cargarUsuario()
{
    if (!idUsuario) {

        mostrarMensaje(
            'No se encontró el usuario solicitado.',
            'error'
        );

        btnGuardar.disabled = true;

        return;
    }

    try {

        const respuesta = await fetch(
            '../ajax/usuario.php?accion=obtener&id_usuario='
            + encodeURIComponent(idUsuario)
        );

        const datos = await respuesta.json();

        if (!datos.ok) {

            mostrarMensaje(
                datos.mensaje,
                'error'
            );

            btnGuardar.disabled = true;

            return;
        }

        const usuario = datos.usuario;

        document.getElementById('id_usuario').value =
            usuario.id_usuario;

        document.getElementById('nombres').value =
            usuario.nombres ?? '';

        document.getElementById('apellidos').value =
            usuario.apellidos ?? '';

        document.getElementById('username').value =
            usuario.username ?? '';

        document.getElementById('email').value =
            usuario.email ?? '';

        document.getElementById('id_rol').value =
            usuario.id_rol;

        document.getElementById('estado').value =
            usuario.estado;

        /*
         * Nunca se precarga una contraseña.
         */
        document.getElementById('nueva_password').value =
            '';
            btnGuardar.disabled = false;

    } catch (error) {

        mostrarMensaje(
            'No se pudo completar la operación. Intente nuevamente.',
            'error'
        );

        btnGuardar.disabled = true;
    }
}


formulario.addEventListener(
    'submit',
    async function(evento)
    {
        evento.preventDefault();

        btnGuardar.disabled = true;

        const datosFormulario =
            new FormData(formulario);

        datosFormulario.append(
            'accion',
            'actualizar'
        );

        try {

            const respuesta = await fetch(
                '../ajax/usuario.php',
                {
                    method: 'POST',
                    body: datosFormulario
                }
            );

            const datos = await respuesta.json();

            if (datos.ok) {

                mostrarMensaje(
                    datos.mensaje,
                    'ok'
                );

                /*
                 * Después de actualizar,
                 * la contraseña vuelve a quedar vacía.
                 */
                document.getElementById(
                    'nueva_password'
                ).value = '';

            } else {

                mostrarMensaje(
                    datos.mensaje,
                    'error'
                );
            }

        } catch (error) {

            mostrarMensaje(
                'No se pudo completar la operación. Intente nuevamente.',
                'error'
            );

        } finally {

            btnGuardar.disabled = false;
        }
    }
);


async function iniciar()
{
    btnGuardar.disabled = true;

    const rolesCargados =
        await cargarRoles();

    if (!rolesCargados) {
        return;
    }

    await cargarUsuario();
}


iniciar();

</script>

</body>
</html>