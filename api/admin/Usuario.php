<?php
require_once("../core/Connection.php");
require_once("../models/Usuario.php");
require_once("../core/Response.php");

$conection = new Connection();
$response = new Response();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {

    case 'GET':
        try {
            $usuario = new Usuario($conection, $response);

            $filtros = [
                'nombreUsuario' => $_GET['nombreUsuario'] ?? null,
                'is_active'     => $_GET['is_active'] ?? null,
            ];

            $usuario->getAll($filtros);

        } catch (\Throwable $th) {
            $response->error("Error al consultar usuarios", 4000, 400);
        }
        break;


    case 'POST':
        $input = json_decode(file_get_contents("php://input"), true);

        try {
            $usuario = new Usuario($conection, $response);
            $usuario->add($input);

        } catch (\Throwable $th) {
           
            $response->error("Error al agregar el usuario (¿el nombre de usuario ya existe?)", 4002, 400);
        }
        break;


    case 'PUT':
        $input = json_decode(file_get_contents("php://input"), true);
        $id = (int) ($input['id'] ?? 0);

        try {
            $usuario = new Usuario($conection, $response);
            $usuario->editar($id, $input);

            $response->success("Usuario actualizado correctamente", [], 1);

        } catch (\Throwable $th) {
            $response->error("Error al actualizar el usuario", 4003, 400);
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
