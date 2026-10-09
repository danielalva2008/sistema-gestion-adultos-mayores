<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Gestión de Usuarios</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: #f4f6f9;
            color: #222;
        }

        .contenedor {
            width: min(1200px, 94%);
            margin: 40px auto;
        }

        .cabecera {
            margin-bottom: 25px;
        }

        .cabecera h1 {
            margin-bottom: 8px;
            font-size: 28px;
        }

        .cabecera p {
            margin: 0;
            color: #666;
        }

        .panel {
            background: #ffffff;
            border-radius: 10px;
            padding: 22px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .filtros {
            display: grid;
            grid-template-columns: 1fr 200px auto auto;
            gap: 12px;
            margin-bottom: 22px;
        }

        .campo {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        label {
            font-size: 14px;
            font-weight: bold;
        }

        input,
        select,
        button {
            min-height: 40px;
            border-radius: 6px;
            font-size: 14px;
        }

        input,
        select {
            border: 1px solid #cfd4da;
            padding: 8px 10px;
            background: white;
        }

        input:focus,
        select:focus {
            outline: none;
            border-color: #555;
        }

        button {
            align-self: end;
            border: none;
            padding: 9px 18px;
            cursor: pointer;
        }

        #btnBuscar {
            background: #202938;
            color: white;
        }

        #btnLimpiar {
            background: #e4e7eb;
            color: #222;
        }

        button:hover {
            opacity: 0.9;
        }

        .tabla-contenedor {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 850px;
        }

        thead {
            background: #202938;
            color: white;
        }

        th,
        td {
            padding: 12px 10px;
            border-bottom: 1px solid #e3e6ea;
            text-align: left;
            font-size: 14px;
        }

        tbody tr:hover {
            background: #f7f8fa;
        }

        .estado {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }

        .estado-activo {
            background: #e6f4ea;
            color: #176b32;
        }

        .estado-inactivo {
            background: #fce8e6;
            color: #a12622;
        }

        .mensaje {
            display: none;
            margin-bottom: 18px;
            padding: 12px;
            border-radius: 6px;
        }

        .mensaje-info {
            display: block;
            background: #e8f0fe;
            color: #244b87;
        }

        .mensaje-error {
            display: block;
            background: #fce8e6;
            color: #a12622;
        }

        .resumen {
            margin-top: 15px;
            color: #666;
            font-size: 13px;
        }

        @media (max-width: 800px) {
            .filtros {
                grid-template-columns: 1fr;
            }

            button {
                width: 100%;
            }
        }
    </style>
</head>

<body>

    <main class="contenedor">

        <header class="cabecera">
            <h1>Gestión de Usuarios</h1>
            <p>Listado y búsqueda de usuarios registrados en el sistema.</p>
            <p style="margin-top:15px;">
            <a href="usuario_registro.php">Registrar nuevo usuario</a>
        </p>
        </header>

        <section class="panel">

            <form id="formFiltros" class="filtros">

                <div class="campo">
                    <label for="texto">Buscar usuario</label>

                    <input
                        type="text"
                        id="texto"
                        name="texto"
                        placeholder="Nombre, apellido o username">
                </div>

                <div class="campo">
                    <label for="estado">Estado</label>

                    <select id="estado" name="estado">
                        <option value="TODOS">Todos</option>
                        <option value="ACTIVO">Activo</option>
                        <option value="INACTIVO">Inactivo</option>
                    </select>
                </div>

                <button type="submit" id="btnBuscar">
                    Buscar
                </button>

                <button type="button" id="btnLimpiar">
                    Limpiar
                </button>

            </form>

            <div id="mensaje" class="mensaje"></div>

            <div class="tabla-contenedor">

                <table>
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Nombres</th>
                            <th>Apellidos</th>
                            <th>Email</th>
                            <th>Rol</th>
                            <th>Estado</th>
                            <th>Fecha de creación</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>

                    <tbody id="tablaUsuarios">
                    </tbody>

                </table>

            </div>

            <div id="resumen" class="resumen"></div>

        </section>

    </main>

    <script>

        const formFiltros = document.getElementById('formFiltros');
        const inputTexto = document.getElementById('texto');
        const selectEstado = document.getElementById('estado');

        const tablaUsuarios = document.getElementById('tablaUsuarios');
        const mensaje = document.getElementById('mensaje');
        const resumen = document.getElementById('resumen');

        const btnLimpiar = document.getElementById('btnLimpiar');


        /**
         * Muestra un mensaje funcional al usuario.
         */
        function mostrarMensaje(texto, tipo = 'info') {

            mensaje.textContent = texto;

            mensaje.className =
                tipo === 'error'
                    ? 'mensaje mensaje-error'
                    : 'mensaje mensaje-info';
        }


        /**
         * Oculta los mensajes anteriores.
         */
        function ocultarMensaje() {

            mensaje.textContent = '';
            mensaje.className = 'mensaje';
        }


        /**
         * Crea una celda de tabla usando textContent.
         */
        function crearCelda(valor) {

            const td = document.createElement('td');

            td.textContent =
                valor === null || valor === ''
                    ? '—'
                    : valor;

            return td;
        }


        /**
         * Dibuja los usuarios recibidos desde el backend.
         */
        function mostrarUsuarios(usuarios) {

            tablaUsuarios.innerHTML = '';

            usuarios.forEach(usuario => {

                const fila = document.createElement('tr');

                fila.appendChild(crearCelda(usuario.username));
                fila.appendChild(crearCelda(usuario.nombres));
                fila.appendChild(crearCelda(usuario.apellidos));
                fila.appendChild(crearCelda(usuario.email));
                fila.appendChild(crearCelda(usuario.rol));

                const celdaEstado = document.createElement('td');
                const etiquetaEstado = document.createElement('span');

                etiquetaEstado.textContent = usuario.estado;
                etiquetaEstado.classList.add('estado');

                if (usuario.estado === 'ACTIVO') {
                    etiquetaEstado.classList.add('estado-activo');
                } else {
                    etiquetaEstado.classList.add('estado-inactivo');
                }

                celdaEstado.appendChild(etiquetaEstado);
                fila.appendChild(celdaEstado);

                fila.appendChild(
                    crearCelda(usuario.fecha_creacion)
                );

                const celdaAcciones = document.createElement('td');
                const enlaceEditar = document.createElement('a');
                enlaceEditar.href =
                    'usuario_editar.php?id_usuario='
                    + encodeURIComponent(usuario.id_usuario);
                enlaceEditar.textContent = 'Editar';
                celdaAcciones.appendChild(enlaceEditar);

                celdaAcciones.appendChild(
                    document.createTextNode(' · ')
                );

                const botonEstado = document.createElement('button');
                botonEstado.type = 'button';

                const nuevoEstado =
                    usuario.estado === 'ACTIVO'
                        ? 'INACTIVO'
                        : 'ACTIVO';

                botonEstado.textContent =
                    nuevoEstado === 'INACTIVO'
                        ? 'Inactivar'
                        : 'Reactivar';

                botonEstado.addEventListener('click', function () {
                    cambiarEstado(usuario.id_usuario, nuevoEstado);
                });

                celdaAcciones.appendChild(botonEstado);
                fila.appendChild(celdaAcciones);

                tablaUsuarios.appendChild(fila);
            });

            resumen.textContent =
                `Registros encontrados: ${usuarios.length}`;
        }


        /**
         * Solicita usuarios al endpoint AJAX.
         */
        async function cargarUsuarios() {

            ocultarMensaje();

            const texto = inputTexto.value.trim();
            const estado = selectEstado.value;

            const parametros = new URLSearchParams({
                accion: 'listar',
                texto: texto,
                estado: estado
            });

            try {

                const respuesta = await fetch(
                    `../ajax/usuario.php?${parametros.toString()}`
                );

                const resultado = await respuesta.json();

                if (!respuesta.ok || !resultado.ok) {

                    tablaUsuarios.innerHTML = '';
                    resumen.textContent = '';

                    mostrarMensaje(
                        resultado.mensaje ||
                        'No se pudo completar la operación. Intente nuevamente.',
                        'error'
                    );

                    return;
                }

                mostrarUsuarios(resultado.datos);

                if (resultado.datos.length === 0) {

                    mostrarMensaje(
                        resultado.mensaje ||
                        'No se encontraron usuarios con los criterios indicados.'
                    );
                }

            } catch (error) {

                tablaUsuarios.innerHTML = '';
                resumen.textContent = '';

                mostrarMensaje(
                    'No se pudo completar la operación. Intente nuevamente.',
                    'error'
                );
            }
        }


        /**
         * Buscar.
         */
        formFiltros.addEventListener('submit', function (evento) {

            evento.preventDefault();

            cargarUsuarios();
        });


        /**
         * Limpiar filtros y volver a mostrar todos.
         */
        btnLimpiar.addEventListener('click', function () {

            inputTexto.value = '';
            selectEstado.value = 'TODOS';

            cargarUsuarios();
        });


        /**
         * Al abrir la página se muestran todos los usuarios.
         */
        document.addEventListener('DOMContentLoaded', function () {

            cargarUsuarios();
        });

    </script>

    <script>
        async function cambiarEstado(idUsuario, estado) {

            const accionTexto = estado === 'INACTIVO'
                ? 'inactivar'
                : 'reactivar';

            const confirmar = confirm(
                '¿Está seguro de ' + accionTexto + ' este usuario?'
            );

            if (!confirmar) {
                return;
            }

            const datos = new URLSearchParams();

            datos.append('accion', 'cambiar_estado');
            datos.append('id_usuario', idUsuario);
            datos.append('estado', estado);

            try {

                const respuesta = await fetch('../ajax/usuario.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: datos.toString()
                });

                const resultado = await respuesta.json();

                alert(resultado.mensaje);

                if (resultado.ok) {
                    location.reload();
                }

            } catch (error) {

                alert(
                    'No se pudo completar la operación. Intente nuevamente.'
                );
            }
        }
    </script>

</body>

</html>