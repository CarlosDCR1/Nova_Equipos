<?php

class Estado_Prestamo
{
    private Connection $connection;
    private Response $response;

    public function __construct(Connection $connection, Response $response)
    {
        $this->connection = $connection;
        $this->response = $response;
    }

    // PARA EL GET
    public function getAll(array $filtros = [])
    {
        // para los filtros de busqueda
        $condiciones = [];
        $params = [];

        if (!empty($filtros['nombre'])) {
            $condiciones[] = "nombre_estado LIKE :nombre";
            $params[':nombre'] = "%{$filtros['nombre']}%";
        }

        // select completo o filtrado
        $where = $condiciones ? ("WHERE " . implode(" AND ", $condiciones)) : "";
        $query = "SELECT id_estado_prestamo, nombre_estado, descripcion FROM estado_prestamo $where ORDER BY id_estado_prestamo DESC";
        $statement = $this->connection->prepare($query);
        $statement->execute($params);
        $result = $statement->fetchAll(PDO::FETCH_ASSOC);
        $this->response->success("Estados de préstamo obtenidos correctamente", $result, 1);
    }

    // PARA EL POST EN EL ADMIN
    public function add(array $d)
    {
        $query = "INSERT INTO estado_prestamo (nombre_estado, descripcion) VALUES (:nombre, :descripcion)";
        $statement = $this->connection->prepare($query);
        $statement->execute([':nombre' => $d['nombre_estado'],':descripcion' => $d['descripcion'] ?? null
        ]);
        $this->response->success("Estado de préstamo agregado correctamente", [], 1);
    }

    // PARA EL PUT EN EL ADMIN
    public function editar(int $id, array $d)
    {
        $query = "UPDATE estado_prestamo SET nombre_estado = :nombre, descripcion = :descripcion WHERE id_estado_prestamo = :id";
        $statement = $this->connection->prepare($query);
        $statement->execute([':nombre' => $d['nombre_estado'], ':descripcion' => $d['descripcion'] ?? null, ':id' => $id]);
        $this->response->success("Estado de préstamo actualizado correctamente", [], 1);
    }

    // DELETE EN EL ADMIN
    public function eliminar(int $id)
    {
        $statement = $this->connection->prepare("DELETE FROM estado_prestamo WHERE id_estado_prestamo = :id");
        $statement->execute([':id' => $id]);
        $this->response->success("Estado de préstamo eliminado correctamente", [], 1);
    }
}