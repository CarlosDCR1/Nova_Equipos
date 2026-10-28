<?php
require_once("../core/Connection.php");
require_once("../core/Response.php");
require_once("../core/Sesion.php");
Sesion::requerirApi();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::error("Método no permitido", 405, 405);
}

//si ya hay un prestamo activo entonces aca valida el estado :)
const PRESTADO = "EXISTS (
    SELECT 1 FROM detalle_prestamo dp
    JOIN prestamo p ON p.id_prestamo = dp.id_prestamo
    JOIN estado_prestamo ep ON ep.id_estado_prestamo = p.id_estado_prestamo
    WHERE dp.id_equipo = e.id_equipo
      AND dp.estado_entrega = 'ENTREGADO'
      AND p.fecha_devolucion_real IS NULL
      AND ep.nombre_estado NOT IN ('RECHAZADO','CANCELADO','DEVUELTO'))";

try {
    $conn = new Connection();

    // esto es para los estudiantes en su vista y el join filtra por carrera los elemmentos disponibles para el 
    if (Sesion::esEstudiante()) {
        $st = $conn->prepare(
            "SELECT c.id_carrera, c.nombre_carrera
             FROM estudiante s JOIN carrera c ON c.id_carrera = s.id_carrera
             WHERE s.id_estudiante = :id");
        $st->execute([':id' => $_SESSION['idEstudiante']]);
        $carrera = $st->fetch(PDO::FETCH_ASSOC);

        $categorias = [];
        if ($carrera) {
            $st = $conn->prepare(
                "SELECT cp.nombre_categoria_producto AS categoria,
                        COUNT(e.id_equipo) AS total,
                        COALESCE(SUM(CASE WHEN ee.permite_prestamo = 1 AND NOT " . PRESTADO . " THEN 1 ELSE 0 END), 0) AS disponibles
                 FROM carrera_categoria cc
                 JOIN categoria_producto cp ON cp.id_categoria_producto = cc.id_categoria_producto AND cp.is_active = 1
                 LEFT JOIN modelo_producto mp ON mp.id_categoria_producto = cp.id_categoria_producto AND mp.is_active = 1
                 LEFT JOIN equipo e ON e.id_modelo_producto = mp.id_modelo_producto
                 LEFT JOIN estado_equipo ee ON ee.id_estado_equipo = e.id_estado_equipo
                 WHERE cc.id_carrera = :carrera
                 GROUP BY cp.id_categoria_producto, cp.nombre_categoria_producto
                 ORDER BY cp.nombre_categoria_producto");
            $st->execute([':carrera' => $carrera['id_carrera']]);
            $categorias = $st->fetchAll(PDO::FETCH_ASSOC);
        }

        $totalDisp = 0;
        foreach ($categorias as &$c) {
            $c['total'] = (int) $c['total'];
            $c['disponibles'] = (int) $c['disponibles'];
            $totalDisp += $c['disponibles'];
        }
        unset($c);

        Response::success("OK", [
            'tipo'             => 'estudiante',
            'carrera'          => $carrera['nombre_carrera'] ?? null,
            'total_disponibles'=> $totalDisp,
            'categorias'       => $categorias,
        ], 1);
    }

    // ahora para el administrador el puede ver todo entonces solo se le hace un resumen de los prestamos y equipos y stock por modelo
    $esAdmin = in_array('Administrador', array_column($_SESSION['roles'] ?? [], 'nombreRol'), true);

    $eq = $conn->query(
        "SELECT COUNT(*) AS total,
                COALESCE(SUM(CASE WHEN " . PRESTADO . " THEN 1 ELSE 0 END), 0) AS prestados,
                COALESCE(SUM(CASE WHEN ee.permite_prestamo = 1 AND NOT " . PRESTADO . " THEN 1 ELSE 0 END), 0) AS disponibles,
                COALESCE(SUM(CASE WHEN ee.permite_prestamo = 0 THEN 1 ELSE 0 END), 0) AS no_disponibles
         FROM equipo e JOIN estado_equipo ee ON ee.id_estado_equipo = e.id_estado_equipo"
    )->fetch(PDO::FETCH_ASSOC);

    $pr = $conn->query(
        "SELECT COUNT(*) AS activos,
                COALESCE(SUM(CASE WHEN p.fecha_devolucion_programada < NOW() THEN 1 ELSE 0 END), 0) AS vencidos
         FROM prestamo p JOIN estado_prestamo ep ON ep.id_estado_prestamo = p.id_estado_prestamo
         WHERE p.fecha_devolucion_real IS NULL
           AND ep.nombre_estado NOT IN ('RECHAZADO','CANCELADO','DEVUELTO')"
    )->fetch(PDO::FETCH_ASSOC);

    // Stock por modelo ordenado de menos a mas 
    $stock = $conn->query(
        "SELECT mp.nombre_modelo_producto AS modelo,
                cp.nombre_categoria_producto AS categoria,
                COUNT(e.id_equipo) AS total,
                COALESCE(SUM(CASE WHEN ee.permite_prestamo = 1 AND NOT " . PRESTADO . " THEN 1 ELSE 0 END), 0) AS disponibles
         FROM modelo_producto mp
         JOIN categoria_producto cp ON cp.id_categoria_producto = mp.id_categoria_producto
         LEFT JOIN equipo e ON e.id_modelo_producto = mp.id_modelo_producto
         LEFT JOIN estado_equipo ee ON ee.id_estado_equipo = e.id_estado_equipo
         WHERE mp.is_active = 1
         GROUP BY mp.id_modelo_producto, mp.nombre_modelo_producto, cp.nombre_categoria_producto
         ORDER BY disponibles ASC, modelo ASC
         LIMIT 10"
    )->fetchAll(PDO::FETCH_ASSOC);
    foreach ($stock as &$s) {
        $s['total'] = (int) $s['total'];
        $s['disponibles'] = (int) $s['disponibles'];
    }
    unset($s);

    $data = [
        'tipo'     => $esAdmin ? 'administrador' : 'empleado',
        'equipos'  => array_map('intval', $eq),
        'prestamos'=> array_map('intval', $pr),
        'stock'    => $stock,
    ];

    if ($esAdmin) {
        $us = $conn->query(
            "SELECT COUNT(*) AS activos,
                    COALESCE(SUM(idEmpleado IS NOT NULL), 0) AS empleados,
                    COALESCE(SUM(idEstudiante IS NOT NULL), 0) AS estudiantes
             FROM tblusuario WHERE is_active = 1"
        )->fetch(PDO::FETCH_ASSOC);
        $data['usuarios'] = array_map('intval', $us);
    }

    Response::success("OK", $data, 1);

} catch (\Throwable $th) {
    Response::error("Error al cargar el panel principal", 1010, 400);
}