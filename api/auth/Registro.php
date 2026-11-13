<?php

require_once(__DIR__ . "/../core/Connection.php");
require_once(__DIR__ . "/../core/Response.php");
require_once(__DIR__ . "/../core/validar.php");
require_once(__DIR__ . "/../models/Registro.php");

$response = new Response();

header("Cache-Control: no-store");

// Solo se permite registrar mediante POST.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Allow: POST");

    $response->error(
        "Método no permitido",
        7100,
        405
    );
}

// Una persona con sesión iniciada no puede registrar otra cuenta.
if (!empty($_SESSION['idUsuario'])) {
    $response->error(
        "Cierre su sesión antes de crear otra cuenta",
        7101,
        403
    );
}

// Limita el tamaño del contenido recibido.
$contenido = file_get_contents(
    "php://input",
    false,
    null,
    0,
    4097
);

if ($contenido === false) {
    $response->error(
        "No se pudo leer la solicitud",
        7102,
        400
    );
}

if (strlen($contenido) > 4096) {
    $response->error(
        "Solicitud demasiado grande",
        7102,
        413
    );
}

$input = json_decode($contenido, true);

if (!is_array($input)) {
    $response->error(
        "La solicitud no es válida",
        7102,
        400
    );
}

// Todos estos campos deben existir y contener texto.
$campos = [
    'identificacion',
    'correo',
    'nombreUsuario',
    'contrasenaUsuario',
    'confirmarContrasena',
    'tokenRegistro'
];

foreach ($campos as $campo) {
    if (
        !isset($input[$campo]) ||
        !is_string($input[$campo])
    ) {
        $response->error(
            "Complete todos los campos del formulario",
            7102,
            400
        );
    }
}

// Comprueba el token generado por la página de registro.
if (
    empty($_SESSION['tokenRegistro']) ||
    !hash_equals(
        $_SESSION['tokenRegistro'],
        $input['tokenRegistro']
    )
) {
    $response->error(
        "Recargue la página de registro e intente nuevamente",
        7103,
        403
    );
}

// Máximo de cinco envíos cada quince minutos por sesión.
$ahora = time();

$intentos = array_filter(
    $_SESSION['intentosRegistro'] ?? [],
    fn($tiempo) => $tiempo > $ahora - 900
);

if (count($intentos) >= 5) {
    header("Retry-After: 900");

    $response->error(
        "Demasiadas solicitudes. Espere 15 minutos",
        7104,
        429
    );
}

$intentos[] = $ahora;

$_SESSION['intentosRegistro'] = array_values($intentos);

// El registro público solamente permite estudiantes.
if (
    array_key_exists('tipo', $input) &&
    $input['tipo'] !== 'estudiante'
) {
    $response->error(
        "El registro público está disponible únicamente para estudiantes",
        7105,
        403
    );
}

// Limpia los campos de texto, conservando la contraseña original.
$input['identificacion'] = trim($input['identificacion']);
$input['correo'] = trim($input['correo']);
$input['nombreUsuario'] = trim($input['nombreUsuario']);

// Reutiliza las validaciones existentes del proyecto.
if (!Validar::carnet($input['identificacion'])) {
    $response->error(
        "El carné debe tener el formato XXXX-XX-XXXX",
        7105,
        400
    );
}

if (
    strlen($input['correo']) > 150 ||
    !filter_var($input['correo'], FILTER_VALIDATE_EMAIL)
) {
    $response->error(
        "Ingrese un correo válido",
        7105,
        400
    );
}

Validar::estructuraUsuario(
    $input['nombreUsuario'],
    7105
);

Validar::estructuraContrasena(
    $input['contrasenaUsuario'],
    7105
);

if (
    $input['contrasenaUsuario'] !==
    $input['confirmarContrasena']
) {
    $response->error(
        "Las contraseñas no coinciden",
        7105,
        400
    );
}

// El modelo crea la cuenta activa, su rol y la bitácora.
try {
    $conection = new Connection();

    $registro = new Registro(
        $conection,
        $response
    );

    $registro->add($input);

} catch (DomainException $th) {
    $response->error(
        "No se pudo crear el usuario con esos datos. Revise su información o contacte al administrador",
        7106,
        409
    );

} catch (PDOException $th) {
    // El código 1062 indica una restricción de duplicados.
    if (($th->errorInfo[1] ?? 0) == 1062) {
        $response->error(
            "No se pudo crear el usuario con esos datos. Revise su información o contacte al administrador",
            7106,
            409
        );
    }

    $response->error(
        "No se pudo crear el usuario. Contacte al administrador",
        7107,
        500
    );

} catch (\Throwable $th) {
    $response->error(
        "No se pudo crear el usuario. Contacte al administrador",
        7107,
        500
    );
}