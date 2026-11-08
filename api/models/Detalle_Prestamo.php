<?php

class Detalle_Prestamo
{
    // se declaran como constantes para cambiarlas en un futuro
    private const ESTADO_DISPONIBLE = 1;
    private const ESTADO_PRESTADO = 2;

    private Connection $connection;
    private Response $response;

    public function __construct(Connection $connection, Response $response)
    {
        $this->connection = $connection;
        $this->response = $response;
    }

    // Pasa el equipo de "disponible" a "prestado". Solo lo hace si está disponible;
    // devuelve true si lo logró y false si el equipo no estaba disponible.
    private function reservarEquipo(int $idEquipo): bool
    {
        $statement = $this->connection->prepare(
            "UPDATE equipo SET id_estado_equipo = :prestado
             WHERE id_equipo = :id AND id_estado_equipo = :disponible"
        );
        $statement->execute([
            ':prestado' => self::ESTADO_PRESTADO,
            ':id' => $idEquipo,
            ':disponible' => self::ESTADO_DISPONIBLE
        ]);
        return $statement->rowCount() === 1;
    }

    private function liberarEquipo(int $idEquipo): void
    {
        $statement = $this->connection->prepare(
            "UPDATE equipo SET id_estado_equipo = :disponible
             WHERE id_equipo = :id AND id_estado_equipo = :prestado"
        );
        $statement->execute([
            ':disponible' => self::ESTADO_DISPONIBLE,
            ':id' => $idEquipo,
            ':prestado' => self::ESTADO_PRESTADO
        ]);
    }

    // LISTA PARA EL DESPLEGABLE DE PRÉSTAMOS 
    public function listaPrestamos()
    {
        $query = "SELECT p.id_prestamo,
                         CONCAT('Préstamo #', p.id_prestamo, ' - ', e.nombre_estudiante, ' ', COALESCE(e.apellidos_estudiante, ''),
                                ' - ', e.carnet_estudiante,
                                CASE WHEN d.id_devolucion IS NULL THEN '' ELSE ' (ya devuelto)' END) AS texto_prestamo
                  FROM prestamo p
                  INNER JOIN estudiante e ON e.id_estudiante = p.id_estudiante
                  LEFT JOIN devolucion d ON d.id_prestamo = p.id_prestamo
                  ORDER BY p.id_prestamo DESC";
        $statement = $this->connection->prepare($query);
        $statement->execute();
        $result = $statement->fetchAll(PDO::FETCH_ASSOC);
        $this->response->success("Préstamos obtenidos correctamente", $result, 1);
    }

    public function listaEquipos()
    {
        $query = "SELECT eq.id_equipo, eq.id_estado_equipo,
                         CONCAT(eq.codigo_inventario, ' - ', ma.nombre_marca, ' ', mp.nombre_modelo_producto,
                                ' (', ee.nombre_estado, ')') AS texto_equipo
                  FROM equipo eq
                  INNER JOIN modelo_producto mp ON mp.id_modelo_producto = eq.id_modelo_producto
                  INNER JOIN marca ma ON ma.id_marca = mp.id_marca
                  INNER JOIN estado_equipo ee ON ee.id_estado_equipo = eq.id_estado_equipo
                  ORDER BY eq.codigo_inventario ASC";
        $statement = $this->connection->prepare($query);
        $statement->execute();
        $result = $statement->fetchAll(PDO::FETCH_ASSOC);
        $this->response->success("Equipos obtenidos correctamente", $result, 1);
    }

    // PARA EL GET
    public function getAll(array $filtros = [])
    {
        // para los filtros de busqueda
        $condiciones = [];
        $params = [];

        if (!empty($filtros['id_prestamo'])) {
            $condiciones[] = "id_prestamo = :id_prestamo";
            $params[':id_prestamo'] = $filtros['id_prestamo'];
        }
        if (!empty($filtros['id_equipo'])) {
            $condiciones[] = "id_equipo = :id_equipo";
            $params[':id_equipo'] = $filtros['id_equipo'];
        }
        if (!empty($filtros['observaciones'])) {
            $condiciones[] = "observaciones_entrega LIKE :observaciones";
            $params[':observaciones'] = "%{$filtros['observaciones']}%";
        }

        // select completo o filtrado
        $where = $condiciones ? ("WHERE " . implode(" AND ", $condiciones)) : "";
        $query = "SELECT id_detalle_prestamo, id_prestamo, id_equipo, observaciones_entrega
                  FROM detalle_prestamo $where ORDER BY id_detalle_prestamo DESC";
        $statement = $this->connection->prepare($query);
        $statement->execute($params);
        $result = $statement->fetchAll(PDO::FETCH_ASSOC);
        $this->response->success("Detalles de préstamo obtenidos correctamente", $result, 1);
    }

    // PARA EL POST EN EL ADMIN
    // Al asignar el equipo su estado pasa automaticamente a estar prestado
    public function add(array $d)
    {
        $idEquipo = (int) $d['id_equipo'];

        if (!$this->reservarEquipo($idEquipo)) {
            $this->response->error("El equipo no está disponible para préstamo", 18008, 409);
            return;
        }

        try {
            $query = "INSERT INTO detalle_prestamo (id_prestamo, id_equipo, observaciones_entrega)
                      VALUES (:id_prestamo, :id_equipo, :observaciones)";
            $statement = $this->connection->prepare($query);
            $statement->execute([
                ':id_prestamo' => $d['id_prestamo'],
                ':id_equipo' => $idEquipo,
                ':observaciones' => ($d['observaciones_entrega'] ?? '') !== '' ? $d['observaciones_entrega'] : null
            ]);
        } catch (\Throwable $th) {
            $this->liberarEquipo($idEquipo); // deshace el cambio de estado
            throw $th;
        }

        $this->response->success("Detalle de préstamo agregado correctamente", [], 1);
    }

    // PARA EL PUT EN EL ADMIN
    // Si se cambia el equipo  se devuelve el anteriror a disponible 
    public function editar(int $id, array $d)
    {
        $statement = $this->connection->prepare("SELECT id_equipo FROM detalle_prestamo WHERE id_detalle_prestamo = :id");
        $statement->execute([':id' => $id]);
        $anterior = $statement->fetchColumn();

        if ($anterior === false) {
            $this->response->error("El detalle de préstamo no existe", 18009, 404);
            return;
        }

        $nuevo = (int) $d['id_equipo'];
        $cambiaEquipo = (int) $anterior !== $nuevo;

        if ($cambiaEquipo && !$this->reservarEquipo($nuevo)) {
            $this->response->error("El equipo no está disponible para préstamo", 18008, 409);
            return;
        }

        try {
            $query = "UPDATE detalle_prestamo
                      SET id_prestamo = :id_prestamo,
                          id_equipo = :id_equipo,
                          observaciones_entrega = :observaciones
                      WHERE id_detalle_prestamo = :id";
            $statement = $this->connection->prepare($query);
            $statement->execute([
                ':id_prestamo' => $d['id_prestamo'],
                ':id_equipo' => $nuevo,
                ':observaciones' => ($d['observaciones_entrega'] ?? '') !== '' ? $d['observaciones_entrega'] : null,
                ':id' => $id
            ]);
        } catch (\Throwable $th) {
            if ($cambiaEquipo) {
                $this->liberarEquipo($nuevo); // deshace el cambio de estado del equipo nuevo
            }
            throw $th;
        }

        if ($cambiaEquipo) {
            $this->liberarEquipo((int) $anterior);
        }
        $this->response->success("Detalle de préstamo actualizado correctamente", [], 1);
    }

    // DELETE EN EL ADMIN
    // Al eliminar el detalle, el equipo vuelve a estar disponible
    public function eliminar(int $id)
    {
        $statement = $this->connection->prepare("SELECT id_equipo FROM detalle_prestamo WHERE id_detalle_prestamo = :id");
        $statement->execute([':id' => $id]);
        $idEquipo = $statement->fetchColumn();

        $statement = $this->connection->prepare("DELETE FROM detalle_prestamo WHERE id_detalle_prestamo = :id");
        $statement->execute([':id' => $id]);

        if ($idEquipo !== false) {
            $this->liberarEquipo((int) $idEquipo);
        }
        $this->response->success("Detalle de préstamo eliminado correctamente", [], 1);
    }
}