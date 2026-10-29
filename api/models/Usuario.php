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
        // filtro por tipo de usuario: empleado o estudiante
        if (($filtros['tipo'] ?? '') === 'empleado') {
            $condiciones[] = "u.idEmpleado IS NOT NULL";
        } elseif (($filtros['tipo'] ?? '') === 'estudiante') {
            $condiciones[] = "u.idEstudiante IS NOT NULL";
        }
        if (!empty($filtros['nombre'])) {
            $condiciones[] = "u.nombreUsuario LIKE :nombre";
            $params[':nombre'] = "%{$filtros['nombre']}%";
        }
        if (($filtros['is_active'] ?? '') !== '') {
            $condiciones[] = "u.is_active = :estado";
            $params[':estado'] = (int) $filtros['is_active'];
        }

        // LEFT JOIN a las dos tablas porque el usuario es empleado O estudiante
        $where = $condiciones ? ("WHERE " . implode(" AND ", $condiciones)) : "";
        $query = "SELECT u.idUsuario, u.idEmpleado, u.idEstudiante,
                         CASE WHEN u.idEmpleado IS NOT NULL THEN 'Empleado' ELSE 'Estudiante' END AS tipo,
                         CASE WHEN u.idEmpleado IS NOT NULL
                              THEN CONCAT(e.nombre, ' ', COALESCE(e.apellidos, ''))
                              ELSE CONCAT(s.nombre_estudiante, ' ', COALESCE(s.apellidos_estudiante, ''))
                         END AS persona,
                         u.nombreUsuario, u.ultimoAccesoUsuario, u.intentosFallidosUsuario, u.is_active, u.created_at
                  FROM tblusuario u
                  LEFT JOIN empleado e ON u.idEmpleado = e.id_empleado
                  LEFT JOIN estudiante s ON u.idEstudiante = s.id_estudiante
                  $where ORDER BY u.idUsuario DESC";
        $statement = $this->connection->prepare($query);
        $statement->execute($params);
        $result = $statement->fetchAll(PDO::FETCH_ASSOC);
        $this->response->success("Usuarios obtenidos correctamente", $result, 1);
    }

    // PARA EL POST EN EL ADMIN
    public function add(array $d)
    {
        [$idEmpleado, $idEstudiante, $nombreRol] = $this->tipoUsuario($d);

        if (empty($d['nombreUsuario']) || empty($d['contrasenaUsuario'])) {
            $this->response->error("Usuario y contraseña son obligatorios", 4001, 400);
        }

        // el usuario y su rol se crean juntos: si algo falla no queda nada a medias
        $this->connection->beginTransaction();
        try {
            $query = "INSERT INTO tblusuario (idEmpleado, idEstudiante, nombreUsuario, contrasenaUsuario, is_active)
                      VALUES (:idEmpleado, :idEstudiante, :nombre, :contrasena, :estado)";
            $statement = $this->connection->prepare($query);
            $statement->execute([':idEmpleado' => $idEmpleado, ':idEstudiante' => $idEstudiante,
                        ':nombre' => $d['nombreUsuario'],
                        ':contrasena' => password_hash($d['contrasenaUsuario'], PASSWORD_BCRYPT),
                        ':estado' => $d['is_active'] ?? 1]);

            $this->sincronizarRol((int) $this->connection->lastInsertId(), $nombreRol);
            $this->connection->commit();
        } catch (\Throwable $th) {
            $this->connection->rollBack();
            throw $th;
        }

        $this->response->success("Usuario agregado correctamente", [], 1);
    }

    //PARA EL PUT EN EL ADMIN
    public function editar(int $id, array $d)
    {
        [$idEmpleado, $idEstudiante, $nombreRol] = $this->tipoUsuario($d);

        $query = "UPDATE tblusuario SET idEmpleado = :idEmpleado, idEstudiante = :idEstudiante,
                         nombreUsuario = :nombre, is_active = :estado";
        $params = [':idEmpleado' => $idEmpleado, ':idEstudiante' => $idEstudiante,
                   ':nombre' => $d['nombreUsuario'], ':estado' => $d['is_active'] ?? 1, ':id' => $id];

        // la contraseña solo se cambia si viene escrita
        if (!empty($d['contrasenaUsuario'])) {
            $query .= ", contrasenaUsuario = :contrasena, fechaUltimaContrasena = NOW()";
            $params[':contrasena'] = password_hash($d['contrasenaUsuario'], PASSWORD_BCRYPT);
        }

        $query .= " WHERE idUsuario = :id";

        $this->connection->beginTransaction();
        try {
            $statement = $this->connection->prepare($query);
            $statement->execute($params);

            // si cambió de empleado a estudiante (o al revés) se ajusta el rol automático
            $this->sincronizarRol($id, $nombreRol);
            $this->connection->commit();
        } catch (\Throwable $th) {
            $this->connection->rollBack();
            throw $th;
        }

        $this->response->success("Usuario actualizado correctamente", [], 1);
    }

    // DELETE EN EL ADMIN
    public function eliminar(int $id)
    {
        $statement = $this->connection->prepare("DELETE FROM tblusuario WHERE idUsuario = :id");
        $statement->execute([':id' => $id]);
        $this->response->success("Usuario eliminado correctamente", [], 1);
    }

    // Debe venir un empleado O un estudiante. El rol sale de ese dato, nunca del formulario,
    // así nadie puede darse el rol de Administrador desde aquí.
    private function tipoUsuario(array $d): array
    {
        $idEmpleado = !empty($d['idEmpleado']) ? (int) $d['idEmpleado'] : null;
        $idEstudiante = !empty($d['idEstudiante']) ? (int) $d['idEstudiante'] : null;

        if ($idEmpleado === null && $idEstudiante === null) {
            $this->response->error("Selecciona un empleado o un estudiante", 4001, 400);
        }
        if ($idEmpleado !== null && $idEstudiante !== null) {
            $this->response->error("Selecciona solo un empleado o un estudiante, no los dos", 4001, 400);
        }

        return [$idEmpleado, $idEstudiante, $idEmpleado !== null ? 'Empleado' : 'Estudiante'];
    }

    // aqui es donde se asigna el rol de Empleado o Estudiante al usuario, y se quita otro si existía 
    private function sincronizarRol(int $idUsuario, string $nombreRol): void
    {
        $statement = $this->connection->prepare("SELECT idRol FROM tblrol WHERE nombreRol = :rol AND is_active = 1");
        $statement->execute([':rol' => $nombreRol]);
        $idRol = $statement->fetchColumn();

        if (!$idRol) {
            throw new RuntimeException("No existe el rol $nombreRol");
        }

        $this->connection->prepare(
            "DELETE ur FROM tblusuariorol ur INNER JOIN tblrol r ON r.idRol = ur.idRol
             WHERE ur.idUsuario = :u AND r.nombreRol IN ('Empleado', 'Estudiante') AND ur.idRol <> :r")
            ->execute([':u' => $idUsuario, ':r' => $idRol]);

        $this->connection->prepare(
            "INSERT INTO tblusuariorol (idUsuario, idRol, fechaAsignacionUsuarioRol)
             SELECT :u1, :r1, CURDATE() FROM DUAL
             WHERE NOT EXISTS (SELECT 1 FROM tblusuariorol WHERE idUsuario = :u2 AND idRol = :r2)")
            ->execute([':u1' => $idUsuario, ':r1' => $idRol, ':u2' => $idUsuario, ':r2' => $idRol]);
    }
}