<?php

require_once("../core/Connection.php");
require_once("../core/Response.php");
require_once("../core/Sesion.php");
require_once("../models/Bitacora.php");

Sesion::requerirApi();

$conection = new Connection();
$response = new Response();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {

    case 'GET':
        try {
        $bitacora = new Bitacora($conection, $response);
        $filtros = [
            'idUsuario' => $_GET['idUsuario'] ?? null, 'accion' => $_GET['accion'] ?? null,
            'fecha' => $_GET['fecha'] ?? null, 'descripcion' => $_GET['descripcion'] ?? null,
            'aplicacion' => $_GET['aplicacion'] ?? null,
        ];
        $bitacora->getAll($filtros);
    } catch (\Throwable $th) {
        $response->error("Error al consultar la bitácora", 8001, 400);
    }
    break;

    default:
        $response->error("La bitácora es de solo consulta", 8002, 405);
}