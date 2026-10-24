<?php
require_once("../core/Connection.php");
require_once("../models/Modulo.php");
require_once("../core/Response.php");

$conection = new Connection();
$response = new Response();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {

    case 'GET':
        try {
        $modulo = new Modulo($conection, $response);
        $filtros = [
            'idModulo' => $_GET['idModulo'] ?? null, 'nombre' => $_GET['nombre'] ?? null,
            'is_active' => $_GET['is_active'] ?? null,
        ];
        $modulo->getAll($filtros);
    } catch (\Throwable $th) {
        $response->error("Error al consultar módulos", 5001, 400);
    }
    break;

    case 'POST':
        $input = json_decode(file_get_contents("php://input"), true);

        Validar::estructuraSoloLetras($input['nombreModulo']??'',6005);
        if (trim($input['nombreModulo'] ?? '') === '') {
            $response->error("El nombre del modulo es obligatorio", 7005, 400);
        }

        try {
            $modulo = new Modulo($conection, $response);
            $modulo->add($input);

        } catch (\Throwable $th) {
           $dup = Validar::mensajeDuplicado($th);
            $response->error($dup ?? "Error al agregar el Modulo", $dup ? 7006 : 7002, $dup ? 409 : 400);
        }
        break;


    case 'PUT':
        $input = json_decode(file_get_contents("php://input"), true);
        $id = (int) ($input['id'] ?? 0);

        if (trim($input['nombreModulo'] ?? '') === '') {
            $response->error("El nombre del modulo es obligatorio", 7005, 400);
        }

        try {
            $modulo = new Modulo($conection, $response);
            $modulo->editar($id, $input);

        } catch (\Throwable $th) {
            $dup = Validar::mensajeDuplicado($th);
            $response->error($dup ?? "Error al actualizar el modulo", $dup ? 7006 : 7003, $dup ? 409 : 400);
        }
        break;


    case 'DELETE':
        $input = json_decode(file_get_contents("php://input"), true);
        $id = (int) ($input['id'] ?? 0);

        try {
            $modulo = new Modulo($conection, $response);
            $modulo->eliminar($id);

        } catch (\Throwable $th) {

            $response->error("No se puede eliminar: el módulo tiene aplicaciones o permisos asociados", 5004, 400);
        }
        break;
}