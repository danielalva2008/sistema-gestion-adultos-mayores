<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuarios</title>
</head>

<body>

    <h1>Módulo de Usuarios</h1>

    <p>Estructura base preparada. CRUD pendiente de implementación.</p>

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