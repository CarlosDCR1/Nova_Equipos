<?php
require_once("../core/Connection.php");
require_once("../core/Response.php");
require_once("../models/Facultad.php");
require_once("../core/Validar.php");



$conection = new Connection();
$response = new Response();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    $response->error("Método no permitido", 405, 405);
}

try {
    $facultad = new Facultad($conection, $response);
    $facultad->getAll();
} catch (\Throwable $th) {
    $response->error("Error al consultar facultades", 4001, 400);
}