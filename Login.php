<?php
require_once "api/core/Sesion.php";
Sesion::redirigirSiAutenticado();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso al Sistema - Nova Equipos</title>
    <link rel="stylesheet" href="css/login.css">
</head>
<body>
    <main class="login-card">
        <h1>Nova Equipos</h1>
        <p class="subtitulo">Ingrese sus credenciales para continuar.</p>

        <form id="formLogin" novalidate>
            <label for="usuario">Usuario:</label>
            <input type="text" id="usuario" name="usuario" maxlength="50" autocomplete="username" required>

            <label for="contrasena">Contraseña:</label>
            <input type="password" id="contrasena" name="contrasena" autocomplete="current-password" required>

            <label class="check"><input type="checkbox" id="verClave"> Mostrar contraseña</label>

            <p id="mensaje" class="mensaje" role="alert" aria-live="polite"></p>

            <button type="submit" id="btnIngresar">Ingresar</button>
        </form>
    </main>
    <script src="js/login.js"></script>
</body>
</html>