<?php

class Detalle_Devolucion
{
    private const ESTADO_DISPONIBLE = 1;
    private const ESTADO_PRESTADO = 2;
    private const ESTADO_DANADO = 4;

    private Connection $connection;
    private Response $response;

    public function __construct(Connection $connection, Response $response)
    {
        $this->connection = $connection;
        $this->response = $response;
    }

    // mueve el valor del fomrulario a la base de datos para que quede en null
    private function idIncidente(mixed $valor): ?int
    {
        return ($valor === null || $valor === '') ? null : (int) $valor;
    }

    //sin tipo de incidente el equipo queda disponible
    private function estadoEquipoPara(?int $idIncidente): int
    {
        return $idIncidente === null ? self::ESTADO_DISPONIBLE : self::ESTADO_DANADO;
    }

    // aqui cambiamos el estado del producto si esta prestado se cambia al regrezar 
    private function aplicarDevolucion(int $idDetallePrestamo, ?int $idIncidente): void
    {
        $statement = $this->connection->prepare(
            "UPDATE equipo SET id_estado_equipo = :nuevo
             WHERE id_equipo = (SELECT id_equipo FROM detalle_prestamo WHERE id_detalle_prestamo = :id_detalle)
               AND id_estado_equipo = :prestado"
        );
        $statement->execute([
            ':nuevo' => $this->estadoEquipoPara($idIncidente),
            ':id_detalle' => $idDetallePrestamo,
            ':prestado' => self::ESTADO_PRESTADO
        ]);
    }

    // Deja el equipo prestado si lo edita 
    private function deshacerDevolucion(int $idDetallePrestamo, ?int $idIncidente): void
    {
        $statement = $this->connection->prepare(
            "UPDATE equipo SET id_estado_equipo = :prestado
             WHERE id_equipo = (SELECT id_equipo FROM detalle_prestamo WHERE id_detalle_prestamo = :id_detalle)
               AND id_estado_equipo = :aplicado"
        );
        $statement->execute([
            ':prestado' => self::ESTADO_PRESTADO,
            ':id_detalle' => $idDetallePrestamo,
            ':aplicado' => $this->estadoEquipoPara($idIncidente)
        ]);
    }

    // LISTA PARA EL DESPLEGABLE DE DEVOLUCIONES 
    public function listaDevoluciones()
    {
        $query = "SELECT d.id_devolucion, d.id_prestamo,
                         CONCAT('Devolución #', d.id_devolucion, ' - Préstamo #', d.id_prestamo, ' - ',
                                e.nombre_estudiante, ' ', COALESCE(e.apellidos_estudiante, ''),
                                ' (', DATE_FORMAT(d.fecha_devolucion, '%d/%m/%Y'), ')') AS texto_devolucion
                  FROM devolucion d
                  INNER JOIN prestamo p ON p.id_prestamo = d.id_prestamo
                  INNER JOIN estudiante e ON e.id_estudiante = p.id_estudiante
                  ORDER BY d.id_devolucion DESC";
        $statement = $this->connection->prepare($query);
        $statement->execute();
        $result = $statement->fetchAll(PDO::FETCH_ASSOC);
        $this->response->success("Devoluciones obtenidas correctamente", $result, 1);
    }

    public function listaDetallesPrestamo()
    {
        $query = "SELECT dp.id_detalle_prestamo, dp.id_prestamo,
                         CONCAT(ma.nombre_marca, ' ', mp.nombre_modelo_producto, ' (', eq.codigo_inventario, ')',
                                ' - Préstamo #', dp.id_prestamo) AS texto_producto,
                         (dd.id_detalle_devolucion IS NOT NULL) AS ya_devuelto
                  FROM detalle_prestamo dp
                  INNER JOIN equipo eq ON eq.id_equipo = dp.id_equipo
                  INNER JOIN modelo_producto mp ON mp.id_modelo_producto = eq.id_modelo_producto
                  INNER JOIN marca ma ON ma.id_marca = mp.id_marca
                  LEFT JOIN detalle_devolucion dd ON dd.id_detalle_prestamo = dp.id_detalle_prestamo
                  ORDER BY dp.id_prestamo DESC, dp.id_detalle_prestamo ASC";
        $statement = $this->connection->prepare($query);
        $statement->execute();
        $result = $statement->fetchAll(PDO::FETCH_ASSOC);
        $this->response->success("Productos prestados obtenidos correctamente", $result, 1);
    }

    // PARA EL GET
    public function getAll(array $filtros = [])
    {
        // para los filtros de busqueda
        $condiciones = [];
        $params = [];

        if (!empty($filtros['id_devolucion'])) {
            $condiciones[] = "id_devolucion = :id_devolucion";
            $params[':id_devolucion'] = $filtros['id_devolucion'];
        }
        if (!empty($filtros['estado_devolucion'])) {
            $condiciones[] = "estado_devolucion = :estado";
            $params[':estado'] = $filtros['estado_devolucion'];
        }
        if (!empty($filtros['observaciones'])) {
            $condiciones[] = "observaciones LIKE :observaciones";
            $params[':observaciones'] = "%{$filtros['observaciones']}%";
        }

        // select completo o filtrado
        $where = $condiciones ? ("WHERE " . implode(" AND ", $condiciones)) : "";
        $query = "SELECT id_detalle_devolucion, id_devolucion, id_detalle_prestamo, id_tipo_incidente,
                         estado_devolucion, observaciones
                  FROM detalle_devolucion $where ORDER BY id_detalle_devolucion DESC";
        $statement = $this->connection->prepare($query);
        $statement->execute($params);
        $result = $statement->fetchAll(PDO::FETCH_ASSOC);
        $this->response->success("Detalles de devolución obtenidos correctamente", $result, 1);
    }

    // PARA EL POST EN EL ADMIN
    // al registrar la devolucion se aplican las reglas y se queda como disponible
    public function add(array $d)
    {
        $idDetalle = (int) $d['id_detalle_prestamo'];
        $idIncidente = $this->idIncidente($d['id_tipo_incidente'] ?? null);
        $estado = ($d['estado_devolucion'] ?? '') !== '' ? $d['estado_devolucion'] : 'BUENO';

        $query = "INSERT INTO detalle_devolucion (id_devolucion, id_detalle_prestamo, id_tipo_incidente, estado_devolucion, observaciones)
                  VALUES (:id_devolucion, :id_detalle_prestamo, :id_incidente, :estado, :observaciones)";
        $statement = $this->connection->prepare($query);
        $statement->execute([
            ':id_devolucion' => $d['id_devolucion'],
            ':id_detalle_prestamo' => $idDetalle,
            ':id_incidente' => $idIncidente,
            ':estado' => $estado,
            ':observaciones' => ($d['observaciones'] ?? '') !== '' ? $d['observaciones'] : null
        ]);

        try {
            $this->aplicarDevolucion($idDetalle, $idIncidente);
        } catch (\Throwable $th) {
            // id_detalle_prestamo es único, así que identifica el registro recién insertado
            $statement = $this->connection->prepare("DELETE FROM detalle_devolucion WHERE id_detalle_prestamo = :id");
            $statement->execute([':id' => $idDetalle]);
            throw $th;
        }

        $this->response->success("Detalle de devolución agregado correctamente", [], 1);
    }

    // PARA EL PUT EN EL ADMIN
    // Si cambia el producto o el tipo de incidente se regresa al estado anterior
    public function editar(int $id, array $d)
    {
        $statement = $this->connection->prepare(
            "SELECT id_detalle_prestamo, id_tipo_incidente FROM detalle_devolucion WHERE id_detalle_devolucion = :id"
        );
        $statement->execute([':id' => $id]);
        $anterior = $statement->fetch(PDO::FETCH_ASSOC);

        if (!$anterior) {
            $this->response->error("El detalle de devolución no existe", 19010, 404);
            return;
        }

        $detalleAnterior = (int) $anterior['id_detalle_prestamo'];
        $incidenteAnterior = $this->idIncidente($anterior['id_tipo_incidente']);
        $detalleNuevo = (int) $d['id_detalle_prestamo'];
        $incidenteNuevo = $this->idIncidente($d['id_tipo_incidente'] ?? null);
        $estadoNuevo = ($d['estado_devolucion'] ?? '') !== '' ? $d['estado_devolucion'] : 'BUENO';
        $cambia = $detalleAnterior !== $detalleNuevo
            || $this->estadoEquipoPara($incidenteAnterior) !== $this->estadoEquipoPara($incidenteNuevo);

        if ($cambia) {
            $this->deshacerDevolucion($detalleAnterior, $incidenteAnterior);
            $this->aplicarDevolucion($detalleNuevo, $incidenteNuevo);
        }

        try {
            $query = "UPDATE detalle_devolucion
                      SET id_devolucion = :id_devolucion,
                          id_detalle_prestamo = :id_detalle_prestamo,
                          id_tipo_incidente = :id_incidente,
                          estado_devolucion = :estado,
                          observaciones = :observaciones
                      WHERE id_detalle_devolucion = :id";
            $statement = $this->connection->prepare($query);
            $statement->execute([
                ':id_devolucion' => $d['id_devolucion'],
                ':id_detalle_prestamo' => $detalleNuevo,
                ':id_incidente' => $incidenteNuevo,
                ':estado' => $estadoNuevo,
                ':observaciones' => ($d['observaciones'] ?? '') !== '' ? $d['observaciones'] : null,
                ':id' => $id
            ]);
        } catch (\Throwable $th) {
            if ($cambia) { // deja el equipo como estaba
                $this->deshacerDevolucion($detalleNuevo, $incidenteNuevo);
                $this->aplicarDevolucion($detalleAnterior, $incidenteAnterior);
            }
            throw $th;
        }

        $this->response->success("Detalle de devolución actualizado correctamente", [], 1);
    }

    // DELETE EN EL ADMIN
    // Al eliminar la devolución, el equipo vuelve a quedar como prestado.
    public function eliminar(int $id)
    {
        $statement = $this->connection->prepare(
            "SELECT id_detalle_prestamo, id_tipo_incidente FROM detalle_devolucion WHERE id_detalle_devolucion = :id"
        );
        $statement->execute([':id' => $id]);
        $anterior = $statement->fetch(PDO::FETCH_ASSOC);

        $statement = $this->connection->prepare("DELETE FROM detalle_devolucion WHERE id_detalle_devolucion = :id");
        $statement->execute([':id' => $id]);

        if ($anterior) {
            $this->deshacerDevolucion(
                (int) $anterior['id_detalle_prestamo'],
                $this->idIncidente($anterior['id_tipo_incidente'])
            );
        }
        $this->response->success("Detalle de devolución eliminado correctamente", [], 1);
    }
}