<?php
require_once(__DIR__ . "/Apartado.php"); 

class Dashboard
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    
    public function paraEstudiante(int $idEstudiante): array
    {
        $statement = $this->connection->prepare(
            "SELECT c.id_carrera, c.nombre_carrera
             FROM estudiante s JOIN carrera c ON c.id_carrera = s.id_carrera
             WHERE s.id_estudiante = :id");
        $statement->execute([':id' => $idEstudiante]);
        $carrera = $statement->fetch(PDO::FETCH_ASSOC);

        $categorias = [];
        if ($carrera) {
            $statement = $this->connection->prepare(
                "SELECT cp.id_categoria_producto AS id_categoria, cp.nombre_categoria_producto AS categoria,
                        COUNT(e.id_equipo) AS total,
                        COALESCE(SUM(CASE WHEN ee.permite_prestamo = 1 AND NOT " . Apartado::ocupado() . " THEN 1 ELSE 0 END), 0) AS disponibles
                 FROM carrera_categoria cc
                 JOIN categoria_producto cp ON cp.id_categoria_producto = cc.id_categoria_producto AND cp.is_active = 1
                 LEFT JOIN modelo_producto mp ON mp.id_categoria_producto = cp.id_categoria_producto AND mp.is_active = 1
                 LEFT JOIN equipo e ON e.id_modelo_producto = mp.id_modelo_producto
                 LEFT JOIN estado_equipo ee ON ee.id_estado_equipo = e.id_estado_equipo
                 WHERE cc.id_carrera = :carrera
                 GROUP BY cp.id_categoria_producto, cp.nombre_categoria_producto
                 ORDER BY cp.nombre_categoria_producto");
            $statement->execute([':carrera' => $carrera['id_carrera']]);
            $categorias = $statement->fetchAll(PDO::FETCH_ASSOC);
        }

        $totalDisponibles = 0;
        foreach ($categorias as &$c) {
            $c['id_categoria'] = (int) $c['id_categoria'];
            $c['total'] = (int) $c['total'];
            $c['disponibles'] = (int) $c['disponibles'];
            $totalDisponibles += $c['disponibles'];
        }
        unset($c);

        return [
            'tipo'              => 'estudiante',
            'carrera'           => $carrera['nombre_carrera'] ?? null,
            'total_disponibles' => $totalDisponibles,
            'categorias'        => $categorias,
        ];
    }

  
    public function paraPersonal(bool $esAdmin): array
    {
      
        $equipos = $this->connection->query(
            "SELECT COUNT(*) AS total,
                    COALESCE(SUM(CASE WHEN " . Apartado::prestado() . " THEN 1 ELSE 0 END), 0) AS prestados,
                    COALESCE(SUM(CASE WHEN ee.permite_prestamo = 1 AND NOT " . Apartado::ocupado() . " THEN 1 ELSE 0 END), 0) AS disponibles,
                    COALESCE(SUM(CASE WHEN ee.permite_prestamo = 0 AND NOT " . Apartado::prestado() . " THEN 1 ELSE 0 END), 0) AS no_disponibles
             FROM equipo e JOIN estado_equipo ee ON ee.id_estado_equipo = e.id_estado_equipo"
        )->fetch(PDO::FETCH_ASSOC);

        $prestamos = $this->connection->query(
            "SELECT COUNT(*) AS activos,
                    COALESCE(SUM(CASE WHEN p.fecha_devolucion_programada < NOW() THEN 1 ELSE 0 END), 0) AS vencidos
             FROM prestamo p JOIN estado_prestamo ep ON ep.id_estado_prestamo = p.id_estado_prestamo
             WHERE ep.nombre_estado IN ('Activo', 'Vencido')
               AND EXISTS (SELECT 1 FROM detalle_prestamo dp
                           WHERE dp.id_prestamo = p.id_prestamo
                             AND NOT EXISTS (SELECT 1 FROM detalle_devolucion dd WHERE dd.id_detalle_prestamo = dp.id_detalle_prestamo))"
        )->fetch(PDO::FETCH_ASSOC);

        
        $pendientes = $this->connection->query(
            "SELECT COALESCE(SUM(ep.nombre_estado = 'Solicitado'), 0) AS por_aprobar,
                    COALESCE(SUM(ep.nombre_estado = 'Apartado'), 0) AS por_recoger
             FROM prestamo p JOIN estado_prestamo ep ON ep.id_estado_prestamo = p.id_estado_prestamo
             WHERE ep.nombre_estado IN ('Solicitado', 'Apartado') AND p.apartado_hasta >= NOW()"
        )->fetch(PDO::FETCH_ASSOC);

        // Stock por modelo, de menos a más
        $stock = $this->connection->query(
            "SELECT mp.nombre_modelo_producto AS modelo,
                    cp.nombre_categoria_producto AS categoria,
                    COUNT(e.id_equipo) AS total,
                    COALESCE(SUM(CASE WHEN ee.permite_prestamo = 1 AND NOT " . Apartado::ocupado() . " THEN 1 ELSE 0 END), 0) AS disponibles
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
            'tipo'      => $esAdmin ? 'administrador' : 'empleado',
            'equipos'   => array_map('intval', $equipos),
            'prestamos' => array_map('intval', $prestamos + $pendientes),
            'stock'     => $stock,
        ];

        if ($esAdmin) {
            $usuarios = $this->connection->query(
                "SELECT COUNT(*) AS activos,
                        COALESCE(SUM(idEmpleado IS NOT NULL), 0) AS empleados,
                        COALESCE(SUM(idEstudiante IS NOT NULL), 0) AS estudiantes
                 FROM tblusuario WHERE is_active = 1"
            )->fetch(PDO::FETCH_ASSOC);
            $data['usuarios'] = array_map('intval', $usuarios);
        }

        return $data;
    }
}