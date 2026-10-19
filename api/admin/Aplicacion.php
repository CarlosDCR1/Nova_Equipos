<?php
require_once("../core/Connection.php");
require_once("../models/Aplicacion.php");
require_once("../core/Response.php");

$conection = new Connection();
$response = new Response();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {

    case 'GET':
        try {
        $aplicacion = new Aplicacion($conection, $response);
        $filtros = [
            'idModulo' => $_GET['idModulo'] ?? null, 'nombre' => $_GET['nombre'] ?? null,
            'is_active' => $_GET['is_active'] ?? null,
        ];
        $aplicacion->getAll($filtros);
    } catch (\Throwable $th) {
        $response->error("Error al consultar aplicaciones", 6001, 400);
    }
    break;

    case 'POST':
        $input = json_decode(file_get_contents("php://input"), true);

        try {
            $aplicacion = new Aplicacion($conection, $response);
            $aplicacion->add($input);

        } catch (\Throwable $th) {
            $response->error("Error al agregar la aplicación (¿ya existe ese nombre en el módulo?)", 6002, 400);
        }
        break;


    case 'PUT':
        $input = json_decode(file_get_contents("php://input"), true);
        $id = (int) ($input['id'] ?? 0);

        try {
            $aplicacion = new Aplicacion($conection, $response);
            $aplicacion->editar($id, $input);

        } catch (\Throwable $th) {
            $response->error("Error al actualizar la aplicación (¿ya existe ese nombre en el módulo?)", 6003, 400);
        }
        break;


    case 'DELETE':
        $input = json_decode(file_get_contents("php://input"), true);
        $id = (int) ($input['id'] ?? 0);

        try {
            $aplicacion = new Aplicacion($conection, $response);
            $aplicacion->eliminar($id);

        } catch (\Throwable $th) {

            $response->error("No se puede eliminar: la aplicación tiene permisos asociados", 6004, 400);
        }
        break;
}