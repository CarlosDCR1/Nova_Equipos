<?php

require_once "api/core/Sesion.php";

// Si ya tiene sesión iniciada, lo envía al sistema.
Sesion::redirigirSiAutenticado();

// Token interno para proteger el envío del formulario.
if (empty($_SESSION['tokenRegistro'])) {
    $_SESSION['tokenRegistro'] = bin2hex(random_bytes(32));
}

header("Cache-Control: no-store");

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Crear usuario de estudiante - Nova Equipos</title>

    <link rel="stylesheet" href="css/login.css?v=<?= filemtime(__DIR__ . '/css/login.css') ?>">

<link rel="stylesheet" href="css/registro.css?v=<?= filemtime(__DIR__ . '/css/registro.css') ?>">
</head>

<body>

    <main class="login-card registro-card">

        <h1>Nova Equipos</h1>

        <h2>Crear usuario de estudiante</h2>

        <p class="subtitulo">
            Registro exclusivo para estudiantes registrados
            en la institución.
        </p>

        <form id="formRegistro">

            <input
                type="hidden"
                name="tokenRegistro"
                value="<?= htmlspecialchars(
                    $_SESSION['tokenRegistro'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
            >

            <label for="identificacion">Carné:</label>

            <input
                type="text"
                id="identificacion"
                name="identificacion"
                placeholder="0000-00-0000"
                pattern="[0-9]{4}-[0-9]{2}-[0-9]{4}"
                maxlength="12"
                required
                aria-describedby="ayudaIdentificacion"
            >

            <small id="ayudaIdentificacion">
                Use su carné con el formato XXXX-XX-XXXX.
            </small>

            <label for="correo">
                Correo institucional registrado:
            </label>

            <input
                type="email"
                id="correo"
                name="correo"
                maxlength="150"
                autocomplete="email"
                required
            >

            <label for="nombreUsuario">
                Nombre de usuario:
            </label>

            <input
                type="text"
                id="nombreUsuario"
                name="nombreUsuario"
                minlength="4"
                maxlength="50"
                pattern="[A-Za-z0-9._\-]{4,50}"
                autocomplete="username"
                required
                aria-describedby="ayudaUsuario"
            >

            <small id="ayudaUsuario">
                De 4 a 50 caracteres: letras, números, punto,
                guion o guion bajo.
            </small>

            <div class="registro-fila">

                <div>
                    <label for="contrasenaUsuario">
                        Contraseña:
                    </label>

                    <input
                        type="password"
                        id="contrasenaUsuario"
                        name="contrasenaUsuario"
                        minlength="8"
                        maxlength="72"
                        autocomplete="new-password"
                        required
                        aria-describedby="ayudaClave"
                    >
                </div>

                <div>
                    <label for="confirmarContrasena">
                        Confirmar contraseña:
                    </label>

                    <input
                        type="password"
                        id="confirmarContrasena"
                        name="confirmarContrasena"
                        minlength="8"
                        maxlength="72"
                        autocomplete="new-password"
                        required
                    >
                </div>

            </div>

            <small id="ayudaClave">
                Mínimo 8 caracteres, incluyendo una letra
                y un número.
            </small>

            <label class="check">
                <input type="checkbox" id="verClave">
                Mostrar contraseñas
            </label>

            <p class="aviso-registro">
                Su cuenta quedará activa al crearla.
                Después podrá iniciar sesión con su usuario
                y contraseña.
            </p>

            <p
                id="mensaje"
                class="mensaje"
                role="alert"
                aria-live="polite"
            ></p>

            <button type="submit" id="btnRegistrar">
                Crear usuario
            </button>

        </form>

        <div class="acceso-registro">
            <a class="boton-secundario" href="Login.php">
                Volver al inicio de sesión
            </a>
        </div>

    </main>

    <script src="js/registro.js"></script>

</body>
</html>