<?php
require_once("../core/Connection.php");
require_once("../core/Response.php");
require_once("../core/Validar.php");
require_once("../models/Equipo.php");
require_once("../core/Sesion.php");
Sesion::requerirApi();

$conection = new Connection();
$response = new Response();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
 
    case 'GET':
        try {
            $equipo = new Equipo($conection, $response);
            $filtros = [
                'id_modelo_producto' => $_GET['id_modelo_producto'] ?? null,
                'id_estado_equipo' => $_GET['id_estado_equipo'] ?? null,
                'codigo' => $_GET['codigo'] ?? null,
                'serie' => $_GET['serie'] ?? null,
            ];
            $equipo->getAll($filtros);
        } catch (\Throwable $th) {
            $response->error("Error al consultar equipos", 11001, 400);
        }
        break;
 
    case 'POST':
        $input = json_decode(file_get_contents("php://input"), true);
 
        if (empty($input['id_modelo_producto']) || empty($input['id_estado_equipo'])) {
            $response->error("El modelo y el estado del equipo son obligatorios", 11007, 400);
        }
        if (trim($input['codigo_inventario'] ?? '') === '') {
            $response->error("El código de inventario es obligatorio", 11005, 400);
        }
        if (($input['valor_adquisicion'] ?? '') !== '' && (!is_numeric($input['valor_adquisicion']) || $input['valor_adquisicion'] < 0)) {
            $response->error("El valor de adquisición debe ser un número mayor o igual a 0", 11008, 400);
        }
 
        try {
            $equipo = new Equipo($conection, $response);
            $equipo->add($input);
 
        } catch (\Throwable $th) {
            $dup = Validar::mensajeDuplicado($th);
            $response->error($dup ?? "Error al agregar el equipo", $dup ? 11006 : 11002, $dup ? 409 : 400);
        }
        break;
 
    case 'PUT':
        $input = json_decode(file_get_contents("php://input"), true);
        $id = (int) ($input['id'] ?? $input['id_equipo'] ?? 0);
 
        if (empty($input['id_modelo_producto']) || empty($input['id_estado_equipo'])) {
            $response->error("El modelo y el estado del equipo son obligatorios", 11007, 400);
        }
        if (trim($input['codigo_inventario'] ?? '') === '') {
            $response->error("El código de inventario es obligatorio", 11005, 400);
        }
        if (($input['valor_adquisicion'] ?? '') !== '' && (!is_numeric($input['valor_adquisicion']) || $input['valor_adquisicion'] < 0)) {
            $response->error("El valor de adquisición debe ser un número mayor o igual a 0", 11008, 400);
        }
 
        try {
            $equipo = new Equipo($conection, $response);
            $equipo->editar($id, $input);
 
        } catch (\Throwable $th) {
            $dup = Validar::mensajeDuplicado($th);
            $response->error($dup ?? "Error al actualizar el equipo", $dup ? 11006 : 11003, $dup ? 409 : 400);
        }
        break;
 
    case 'DELETE':
        $input = json_decode(file_get_contents("php://input"), true);
        $id = (int) ($input['id'] ?? $input['id_equipo'] ?? 0);
 
        try {
            $equipo = new Equipo($conection, $response);
            $equipo->eliminar($id);
 
        } catch (\Throwable $th) {
            $response->error("No se puede eliminar: el equipo tiene registros asociados (inventario, préstamos, etc.)", 11004, 400);
        }
        break;
}