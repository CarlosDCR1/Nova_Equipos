<?php

require_once("../core/Connection.php");
require_once("../core/Response.php");
require_once("../core/Validar.php");
require_once("../models/modelo_producto.php");
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
Sesion::requerirPermiso('Inventario', 'Modelo de producto', $derechos[$method] ?? 'ver');

switch ($method) {

    case 'GET':
        try {
            $modelo = new modelo_Producto($conection, $response);
            $filtros = [
                'id_marca' => $_GET['id_marca'] ?? null,
                'id_categoria_producto' => $_GET['id_categoria_producto'] ?? null,
                'nombre' => $_GET['nombre'] ?? null,
                'is_active' => $_GET['is_active'] ?? null,
            ];
            $modelo->getAll($filtros);
        } catch (\Throwable $th) {
            $response->error("Error al consultar modelos de producto", 10001, 400);
        }
        break;

    case 'POST':
        $input = json_decode(file_get_contents("php://input"), true);

        Validar::estructuraSoloLetras($input['nombre_modelo_producto'] ?? '', 6005);
        Validar::estructuraSoloLetras($input['descripcion_modelo_producto'] ?? '', 6005);

        if (empty($input['id_marca']) || empty($input['id_categoria_producto'])) {
            $response->error("La marca y la categoría son obligatorias", 10007, 400);
        }
        if (trim($input['nombre_modelo_producto'] ?? '') === '') {
            $response->error("El nombre del modelo es obligatorio", 10005, 400);
        }

        try {
            $modelo = new modelo_Producto($conection, $response);
            $modelo->add($input);

        } catch (\Throwable $th) {
            $dup = Validar::mensajeDuplicado($th);
            $response->error($dup ?? "Error al agregar el modelo de producto", $dup ? 10006 : 10002, $dup ? 409 : 400);
        }
        break;

    case 'PUT':
        $input = json_decode(file_get_contents("php://input"), true);
        $id = (int) ($input['id'] ?? $input['id_modelo_producto'] ?? 0);

        if (empty($input['id_marca']) || empty($input['id_categoria_producto'])) {
            $response->error("La marca y la categoría son obligatorias", 10007, 400);
        }
        if (trim($input['nombre_modelo_producto'] ?? '') === '') {
            $response->error("El nombre del modelo es obligatorio", 10005, 400);
        }

        try {
            $modelo = new modelo_Producto($conection, $response);
            $modelo->editar($id, $input);

        } catch (\Throwable $th) {
            $dup = Validar::mensajeDuplicado($th);
            $response->error($dup ?? "Error al actualizar el modelo de producto", $dup ? 10006 : 10003, $dup ? 409 : 400);
        }
        break;

    case 'DELETE':
        $input = json_decode(file_get_contents("php://input"), true);
        $id = (int) ($input['id'] ?? $input['id_modelo_producto'] ?? 0);

        try {
            $modelo = new modelo_Producto($conection, $response);
            $modelo->eliminar($id);

        } catch (\Throwable $th) {
            $response->error("No se puede eliminar: el modelo tiene equipos asociados", 10004, 400);
        }
        break;
}