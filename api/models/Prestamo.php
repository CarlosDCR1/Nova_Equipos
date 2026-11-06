<?php

class Prestamo
{
    private Connection $connection;
    private Response $response;

    public function __construct(Connection $connection, Response $response)
    {
        $this->connection = $connection;
        $this->response = $response;
    }

    // Convierte "2026-11-05T14:30" (datetime-local) a "2026-11-05 14:30:00"; vacío pasa a null
    private function fechaHora(mixed $valor): ?string
    {
        $texto = trim((string) $valor);
        if ($texto === '') {
            return null;
        }
        $texto = str_replace('T', ' ', $texto);
        return strlen($texto) === 16 ? $texto . ':00' : $texto;
    }

    // LISTA PARA EL DESPLEGABLE DE ESTUDIANTES (nombre completo y carnet en un solo texto)
    public function listaEstudiantes()
    {
        $query = "SELECT id_estudiante,
                         CONCAT(nombre_estudiante, ' ', COALESCE(apellidos_estudiante, ''), ' - ', carnet_estudiante) AS nombre_completo
                  FROM estudiante
                  ORDER BY nombre_estudiante ASC";
        $statement = $this->connection->prepare($query);
        $statement->execute();
        $result = $statement->fetchAll(PDO::FETCH_ASSOC);
        $this->response->success("Estudiantes obtenidos correctamente", $result, 1);
    }

    // LISTA PARA EL DESPLEGABLE DE USUARIOS: solo usuarios activos que pertenecen a un empleado
    // (solo id y nombre; nunca se envía la contraseña)
    public function listaUsuariosEmpleado()
    {
        $query = "SELECT idUsuario AS id_usuario, nombreUsuario AS nombre_usuario
                  FROM tblusuario
                  WHERE idEmpleado IS NOT NULL AND is_active = 1
                  ORDER BY nombreUsuario ASC";
        $statement = $this->connection->prepare($query);
        $statement->execute();
        $result = $statement->fetchAll(PDO::FETCH_ASSOC);
        $this->response->success("Usuarios empleados obtenidos correctamente", $result, 1);
    }

    // PARA EL GET
    public function getAll(array $filtros = [])
    {
        // para los filtros de busqueda
        $condiciones = [];
        $params = [];

        if (!empty($filtros['id_estudiante'])) {
            $condiciones[] = "id_estudiante = :id_estudiante";
            $params[':id_estudiante'] = $filtros['id_estudiante'];
        }
        if (!empty($filtros['id_estado_prestamo'])) {
            $condiciones[] = "id_estado_prestamo = :id_estado";
            $params[':id_estado'] = $filtros['id_estado_prestamo'];
        }
        if (!empty($filtros['observaciones'])) {
            $condiciones[] = "observaciones LIKE :observaciones";
            $params[':observaciones'] = "%{$filtros['observaciones']}%";
        }

        // select completo o filtrado
        $where = $condiciones ? ("WHERE " . implode(" AND ", $condiciones)) : "";
        $query = "SELECT id_prestamo, id_estudiante, id_estado_prestamo, id_usuario_registro, fecha_prestamo,
                         DATE(fecha_devolucion_programada) AS fecha_devolucion_programada,
                         fecha_devolucion_real, observaciones
                  FROM prestamo $where ORDER BY id_prestamo DESC";
        $statement = $this->connection->prepare($query);
        $statement->execute($params);
        $result = $statement->fetchAll(PDO::FETCH_ASSOC);
        $this->response->success("Préstamos obtenidos correctamente", $result, 1);
    }

    // PARA EL POST EN EL ADMIN (fecha_prestamo la llena MySQL con la fecha actual)
    public function add(array $d)
    {
        $query = "INSERT INTO prestamo (id_estudiante, id_estado_prestamo, id_usuario_registro,
                                        fecha_devolucion_programada, observaciones)
                  VALUES (:id_estudiante, :id_estado, :id_usuario, :programada, :observaciones)";
        $statement = $this->connection->prepare($query);
        $statement->execute([
            ':id_estudiante' => $d['id_estudiante'],
            ':id_estado' => $d['id_estado_prestamo'],
            ':id_usuario' => $d['id_usuario_registro'],
            ':programada' => $this->fechaHora($d['fecha_devolucion_programada']),
            ':observaciones' => ($d['observaciones'] ?? '') !== '' ? $d['observaciones'] : null
        ]);
        $this->response->success("Préstamo agregado correctamente", [], 1);
    }

    // PARA EL PUT EN EL ADMIN
    public function editar(int $id, array $d)
    {
        $query = "UPDATE prestamo
                  SET id_estudiante = :id_estudiante,
                      id_estado_prestamo = :id_estado,
                      id_usuario_registro = :id_usuario,
                      fecha_devolucion_programada = :programada,
                      observaciones = :observaciones
                  WHERE id_prestamo = :id";
        $statement = $this->connection->prepare($query);
        $statement->execute([
            ':id_estudiante' => $d['id_estudiante'],
            ':id_estado' => $d['id_estado_prestamo'],
            ':id_usuario' => $d['id_usuario_registro'],
            ':programada' => $this->fechaHora($d['fecha_devolucion_programada']),
            ':observaciones' => ($d['observaciones'] ?? '') !== '' ? $d['observaciones'] : null,
            ':id' => $id
        ]);
        $this->response->success("Préstamo actualizado correctamente", [], 1);
    }

    // DELETE EN EL ADMIN
    public function eliminar(int $id)
    {
        $statement = $this->connection->prepare("DELETE FROM prestamo WHERE id_prestamo = :id");
        $statement->execute([':id' => $id]);
        $this->response->success("Préstamo eliminado correctamente", [], 1);
    }
}