<?php

require_once __DIR__ . '/../config/conexion.php';

$buscar = $_GET['buscar'] ?? '';

$sql = "SELECT * FROM personal
        WHERE codigo_personal LIKE ?
        OR nombres LIKE ?
        OR apellidos LIKE ?
        OR cargo LIKE ?
        OR especialidad LIKE ?
        ORDER BY id_personal DESC";
$termino = "%" . $buscar . "%";

$stmt = $conexion->prepare($sql);

$stmt->bind_param(
    "sssss",
    $termino,
    $termino,
    $termino,
    $termino,
    $termino
);
$stmt->execute();

$resultado = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <title>Gestión de Personal</title>

</head>

<body>

<h1>Gestión de Personal</h1>

<a href="crear.php">+ Registrar personal</a>

<br><br>

<form method="GET" action="index.php">

    <input
        type="text"
        name="buscar"
        placeholder="Buscar por código, nombre o cargo"
        value="<?= htmlspecialchars($buscar) ?>"
    >

    <button type="submit">Buscar</button>

    <a href="index.php">Limpiar</a>

</form>

<br>

<table border="1" cellpadding="10">

    <tr>

        <th>Código</th>
        <th>Nombres</th>
        <th>Apellidos</th>
        <th>Cargo</th>
        <th>Especialidad</th>
        <th>Teléfono</th>
        <th>Email</th>
        <th>Fecha ingreso</th>
        <th>Estado</th>
        <th>Acciones</th>

    </tr>

    <?php while ($fila = $resultado->fetch_assoc()): ?>

        <tr>

            <td>
                <?= htmlspecialchars($fila['codigo_personal']) ?>
            </td>

            <td>
                <?= htmlspecialchars($fila['nombres']) ?>
            </td>

            <td>
                <?= htmlspecialchars($fila['apellidos']) ?>
            </td>

            <td>
                <?= htmlspecialchars($fila['cargo']) ?>
            </td>

            <td>
                <?= htmlspecialchars($fila['especialidad'] ?? '') ?>
            </td>

            <td>
                <?= htmlspecialchars($fila['telefono'] ?? '') ?>
            </td>

            <td>
                <?= htmlspecialchars($fila['email'] ?? '') ?>
            </td>

            <td>
                <?= htmlspecialchars($fila['fecha_ingreso']) ?>
            </td>

            <td>
                <?= htmlspecialchars($fila['estado']) ?>
            </td>

            <td>

                <a href="editar.php?id=<?= $fila['id_personal'] ?>">
                    Editar
                </a>

                <?php if ($fila['estado'] === 'ACTIVO'): ?>

                    |

                    <a
                        href="inactivar.php?id=<?= $fila['id_personal'] ?>"
                        onclick="return confirm('¿Deseas inactivar este personal?');"
                    >
                        Inactivar
                    </a>

                <?php endif; ?>

            </td>

        </tr>

    <?php endwhile; ?>

</table>

</body>

</html>