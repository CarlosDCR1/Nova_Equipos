<?php

class tipo_incidente
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
            $condiciones[] = "nombre_tipo_incidente LIKE :nombre";
            $params[':nombre'] = "%{$filtros['nombre']}%";
        }
        if (($filtros['is_active'] ?? '') !== '') {
            $condiciones[] = "is_active = :estado";
            $params[':estado'] = (int) $filtros['is_active'];
        }

        // select completo o filtrado
        $where = $condiciones ? ("WHERE " . implode(" AND ", $condiciones)) : "";
        $query = "SELECT id_tipo_incidente, nombre_tipo_incidente, descripcion, is_active
                  FROM tipo_incidente $where ORDER BY id_tipo_incidente DESC";
        $statement = $this->connection->prepare($query);
        $statement->execute($params);
        $result = $statement->fetchAll(PDO::FETCH_ASSOC);
        $this->response->success("Tipos de incidente obtenidos correctamente", $result, 1);
    }

    // PARA EL POST EN EL ADMIN
    public function add(array $d)
    {
        $query = "INSERT INTO tipo_incidente (nombre_tipo_incidente, descripcion, is_active)
                  VALUES (:nombre, :descripcion, :estado)";
        $statement = $this->connection->prepare($query);
        $statement->execute([
            ':nombre' => $d['nombre_tipo_incidente'],
            ':descripcion' => ($d['descripcion'] ?? '') !== '' ? $d['descripcion'] : null,
            ':estado' => $d['is_active'] ?? 1
        ]);
        $this->response->success("Tipo de incidente agregado correctamente", [], 1);
    }

    // PARA EL PUT EN EL ADMIN
    public function editar(int $id, array $d)
    {
        $query = "UPDATE tipo_incidente
                  SET nombre_tipo_incidente = :nombre, descripcion = :descripcion, is_active = :estado
                  WHERE id_tipo_incidente = :id";
        $statement = $this->connection->prepare($query);
        $statement->execute([
            ':nombre' => $d['nombre_tipo_incidente'],
            ':descripcion' => ($d['descripcion'] ?? '') !== '' ? $d['descripcion'] : null,
            ':estado' => $d['is_active'] ?? 1,
            ':id' => $id
        ]);
        $this->response->success("Tipo de incidente actualizado correctamente", [], 1);
    }

    // DELETE EN EL ADMIN
    public function eliminar(int $id)
    {
        $statement = $this->connection->prepare("DELETE FROM tipo_incidente WHERE id_tipo_incidente = :id");
        $statement->execute([':id' => $id]);
        $this->response->success("Tipo de incidente eliminado correctamente", [], 1);
    }
}