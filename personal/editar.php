<?php
require_once __DIR__ . '/../config/conexion.php';

$id = $_GET['id'] ?? 0;

$stmt = $conexion->prepare("SELECT * FROM personal WHERE id_personal = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

$resultado = $stmt->get_result();
$personal = $resultado->fetch_assoc();

if (!$personal) {
    die("Personal no encontrado");
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Personal</title>
</head>

<body>

<h1>Editar Personal</h1>

<form action="actualizar.php" method="POST">

    <input type="hidden" name="id_personal"
           value="<?= $personal['id_personal'] ?>">

    <label>Código:</label><br>
    <input type="text"
           value="<?= htmlspecialchars($personal['codigo_personal']) ?>"
           disabled>
    <br><br>

    <label>Nombres:</label><br>
    <input type="text" name="nombres"
           value="<?= htmlspecialchars($personal['nombres']) ?>"
           required>
    <br><br>

    <label>Apellidos:</label><br>
    <input type="text" name="apellidos"
           value="<?= htmlspecialchars($personal['apellidos']) ?>"
           required>
    <br><br>

    <label>Cargo:</label><br>
    <input type="text" name="cargo"
           value="<?= htmlspecialchars($personal['cargo']) ?>"
           required>
    <br><br>

    <label>Especialidad:</label><br>
    <input type="text" name="especialidad"
           value="<?= htmlspecialchars($personal['especialidad'] ?? '') ?>">
    <br><br>

    <label>Teléfono:</label><br>
    <input type="text" name="telefono"
           value="<?= htmlspecialchars($personal['telefono'] ?? '') ?>">
    <br><br>

    <label>Email:</label><br>
    <input type="email" name="email"
           value="<?= htmlspecialchars($personal['email'] ?? '') ?>">
    <br><br>

    <label>Estado:</label><br>
    <select name="estado">
        <option value="ACTIVO"
            <?= $personal['estado'] === 'ACTIVO' ? 'selected' : '' ?>>
            ACTIVO
        </option>

        <option value="INACTIVO"
            <?= $personal['estado'] === 'INACTIVO' ? 'selected' : '' ?>>
            INACTIVO
        </option>
    </select>

    <br><br>

    <button type="submit">Guardar cambios</button>

</form>

<br>

<a href="index.php">Volver</a>

</body>
</html>