<?php

class Bitacora
{
    private Connection $connection;
    private Response $response;

    public function __construct(Connection $connection, Response $response)
    {
        $this->connection = $connection;
        $this->response = $response;
    }


    public function getAll(array $filtros = [])
    {


        $condiciones = [];
        $params = [];
        if (!empty($filtros['idUsuario'])) {
            $condiciones[] = "b.idUsuario = :idUsuario";
            $params[':idUsuario'] = $filtros['idUsuario'];
        }
        if (!empty($filtros['accion'])) {
            $condiciones[] = "b.accion = :accion";
            $params[':accion'] = $filtros['accion'];
        }
        if (!empty($filtros['fecha'])) {
            $condiciones[] = "b.fechaEvento >= :desde AND b.fechaEvento < DATE_ADD(:hasta, INTERVAL 1 DAY)";
            $params[':desde'] = $filtros['fecha'];
            $params[':hasta'] = $filtros['fecha'];
        }
        if (!empty($filtros['descripcion'])) {
            $condiciones[] = "b.descripcion LIKE :descripcion";
            $params[':descripcion'] = "%{$filtros['descripcion']}%";
        }
        if (!empty($filtros['aplicacion'])) {
            $condiciones[] = "b.aplicacion LIKE :aplicacion";
            $params[':aplicacion'] = "%{$filtros['aplicacion']}%";
        }


        $where = $condiciones ? ("WHERE " . implode(" AND ", $condiciones)) : "";
        $query = "SELECT b.idBitacora, b.fechaEvento, u.nombreUsuario, b.modulo, b.aplicacion, b.accion,
                         b.tablaAfectada, b.idRegistro, b.descripcion, b.ipOrigen
                  FROM tblbitacora b LEFT JOIN tblusuario u ON b.idUsuario = u.idUsuario
                  $where ORDER BY b.idBitacora DESC LIMIT 500";
        $statement = $this->connection->prepare($query);
        $statement->execute($params);
        $result = $statement->fetchAll(PDO::FETCH_ASSOC);
        $this->response->success("Bitácora obtenida correctamente", $result, 1);
    }
}