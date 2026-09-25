<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar Personal</title>
</head>

<body>

<h1>Registrar Personal</h1>

<form action="guardar.php" method="POST">

    <label>Código:</label><br>
    <input type="text" name="codigo_personal" required>
    <br><br>

    <label>Nombres:</label><br>
    <input type="text" name="nombres" required>
    <br><br>

    <label>Apellidos:</label><br>
    <input type="text" name="apellidos" required>
    <br><br>

    <label>Cargo:</label><br>
    <input type="text" name="cargo" required>
    <br><br>

    <label>Especialidad:</label><br>
    <input type="text" name="especialidad">
    <br><br>

    <label>Teléfono:</label><br>
    <input type="text" name="telefono">
    <br><br>

    <label>Email:</label><br>
    <input type="email" name="email">
    <br><br>

    <label>Fecha de ingreso:</label><br>
    <input type="date" name="fecha_ingreso" required>
    <br><br>

    <button type="submit">Guardar personal</button>

</form>

<br>

<a href="index.php">Volver</a>

</body>
</html>