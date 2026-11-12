<?php

require_once("../core/Connection.php");
require_once("../core/Response.php");
require_once("../core/Validar.php");
require_once("../models/Detalle_Prestamo.php");
require_once("../core/Sesion.php");
Sesion::requerirApi();


$conection = new Connection();
$response = new Response();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {

    case 'GET':
        try {
            $detalle = new Detalle_Prestamo($conection, $response);

            // Listas para los desplegables del formulario: Detalle_Prestamo.php?lista=prestamos | equipos
            if (($_GET['lista'] ?? '') === 'prestamos') {
                $detalle->listaPrestamos();
                break;
            }
            if (($_GET['lista'] ?? '') === 'equipos') {
                $detalle->listaEquipos();
                break;
            }

            $filtros = [
                'id_prestamo' => $_GET['id_prestamo'] ?? null,
                'id_equipo' => $_GET['id_equipo'] ?? null,
                'observaciones' => $_GET['observaciones'] ?? null,
            ];
            $detalle->getAll($filtros);
        } catch (\Throwable $th) {
            $response->error("Error al consultar detalles de préstamo", 18001, 400);
        }
        break;

    case 'POST':
        $input = json_decode(file_get_contents("php://input"), true);
        
        
        if (empty($input['id_prestamo']) || empty($input['id_equipo'])) {
            $response->error("El préstamo y el equipo son obligatorios", 18007, 400);
        }

        try {
            $detalle = new Detalle_Prestamo($conection, $response);
            $detalle->add($input);

        } catch (\Throwable $th) {
            $dup = Validar::mensajeDuplicado($th);
            $response->error($dup ?? "Error al agregar el detalle de préstamo", $dup ? 18006 : 18002, $dup ? 409 : 400);
        }
        break;

    case 'PUT':
        $input = json_decode(file_get_contents("php://input"), true);
        $id = (int) ($input['id'] ?? $input['id_detalle_prestamo'] ?? 0);
        
        
        if (empty($input['id_prestamo']) || empty($input['id_equipo'])) {
            $response->error("El préstamo y el equipo son obligatorios", 18007, 400);
        }

        try {
            $detalle = new Detalle_Prestamo($conection, $response);
            $detalle->editar($id, $input);

        } catch (\Throwable $th) {
            $dup = Validar::mensajeDuplicado($th);
            $response->error($dup ?? "Error al actualizar el detalle de préstamo", $dup ? 18006 : 18003, $dup ? 409 : 400);
        }
        break;

    case 'DELETE':
        $input = json_decode(file_get_contents("php://input"), true);
        $id = (int) ($input['id'] ?? $input['id_detalle_prestamo'] ?? 0);

        try {
            $detalle = new Detalle_Prestamo($conection, $response);
            $detalle->eliminar($id);

        } catch (\Throwable $th) {
            $response->error("No se puede eliminar: el detalle tiene detalles de devolución asociados", 18004, 400);
        }
        break;
}