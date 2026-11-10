<?php

class estado_equipo
{
    
    const ESTADOS_DEL_SISTEMA = ['Disponible', 'Prestado', 'Dañado', 'Extraviado'];

    private Connection $connection;
    private Response $response;

    public function __construct(Connection $connection, Response $response)
    {
        $this->connection = $connection;
        $this->response = $response;
    }

    public function getAll(array $filtros = [])
    {
        // para los filtros
        $condiciones = [];
        $params = [];

        if (!empty($filtros['nombre_estado'])) {
            $condiciones[] = "nombre_estado LIKE :nombre";
            $params[':nombre'] = "%{$filtros['nombre_estado']}%";
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
        $result = $statement->fetchAll(PDO::FETCH_ASSOC);
        $this->response->success("Estados de equipo obtenidos correctamente", $result, 1);
    }

    public function add(array $d)
    {
        $query = "INSERT INTO estado_equipo (nombre_estado, descripcion, permite_prestamo, is_active)
                  VALUES (:nombre, :descripcion, :prestamo, :estado)";
        $statement = $this->connection->prepare($query);
        $statement->execute([
            ':nombre' => $d['nombre_estado'],
            ':descripcion' => ($d['descripcion'] ?? '') !== '' ? $d['descripcion'] : null,
            ':prestamo' => $d['permite_prestamo'] ?? 1,
            ':estado' => $d['is_active'] ?? 1
        ]);
        $this->response->success("Estado de equipo agregado correctamente", [], 1);
    }

    public function editar(int $id, array $d)
    {
        // un estado del sistema lo puede ajustar pero no renombrar
        $actual = $this->nombreDe($id);
        if ($actual !== null && in_array($actual, self::ESTADOS_DEL_SISTEMA, true) && $d['nombre_estado'] !== $actual) {
            $this->response->error("El estado \"$actual\" lo usa el sistema y no se puede renombrar", 5101, 400);
        }

        $query = "UPDATE estado_equipo SET nombre_estado = :nombre, descripcion = :descripcion, permite_prestamo = :prestamo,
                  is_active = :estado WHERE id_estado_equipo = :id";
        $statement = $this->connection->prepare($query);
        $statement->execute([
            ':nombre' => $d['nombre_estado'],
            ':descripcion' => ($d['descripcion'] ?? '') !== '' ? $d['descripcion'] : null,
            ':prestamo' => $d['permite_prestamo'] ?? 1,
            ':estado' => $d['is_active'] ?? 1,
            ':id' => $id
        ]);
        $this->response->success("Estado de equipo actualizado correctamente", [], 1);
    }

    public function eliminar(int $id)
    {
        $actual = $this->nombreDe($id);
        if ($actual !== null && in_array($actual, self::ESTADOS_DEL_SISTEMA, true)) {
            $this->response->error("El estado \"$actual\" lo usa el sistema y no se puede eliminar", 5102, 400);
        }

        $statement = $this->connection->prepare("DELETE FROM estado_equipo WHERE id_estado_equipo = :id");
        $statement->execute([':id' => $id]);
        $this->response->success("Estado de equipo eliminado correctamente", [], 1);
    }

    private function nombreDe(int $id): ?string
    {
        $statement = $this->connection->prepare("SELECT nombre_estado FROM estado_equipo WHERE id_estado_equipo = :id");
        $statement->execute([':id' => $id]);
        $nombre = $statement->fetchColumn();
        return $nombre === false ? null : $nombre;
    }
}