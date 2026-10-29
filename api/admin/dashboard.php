<?php
require_once("../core/Connection.php");
require_once("../core/Response.php");
require_once("../core/Sesion.php");
require_once("../models/Dashboard.php");
Sesion::requerirApi();

$conection = new Connection();
$response = new Response();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    $response->error("Método no permitido", 405, 405);
}

try {
    $dashboard = new Dashboard($conection, $response);

    if (Sesion::esEstudiante()) {
        $data = $dashboard->paraEstudiante((int) $_SESSION['idEstudiante']);
    } else {
        $esAdmin = in_array('Administrador', array_column($_SESSION['roles'] ?? [], 'nombreRol'), true);
        $data = $dashboard->paraPersonal($esAdmin);
    }

    $response->success("OK", $data, 1);
} catch (\Throwable $th) {
    $response->error("Error al cargar el panel principal", 1010, 400);
}