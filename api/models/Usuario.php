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

    // PARA EL GET
    public function getAll(array $filtros = [])
    {

    //para los filtros de busqueda 
        $condiciones = [];
        $params = [];
        if (!empty($filtros['idEmpleado'])) {
            $condiciones[] = "u.idEmpleado = :idEmpleado";
            $params[':idEmpleado'] = $filtros['idEmpleado'];
        }
        if (!empty($filtros['nombre'])) {
            $condiciones[] = "u.nombreUsuario LIKE :nombre";
            $params[':nombre'] = "%{$filtros['nombre']}%";
        }
        if (($filtros['is_active'] ?? '') !== '') {
            $condiciones[] = "u.is_active = :estado";
            $params[':estado'] = (int) $filtros['is_active'];
        }

        //select compelto o filtrado completo 
        
        $where = $condiciones ? ("WHERE " . implode(" AND ", $condiciones)) : "";
        $query = "SELECT u.idUsuario, u.idEmpleado, e.nombre AS empleado_nombre, e.apellidos AS empleado_apellidos,
                         u.nombreUsuario, u.ultimoAccesoUsuario, u.intentosFallidosUsuario, u.is_active, u.created_at
                  FROM tblusuario u INNER JOIN empleado e ON u.idEmpleado = e.id_empleado
                  $where ORDER BY u.idUsuario DESC";
        $statement = $this->connection->prepare($query);
        $statement->execute($params);
        $result = $statement->fetchAll(PDO::FETCH_ASSOC);
        $this->response->success("Usuarios obtenidos correctamente", $result, 1);
    }

    // PARA EL POST EN EL ADMIN
    public function add(array $d)
    {
        if (empty($d['idEmpleado']) || empty($d['nombreUsuario']) || empty($d['contrasenaUsuario'])) {
            $this->response->error("Empleado, usuario y contraseña son obligatorios", 4001, 400);
        }

        $query = "INSERT INTO tblusuario (idEmpleado, nombreUsuario, contrasenaUsuario, is_active)
                  VALUES (:idEmpleado, :nombre, :contrasena, :estado)";
        $statement = $this->connection->prepare($query);
        $statement->execute([':idEmpleado' => $d['idEmpleado'], ':nombre' => $d['nombreUsuario'],
                    ':contrasena' => password_hash($d['contrasenaUsuario'], PASSWORD_BCRYPT), ':estado' => $d['is_active'] ?? 1]);
        $this->response->success("Usuario agregado correctamente", [], 1);
    }

    //PARA EL PUT EN EL ADMIN
    public function editar(int $id, array $d)
    {
        $query = "UPDATE tblusuario SET idEmpleado = :idEmpleado, nombreUsuario = :nombre, is_active = :estado";
        $params = [':idEmpleado' => $d['idEmpleado'], ':nombre' => $d['nombreUsuario'], ':estado' => $d['is_active'] ?? 1, ':id' => $id];

        // la contraseña solo se cambia si viene escrita
        if (!empty($d['contrasenaUsuario'])) {
            $query .= ", contrasenaUsuario = :contrasena, fechaUltimaContrasena = NOW()";
            $params[':contrasena'] = password_hash($d['contrasenaUsuario'], PASSWORD_BCRYPT);
        }

        $query .= " WHERE idUsuario = :id";
        $statement = $this->connection->prepare($query);
        $statement->execute($params);
        $this->response->success("Usuario actualizado correctamente", [], 1);
    }

    // DELETE EN EL ADMIN
    public function eliminar(int $id)
    {
        $statement = $this->connection->prepare("DELETE FROM tblusuario WHERE idUsuario = :id");
        $statement->execute([':id' => $id]);
        $this->response->success("Usuario eliminado correctamente", [], 1);
    }
}