<?php

require_once("../core/Connection.php");
require_once("../core/Response.php");
require_once("../core/Validar.php");
require_once("../models/estado_equipo.php");
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
Sesion::requerirPermiso('Inventario', 'Estado de equipo', $derechos[$method] ?? 'ver');

switch ($method) {
 
    case 'GET':
        try {
            $estado = new Estado_Equipo($conection, $response);
            $filtros = ['nombre' => $_GET['nombre'] ?? null, 'permite_prestamo' => $_GET['permite_prestamo'] ?? null, 'is_active' => $_GET['is_active'] ?? null,];
            $estado->getAll($filtros);
        } catch (\Throwable $th) {
            $response->error("Error al consultar estados de equipo", 8001, 400);
        }
        break;
 
    case 'POST':
        $input = json_decode(file_get_contents("php://input"), true);

        Validar::estructuraSoloLetras($input['nombre_estado'] ?? '', 6005);
        Validar::estructuraSoloLetras($input['descripcion'] ?? '', 6005);
        
 
        if (trim($input['nombre_estado'] ?? '') === '') {
            $response->error("El nombre del estado es obligatorio", 8005, 400);
        }
 
        try {
            $estado = new Estado_Equipo($conection, $response);
            $estado->add($input);
 
        } catch (\Throwable $th) {
            $dup = Validar::mensajeDuplicado($th);
            $response->error($dup ?? "Error al agregar el estado de equipo", $dup ? 8006 : 8002, $dup ? 409 : 400);
        }
        break;
 
    case 'PUT':
        $input = json_decode(file_get_contents("php://input"), true);
        $id = (int) ($input['id'] ?? $input['id_estado_equipo'] ?? 0);
 
        if (trim($input['nombre_estado'] ?? '') === '') {
            $response->error("El nombre del estado es obligatorio", 8005, 400);
        }
 
        try {
            $estado = new Estado_Equipo($conection, $response);
            $estado->editar($id, $input);
 
        } catch (\Throwable $th) {
            $dup = Validar::mensajeDuplicado($th);
            $response->error($dup ?? "Error al actualizar el estado de equipo", $dup ? 8006 : 8003, $dup ? 409 : 400);
        }
        break;
 
    case 'DELETE':
        $input = json_decode(file_get_contents("php://input"), true);
        $id = (int) ($input['id'] ?? $input['id_estado_equipo'] ?? 0);
 
        try {
            $estado = new Estado_Equipo($conection, $response);
            $estado->eliminar($id);
 
        } catch (\Throwable $th) {
            $response->error("No se puede eliminar: el estado tiene equipos asociados", 8004, 400);
        }
        break;
}