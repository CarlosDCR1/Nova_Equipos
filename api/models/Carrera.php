<?php

class Carrera
{
    private Connection $connection;
    private Response $response;

    public function __construct(Connection $connection, Response $response)
    {
        $this->connection = $connection;
        $this->response = $response;
    }

    // GET
    public function getAll(array $filtros = [])
    {
        $condiciones = [];
        $params = [];
        if (!empty($filtros['id_facultad'])) {
            $condiciones[] = "c.id_facultad = :id_facultad";
            $params[':id_facultad'] = $filtros['id_facultad'];
        }
        if (!empty($filtros['nombre'])) {
            $condiciones[] = "c.nombre_carrera LIKE :nombre";
            $params[':nombre'] = "%{$filtros['nombre']}%";
        }
        $where = $condiciones ? ("WHERE " . implode(" AND ", $condiciones)) : "";
        $query = "SELECT c.id_carrera, c.id_facultad, f.nombre_facultad, c.nombre_carrera
                  FROM carrera c
                  LEFT JOIN facultad f ON f.id_facultad = c.id_facultad
                  $where
                  ORDER BY c.id_carrera DESC";
        $statement = $this->connection->prepare($query);
        $statement->execute($params);
        $result = $statement->fetchAll(PDO::FETCH_ASSOC);
        $this->response->success("Carreras obtenidas correctamente", $result, 1);
    }

    // POST
    public function add(array $d)
    {
        $query = "INSERT INTO carrera (id_facultad, nombre_carrera)
                  VALUES (:id_facultad, :nombre)";
        $statement = $this->connection->prepare($query);
        $statement->execute([':id_facultad' => $d['id_facultad'], ':nombre' => $d['nombre_carrera']]);
        $this->response->success("Carrera agregada correctamente", [], 1);
    }

    // PUT
    public function editar(int $id, array $d)
    {
        $query = "UPDATE carrera SET id_facultad = :id_facultad, nombre_carrera = :nombre
                  WHERE id_carrera = :id";
        $statement = $this->connection->prepare($query);
        $statement->execute([':id_facultad' => $d['id_facultad'], ':nombre' => $d['nombre_carrera'], ':id' => $id]);
        $this->response->success("Carrera actualizada correctamente", [], 1);
    }

    // DELETE
    public function eliminar(int $id)
    {
        $statement = $this->connection->prepare("DELETE FROM carrera WHERE id_carrera = :id");
        $statement->execute([':id' => $id]);
        $this->response->success("Carrera eliminada correctamente", [], 1);
    }
}