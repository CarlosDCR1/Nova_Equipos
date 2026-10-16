<?php

class Usuario
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

        if (!empty($filtros['idEmpleado'])) {
            $condiciones[] = "u.idEmpleado = :idEmpleado";
            $params[':idEmpleado'] = (int) $filtros['idEmpleado'];
        }
        if ($filtros['is_active'] !== null && $filtros['is_active'] !== '') {
            $condiciones[] = "u.is_active = :is_active";
            $params[':is_active'] = (int) $filtros['is_active'];
        }

        $where = $condiciones ? ("WHERE " . implode(" AND ", $condiciones)) : "";

        $query = "SELECT u.idUsuario, u.idEmpleado, e.nombre      AS empleado_nombre,  e.apellidos   AS empleado_apellidos,
                    u.nombreUsuario, u.ultimoAccesoUsuario, u.intentosFallidosUsuario, u.is_active, u.created_at
                  FROM tblusuario u
                  INNER JOIN empleado e ON u.idEmpleado = e.id_empleado
                  $where
                  ORDER BY u.idUsuario DESC";

        $statement = $this->connection->prepare($query);
        $statement->execute($params);

        $result = $statement->fetchAll(PDO::FETCH_ASSOC);

        $this->response->success("Usuarios obtenidos correctamente", $result, 1);
    }

    // POST
    function add(array $d)
    {
        if (empty($d['idEmpleado']) || empty($d['nombreUsuario']) || empty($d['contrasenaUsuario'])) {
            $this->response->error("Empleado, usuario y contraseña son obligatorios", 4001, 400);
        }

        $hash = password_hash($d['contrasenaUsuario'], PASSWORD_BCRYPT);

        $query = "INSERT INTO tblusuario
                    (idEmpleado, nombreUsuario, contrasenaUsuario, is_active)
                  VALUES
                    (:idEmpleado, :nombreUsuario, :contrasena, :is_active)";

        $statement = $this->connection->prepare($query);

        $statement->execute([':idEmpleado'    => $d['idEmpleado'],':nombreUsuario' => $d['nombreUsuario'],
            ':contrasena'    => $hash,':is_active'     => $d['is_active'] ?? 1,]);

        $this->response->success("Usuario agregado correctamente", [], 1);
    }

    // PUT 
    function editar(int $id, array $d)
    {
        $set = ["idEmpleado = :idEmpleado", "nombreUsuario = :nombreUsuario","is_active = :is_active",];
        $params = [':idEmpleado'    => $d['idEmpleado'],':nombreUsuario' => $d['nombreUsuario'],':is_active' => $d['is_active'] ?? 1,':id' => $id,];

        if (!empty($d['contrasenaUsuario'])) {
            $set[] = "contrasenaUsuario = :contrasena";
            $set[] = "fechaUltimaContrasena = NOW()";
            $params[':contrasena'] = password_hash($d['contrasenaUsuario'], PASSWORD_BCRYPT);
        }

        $query = "UPDATE tblusuario SET " . implode(", ", $set) . " WHERE idUsuario = :id";

        return $this->connection->prepare($query)->execute($params);
    }

    // DELETE
    function eliminar(int $id)
    {
        return $this->connection
            ->prepare("DELETE FROM tblusuario WHERE idUsuario = :id")
            ->execute([':id' => $id]);
    }
}
