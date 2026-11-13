<?php
require_once("../core/Sesion.php");      
require_once("../core/Connection.php");
require_once("../core/Response.php");
require_once("../core/Validar.php");
require_once("../models/Devolucion.php");

Sesion::requerirApi();

$conection = new Connection();
$response = new Response();
$method = $_SERVER['REQUEST_METHOD'];

$derechos = ['GET' => 'ver', 'POST' => 'insertar', 'PUT' => 'editar', 'DELETE' => 'eliminar'];
Sesion::requerirPermiso('Devoluciones', 'Devolucion', $derechos[$method] ?? 'ver');

function validarDevolucion(Connection $conection, Response $response, array $input, bool $esNuevo): void
{
    if (empty($input['id_prestamo'])) {
        $response->error("El préstamo es obligatorio", 17007, 400);
    }

    if ($esNuevo) {
        if (empty($input['id_usuario'])) {
            $response->error("No se pudo identificar al usuario conectado", 17007, 401);
        }

        $statement = $conection->prepare("SELECT COUNT(*) FROM tblusuario WHERE idUsuario = :id AND idEmpleado IS NOT NULL");
        $statement->execute([':id' => $input['id_usuario']]);
        if ((int) $statement->fetchColumn() === 0) {
            $response->error("El usuario que registra la devolución debe ser un empleado", 17009, 400);
        }
    }
}

switch ($method) {

    case 'GET':
        try {
            $devolucion = new Devolucion($conection, $response);

            if (($_GET['lista'] ?? '') === 'prestamos') {
                $devolucion->listaPrestamos();
                break;
            }
            if (($_GET['lista'] ?? '') === 'usuarios') {
                $devolucion->listaUsuariosEmpleado((int) ($_SESSION['idUsuario'] ?? 0));
                break;
            }

            $filtros = [
                'id_prestamo' => $_GET['id_prestamo'] ?? null,
                'observaciones' => $_GET['observaciones'] ?? null,
            ];
            $devolucion->getAll($filtros);
        } catch (\Throwable $th) {
            $response->error("Error al consultar devoluciones", 17001, 400);
        }
        break;

    case 'POST':
        $input = json_decode(file_get_contents("php://input"), true) ?? [];
        $input['id_usuario'] = (int) ($_SESSION['idUsuario'] ?? 0);
        validarDevolucion($conection, $response, $input, true);
      
        try {
            $devolucion = new Devolucion($conection, $response);
            $devolucion->add($input);

        } catch (\Throwable $th) {
            $esDuplicado = $th instanceof PDOException && ($th->errorInfo[1] ?? 0) == 1062;
            $response->error(
                $esDuplicado ? "Ese préstamo ya tiene una devolución registrada" : "Error al registrar la devolución",
                $esDuplicado ? 17006 : 17002,
                $esDuplicado ? 409 : 400
            );
        }
        break;

    case 'PUT':
        $input = json_decode(file_get_contents("php://input"), true) ?? [];
        $id = (int) ($input['id'] ?? $input['id_devolucion'] ?? 0);
 
        validarDevolucion($conection, $response, $input, false);
 
        try {
            $devolucion = new Devolucion($conection, $response);
            $devolucion->editar($id, $input);
 
        } catch (\Throwable $th) {
            $response->error("Error al actualizar la devolución", 17003, 400);
        }
        break;

    case 'DELETE':
        $input = json_decode(file_get_contents("php://input"), true);
        $id = (int) ($input['id'] ?? $input['id_devolucion'] ?? 0);

        try {
            $devolucion = new Devolucion($conection, $response);
            $devolucion->eliminar($id);

        } catch (\Throwable $th) {
            $response->error("No se puede eliminar: la devolución tiene detalles de devolución asociados", 17004, 400);
        }
        break;
}