<?php

class Modulo
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

    //para los filtros de busqueda 
        $condiciones = [];
        $params = [];
        if (!empty($filtros['idModulo'])) {
            $condiciones[] = "idModulo = :idModulo";
            $params[':idModulo'] = $filtros['idModulo'];
        }
        if (!empty($filtros['nombre'])) {
            $condiciones[] = "nombreModulo LIKE :nombre";
            $params[':nombre'] = "%{$filtros['nombre']}%";
        }
        if (($filtros['is_active'] ?? '') !== '') {
            $condiciones[] = "is_active = :estado";
            $params[':estado'] = (int) $filtros['is_active'];
        }

        //select compelto o filtrado completo 
        
        $where = $condiciones ? ("WHERE " . implode(" AND ", $condiciones)) : "";
        $query = "SELECT idModulo, nombreModulo, descripcionModulo, is_active, created_at, updated_at
                  FROM tblmodulo $where ORDER BY idModulo DESC";
        $statement = $this->connection->prepare($query);
        $statement->execute($params);
        $result = $statement->fetchAll(PDO::FETCH_ASSOC);
        $this->response->success("Módulos obtenidos correctamente", $result, 1);
    }

    // PARA EL POST EN EL ADMIN
    public function add(array $d)
    {
        $query = "INSERT INTO tblmodulo (nombreModulo, descripcionModulo, is_active)
                  VALUES (:nombre, :descripcion, :estado)";
        $statement = $this->connection->prepare($query);
        $statement->execute([':nombre' => $d['nombreModulo'], ':descripcion' => $d['descripcionModulo'], ':estado' => $d['is_active'] ?? 1]);
        $this->response->success("Módulo agregado correctamente", [], 1);
    }

    //PARA EL PUT EN EL ADMIN
    public function editar(int $id, array $d)
    {
        $query = "UPDATE tblmodulo SET nombreModulo = :nombre, descripcionModulo = :descripcion, is_active = :estado
                WHERE idModulo = :id";
        $statement = $this->connection->prepare($query);
        $statement->execute([':nombre' => $d['nombreModulo'], ':descripcion' => $d['descripcionModulo'],
                ':estado' => $d['is_active'] ?? 1, ':id' => $id]);
        $this->response->success("Módulo actualizado correctamente", [], 1);
    }

    // DELETE EN EL ADMIN
    public function eliminar(int $id)
    {
        $statement = $this->connection->prepare("DELETE FROM tblmodulo WHERE idModulo = :id");
        $statement->execute([':id' => $id]);
        $this->response->success("Módulo eliminado correctamente", [], 1);
    }
}