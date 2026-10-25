<?php

require_once("../core/Connection.php");
require_once("../core/Response.php");
require_once("../core/Validar.php");
require_once("../models/Estudiante.php");
require_once("../core/Sesion.php");
Sesion::requerirApi();

$conection = new Connection();
$response = new Response();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {

    case 'GET':
        try {
        $estudiante = new Estudiante($conection, $response);
        $filtros = [
            'id_carrera' => $_GET['id_carrera'] ?? null, 'nombre' => $_GET['nombre'] ?? null,
            'apellidos' => $_GET['apellidos'] ?? null, 'carnet' => $_GET['carnet'] ?? null, 'numero' => $_GET['numero'] ?? null,
            'correo' => $_GET['correo'] ?? null, 'estado' => $_GET['estado'] ?? null,
        ];
        $estudiante->getAll($filtros);
    } catch (\Throwable $th) {
        $response->error("Error al consultar estudiantes", 1001, 400);
    }
    break;

    case 'POST':
        $input = json_decode(file_get_contents("php://input"), true);

        Validar::estructuraCorreoumg($input['correo_institucional'] ?? '', 1005);
        Validar::estructuraCarnet($input['carnet'] ?? '', 1007);
        Validar::estructuratelefono($input['telefono'] ?? '', 1006);
        Validar::validarEdad($input['fecha_nacimiento'] ?? '', 1008);

        try {
            $estudiante = new Estudiante($conection, $response);
            $estudiante->add($input);

        } catch (\Throwable $th) {
            $dup = Validar::mensajeDuplicado($th);
            $response->error($dup ?? "Error al agregar el estudiante", $dup ? 1008 : 1002, $dup ? 409 : 400);
        }
        break;


    case 'PUT':
        $input = json_decode(file_get_contents("php://input"), true);
        $id = (int) ($input['id'] ?? 0);

        Validar::estructuraCorreoumg($input['correo_institucional'] ?? '', 1005);
        Validar::estructuraCarnet($input['carnet'] ?? '', 1007);
        Validar::estructuratelefono($input['telefono'] ?? '', 1006);
        Validar::validarEdad($input['fecha_nacimiento'] ?? '', 1008);
    

        try {
            $estudiante = new Estudiante($conection, $response);
            $estudiante->editar($id, $input);

            $response->success("Estudiante actualizado correctamente", [], 1);

        } catch (\Throwable $th) {
            $dup = Validar::mensajeDuplicado($th);
            $response->error($dup ?? "Error al actualizar el estudiante", $dup ? 1008 : 1002, $dup ? 409 : 400);
        }
        break;


    case 'DELETE':
        $input = json_decode(file_get_contents("php://input"), true);
        $id = (int) ($input['id'] ?? 0);

        try {
            $estudiante = new Estudiante($conection, $response);
            $estudiante->eliminar($id);

            $response->success("Estudiante eliminado correctamente", [], 1);

        } catch (\Throwable $th) {

            $response->error("No se puede eliminar: el estudiante tiene registros asociados (usuario, movimientos, etc.)", 1004, 400);
        }
        break;
}