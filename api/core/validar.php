<?php
require_once(__DIR__ . "/Response.php");

class Validar
{
    static function correoMiumg(mixed $correo): bool
    {
        return (bool) preg_match('/^[A-Za-z0-9._%+\-]+@miumg\.edu\.gt$/i', trim((string) $correo));
    }

    static function estructuraGuatetelefono(mixed $numero): bool
    {
        return (bool) preg_match('/^\d{8}$/', trim((string) $numero));
    }

    static function carnet(mixed $carnet): bool
    {
        return (bool) preg_match('/^\d{4}-\d{2}-\d{4}$/', trim((string) $carnet));
    }

    static function soloLetras(mixed $texto): bool
    {
        return (bool) preg_match('/^\p{L}+(?: \p{L}+)*$/u', trim((string) $texto));
    } 

    static function estructuraCorreoumg(mixed $correo, int $code): void
    {
        if (!self::correoMiumg($correo)) {
            Response::error("El correo no pertence a la UMG", $code, 400);
        }
    }

    static function estructuraTelefono(mixed $numero, int $code): void
    {
        if (!self::estructuraGuatetelefono($numero)) {
            Response::error("El teléfono debe tener 8 dígitos", $code, 400);
        }
    }

    static function estructuraCarnet(mixed $carnet, int $code): void
    {
        if (!self::carnet($carnet)) {
            Response::error("El carnet debe tener el formato XXXX-XX-XXXX", $code, 400);
        }
    }

    static function estructuraSoloLetras(mixed $texto, int $code): void
    {
        if (!self::soloLetras($texto)) {
            Response::error("Solo se permiten letras", $code, 400);
        }
    }   

    static function edadEntre(mixed $fecha, int $min = 18, int $max = 65): bool
    {
        $texto = trim((string) $fecha);
        $f = DateTime::createFromFormat('Y-m-d', $texto);

        if (!$f || $f->format('Y-m-d') !== $texto) {
            return false;
        }

        $hoy = new DateTime('today');

        if ($f > $hoy) {
            return false;
        }

        $edad = $f->diff($hoy)->y;
        return $edad >= $min && $edad <= $max;
    }

    static function validarEdad(mixed $fecha, int $code): void
    {
        if (!self::edadEntre($fecha)) {
            Response::error("La edad debe estar entre 18 y 65 años", $code, 400);
        }
    }

    static function mensajeDuplicado(\Throwable $th): ?string
{
    if (!($th instanceof PDOException) || ($th->errorInfo[1] ?? 0) != 1062) {
        return null;
    }

    $mensajes = [
        'uq_estudiante_carnet' => "El carnet ya está registrado",
        'uq_estudiante_correo'=> "El correo ya está registrado",
        'uq_estudiante_telefono'=> "El número ya está registrado",
        'uq_empleado_dpi'=> "El DPI ya está registrado",
        'uq_empleado_correo'=> "El correo ya está registrado",
    ];

    foreach ($mensajes as $llave => $texto) {
        if (str_contains($th->getMessage(), $llave)) {
            return $texto;
        }
    }
    return "Ya existe un registro con esos datos";
}
}