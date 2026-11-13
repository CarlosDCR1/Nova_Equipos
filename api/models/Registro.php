<?php

class Registro
{
    private Connection $connection;
    private Response $response;

    public function __construct(Connection $connection, Response $response)
    {
        $this->connection = $connection;
        $this->response = $response;
    }

    // Registro público exclusivo para estudiantes existentes.
    public function add(array $d)
    {
        $hash = password_hash(
            $d['contrasenaUsuario'],
            PASSWORD_BCRYPT
        );

        $this->connection->beginTransaction();

        try {
            // Busca un estudiante activo con el carné y correo indicados.
            // El bloqueo evita registrar simultáneamente a la misma persona.
            $statement = $this->connection->prepare(
                "SELECT id_estudiante
                 FROM estudiante
                 WHERE carnet_estudiante = :identificacion
                   AND correo_institucional = :correo
                   AND estado_estudiante = 'ACTIVO'
                 FOR UPDATE"
            );

            $statement->execute([
                ':identificacion' => $d['identificacion'],
                ':correo' => $d['correo']
            ]);

            $idEstudiante = $statement->fetchColumn();

            if (!$idEstudiante) {
                throw new DomainException(
                    "Estudiante no disponible para registro"
                );
            }

            // Un estudiante solamente puede tener una cuenta.
            $statement = $this->connection->prepare(
                "SELECT idUsuario
                 FROM tblusuario
                 WHERE idEstudiante = :id"
            );

            $statement->execute([
                ':id' => $idEstudiante
            ]);

            if ($statement->fetchColumn()) {
                throw new DomainException(
                    "El estudiante ya tiene una cuenta"
                );
            }

            // El rol siempre se obtiene desde el servidor.
            $statement = $this->connection->prepare(
                "SELECT idRol
                 FROM tblrol
                 WHERE nombreRol = :rol
                   AND is_active = 1"
            );

            $statement->execute([
                ':rol' => 'Estudiante'
            ]);

            $idRol = $statement->fetchColumn();

            if (!$idRol) {
                throw new RuntimeException(
                    "No existe el rol Estudiante activo"
                );
            }

            // La cuenta se crea activa y sin vinculación a un empleado.
            $statement = $this->connection->prepare(
                "INSERT INTO tblusuario (
                    idEmpleado,
                    idEstudiante,
                    nombreUsuario,
                    contrasenaUsuario,
                    is_active
                 )
                 VALUES (
                    NULL,
                    :estudiante,
                    :usuario,
                    :contrasena,
                    1
                 )"
            );

            $statement->execute([
                ':estudiante' => $idEstudiante,
                ':usuario' => $d['nombreUsuario'],
                ':contrasena' => $hash
            ]);

            $idUsuario = (int) $this->connection->lastInsertId();

            // Asigna únicamente el rol Estudiante.
            $statement = $this->connection->prepare(
                "INSERT INTO tblusuariorol (
                    idUsuario,
                    idRol,
                    fechaAsignacionUsuarioRol
                 )
                 VALUES (
                    :usuario,
                    :rol,
                    CURDATE()
                 )"
            );

            $statement->execute([
                ':usuario' => $idUsuario,
                ':rol' => $idRol
            ]);

            // Registra la creación de la cuenta.
            $statement = $this->connection->prepare(
                "INSERT INTO tblbitacora (
                    idUsuario,
                    modulo,
                    aplicacion,
                    accion,
                    tablaAfectada,
                    idRegistro,
                    descripcion,
                    ipOrigen
                 )
                 VALUES (
                    :usuario,
                    'Seguridad',
                    'Registro',
                    'INSERT',
                    'tblusuario',
                    :registro,
                    :descripcion,
                    :ip
                 )"
            );

            $statement->execute([
                ':usuario' => $idUsuario,
                ':registro' => $idUsuario,
                ':descripcion' =>
                    'Cuenta activa de estudiante creada desde el registro público',
                ':ip' => $_SERVER['REMOTE_ADDR'] ?? null
            ]);

            $this->connection->commit();

        } catch (\Throwable $th) {
            // Si algo falla, no queda una cuenta incompleta.
            if ($this->connection->inTransaction()) {
                $this->connection->rollBack();
            }

            throw $th;
        }

        $this->response->success(
            "Usuario creado correctamente. Ya puede iniciar sesión con sus credenciales.",
            [],
            1
        );
    }
}