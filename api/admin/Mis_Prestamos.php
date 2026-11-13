<?php
require_once("../core/Connection.php");
require_once("../core/Response.php");
require_once("../core/Sesion.php");
require_once("../core/validar.php");
require_once("../models/Mis_Prestamos.php");
Sesion::requerirApi();

$conection = new Connection();
$response = new Response();
$method = $_SERVER['REQUEST_METHOD'];

$derechos = ['GET' => 'ver', 'PUT' => 'editar'];
if (!isset($derechos[$method])) {
    $response->error("Método no permitido", 405, 405);
}
Sesion::requerirPermiso('Prestamos', 'Mis prestamos', $derechos[$method]);

if (!Sesion::esEstudiante()) {
    $response->error("Esta pantalla es solo para estudiantes", 5101, 403);
}

$idEstudiante = (int) $_SESSION['idEstudiante'];
$misPrestamos = new Mis_Prestamos($conection);

switch ($method) {

    case 'GET':
        try {
            $response->success("OK", $misPrestamos->listar($idEstudiante), 1);
        } catch (\Throwable $th) {
            $response->error("Error al consultar tus préstamos", 5100, 400);
        }
        break;

    case 'PUT':
        $input = json_decode(file_get_contents("php://input"), true);
        $id = (int) ($input['id'] ?? 0);
        $accion = (string) ($input['accion'] ?? '');

        if ($accion !== 'cancelar') {
            $response->error("Acción no válida", 5102, 400);
        }

        try {
            $data = $misPrestamos->cancelar($id, $idEstudiante);
            $response->success("Préstamo cancelado correctamente", $data, 1);
        } catch (ReglaApartado $e) {
            $response->error($e->getMessage(), 5103, 409);
        } catch (\Throwable $th) {
            $response->error("Error al cancelar el préstamo", 5104, 400);
        }
        break;
}