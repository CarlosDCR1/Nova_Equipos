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
            $condiciones[] = "id_facultad = :id_facultad";
            $params[':id_facultad'] = $filtros['id_facultad'];
        }
        if (!empty($filtros['nombre'])) {
            $condiciones[] = "nombre_carrera LIKE :nombre";
            $params[':nombre'] = "%{$filtros['nombre']}%";
        }
        $where = $condiciones ? ("WHERE " . implode(" AND ", $condiciones)) : "";
        $query = "SELECT id_carrera, id_facultad, nombre_carrera
                  FROM carrera
                  $where
                  ORDER BY id_carrera DESC";
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