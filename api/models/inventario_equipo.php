<?php

class inventario_equipo
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

        if (!empty($filtros['id_equipo'])) {
            $condiciones[] = "id_equipo = :id_equipo";
            $params[':id_equipo'] = $filtros['id_equipo'];
        }
        if (!empty($filtros['ubicacion'])) {
            $condiciones[] = "ubicacion_actual LIKE :ubicacion";
            $params[':ubicacion'] = "%{$filtros['ubicacion']}%";
        }

        // select completo o filtrado
        $where = $condiciones ? ("WHERE " . implode(" AND ", $condiciones)) : "";
        $query = "SELECT id_inventario, id_equipo, ubicacion_actual, fecha_ingreso, fecha_ultimo_mov, observaciones
                  FROM inventario_equipo $where ORDER BY id_inventario DESC";
        $statement = $this->connection->prepare($query);
        $statement->execute($params);
        $result = $statement->fetchAll(PDO::FETCH_ASSOC);
        $this->response->success("Inventario obtenido correctamente", $result, 1);
    }

    // PARA EL POST EN EL ADMIN
    public function add(array $d)
    {
        $query = "INSERT INTO inventario_equipo (id_equipo, ubicacion_actual, fecha_ingreso, observaciones)
                  VALUES (:id_equipo, :ubicacion, :fecha_ingreso, :observaciones)";
        $statement = $this->connection->prepare($query);
        $statement->execute([
            ':id_equipo' => $d['id_equipo'],
            ':ubicacion' => ($d['ubicacion_actual'] ?? '') !== '' ? $d['ubicacion_actual'] : null,
            ':fecha_ingreso' => $d['fecha_ingreso'],
            ':observaciones' => ($d['observaciones'] ?? '') !== '' ? $d['observaciones'] : null
        ]);
        $this->response->success("Registro de inventario agregado correctamente", [], 1);
    }

    // PARA EL PUT EN EL ADMIN
    public function editar(int $id, array $d)
    {
        $query = "UPDATE inventario_equipo
                  SET id_equipo = :id_equipo, ubicacion_actual = :ubicacion, fecha_ingreso = :fecha_ingreso, observaciones = :observaciones
                  WHERE id_inventario = :id";
        $statement = $this->connection->prepare($query);
        $statement->execute([
            ':id_equipo' => $d['id_equipo'],
            ':ubicacion' => ($d['ubicacion_actual'] ?? '') !== '' ? $d['ubicacion_actual'] : null,
            ':fecha_ingreso' => $d['fecha_ingreso'],
            ':observaciones' => ($d['observaciones'] ?? '') !== '' ? $d['observaciones'] : null,
            ':id' => $id
        ]);
        $this->response->success("Registro de inventario actualizado correctamente", [], 1);
    }

    // DELETE EN EL ADMIN
    public function eliminar(int $id)
    {
        $statement = $this->connection->prepare("DELETE FROM inventario_equipo WHERE id_inventario = :id");
        $statement->execute([':id' => $id]);
        $this->response->success("Registro de inventario eliminado correctamente", [], 1);
    }
}