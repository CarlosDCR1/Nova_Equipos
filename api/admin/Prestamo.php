<?php

require_once("../core/Connection.php");
require_once("../core/Response.php");
require_once("../core/Validar.php");
require_once("../models/Prestamo.php");

$conection = new Connection();
$response = new Response();
$method = $_SERVER['REQUEST_METHOD'];

function fechaHoraValida(mixed $valor): bool
{
    $texto = trim((string) $valor);
    return $texto !== '' && strtotime($texto) !== false;
}

// Validaciones comunes de POST y PUT; corta con error si algo fall
function validarPrestamo(Connection $conection, Response $response, array $input): void
{
    if (empty($input['id_estudiante']) || empty($input['id_estado_prestamo']) || empty($input['id_usuario_registro'])) {
        $response->error("El estudiante, el estado y el usuario que registra son obligatorios", 14007, 400);
    }

    $statement = $conection->prepare("SELECT COUNT(*) FROM tblusuario WHERE idUsuario = :id AND idEmpleado IS NOT NULL");
    $statement->execute([':id' => $input['id_usuario_registro']]);
    if ((int) $statement->fetchColumn() === 0) {
        $response->error("El usuario que registra el préstamo debe ser un empleado", 14009, 400);
    }

    if (!fechaHoraValida($input['fecha_devolucion_programada'] ?? '')) {
        $response->error("La fecha de devolución programada es obligatoria y debe ser válida", 14005, 400);
    }
}

switch ($method) {

    case 'GET':
        try {
            $prestamo = new Prestamo($conection, $response);

            if (($_GET['lista'] ?? '') === 'estudiantes') {
                $prestamo->listaEstudiantes();
                break;
            }
            if (($_GET['lista'] ?? '') === 'usuarios') {
                $prestamo->listaUsuariosEmpleado();
                break;
            }

            $filtros = [
                'id_estudiante' => $_GET['id_estudiante'] ?? null,
                'id_estado_prestamo' => $_GET['id_estado_prestamo'] ?? null,
                'observaciones' => $_GET['observaciones'] ?? null,
            ];
            $prestamo->getAll($filtros);
        } catch (\Throwable $th) {
            $response->error("Error al consultar préstamos", 14001, 400);
        }
        break;

    case 'POST':
        $input = json_decode(file_get_contents("php://input"), true);

        validarPrestamo($conection, $response, $input ?? []);

        // Al crear un préstamo, la devolución programada debe ser posterior a hoy
        if (strtotime(substr($input['fecha_devolucion_programada'], 0, 10)) <= strtotime('today')) {
            $response->error("La fecha de devolución programada debe ser posterior a la fecha actual", 14010, 400);
        }

        try {
            $prestamo = new Prestamo($conection, $response);
            $prestamo->add($input);

        } catch (\Throwable $th) {
            $dup = Validar::mensajeDuplicado($th);
            // 3819 (MySQL) / 4025 (MariaDB): se violó el CHECK de fechas de la tabla prestamo
            if ($th instanceof PDOException && in_array($th->errorInfo[1] ?? 0, [3819, 4025])) {
                $response->error("La fecha de devolución programada no puede ser anterior a la fecha del préstamo", 14011, 400);
            }
            $response->error($dup ?? "Error al agregar el préstamo", $dup ? 14006 : 14002, $dup ? 409 : 400);
        }
        break;

    case 'PUT':
        $input = json_decode(file_get_contents("php://input"), true);
        $id = (int) ($input['id'] ?? $input['id_prestamo'] ?? 0);

        validarPrestamo($conection, $response, $input ?? []);

        try {
            $prestamo = new Prestamo($conection, $response);
            $prestamo->editar($id, $input);

        } catch (\Throwable $th) {
            $dup = Validar::mensajeDuplicado($th);
            if ($th instanceof PDOException && in_array($th->errorInfo[1] ?? 0, [3819, 4025])) {
                $response->error("La fecha de devolución programada no puede ser anterior a la fecha del préstamo", 14011, 400);
            }
            $response->error($dup ?? "Error al actualizar el préstamo", $dup ? 14006 : 14003, $dup ? 409 : 400);
        }
        break;

    case 'DELETE':
        $input = json_decode(file_get_contents("php://input"), true);
        $id = (int) ($input['id'] ?? $input['id_prestamo'] ?? 0);

        try {
            $prestamo = new Prestamo($conection, $response);
            $prestamo->eliminar($id);

        } catch (\Throwable $th) {
            $response->error("No se puede eliminar: el préstamo tiene detalles o devoluciones asociados", 14004, 400);
        }
        break;
}