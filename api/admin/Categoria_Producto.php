<?php
require_once("../core/Connection.php");
require_once("../models/Categoria_Producto.php");
require_once("../core/validar.php");
require_once("../core/Response.php");

$conection = new Connection();
$response = new Response();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {

    case 'GET':
        try {
            $categoria = new Categoria_Producto($conection, $response);
            $filtros = [
                'nombre' => $_GET['nombre'] ?? null,
                'is_active' => $_GET['is_active'] ?? null,
            ];
            $categoria->getAll($filtros);
        } catch (\Throwable $th) {
            $response->error("Error al consultar categorías de producto", 6001, 400);
        }
        break;

    case 'POST':
        $input = json_decode(file_get_contents("php://input"), true);

        Validar::estructuraSoloLetras($input['nombre_categoria_producto'] ?? '', 6005);
        Validar::estructuraSoloLetras($input['descripcion_categoria_producto'] ?? '', 6005);

        try {
            $categoria = new Categoria_Producto($conection, $response);
            $categoria->add($input);

        } catch (\Throwable $th) {
            $response->error("Error al agregar la categoría de producto (¿ya existe una categoría con ese nombre?)", 6002, 400);
        }
        break;

    case 'PUT':
        $input = json_decode(file_get_contents("php://input"), true);
        $id = (int) ($input['id'] ?? 0);

        try {
            $categoria = new Categoria_Producto($conection, $response);
            $categoria->editar($id, $input);

        } catch (\Throwable $th) {
            $response->error("Error al actualizar la categoría de producto (¿ya existe una categoría con ese nombre?)", 6003, 400);
        }
        break;

    case 'DELETE':
        $input = json_decode(file_get_contents("php://input"), true);
        $id = (int) ($input['id'] ?? 0);

        try {
            $categoria = new Categoria_Producto($conection, $response);
            $categoria->eliminar($id);

        } catch (\Throwable $th) {
            $response->error("No se puede eliminar: la categoría tiene productos asociados", 6004, 400);
        }
        break;
}