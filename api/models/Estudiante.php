<?php

class Estudiante
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
        if (!empty($filtros['id_carrera'])) {
            $condiciones[] = "id_carrera = :id_carrera";
            $params[':id_carrera'] = $filtros['id_carrera'];
        }
        if (!empty($filtros['nombre'])) {
            $condiciones[] = "nombre_estudiante LIKE :nombre";
            $params[':nombre'] = "%{$filtros['nombre']}%";
        }
        if (!empty($filtros['apellidos'])) {
            $condiciones[] = "apellidos_estudiante LIKE :apellidos";
            $params[':apellidos'] = "%{$filtros['apellidos']}%";
        }
        if (!empty($filtros['carnet'])) {
            $condiciones[] = "carnet_estudiante LIKE :carnet";
            $params[':carnet'] = "%{$filtros['carnet']}%";
        }
        if (!empty($filtros['numero'])) {
            $condiciones[] = "numero_estudiante LIKE :numero";
            $params[':numero'] = "%{$filtros['numero']}%";
        }
        if (!empty($filtros['correo'])) {
            $condiciones[] = "correo_institucional LIKE :correo";
            $params[':correo'] = "%{$filtros['correo']}%";
        }
        if (!empty($filtros['estado'])) {
            $condiciones[] = "estado_estudiante = :estado";
            $params[':estado'] = $filtros['estado'];
        }

        //select compelto o filtrado completo 
        
        $where = $condiciones ? ("WHERE " . implode(" AND ", $condiciones)) : "";
        $query = "SELECT id_estudiante, id_carrera, nombre_estudiante, apellidos_estudiante, carnet_estudiante, numero_estudiante, correo_institucional, estado_estudiante, fecha_creacion
                  FROM estudiante $where ORDER BY id_estudiante DESC";
        $statement = $this->connection->prepare($query);
        $statement->execute($params);
        $result = $statement->fetchAll(PDO::FETCH_ASSOC);
        $this->response->success("Estudiantes obtenidos correctamente", $result, 1);
    }

    // PARA EL POST EN EL ADMIN
    public function add(array $d)
    {
        $query = "INSERT INTO estudiante (id_carrera, nombre_estudiante, apellidos_estudiante,
                    carnet_estudiante, numero_estudiante, correo_institucional, estado_estudiante)
                  VALUES (:id_carrera, :nombre, :apellidos,:carnet, :numero, :correo, :estado)";
        $statement = $this->connection->prepare($query);
        $statement->execute([':id_carrera' => $d['id_carrera'], ':nombre' => $d['nombre_estudiante'], ':apellidos' => $d['apellidos_estudiante'] ?? null,
                    ':carnet' => $d['carnet_estudiante'], ':numero' => $d['numero_estudiante'], ':correo' => $d['correo_institucional'], ':estado' => $d['estado_estudiante'] ?? 'ACTIVO']);
        $this->response->success("Estudiante agregado correctamente", [], 1);
    }

    //PARA EL PUT EN EL ADMIN
    public function editar(int $id, array $d)
    {
        $query = "UPDATE estudiante SET id_carrera = :id_carrera, nombre_estudiante = :nombre, apellidos_estudiante = :apellidos,
                carnet_estudiante = :carnet, numero_estudiante = :numero, correo_institucional = :correo, estado_estudiante = :estado
                WHERE id_estudiante = :id";
        $statement = $this->connection->prepare($query);
        $statement->execute([':id_carrera' => $d['id_carrera'], ':nombre' => $d['nombre_estudiante'], ':apellidos' => $d['apellidos_estudiante'] ?? null,
                ':carnet' => $d['carnet_estudiante'], ':numero' => $d['numero_estudiante'], ':correo' => $d['correo_institucional'],
                ':estado' => $d['estado_estudiante'] ?? 'ACTIVO', ':id' => $id]);
        $this->response->success("Estudiante actualizado correctamente", [], 1);
    }

    // DELETE EN EL ADMIN
    public function eliminar(int $id)
    {
        $statement = $this->connection->prepare("DELETE FROM estudiante WHERE id_estudiante = :id");
        $statement->execute([':id' => $id]);
        $this->response->success("Estudiante eliminado correctamente", [], 1);
    }
}