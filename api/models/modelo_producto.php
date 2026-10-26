<?php

class modelo_Producto
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

        if (!empty($filtros['id_marca'])) {
            $condiciones[] = "id_marca = :id_marca";
            $params[':id_marca'] = $filtros['id_marca'];
        }
        if (!empty($filtros['id_categoria_producto'])) {
            $condiciones[] = "id_categoria_producto = :id_categoria";
            $params[':id_categoria'] = $filtros['id_categoria_producto'];
        }
        if (!empty($filtros['nombre'])) {
            $condiciones[] = "nombre_modelo_producto LIKE :nombre";
            $params[':nombre'] = "%{$filtros['nombre']}%";
        }
        if (($filtros['is_active'] ?? '') !== '') {
            $condiciones[] = "is_active = :estado";
            $params[':estado'] = (int) $filtros['is_active'];
        }

        // select completo o filtrado
        $where = $condiciones ? ("WHERE " . implode(" AND ", $condiciones)) : "";
        $query = "SELECT id_modelo_producto, id_marca, id_categoria_producto, nombre_modelo_producto, descripcion_modelo_producto, is_active
                  FROM modelo_producto $where ORDER BY id_modelo_producto DESC";
        $statement = $this->connection->prepare($query);
        $statement->execute($params);
        $result = $statement->fetchAll(PDO::FETCH_ASSOC);
        $this->response->success("Modelos de producto obtenidos correctamente", $result, 1);
    }

    // PARA EL POST EN EL ADMIN
    public function add(array $d)
    {
        $query = "INSERT INTO modelo_producto (id_marca, id_categoria_producto, nombre_modelo_producto, descripcion_modelo_producto, is_active)
                  VALUES (:id_marca, :id_categoria, :nombre, :descripcion, :estado)";
        $statement = $this->connection->prepare($query);
        $statement->execute([
            ':id_marca' => $d['id_marca'],':id_categoria' => $d['id_categoria_producto'],':nombre' => $d['nombre_modelo_producto'],
            ':descripcion' => $d['descripcion_modelo_producto'] ?? null,':estado' => $d['is_active'] ?? 1
        ]);
        $this->response->success("Modelo de producto agregado correctamente", [], 1);
    }

    // PARA EL PUT EN EL ADMIN
    public function editar(int $id, array $d)
    {
        $query = "UPDATE modelo_producto
                  SET id_marca = :id_marca, id_categoria_producto = :id_categoria, nombre_modelo_producto = :nombre,
                      descripcion_modelo_producto = :descripcion, is_active = :estado WHERE id_modelo_producto = :id";
        $statement = $this->connection->prepare($query);
        $statement->execute([
            ':id_marca' => $d['id_marca'],':id_categoria' => $d['id_categoria_producto'],':nombre' => $d['nombre_modelo_producto'],
            ':descripcion' => $d['descripcion_modelo_producto'] ?? null, ':estado' => $d['is_active'] ?? 1,':id' => $id
        ]);
        $this->response->success("Modelo de producto actualizado correctamente", [], 1);
    }

    // DELETE EN EL ADMIN
    public function eliminar(int $id)
    {
        $statement = $this->connection->prepare("DELETE FROM modelo_producto WHERE id_modelo_producto = :id");
        $statement->execute([':id' => $id]);
        $this->response->success("Modelo de producto eliminado correctamente", [], 1);
    }
}