<?php

require_once("../core/Connection.php");
require_once("../core/Response.php");
require_once("../core/Validar.php");
require_once("../models/tipo_incidente.php");
require_once("../core/Sesion.php");
Sesion::requerirApi();


$conection = new Connection();
$response = new Response();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {

    case 'GET':
        try {
            $tipo = new tipo_incidente($conection, $response);
            $filtros = [
                'nombre' => $_GET['nombre'] ?? null,
                'is_active' => $_GET['is_active'] ?? null,
            ];
            $tipo->getAll($filtros);
        } catch (\Throwable $th) {
            $response->error("Error al consultar tipos de incidente", 13001, 400);
        }
        break;

    case 'POST':
        $input = json_decode(file_get_contents("php://input"), true);

        if (trim($input['nombre_tipo_incidente'] ?? '') === '') {
            $response->error("El nombre del tipo de incidente es obligatorio", 13005, 400);
        }

        try {
            $tipo = new tipo_incidente($conection, $response);
            $tipo->add($input);

        } catch (\Throwable $th) {
            $dup = Validar::mensajeDuplicado($th);
            $response->error($dup ?? "Error al agregar el tipo de incidente", $dup ? 13006 : 13002, $dup ? 409 : 400);
        }
        break;

    case 'PUT':
        $input = json_decode(file_get_contents("php://input"), true);
        $id = (int) ($input['id'] ?? $input['id_tipo_incidente'] ?? 0);

        if (trim($input['nombre_tipo_incidente'] ?? '') === '') {
            $response->error("El nombre del tipo de incidente es obligatorio", 13005, 400);
        }

        try {
            $tipo = new tipo_incidente($conection, $response);
            $tipo->editar($id, $input);

        } catch (\Throwable $th) {
            $dup = Validar::mensajeDuplicado($th);
            $response->error($dup ?? "Error al actualizar el tipo de incidente", $dup ? 13006 : 13003, $dup ? 409 : 400);
        }
        break;

    case 'DELETE':
        $input = json_decode(file_get_contents("php://input"), true);
        $id = (int) ($input['id'] ?? $input['id_tipo_incidente'] ?? 0);

        try {
            $tipo = new tipo_incidente($conection, $response);
            $tipo->eliminar($id);

        } catch (\Throwable $th) {
            $response->error("No se puede eliminar: el tipo de incidente tiene detalles de devolución asociados", 13004, 400);
        }
        break;
}