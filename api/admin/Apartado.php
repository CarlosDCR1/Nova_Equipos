<?php
require_once("../core/Connection.php");
require_once("../core/Response.php");
require_once("../core/Sesion.php");
require_once("../models/Apartado.php");
Sesion::requerirApi();

$conection = new Connection();
$response = new Response();
$method = $_SERVER['REQUEST_METHOD'];
$apartado = new Apartado($conection);

switch ($method) {

    // Estudiante: equipos que puede apartar y sus préstamos. Empleado: solicitudes y apartados pendientes.
    case 'GET':
        Sesion::requerirPermiso('Prestamos', 'Prestamo', 'ver');
        try {
            if (Sesion::esEstudiante()) {
                $data = [
                    'disponibles'  => $apartado->disponibles((int) $_SESSION['idEstudiante']),
                    'mis_prestamos' => $apartado->misPrestamos((int) $_SESSION['idEstudiante']),
                    'abiertos' => $apartado->abiertos((int) $_SESSION['idEstudiante']),
                    'max' => Apartado::MAX_EQUIPOS,
                ];
            } else {
                $data = ['pendientes' => $apartado->pendientes()];
            }
            $response->success("OK", $data, 1);
        } catch (\Throwable $th) {
            $response->error("Error al consultar los apartados", 5000, 400);
        }
        break;

    // El estudiante y el usuario salen de la sesión, nunca del formulario.
    case 'POST':
        Sesion::requerirPermiso('Prestamos', 'Prestamo', 'insertar');
        if (!Sesion::esEstudiante()) {
            $response->error("Solo un estudiante puede apartar equipos desde su cuenta", 5001, 403);
        }
        $input = json_decode(file_get_contents("php://input"), true);

        try {
            $data = $apartado->apartar((int) $_SESSION['idEstudiante'], (int) $_SESSION['idUsuario'], (array) ($input['equipos'] ?? []));

            $estados = array_column($data['prestamos'], 'estado');
            if (in_array('Solicitado', $estados, true) && in_array('Apartado', $estados, true)) {
                $mensaje = "Apartamos los equipos de hasta Q8,000. Los demás esperan la aprobación de un empleado";
            } elseif (in_array('Solicitado', $estados, true)) {
                $mensaje = "Solicitud enviada: un empleado debe aprobarla";
            } else {
                $mensaje = "Equipo apartado: tienes " . Apartado::HORAS_APARTADO . " horas para recogerlo";
            }
            $response->success($mensaje, $data, 1);
        } catch (ReglaApartado $e) {
            $response->error($e->getMessage(), 5002, 409);
        } catch (\Throwable $th) {
            $response->error("Error al apartar el equipo", 5003, 400);
        }
        break;

    // permisos para aprobar y rechazar y entregar  solo los empleados
    case 'PUT':
        $input = json_decode(file_get_contents("php://input"), true);
        $id = (int) ($input['id'] ?? 0);
        $accion = (string) ($input['accion'] ?? '');

        if ($accion === 'cancelar') {
            Sesion::requerirPermiso('Prestamos', 'Prestamo', 'ver');
            if (!Sesion::esEstudiante()) {
                Sesion::requerirPermiso('Prestamos', 'Prestamo', 'editar');
            }
        } elseif (in_array($accion, ['aprobar', 'rechazar', 'entregar'], true)) {
            Sesion::requerirPermiso('Prestamos', 'Prestamo', 'editar');
            if (Sesion::esEstudiante()) {
                $response->error("Esta acción es solo para empleados", 5004, 403);
            }
        } else {
            $response->error("Acción no válida", 5005, 400);
        }

        try {
            switch ($accion) {
                case 'cancelar':
                    $data = $apartado->cancelar($id, Sesion::esEstudiante() ? (int) $_SESSION['idEstudiante'] : null);
                    break;
                case 'aprobar':
                    $data = $apartado->aprobar($id, (int) $_SESSION['idUsuario']);
                    break;
                case 'rechazar':
                    $data = $apartado->rechazar($id, $input['motivo'] ?? null);
                    break;
                case 'entregar':
                    $data = $apartado->entregar($id, (int) $_SESSION['idUsuario']);
                    break;
            }
            $response->success("Préstamo actualizado correctamente", $data, 1);
        } catch (ReglaApartado $e) {
            $response->error($e->getMessage(), 5002, 409);
        } catch (\Throwable $th) {
            $response->error("Error al actualizar el préstamo", 5006, 400);
        }
        break;
}