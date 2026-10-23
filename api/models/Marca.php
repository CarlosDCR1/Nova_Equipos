<?php

class Marca
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
            $condiciones[] = "nombre_marca LIKE :nombre";
            $params[':nombre'] = "%{$filtros['nombre']}%";
        }
        if (($filtros['is_active'] ?? '') !== '') {
            $condiciones[] = "is_active = :estado";
            $params[':estado'] = (int) $filtros['is_active'];
        }

        // select completo o filtrado
        $where = $condiciones ? ("WHERE " . implode(" AND ", $condiciones)) : "";
        $query = "SELECT id_marca, nombre_marca, is_active
                  FROM marca $where ORDER BY id_marca DESC";
        $statement = $this->connection->prepare($query);
        $statement->execute($params);
        $result = $statement->fetchAll(PDO::FETCH_ASSOC);
        $this->response->success("Marcas obtenidas correctamente", $result, 1);
    }

    // PARA EL POST EN EL ADMIN
    public function add(array $d)
    {
        $query = "INSERT INTO marca (nombre_marca, is_active)
                  VALUES (:nombre, :estado)";
        $statement = $this->connection->prepare($query);
        $statement->execute([
            ':nombre' => $d['nombre_marca'],
            ':estado' => $d['is_active'] ?? 1
        ]);
        $this->response->success("Marca agregada correctamente", [], 1);
    }

    // PARA EL PUT EN EL ADMIN
    public function editar(int $id, array $d)
    {
        $query = "UPDATE marca
                  SET nombre_marca = :nombre,
                      is_active = :estado
                  WHERE id_marca = :id";
        $statement = $this->connection->prepare($query);
        $statement->execute([
            ':nombre' => $d['nombre_marca'],
            ':estado' => $d['is_active'] ?? 1,
            ':id' => $id
        ]);
        $this->response->success("Marca actualizada correctamente", [], 1);
    }

    // DELETE EN EL ADMIN
    public function eliminar(int $id)
    {
        $statement = $this->connection->prepare("DELETE FROM marca WHERE id_marca = :id");
        $statement->execute([':id' => $id]);
        $this->response->success("Marca eliminada correctamente", [], 1);
    }
}