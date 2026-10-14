<?php
require_once("../core/Connection.php");
require_once("../models/Carreras.php");
require_once("../core/Response.php");

$metodo = $_SERVER['REQUEST_METHOD'];
$data = json_decode(file_get_contents("php://input"), true) ?? [];

switch ($metodo) {
    case 'GET':
        try {
            $carrera = new Carrera($conection, $response);
            $filtros = ['id_facultad' => $_GET['id_facultad'] ?? null, 'nombre' => $_GET['nombre'] ?? null];
            $carrera->getAll($filtros);
        } catch (\Throwable $th) {
            $response->error("Error al consultar carreras", 1001, 400);
        }
        break;

    case 'POST':
        try {
            $carrera = new Carrera($conection, $response);
            $carrera->add($data);
        } catch (\Throwable $th) {
            $response->error("Error al agregar la carrera", 1002, 400);
        }
        break;

    case 'PUT':
        try {
            $carrera = new Carrera($conection, $response);
            $carrera->editar((int)$data['id'], $data);
        } catch (\Throwable $th) {
            $response->error("Error al actualizar la carrera", 1003, 400);
        }
        break;

    case 'DELETE':
        try {
            $carrera = new Carrera($conection, $response);
            $carrera->eliminar((int)$data['id']);
        } catch (\Throwable $th) {
            $response->error("Error al eliminar la carrera (puede tener estudiantes asociados)", 1004, 400);
        }
        break;
}