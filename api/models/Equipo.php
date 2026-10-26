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
                         descripcion_equipo, fecha_adquisicion, valor_adquisicion, fecha_creacion, fecha_actualizacion
                  FROM equipo $where ORDER BY id_equipo DESC";
        $statement = $this->connection->prepare($query);
        $statement->execute($params);
        $result = $statement->fetchAll(PDO::FETCH_ASSOC);
        $this->response->success("Equipos obtenidos correctamente", $result, 1);
    }
/*
        CREATE TABLE equipo (
    id_equipo INT NOT NULL AUTO_INCREMENT,
    id_modelo_producto INT NOT NULL,
    id_estado_equipo INT NOT NULL,
    codigo_inventario VARCHAR(50) NOT NULL,
    numero_serie VARCHAR(100),
    descripcion_equipo VARCHAR(300),
    fecha_adquisicion DATE,
    valor_adquisicion DECIMAL(12,2),
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_equipo),
    KEY idx_equipo_modelo (id_modelo_producto),
    KEY idx_equipo_estado (id_estado_equipo),
    UNIQUE KEY uq_equipo_codigo (codigo_inventario),
    UNIQUE KEY uq_equipo_serie (numero_serie),
    CONSTRAINT fk_equipo_modelo FOREIGN KEY (id_modelo_producto) REFERENCES modelo_producto(id_modelo_producto) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_equipo_estado FOREIGN KEY (id_estado_equipo) REFERENCES estado_equipo(id_estado_equipo) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT chk_equipo_valor CHECK (valor_adquisicion IS NULL OR valor_adquisicion >= 0)
);

        */

     public function add(array $d)
    {
        $query = "INSERT INTO equipo (id_modelo_producto, id_estado_equipo, codigo_inventario, numero_serie, descripcion_equipo, fecha_adquisicion, valor_adquisicion)
                  VALUES (:id_modelo, :id_estado, :codigo, :serie, :descripcion, :fecha, :valor)";
        $statement = $this->connection->prepare($query);
        $statement->execute([
            ':id_modelo' => $d['id_modelo_producto'],
            ':id_estado' => $d['id_estado_equipo'],
            ':codigo' => $d['codigo_inventario'],
            ':serie' => ($d['numero_serie'] ?? '') !== '' ? $d['numero_serie'] : null,
            ':descripcion' => ($d['descripcion_equipo'] ?? '') !== '' ? $d['descripcion_equipo'] : null,
            ':fecha' => ($d['fecha_adquisicion'] ?? '') !== '' ? $d['fecha_adquisicion'] : null,
            ':valor' => ($d['valor_adquisicion'] ?? '') !== '' ? $d['valor_adquisicion'] : null
        ]);
        $this->response->success("Equipo agregado correctamente", [], 1);
    }

    public function editar(int $id, array $d)
    {
        $query = "UPDATE equipo
                  SET id_modelo_producto = :id_modelo, id_estado_equipo = :id_estado, codigo_inventario = :codigo, numero_serie = :serie,
                      descripcion_equipo = :descripcion, fecha_adquisicion = :fecha, valor_adquisicion = :valor
                  WHERE id_equipo = :id";
        $statement = $this->connection->prepare($query);
        $statement->execute([
            ':id_modelo' => $d['id_modelo_producto'],
            ':id_estado' => $d['id_estado_equipo'],
            ':codigo' => $d['codigo_inventario'],
            ':serie' => ($d['numero_serie'] ?? '') !== '' ? $d['numero_serie'] : null,
            ':descripcion' => ($d['descripcion_equipo'] ?? '') !== '' ? $d['descripcion_equipo'] : null,
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
}