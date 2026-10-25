<?php

class Auth
{
    const MAX_INTENTOS = 3;
    const MINUTOS_BLOQUEO = 15;

    private Connection $connection;
    private Response $response;

    public function __construct(Connection $connection, Response $response)
    {
        $this->connection = $connection;
        $this->response = $response;
    }

    public function login(string $usuario, string $contrasena)
    {
        $statement = $this->connection->prepare(
            "SELECT u.idUsuario, u.nombreUsuario, u.contrasenaUsuario, u.is_active,
                    (u.fechaBloqueoUsuario IS NOT NULL AND u.fechaBloqueoUsuario > NOW()) AS bloqueado,
                    e.nombre, e.apellidos, e.estado_empleado
             FROM tblusuario u INNER JOIN empleado e ON u.idEmpleado = e.id_empleado
             WHERE u.nombreUsuario = :usuario");
        $statement->execute([':usuario' => $usuario]);
        $u = $statement->fetch(PDO::FETCH_ASSOC);

        // Si el usuario no existe se le enseña este mensaje
        if (!$u) {
            $this->registrarIntento(null, $usuario, 0, "Usuario inexistente");
            $this->response->error("Usuario o contraseña incorrectos", 7002, 401);
        }

        // Si se bloquea el usuario por intentos fallidos
        if ($u['bloqueado']) {
            $this->registrarIntento($u['idUsuario'], $usuario, 0, "Cuenta bloqueada");
            $this->response->error("Cuenta bloqueada temporalmente por intentos fallidos. Intente más tarde", 7004, 423);
        }

        // Si el usuario ingresa contraseña incorrecta suma intento y bloquea la cuenta al llegar al máximo de intentos
        if (!password_verify($contrasena, $u['contrasenaUsuario'])) {
            $this->registrarFallo((int) $u['idUsuario'], $usuario);
            $this->response->error("Usuario o contraseña incorrectos", 7002, 401);
        }

        // Si el usuario ingresa correctamente la contraseña pero el usuario inactivo
        if (!$u['is_active'] || $u['estado_empleado'] !== 'ACTIVO') {
            $this->registrarIntento($u['idUsuario'], $usuario, 0, "Usuario o empleado inactivo");
            $this->response->error("El usuario está inactivo. Contacte al administrador", 7003, 403);
        }

        // Si el usuario ingresa todo correctamente 
        $this->connection->prepare(
            "UPDATE tblusuario SET intentosFallidosUsuario = 0, fechaBloqueoUsuario = NULL, ultimoAccesoUsuario = NOW()
             WHERE idUsuario = :id")->execute([':id' => $u['idUsuario']]);

        $statement = $this->connection->prepare(
            "SELECT r.idRol, r.nombreRol FROM tblusuariorol ur
             INNER JOIN tblrol r ON ur.idRol = r.idRol
             WHERE ur.idUsuario = :id AND r.is_active = 1");
        $statement->execute([':id' => $u['idUsuario']]);
        $roles = $statement->fetchAll(PDO::FETCH_ASSOC);

        // Con esto evitamos que la sesion se quede fija
        session_regenerate_id(true); 
        $_SESSION['idUsuario'] = (int) $u['idUsuario'];
        $_SESSION['nombreUsuario'] = $u['nombreUsuario'];
        $_SESSION['nombreEmpleado'] = trim($u['nombre'] . ' ' . ($u['apellidos'] ?? ''));
        $_SESSION['roles'] = $roles;
        $_SESSION['ultimaActividad'] = time();

        $this->registrarIntento($u['idUsuario'], $usuario, 1, "Acceso correcto");
        $this->registrarBitacora((int) $u['idUsuario'], "Inicio de sesión");

        $this->response->success("Bienvenido", ['redirect' => 'index.php'], 1);
    }

    private function registrarFallo(int $idUsuario, string $usuario)
    {
        $this->connection->prepare(
            "UPDATE tblusuario SET intentosFallidosUsuario = intentosFallidosUsuario + 1 WHERE idUsuario = :id")
            ->execute([':id' => $idUsuario]);

        // si llega al maximo de intentos se bloquea por 15 minutos y luego se reinicia el contador
        $bloqueo = $this->connection->prepare(
            "UPDATE tblusuario SET intentosFallidosUsuario = 0,
                    fechaBloqueoUsuario = DATE_ADD(NOW(), INTERVAL " . self::MINUTOS_BLOQUEO . " MINUTE)
             WHERE idUsuario = :id AND intentosFallidosUsuario >= " . self::MAX_INTENTOS);
        $bloqueo->execute([':id' => $idUsuario]);

        $motivo = $bloqueo->rowCount() > 0 ? "Contraseña incorrecta, cuenta bloqueada" : "Contraseña incorrecta";
        $this->registrarIntento($idUsuario, $usuario, 0, $motivo);
    }

    private function registrarIntento(?int $idUsuario, string $usuario, int $exitoso, string $motivo)
    {
        $this->connection->prepare(
            "INSERT INTO tblintentologin (idUsuario, nombreUsuarioIngresado, exitoso, ipOrigen, userAgent, motivo)
             VALUES (:idUsuario, :usuario, :exitoso, :ip, :agente, :motivo)")
            ->execute([':idUsuario' => $idUsuario, ':usuario' => mb_substr($usuario, 0, 50), ':exitoso' => $exitoso,
                       ':ip' => $_SERVER['REMOTE_ADDR'] ?? null, ':agente' => mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
                       ':motivo' => $motivo]);
    }

    private function registrarBitacora(int $idUsuario, string $descripcion)
    {
        $this->connection->prepare(
            "INSERT INTO tblbitacora (idUsuario, modulo, aplicacion, accion, tablaAfectada, idRegistro, descripcion, ipOrigen)
             VALUES (:id, 'Seguridad', 'Login', 'LOGIN', 'tblusuario', :id, :descripcion, :ip)")
            ->execute([':id' => $idUsuario, ':descripcion' => $descripcion, ':ip' => $_SERVER['REMOTE_ADDR'] ?? null]);
    }
}