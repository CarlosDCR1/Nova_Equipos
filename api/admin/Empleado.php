<?php
require_once("../core/Connection.php");
require_once("../models/Empleado.php");
require_once("../core/Response.php");
require_once("../core/Validar.php");


$conection = new Connection();
$response = new Response();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {

    case 'GET':
        try {
            $empleado = new Empleado($conection, $response);

            
            $filtros = [ 'nombre'    => $_GET['nombre']    ?? null, 'apellidos' => $_GET['apellidos'] ?? null,
                'correo'    => $_GET['correo']  ?? null, 'estado'  => $_GET['estado']    ?? null,
            ];

            $empleado->getAll($filtros);

        } catch (\Throwable $th) {
            $response->error("Error al consultar empleados", 3001, 400);
        }
        break;


    case 'POST':
        $input = json_decode(file_get_contents("php://input"), true);
        
        Validar::estructuraCorreoumg($input['correo_institucional'] ?? '', 3005);
        Validar::estructuratelefono($input['telefono_empleado'] ?? '', 3006);
        Validar::validarEdad($input['fecha_nacimiento'] ?? '', 3007);

        try {
            $empleado = new Empleado($conection, $response);
            $empleado->add($input);

        } catch (\Throwable $th) {
           $dup = Validar::mensajeDuplicado($th);
            $response->error($dup ?? "Error al agregar el empleado", $dup ? 1008 : 1002, $dup ? 409 : 400);
        }
        break;


    case 'PUT':
        $input = json_decode(file_get_contents("php://input"), true);
        $id = (int) ($input['id'] ?? 0);

        Validar::estructuraCorreoumg($input['correo_institucional'] ?? '', 3005);
        Validar::estructuratelefono($input['telefono_empleado'] ?? '', 3006);
        Validar::validarEdad($input['fecha_nacimiento'] ?? '', 3007);

        try {
            $empleado = new Empleado($conection, $response);
            $empleado->editar($id, $input);

            $response->success("Empleado actualizado correctamente", [], 1);

        } catch (\Throwable $th) {
            $dup = Validar::mensajeDuplicado($th);
            $response->error($dup ?? "Error al actualizar el empleado", $dup ? 1008 : 3002, $dup ? 409 : 400);
        }
        break;


    case 'DELETE':
        $input = json_decode(file_get_contents("php://input"), true);
        $id = (int) ($input['id'] ?? 0);

        try {
            $empleado = new Empleado($conection, $response);
            $empleado->eliminar($id);

            $response->success("Empleado eliminado correctamente", [], 1);

        } catch (\Throwable $th) {

            $response->error("No se puede eliminar: el empleado tiene registros asociados (usuario, movimientos, etc.)", 3004, 400);
        }
        break;
}
