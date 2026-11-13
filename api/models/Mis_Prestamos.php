<?php
require_once(__DIR__ . "/Apartado.php");

class Mis_Prestamos
{
    private Connection $connection;
    private Apartado $apartado;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
        $this->apartado = new Apartado($connection);
    }

    public function listar(int $idEstudiante): array
    {
        $this->apartado->liberarVencidos();

        $statement = $this->connection->prepare(
            "SELECT p.id_prestamo, ep.nombre_estado AS estado, p.fecha_prestamo, p.apartado_hasta, p.fecha_entrega,
                    p.fecha_devolucion_programada, p.observaciones,
                    (SELECT d.fecha_devolucion FROM devolucion d WHERE d.id_prestamo = p.id_prestamo LIMIT 1) AS fecha_devolucion
             FROM prestamo p
             JOIN estado_prestamo ep ON ep.id_estado_prestamo = p.id_estado_prestamo
             WHERE p.id_estudiante = :id
             ORDER BY p.id_prestamo DESC");
        $statement->execute([':id' => $idEstudiante]);
        $prestamos = $statement->fetchAll(PDO::FETCH_ASSOC);

        $porPrestamo = [];
        if ($prestamos) {
            $ids = array_map('intval', array_column($prestamos, 'id_prestamo'));
            $marcas = implode(',', array_fill(0, count($ids), '?'));
            $statement = $this->connection->prepare(
                "SELECT dp.id_prestamo, e.codigo_inventario AS codigo, mp.nombre_modelo_producto AS modelo,
                        ma.nombre_marca AS marca,
                        (dd.id_detalle_devolucion IS NOT NULL) AS devuelto,
                        dd.estado_devolucion
                 FROM detalle_prestamo dp
                 JOIN equipo e ON e.id_equipo = dp.id_equipo
                 JOIN modelo_producto mp ON mp.id_modelo_producto = e.id_modelo_producto
                 JOIN marca ma ON ma.id_marca = mp.id_marca
                 LEFT JOIN detalle_devolucion dd ON dd.id_detalle_prestamo = dp.id_detalle_prestamo
                 WHERE dp.id_prestamo IN ($marcas)
                 ORDER BY dp.id_prestamo, dp.id_detalle_prestamo");
            $statement->execute($ids);
            foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $f) {
                $porPrestamo[(int) $f['id_prestamo']][] = [
                    'codigo'            => $f['codigo'],
                    'modelo'            => $f['modelo'],
                    'marca'             => $f['marca'],
                    'devuelto'          => (bool) $f['devuelto'],
                    'estado_devolucion' => $f['estado_devolucion'],
                ];
            }
        }

        foreach ($prestamos as &$p) {
            $p['id_prestamo'] = (int) $p['id_prestamo'];
            $p['equipos'] = $porPrestamo[$p['id_prestamo']] ?? [];
        }
        unset($p);

        return [
            'prestamos' => $prestamos,
            'abiertos'  => $this->apartado->abiertos($idEstudiante),
            'max'       => Apartado::MAX_EQUIPOS,
        ];
    }

    public function cancelar(int $idPrestamo, int $idEstudiante): array
    {
        return $this->apartado->cancelar($idPrestamo, $idEstudiante);
    }
}