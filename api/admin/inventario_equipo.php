<?php

require_once("../core/Connection.php");
require_once("../core/Response.php");
require_once("../core/Validar.php");
require_once("../models/inventario_equipo.php");
require_once("../core/Sesion.php");
Sesion::requerirApi();


$conection = new Connection();
$response = new Response();
$method = $_SERVER['REQUEST_METHOD'];

// Comprueba que la fecha tenga formato Y-m-d y sea una fecha real
function fechaValida(mixed $fecha): bool
{
    $texto = trim((string) $fecha);
    $f = DateTime::createFromFormat('Y-m-d', $texto);
    return $f && $f->format('Y-m-d') === $texto;
}

// el mapa de permisos para el crud 

$derechos = [
    'GET'    => 'ver',
    'POST'   => 'insertar',
    'PUT'    => 'editar',
    'DELETE' => 'eliminar',
];
Sesion::requerirPermiso('Inventario', 'Inventario de equipo', $derechos[$method] ?? 'ver');

switch ($method) {

    case 'GET':
        try {
            $inventario = new inventario_equipo($conection, $response);
            $filtros = [
                'id_equipo' => $_GET['id_equipo'] ?? null,
                'ubicacion' => $_GET['ubicacion'] ?? null,
            ];
            $inventario->getAll($filtros);
        } catch (\Throwable $th) {
            $response->error("Error al consultar el inventario", 12001, 400);
        }
        break;

    case 'POST':
        $input = json_decode(file_get_contents("php://input"), true);

        if (empty($input['id_equipo'])) {
            $response->error("El equipo es obligatorio", 12007, 400);
        }
        if (!fechaValida($input['fecha_ingreso'] ?? '')) {
            $response->error("La fecha de ingreso es obligatoria y debe ser una fecha válida", 12005, 400);
        }

        try {
            $inventario = new inventario_equipo($conection, $response);
            $inventario->add($input);

        } catch (\Throwable $th) {
            $dup = Validar::mensajeDuplicado($th);
            $response->error($dup ?? "Error al agregar el registro de inventario", $dup ? 12006 : 12002, $dup ? 409 : 400);
        }
        break;

    case 'PUT':
        $input = json_decode(file_get_contents("php://input"), true);
        $id = (int) ($input['id'] ?? $input['id_inventario'] ?? 0);

        if (empty($input['id_equipo'])) {
            $response->error("El equipo es obligatorio", 12007, 400);
        }
        if (!fechaValida($input['fecha_ingreso'] ?? '')) {
            $response->error("La fecha de ingreso es obligatoria y debe ser una fecha válida", 12005, 400);
        }

        try {
            $inventario = new inventario_equipo($conection, $response);
            $inventario->editar($id, $input);

        } catch (\Throwable $th) {
            $dup = Validar::mensajeDuplicado($th);
            $response->error($dup ?? "Error al actualizar el registro de inventario", $dup ? 12006 : 12003, $dup ? 409 : 400);
        }
        break;

    case 'DELETE':
        $input = json_decode(file_get_contents("php://input"), true);
        $id = (int) ($input['id'] ?? $input['id_inventario'] ?? 0);

        try {
            $inventario = new inventario_equipo($conection, $response);
            $inventario->eliminar($id);

        } catch (\Throwable $th) {
            $response->error("No se puede eliminar: el registro tiene movimientos asociados", 12004, 400);
        }
        break;
}