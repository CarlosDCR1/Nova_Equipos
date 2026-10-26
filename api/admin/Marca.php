<?php
require_once("../core/Connection.php");
require_once("../core/Response.php");
require_once("../core/Validar.php");
require_once("../models/Marca.php");
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
Sesion::requerirPermiso('Inventario', 'Marca', $derechos[$method] ?? 'ver');

switch ($method) {

    case 'GET':
        try {
            $marca = new Marca($conection, $response);
            $filtros = [
                'nombre' => $_GET['nombre'] ?? null,
                'is_active' => $_GET['is_active'] ?? null,
            ];
            $marca->getAll($filtros);
        } catch (\Throwable $th) {
            $response->error("Error al consultar marcas", 7001, 400);
        }
        break;

    case 'POST':
        $input = json_decode(file_get_contents("php://input"), true);

        Validar::estructuraSoloLetras($input['nombre_marca'] ?? '', 6005);

        if (trim($input['nombre_marca'] ?? '') === '') {
            $response->error("El nombre de la marca es obligatorio", 7005, 400);
        }

        try {
            $marca = new Marca($conection, $response);
            $marca->add($input);

        } catch (\Throwable $th) {
            $dup = Validar::mensajeDuplicado($th);
            $response->error($dup ?? "Error al agregar la marca", $dup ? 7006 : 7002, $dup ? 409 : 400);
        }
        break;

    case 'PUT':
        $input = json_decode(file_get_contents("php://input"), true);
        $id = (int) ($input['id'] ?? $input['id_marca'] ?? 0);

        if (trim($input['nombre_marca'] ?? '') === '') {
            $response->error("El nombre de la marca es obligatorio", 7005, 400);
        }

        try {
            $marca = new Marca($conection, $response);
            $marca->editar($id, $input);

        } catch (\Throwable $th) {
            $dup = Validar::mensajeDuplicado($th);
            $response->error($dup ?? "Error al actualizar la marca", $dup ? 7006 : 7003, $dup ? 409 : 400);
        }
        break;

    case 'DELETE':
        $input = json_decode(file_get_contents("php://input"), true);
        $id = (int) ($input['id'] ?? $input['id_marca'] ?? 0);

        try {
            $marca = new Marca($conection, $response);
            $marca->eliminar($id);

        } catch (\Throwable $th) {
            $response->error("No se puede eliminar: la marca tiene modelos de producto asociados", 7004, 400);
        }
        break;
}