<?php

class estado_equipo{
    private Connection $connection;
    private Response $response;

    public function __construct(Connection $connection, Response $response)
    {
        $this->connection = $connection;
        $this->response = $response;
    }

    public function getall(array $filtros = [])
    {

    // para los filtros
        $condiciones = [];
        $params = [];
        if(!empty($filtros['nombre_estado'])){
            $condicones[] = "nombre_estado LIKE :nombre";
            $params[':nombre'] = "%{$filtros['nombre']}%";
        }
        if (($filtros['permite_prestamo'] ?? '') !== '') {
            $condiciones[] = "permite_prestamo = :prestamo";
            $params[':prestamo'] = (int) $filtros['permite_prestamo'];
        }
        if (($filtros['is_active'] ?? '') !== '') {
            $condiciones[] = "is_active = :estado";
            $params[':estado'] = (int) $filtros['is_active'];
        }

        $where = $condiciones ? ("WHERE " . implode(" AND ", $condiciones)) : "";
        $query = "SELECT id_estado_equipo, nombre_estado, descripcion, permite_prestamo, is_active
                  FROM estado_equipo $where ORDER BY id_estado_equipo DESC";
        $statement = $this->connection->prepare($query);
        $statement->execute($params);
        $result = $statement->fetchall(PDO::FETCH_ASSOC);
        $this->response->success("Estado equipo obtenidos correctamente", $result,1);
    }

    /*
CREATE TABLE estado_equipo (
    id_estado_equipo INT NOT NULL AUTO_INCREMENT,
    nombre_estado VARCHAR(50) NOT NULL,
    descripcion VARCHAR(200),
    permite_prestamo TINYINT(1) NOT NULL DEFAULT 1,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id_estado_equipo),
    UNIQUE KEY uq_estado_equipo_nombre (nombre_estado)
);
*/
    public function add(array $d)
    {
        $query = "INSERT INTO estado_equipo (nombre_estado, descripcion,   permite_prestamo, is_active)
                  values (:nombre, :descripcion, :prestamo, : estado)";
        $statement = $this->connection->prepare($query);
        $statement->execute([
            ':nombre' => $d['nombre_estado'],
            ':descripcion' => $d['descripcion'] ?? null,
            ':prestamo' => $d['permite_prestamo'] ?? 1,
            ':estado' => $d['is_active'] ?? 1
        ]);
        $this->response->success("Estado de equipo agregado correctamente", [], 1);
    }

    public function editar(int $id, array $d)
    {
        $query = "UPDATE estado_equipo SET nombre_estado = :nombre, descripcion = :descripcion,permite_prestamo = :prestamo,
                 is_active = :estado WHERE id_estado_equipo = :id";
        $statement = $this->connection->prepare($query);
        $statement->execute([':nombre' => $d['nombre_estado'], ':descripcion' => $d['descripcion'] ?? null, ':prestamo' => $d['permite_prestamo'] ?? 1,
                            ':estado' => $d['is_active'] ?? 1,':id' => $id]);
        $this->response->success("Estado de equipo actualizado correctamente", [], 1);
    }

    public function eliminar(int $id)
    {
        $statement = $this->connection->prepare("DELETE FROM estado_equipo WHERE id_estado_equipo = :id");
        $statement->execute([':id' => $id]);
        $this->response->success("Estado de equipo eliminado correctamente", [], 1);
    }

}