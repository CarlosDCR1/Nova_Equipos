<?php
require_once("../core/Connection.php");
require_once("../models/Carrera.php");
require_once("../core/Response.php");
require_once("../core/Sesion.php");
Sesion::requerirApi();

$conection = new Connection();
$response = new Response();
$method = $_SERVER['REQUEST_METHOD'];

// el mapa de permisos para el crud 
$derechos = [
    'GET'    => 'ver',
    'POST'   => 'insertar',
    'PUT'    => 'editar',
    'DELETE' => 'eliminar',
];
Sesion::requerirPermiso('Academico y personal', 'Carrera', $derechos[$method] ?? 'ver');


switch ($method) {

    case 'GET':
        try {
            $carrera = new Carrera($conection, $response);
            $filtros = [
                'id_facultad' => $_GET['id_facultad'] ?? null,
                'nombre' => $_GET['nombre'] ?? null,
            ];
            $carrera->getAll($filtros);
        } catch (\Throwable $th) {
            $response->error("Error al consultar carreras", 1001, 400);
        }
        break;

    case 'POST':
        $input = json_decode(file_get_contents("php://input"), true);
        try {
            $carrera = new Carrera($conection, $response);
            $carrera->add($input);
        } catch (\Throwable $th) {
            $response->error("Error al agregar la carrera", 1002, 400);
        }
        break;

    case 'PUT':
        $input = json_decode(file_get_contents("php://input"), true);
        $id = (int) ($input['id'] ?? 0);
        try {
            $carrera = new Carrera($conection, $response);
            $carrera->editar($id, $input);
        } catch (\Throwable $th) {
            $response->error("Error al actualizar la carrera", 1003, 400);
        }
        break;

    case 'DELETE':
        $input = json_decode(file_get_contents("php://input"), true);
        $id = (int) ($input['id'] ?? 0);
        try {
            $carrera = new Carrera($conection, $response);
            $carrera->eliminar($id);
        } catch (\Throwable $th) {
            $response->error("No se puede eliminar: la carrera tiene estudiantes asociados", 1004, 400);
        }
        break;
}