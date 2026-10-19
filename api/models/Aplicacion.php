<?php

class Aplicacion
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
            $condiciones[] = "nombreAplicacion LIKE :nombre";
            $params[':nombre'] = "%{$filtros['nombre']}%";
        }
        if (($filtros['is_active'] ?? '') !== '') {
            $condiciones[] = "is_active = :estado";
            $params[':estado'] = (int) $filtros['is_active'];
        }

        //select compelto o filtrado completo 
        
        $where = $condiciones ? ("WHERE " . implode(" AND ", $condiciones)) : "";
        $query = "SELECT idAplicacion, idModulo, nombreAplicacion, descripcionAplicacion, is_active, created_at, updated_at
                  FROM tblaplicacion $where ORDER BY idAplicacion DESC";
        $statement = $this->connection->prepare($query);
        $statement->execute($params);
        $result = $statement->fetchAll(PDO::FETCH_ASSOC);
        $this->response->success("Aplicaciones obtenidas correctamente", $result, 1);
    }

    // PARA EL POST EN EL ADMIN
    public function add(array $d)
    {
        $query = "INSERT INTO tblaplicacion (idModulo, nombreAplicacion, descripcionAplicacion, is_active)
                  VALUES (:idModulo, :nombre, :descripcion, :estado)";
        $statement = $this->connection->prepare($query);
        $statement->execute([':idModulo' => $d['idModulo'], ':nombre' => $d['nombreAplicacion'], ':descripcion' => $d['descripcionAplicacion'], ':estado' => $d['is_active'] ?? 1]);
        $this->response->success("Aplicación agregada correctamente", [], 1);
    }

    //PARA EL PUT EN EL ADMIN
    public function editar(int $id, array $d)
    {
        $query = "UPDATE tblaplicacion SET idModulo = :idModulo, nombreAplicacion = :nombre, descripcionAplicacion = :descripcion, is_active = :estado
                WHERE idAplicacion = :id";
        $statement = $this->connection->prepare($query);
        $statement->execute([':idModulo' => $d['idModulo'], ':nombre' => $d['nombreAplicacion'], ':descripcion' => $d['descripcionAplicacion'],
                ':estado' => $d['is_active'] ?? 1, ':id' => $id]);
        $this->response->success("Aplicación actualizada correctamente", [], 1);
    }

    // DELETE EN EL ADMIN
    public function eliminar(int $id)
    {
        $statement = $this->connection->prepare("DELETE FROM tblaplicacion WHERE idAplicacion = :id");
        $statement->execute([':id' => $id]);
        $this->response->success("Aplicación eliminada correctamente", [], 1);
    }
}