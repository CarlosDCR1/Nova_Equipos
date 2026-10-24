<?php
require_once("../core/Connection.php");
require_once("../models/Usuario.php");
require_once("../core/Response.php");
require_once("../core/Validar.php");

$conection = new Connection();
$response = new Response();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {

    case 'GET':
        try {
        $usuario = new Usuario($conection, $response);
        $filtros = [
            'idEmpleado' => $_GET['idEmpleado'] ?? null, 'nombre' => $_GET['nombre'] ?? null,
            'is_active' => $_GET['is_active'] ?? null,
        ];
        $usuario->getAll($filtros);
    } catch (\Throwable $th) {
        $response->error("Error al consultar usuarios", 4000, 400);
    }
    break;

    case 'POST':
        $input = json_decode(file_get_contents("php://input"), true);

        Validar::estructuraUsuario($input['nombreUsuario'] ?? '', 4005);
        Validar::estructuraContrasena($input['contrasenaUsuario'] ?? '', 4006);


        try {
            $usuario = new Usuario($conection, $response);
            $usuario->add($input);

        } catch (\Throwable $th) {
            $dup = Validar::mensajeDuplicado($th);
            $response->error($dup ?? "Error al agregar el usuario", $dup ? 4008 : 4002, $dup ? 409 : 400);
        }
        break;


    case 'PUT':
        $input = json_decode(file_get_contents("php://input"), true);
        $id = (int) ($input['id'] ?? 0);

        Validar::estructuraUsuario($input['nombreUsuario'] ?? '', 4005);
        if (!empty($input['contrasenaUsuario'])) {
            Validar::estructuraContrasena($input['contrasenaUsuario'], 4006);
        }

        try {
            $usuario = new Usuario($conection, $response);
            $usuario->editar($id, $input);

            $response->success("Usuario actualizado correctamente", [], 1);
        } catch (\Throwable $th) {
            $dup = Validar::mensajeDuplicado($th);
            $response->error($dup ?? "Error al actualizar el usuario", $dup ? 4008 : 4003, $dup ? 409 : 400);
        }
        break;


    case 'DELETE':
        $input = json_decode(file_get_contents("php://input"), true);
        $id = (int) ($input['id'] ?? 0);

        try {
            $usuario = new Usuario($conection, $response);
            $usuario->eliminar($id);

            $response->success("Usuario eliminado correctamente", [], 1);
        } catch (\Throwable $th) {

            $response->error("No se puede eliminar: el usuario tiene roles o registros de bitácora asociados", 4004, 400);
        }
        break;
}