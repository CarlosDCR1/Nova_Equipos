<?php

require_once("../core/Connection.php");
require_once("../core/Response.php");
require_once("../core/Validar.php");
require_once("../models/Detalle_Devolucion.php");
require_once("../core/Sesion.php");
Sesion::requerirApi();

$conection = new Connection();
$response = new Response();
$method = $_SERVER['REQUEST_METHOD'];

// Validaciones comunes de POST y PUT; corta con error si algo falla
function validarDetalleDevolucion(Connection $conection, Response $response, array $input): void
{
    if (empty($input['id_devolucion']) || empty($input['id_detalle_prestamo'])) {
        $response->error("La devolución y el producto prestado son obligatorios", 19007, 400);
    }

    $estado = ($input['estado_devolucion'] ?? '') !== '' ? $input['estado_devolucion'] : 'BUENO';
    if (!in_array($estado, ['BUENO', 'DANADO', 'PERDIDO'], true)) {
        $response->error("El estado de devolución no es válido", 19008, 400);
    }

    // El producto debe pertenecer al mismo préstamo al que corresponde la devolución
    $statement = $conection->prepare(
        "SELECT COUNT(*)
         FROM devolucion d
         INNER JOIN detalle_prestamo dp ON dp.id_prestamo = d.id_prestamo
         WHERE d.id_devolucion = :id_devolucion AND dp.id_detalle_prestamo = :id_detalle_prestamo"
    );
    $statement->execute([
        ':id_devolucion' => $input['id_devolucion'],
        ':id_detalle_prestamo' => $input['id_detalle_prestamo']
    ]);
    if ((int) $statement->fetchColumn() === 0) {
        $response->error("El producto seleccionado no pertenece al préstamo de esta devolución", 19009, 400);
    }
}

switch ($method) {

    case 'GET':
        try {
            $detalle = new Detalle_Devolucion($conection, $response);

            // Listas para los desplegables: Detalle_Devolucion.php?lista=devoluciones | productos
            if (($_GET['lista'] ?? '') === 'devoluciones') {
                $detalle->listaDevoluciones();
                break;
            }
            if (($_GET['lista'] ?? '') === 'productos') {
                $detalle->listaDetallesPrestamo();
                break;
            }

            $filtros = [
                'id_devolucion' => $_GET['id_devolucion'] ?? null,
                'estado_devolucion' => $_GET['estado_devolucion'] ?? null,
                'observaciones' => $_GET['observaciones'] ?? null,
            ];
            $detalle->getAll($filtros);
        } catch (\Throwable $th) {
            $response->error("Error al consultar detalles de devolución", 19001, 400);
        }
        break;

    case 'POST':
        $input = json_decode(file_get_contents("php://input"), true);

        validarDetalleDevolucion($conection, $response, $input ?? []);
       
        try {
            $detalle = new Detalle_Devolucion($conection, $response);
            $detalle->add($input);

        } catch (\Throwable $th) {
            $dup = Validar::mensajeDuplicado($th);
            $response->error($dup ?? "Error al agregar el detalle de devolución", $dup ? 19006 : 19002, $dup ? 409 : 400);
        }
        break;

    case 'PUT':
        $input = json_decode(file_get_contents("php://input"), true);
        $id = (int) ($input['id'] ?? $input['id_detalle_devolucion'] ?? 0);

        validarDetalleDevolucion($conection, $response, $input ?? []);        
        
        try {
            $detalle = new Detalle_Devolucion($conection, $response);
            $detalle->editar($id, $input);

        } catch (\Throwable $th) {
            $dup = Validar::mensajeDuplicado($th);
            $response->error($dup ?? "Error al actualizar el detalle de devolución", $dup ? 19006 : 19003, $dup ? 409 : 400);
        }
        break;

    case 'DELETE':
        $input = json_decode(file_get_contents("php://input"), true);
        $id = (int) ($input['id'] ?? $input['id_detalle_devolucion'] ?? 0);

        try {
            $detalle = new Detalle_Devolucion($conection, $response);
            $detalle->eliminar($id);

        } catch (\Throwable $th) {
            $response->error("No se pudo eliminar el detalle de devolución", 19004, 400);
        }
        break;
}