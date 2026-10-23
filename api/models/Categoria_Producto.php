<?php

class Categoria_Producto
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
 
        if (!empty($filtros['nombre'])) {
            $condiciones[] = "nombre_categoria_producto LIKE :nombre";
            $params[':nombre'] = "%{$filtros['nombre']}%";
        }
        if (($filtros['is_active'] ?? '') !== '') {
            $condiciones[] = "is_active = :estado";
            $params[':estado'] = (int) $filtros['is_active'];
        }
 
        // select completo o filtrado
        $where = $condiciones ? ("WHERE " . implode(" AND ", $condiciones)) : "";
        $query = "SELECT id_categoria_producto, nombre_categoria_producto, descripcion_categoria_producto, is_active
                  FROM categoria_producto $where ORDER BY id_categoria_producto DESC";
        $statement = $this->connection->prepare($query);
        $statement->execute($params);
        $result = $statement->fetchAll(PDO::FETCH_ASSOC);
        $this->response->success("Categorías de producto obtenidas correctamente", $result, 1);
    }
 
    // PARA EL POST EN EL ADMIN
    public function add(array $d)
    {
        $query = "INSERT INTO categoria_producto (nombre_categoria_producto, descripcion_categoria_producto, is_active)
                  VALUES (:nombre, :descripcion, :estado)";
        $statement = $this->connection->prepare($query);
        $statement->execute([
            ':nombre' => $d['nombre_categoria_producto'],
            ':descripcion' => $d['descripcion_categoria_producto'] ?? null,
            ':estado' => $d['is_active'] ?? 1
        ]);
        $this->response->success("Categoría de producto agregada correctamente", [], 1);
    }
 
    // PARA EL PUT EN EL ADMIN
    public function editar(int $id, array $d)
    {
        $query = "UPDATE categoria_producto SET nombre_categoria_producto = :nombre, descripcion_categoria_producto = :descripcion, is_active = :estado
                  WHERE id_categoria_producto = :id";
        $statement = $this->connection->prepare($query);
        $statement->execute([
            ':nombre' => $d['nombre_categoria_producto'], ':descripcion' => $d['descripcion_categoria_producto'] ?? null,
            ':estado' => $d['is_active'] ?? 1, ':id' => $id
        ]);
        $this->response->success("Categoría de producto actualizada correctamente", [], 1);
    }
 
    // DELETE EN EL ADMIN
    public function eliminar(int $id)
    {
        $statement = $this->connection->prepare("DELETE FROM categoria_producto WHERE id_categoria_producto = :id");
         $statement->execute([':id' => $id]);
        $this->response->success("Categoría de producto eliminada correctamente", [], 1);
    }


}