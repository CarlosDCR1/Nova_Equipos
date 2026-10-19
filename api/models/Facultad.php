<?php

class Facultad
{
    private Connection $connection;
    private Response $response;

    public function __construct(Connection $connection, Response $response)
    {
        $this->connection = $connection;
        $this->response = $response;
    }

   
    public function getAll()
    {
        $query = "SELECT f.id_facultad, f.id_jornada, j.nombre_jornada, f.nombre_facultad
                  FROM facultad f LEFT JOIN jornada j ON j.id_jornada = f.id_jornada ORDER BY f.nombre_facultad";

        $statement = $this->connection->prepare($query);
        $statement->execute();

        $this->response->success("Facultades obtenidas correctamente", $statement->fetchAll(PDO::FETCH_ASSOC), 1);
    }
}