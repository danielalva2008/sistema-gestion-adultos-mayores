<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar usuario</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 40px auto;
            padding: 0 20px;
        }

        label {
            display: block;
            margin-top: 15px;
        }

        input, select, button {
            box-sizing: border-box;
            width: 100%;
            padding: 10px;
            margin-top: 5px;
        }

        button {
            margin-top: 20px;
            cursor: pointer;
        }

        #mensaje {
            margin-top: 15px;
        }

        .error {
            color: #b00020;
        }

        .exito {
            color: #147a32;
        }
    </style>
</head>
<body>

    <h1>Registrar usuario</h1>

    <p><a href="usuario.php">← Volver al listado</a></p>

    <form id="formulario-usuario">
        <label for="nombres">Nombres</label>
        <input
            type="text"
            id="nombres"
            name="nombres"
            maxlength="100"
            required
        >

        <label for="apellidos">Apellidos</label>
        <input
            type="text"
            id="apellidos"
            name="apellidos"
            maxlength="100"
            required
        >

        <label for="email">Email (opcional)</label>
        <input
            type="email"
            id="email"
            name="email"
            maxlength="120"
        >

        <label for="username">Username</label>
        <input
            type="text"
            id="username"
            name="username"
            maxlength="50"
            required
        >

        <label for="password">Contraseña</label>
        <input
            type="password"
            id="password"
            name="password"
            autocomplete="new-password"
            required
        >

        <label for="id_rol">Rol</label>
        <select id="id_rol" name="id_rol" required>
            <option value="">Cargando roles...</option>
        </select>

        <button type="submit" id="boton-registrar">
            Registrar usuario
        </button>

        <p id="mensaje" aria-live="polite"></p>
    </form>

    <script>
        const urlAjax = '../ajax/usuario.php';
        const formulario = document.getElementById('formulario-usuario');
        const selectorRol = document.getElementById('id_rol');
        const mensaje = document.getElementById('mensaje');
        const boton = document.getElementById('boton-registrar');

        function mostrarMensaje(texto, esError) {
            mensaje.textContent = texto;
            mensaje.className = esError ? 'error' : 'exito';
        }

        async function cargarRoles() {
            try {
                const respuesta = await fetch(
                    `${urlAjax}?accion=roles`
                );
                const datos = await respuesta.json();

                if (!respuesta.ok || !datos.exito) {
                    throw new Error('No se pudieron cargar los roles.');
                }

                selectorRol.replaceChildren();

                const opcionInicial = document.createElement('option');
                opcionInicial.value = '';
                opcionInicial.textContent = 'Seleccione un rol';
                selectorRol.appendChild(opcionInicial);

                for (const rol of datos.roles) {
                    const opcion = document.createElement('option');
                    opcion.value = rol.id_rol;
                    opcion.textContent = rol.nombre;
                    selectorRol.appendChild(opcion);
                }
            } catch (error) {
                selectorRol.replaceChildren();

                const opcionError = document.createElement('option');
                opcionError.value = '';
                opcionError.textContent = 'No se pudieron cargar los roles';
                selectorRol.appendChild(opcionError);

                mostrarMensaje(
                    'No se pudo cargar la lista de roles.',
                    true
                );
            }
        }

        formulario.addEventListener('submit', async function (evento) {
            evento.preventDefault();
            mostrarMensaje('', false);
            boton.disabled = true;

            const datosFormulario = new FormData(formulario);
            datosFormulario.append('accion', 'registrar');

            try {
                const respuesta = await fetch(urlAjax, {
                    method: 'POST',
                    body: datosFormulario
                });

                const datos = await respuesta.json();

                if (!respuesta.ok || !datos.exito) {
                    mostrarMensaje(
                        datos.mensaje || 'No se pudo completar el registro.',
                        true
                    );
                    return;
                }

                mostrarMensaje(datos.mensaje, false);
                formulario.reset();
            } catch (error) {
                mostrarMensaje(
                    'No se pudo completar la operación. Intente nuevamente.',
                    true
                );
            } finally {
                boton.disabled = false;
            }
        });

        cargarRoles();
    </script>

</body>
</html>