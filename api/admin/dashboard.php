<?php
require_once("../core/Connection.php");
require_once("../core/Response.php");
require_once("../core/Sesion.php");
require_once("../models/Dashboard.php");
Sesion::requerirApi();

$conection = new Connection();
$response = new Response();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    $response->error("Método no permitido", 405, 405);
}

$desde = trim((string) ($_GET['desde'] ?? ''));
$hasta = trim((string) ($_GET['hasta'] ?? ''));

if (($desde === '') !== ($hasta === '')) {
    $response->error("Indica la fecha inicial y la final", 1011, 400);
}
foreach ([$desde, $hasta] as $fecha) {
    if ($fecha !== '') {
        $f = DateTime::createFromFormat('Y-m-d', $fecha);
        if (!$f || $f->format('Y-m-d') !== $fecha) {
            $response->error("Las fechas no son válidas", 1012, 400);
        }
    }
}
if ($desde !== '' && $desde > $hasta) {
    $response->error("La fecha inicial no puede ser posterior a la final", 1013, 400);
}
if ($desde !== '' && (new DateTime($desde))->diff(new DateTime($hasta))->days > 1100) {
    $response->error("El rango máximo es de 3 años", 1014, 400);
}

try {
    $dashboard = new Dashboard($conection, $response);

    if (Sesion::esEstudiante()) {
        $data = $dashboard->paraEstudiante((int) $_SESSION['idEstudiante']);
    } else {
        $esAdmin = in_array('Administrador', array_column($_SESSION['roles'] ?? [], 'nombreRol'), true);
        $data = $dashboard->paraPersonal($esAdmin, $desde ?: null, $hasta ?: null);
    }

    $response->success("OK", $data, 1);
} catch (\Throwable $th) {
    $response->error("Error al cargar el panel principal", 1010, 400);
}