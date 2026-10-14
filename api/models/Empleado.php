<?php

class Empleado
{
    private Connection $connection;
    private Response $response;

    public function __construct(Connection $connection, Response $response)
    {
        $this->connection = $connection;
        $this->response = $response;
    }

    // GET 
    function getAll(array $filtros = [])
    {
        $condiciones = [];
        $params = [];

        if (!empty($filtros['nombre'])) {
            $condiciones[] = "nombre LIKE :nombre";
            $params[':nombre'] = "%{$filtros['nombre']}%";
        }
        if (!empty($filtros['apellidos'])) {
            $condiciones[] = "apellidos LIKE :apellidos";
            $params[':apellidos'] = "%{$filtros['apellidos']}%";
        }
        if (!empty($filtros['correo'])) {
            $condiciones[] = "correo_institucional LIKE :correo";
            $params[':correo'] = "%{$filtros['correo']}%";
        }
        if (!empty($filtros['estado'])) {
            $condiciones[] = "estado_empleado = :estado";
            $params[':estado'] = $filtros['estado'];
        }

        $where = $condiciones ? ("WHERE " . implode(" AND ", $condiciones)) : "";

        $query = "SELECT
                    id_empleado,
                    nombre,
                    apellidos,
                    fecha_nacimiento,
                    telefono_empleado,
                    dpi,
                    estado_civil,
                    correo_institucional,
                    estado_empleado,
                    fecha_creacion
                  FROM empleado
                  $where
                  ORDER BY id_empleado DESC";

        $statement = $this->connection->prepare($query);
        $statement->execute($params);

        $result = $statement->fetchAll(PDO::FETCH_ASSOC);

        $this->response->success("Empleados obtenidos correctamente", $result, 1);
    }

    // POST
    function add(array $d)
    {
        $query = "INSERT INTO empleado
                    (nombre, apellidos, fecha_nacimiento, telefono_empleado, dpi, estado_civil, correo_institucional, estado_empleado)
                  VALUES
                    (:nombre, :apellidos, :fecha_nacimiento, :telefono, :dpi, :estado_civil, :correo, :estado)";

        $statement = $this->connection->prepare($query);

        $statement->execute([
            ':nombre'           => $d['nombre'],
            ':apellidos'        => $d['apellidos'] ?? null,
            ':fecha_nacimiento' => $d['fecha_nacimiento'] ?: null,
            ':telefono'         => $d['telefono_empleado'] ?? null,
            ':dpi'              => $d['dpi'] ?? null,
            ':estado_civil'     => $d['estado_civil'] ?? null,
            ':correo'           => $d['correo_institucional'],
            ':estado'           => $d['estado_empleado'] ?? 'ACTIVO',
        ]);

        $this->response->success("Empleado agregado correctamente", [], 1);
    }

    // PUT
    function editar(int $id, array $d)
    {
        $query = "UPDATE empleado SET
                    nombre = :nombre,
                    apellidos = :apellidos,
                    fecha_nacimiento = :fecha_nacimiento,
                    telefono_empleado = :telefono,
                    dpi = :dpi,
                    estado_civil = :estado_civil,
                    correo_institucional = :correo,
                    estado_empleado = :estado
                  WHERE id_empleado = :id";

        $params = [
            ':nombre'           => $d['nombre'],
            ':apellidos'        => $d['apellidos'] ?? null,
            ':fecha_nacimiento' => $d['fecha_nacimiento'] ?: null,
            ':telefono'         => $d['telefono_empleado'] ?? null,
            ':dpi'              => $d['dpi'] ?? null,
            ':estado_civil'     => $d['estado_civil'] ?? null,
            ':correo'           => $d['correo_institucional'],
            ':estado'           => $d['estado_empleado'] ?? 'ACTIVO',
            ':id'               => $id,
        ];

        return $this->connection->prepare($query)->execute($params);
    }

    // DELETE
    function eliminar(int $id)
    {
        return $this->connection
            ->prepare("DELETE FROM empleado WHERE id_empleado = :id")
            ->execute([':id' => $id]);
    }
}
