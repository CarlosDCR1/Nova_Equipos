<?php

class Devolucion
{
    private Connection $connection;
    private Response $response;

    public function __construct(Connection $connection, Response $response)
    {
        $this->connection = $connection;
        $this->response = $response;
    }

    // lista para los prestamos
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

    public function listaUsuariosEmpleado()
    {
        $query = "SELECT idUsuario AS id_usuario, nombreUsuario AS nombre_usuario
                  FROM tblusuario
                  WHERE idEmpleado IS NOT NULL AND is_active = 1
                  ORDER BY nombreUsuario ASC";
        $statement = $this->connection->prepare($query);
        $statement->execute();
        $result = $statement->fetchAll(PDO::FETCH_ASSOC);
        $this->response->success("Usuarios empleados obtenidos correctamente", $result, 1);
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
        if (!empty($filtros['observaciones'])) {
            $condiciones[] = "observaciones LIKE :observaciones";
            $params[':observaciones'] = "%{$filtros['observaciones']}%";
        }

        $where = $condiciones ? ("WHERE " . implode(" AND ", $condiciones)) : "";
        $query = "SELECT id_devolucion, id_prestamo, id_usuario, fecha_devolucion, observaciones
                  FROM devolucion $where ORDER BY id_devolucion DESC";
        $statement = $this->connection->prepare($query);
        $statement->execute($params);
        $result = $statement->fetchAll(PDO::FETCH_ASSOC);
        $this->response->success("Devoluciones obtenidas correctamente", $result, 1);
    }

    // PARA EL POST EN EL ADMIN
    public function add(array $d)
    {
        $this->connection->beginTransaction();
        try {
            $statement = $this->connection->prepare(
                "INSERT INTO devolucion (id_prestamo, id_usuario, observaciones)
                 VALUES (:id_prestamo, :id_usuario, :observaciones)"
            );
            $statement->execute([
                ':id_prestamo' => $d['id_prestamo'],
                ':id_usuario' => $d['id_usuario'],
                ':observaciones' => ($d['observaciones'] ?? '') !== '' ? $d['observaciones'] : null
            ]);

            $statement = $this->connection->prepare(
                "UPDATE prestamo
                 SET fecha_devolucion_real = (SELECT fecha_devolucion FROM devolucion WHERE id_prestamo = :id_dev)
                 WHERE id_prestamo = :id_pre"
            );
            $statement->execute([':id_dev' => $d['id_prestamo'], ':id_pre' => $d['id_prestamo']]);

            $this->connection->commit();
        } catch (\Throwable $th) {
            if ($this->connection->inTransaction()) {
                $this->connection->rollBack();
            }
            throw $th;
        }
        $this->response->success("Devolución registrada correctamente", [], 1);
    }

    // PARA EL PUT EN EL ADMIN
    public function editar(int $id, array $d)
    {
        $statement = $this->connection->prepare("SELECT id_prestamo FROM devolucion WHERE id_devolucion = :id");
        $statement->execute([':id' => $id]);
        $actual = $statement->fetchColumn();

        if ($actual === false) {
            $this->response->error("La devolución no existe", 17003, 404);
            return;
        }
        if ((int) $actual !== (int) $d['id_prestamo']) {
            $this->response->error("No se puede cambiar el préstamo de una devolución. Elimina la devolución y crea otra.", 17008, 400);
            return;
        }

        $statement = $this->connection->prepare(
            "UPDATE devolucion
             SET id_usuario = :id_usuario,
                 observaciones = :observaciones
             WHERE id_devolucion = :id"
        );
        $statement->execute([
            ':id_usuario' => $d['id_usuario'],
            ':observaciones' => ($d['observaciones'] ?? '') !== '' ? $d['observaciones'] : null,
            ':id' => $id
        ]);
        $this->response->success("Devolución actualizada correctamente", [], 1);
    }

    // DELETE EN EL ADMIN
    // cuando elminamos la devolución la fecha del prestamos se queda sin aguardar 
    public function eliminar(int $id)
    {
        $this->connection->beginTransaction();
        try {
            $statement = $this->connection->prepare("SELECT id_prestamo FROM devolucion WHERE id_devolucion = :id");
            $statement->execute([':id' => $id]);
            $idPrestamo = $statement->fetchColumn();

            $statement = $this->connection->prepare("DELETE FROM devolucion WHERE id_devolucion = :id");
            $statement->execute([':id' => $id]);

            if ($idPrestamo !== false) {
                $statement = $this->connection->prepare("UPDATE prestamo SET fecha_devolucion_real = NULL WHERE id_prestamo = :id_pre");
                $statement->execute([':id_pre' => $idPrestamo]);
            }

            $this->connection->commit();
        } catch (\Throwable $th) {
            if ($this->connection->inTransaction()) {
                $this->connection->rollBack();
            }
            throw $th;
        }
        $this->response->success("Devolución eliminada correctamente", [], 1);
    }
}