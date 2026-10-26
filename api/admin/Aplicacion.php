<?php
require_once("../core/Connection.php");
require_once("../models/Aplicacion.php");
require_once("../core/Response.php");
require_once("../core/Validar.php");
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
Sesion::requerirPermiso('Seguridad', 'Aplicacion', $derechos[$method] ?? 'ver');

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

        Validar::estructuraSoloLetras($input['nombreAplicacion']??'',6005);
        if (trim($input['nombreAplicacion'] ?? '') === '') {
            $response->error("El nombre de la Aplicacion es obligatorio", 7005, 400);
        }

        try {
            $aplicacion = new Aplicacion($conection, $response);
            $aplicacion->add($input);

        } catch (\Throwable $th) {
            $dup = Validar::mensajeDuplicado($th);
            $response->error($dup ?? "Error al agregar la Aplicacion", $dup ? 7006 : 7002, $dup ? 409 : 400);
        }
        break;


    case 'PUT':
        $input = json_decode(file_get_contents("php://input"), true);
        $id = (int) ($input['id'] ?? 0);

        if (trim($input['nombreAplicacion'] ?? '') === '') {
            $response->error("El nombre de la aplicacion es obligatorio", 7005, 400);
        }

        try {
            $aplicacion = new Aplicacion($conection, $response);
            $aplicacion->editar($id, $input);

        } catch (\Throwable $th) {
            $dup = Validar::mensajeDuplicado($th);
            $response->error($dup ?? "Error al actualizar la Aplicacion", $dup ? 7006 : 7003, $dup ? 409 : 400);
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