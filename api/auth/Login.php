<?php
require_once("../core/Connection.php");
require_once("../core/Response.php");
require_once("../models/Auth.php");

$conection = new Connection();
$response = new Response();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response->error("Método no permitido", 7000, 405);
}

$input = json_decode(file_get_contents("php://input"), true);
$usuario = trim((string) ($input['usuario'] ?? ''));
$contrasena = (string) ($input['contrasena'] ?? '');

if ($usuario === '' || $contrasena === '') {
    $response->error("Ingrese usuario y contraseña", 7001, 400);
}

try {
    $auth = new Auth($conection, $response);
    $auth->login($usuario, $contrasena);
    $response->error("Sesion iniciada correctamente", 7005, 500);
} catch (\Throwable $th) {
    $response->error("Error al iniciar sesión", 7005, 500);
}