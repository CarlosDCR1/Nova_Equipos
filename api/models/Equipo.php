<?php

class Equipo
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

        if (!empty($filtros['id_modelo_producto'])) {
            $condiciones[] = "id_modelo_producto = :id_modelo";
            $params[':id_modelo'] = $filtros['id_modelo_producto'];
        }
        if (!empty($filtros['id_estado_equipo'])) {
            $condiciones[] = "id_estado_equipo = :id_estado";
            $params[':id_estado'] = $filtros['id_estado_equipo'];
        }
        if (!empty($filtros['codigo'])) {
            $condiciones[] = "codigo_inventario LIKE :codigo";
            $params[':codigo'] = "%{$filtros['codigo']}%";
        }
        if (!empty($filtros['serie'])) {
            $condiciones[] = "numero_serie LIKE :serie";
            $params[':serie'] = "%{$filtros['serie']}%";
        }

        // select completo o filtrado
        $where = $condiciones ? ("WHERE " . implode(" AND ", $condiciones)) : "";
        $query = "SELECT id_equipo, id_modelo_producto, id_estado_equipo, codigo_inventario, numero_serie,
                         descripcion_equipo, ubicacion_actual, fecha_adquisicion, valor_adquisicion, fecha_creacion, fecha_actualizacion
                  FROM equipo $where ORDER BY id_equipo DESC";
        $statement = $this->connection->prepare($query);
        $statement->execute($params);
        $result = $statement->fetchAll(PDO::FETCH_ASSOC);
        $this->response->success("Equipos obtenidos correctamente", $result, 1);
    }

    public function add(array $d)
    {
        // un equipo nuevo no puede nacer Prestado: ese estado lo pone el sistema al entregar
        if ($this->esEstadoPrestado((int) $d['id_estado_equipo'])) {
            $this->response->error("El estado Prestado lo controla el sistema, no se puede asignar manualmente", 5003, 400);
        }

        $query = "INSERT INTO equipo (id_modelo_producto, id_estado_equipo, codigo_inventario, numero_serie, descripcion_equipo,
                                      ubicacion_actual, fecha_adquisicion, valor_adquisicion)
                  VALUES (:id_modelo, :id_estado, :codigo, :serie, :descripcion, :ubicacion, :fecha, :valor)";
        $statement = $this->connection->prepare($query);
        $statement->execute([
            ':id_modelo' => $d['id_modelo_producto'],
            ':id_estado' => $d['id_estado_equipo'],
            ':codigo' => $d['codigo_inventario'],
            ':serie' => ($d['numero_serie'] ?? '') !== '' ? $d['numero_serie'] : null,
            ':descripcion' => ($d['descripcion_equipo'] ?? '') !== '' ? $d['descripcion_equipo'] : null,
            ':ubicacion' => ($d['ubicacion_actual'] ?? '') !== '' ? $d['ubicacion_actual'] : null,
            ':fecha' => ($d['fecha_adquisicion'] ?? '') !== '' ? $d['fecha_adquisicion'] : null,
            ':valor' => ($d['valor_adquisicion'] ?? '') !== '' ? $d['valor_adquisicion'] : null
        ]);
        $this->response->success("Equipo agregado correctamente", [], 1);
    }

    public function editar(int $id, array $d)
    {
        // el estado Prestado lo pone el sistema al entregar y lo quita al devolver:
        // el administrador no puede ponerlo ni quitarlo a mano
        $actual = $this->estadoActual($id);
        if ($actual === null) {
            $this->response->error("El equipo no existe", 5004, 404);
        }

        $nuevo = (int) $d['id_estado_equipo'];
        if ($nuevo !== $actual && ($this->esEstadoPrestado($nuevo) || $this->esEstadoPrestado($actual))) {
            $this->response->error("El estado Prestado lo controla el sistema: se pone al entregar y se quita al registrar la devolución", 5003, 400);
        }

        $query = "UPDATE equipo
                  SET id_modelo_producto = :id_modelo, id_estado_equipo = :id_estado, codigo_inventario = :codigo, numero_serie = :serie,
                      descripcion_equipo = :descripcion, ubicacion_actual = :ubicacion, fecha_adquisicion = :fecha, valor_adquisicion = :valor
                  WHERE id_equipo = :id";
        $statement = $this->connection->prepare($query);
        $statement->execute([
            ':id_modelo' => $d['id_modelo_producto'],
            ':id_estado' => $d['id_estado_equipo'],
            ':codigo' => $d['codigo_inventario'],
            ':serie' => ($d['numero_serie'] ?? '') !== '' ? $d['numero_serie'] : null,
            ':descripcion' => ($d['descripcion_equipo'] ?? '') !== '' ? $d['descripcion_equipo'] : null,
            ':ubicacion' => ($d['ubicacion_actual'] ?? '') !== '' ? $d['ubicacion_actual'] : null,
            ':fecha' => ($d['fecha_adquisicion'] ?? '') !== '' ? $d['fecha_adquisicion'] : null,
            ':valor' => ($d['valor_adquisicion'] ?? '') !== '' ? $d['valor_adquisicion'] : null,
            ':id' => $id
        ]);
        $this->response->success("Equipo actualizado correctamente", [], 1);
    }

    // DELETE EN EL ADMIN
    public function eliminar(int $id)
    {
        $statement = $this->connection->prepare("DELETE FROM equipo WHERE id_equipo = :id");
        $statement->execute([':id' => $id]);
        $this->response->success("Equipo eliminado correctamente", [], 1);
    }

    // ---------- auxiliares ----------
    private function estadoActual(int $id): ?int
    {
        $statement = $this->connection->prepare("SELECT id_estado_equipo FROM equipo WHERE id_equipo = :id");
        $statement->execute([':id' => $id]);
        $estado = $statement->fetchColumn();
        return $estado === false ? null : (int) $estado;
    }

    private function esEstadoPrestado(int $idEstado): bool
    {
        $statement = $this->connection->prepare("SELECT 1 FROM estado_equipo WHERE id_estado_equipo = :id AND nombre_estado = 'Prestado'");
        $statement->execute([':id' => $idEstado]);
        return (bool) $statement->fetchColumn();
    }
}