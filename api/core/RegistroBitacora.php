<?php
require_once(__DIR__ . "/Configuration.php");
//require_once("../core/Sesion.php");

//Sesion::requerirApi();

class RegistroBitacora
{
    private static bool $programado = false;
    const ACCIONES = ['POST' => 'INSERTAR', 'PUT' => 'EDITAR', 'DELETE' => 'ELIMINAR'];

    // controlador => [módulo, aplicación, tabla]
    const APLICACIONES = [
        'carrera'            => ['Académico y personal', 'Carrera', 'carrera'],
        'estudiante'         => ['Académico y personal', 'Estudiante', 'estudiante'],
        'empleado'           => ['Académico y personal', 'Empleado', 'empleado'],
        'facultad'           => ['Académico y personal', 'Facultad', 'facultad'],
        'modulo'             => ['Seguridad', 'Módulo', 'tblmodulo'],
        'aplicacion'         => ['Seguridad', 'Aplicación', 'tblaplicacion'],
        'usuario'            => ['Seguridad', 'Usuario', 'tblusuario'],
        'categoria_producto' => ['Inventario', 'Categoría de producto', 'categoria_producto'],
        'marca'              => ['Inventario', 'Marca', 'marca'],
        'estado_equipo'      => ['Inventario', 'Estado de equipo', 'estado_equipo'],
        'modelo_producto'    => ['Inventario', 'Modelo de producto', 'modelo_producto'],
        'equipo'             => ['Inventario', 'Equipo', 'equipo'],
        'inventario_equipo'  => ['Inventario', 'Inventario de equipo', 'inventario_equipo'],
        'estado_prestamo'    => ['Préstamos', 'Estado de préstamo', 'estado_prestamo'],
        'prestamo'           => ['Préstamos', 'Préstamo', 'prestamo'],
        'detalle_prestamo'   => ['Préstamos', 'Detalle de préstamo', 'detalle_prestamo'],
        'tipo_incidente'     => ['Devoluciones', 'Tipo de incidente', 'tipo_incidente'],
        'devolucion'         => ['Devoluciones', 'Devolución', 'devolucion'],
        'detalle_devolucion' => ['Devoluciones', 'Detalle de devolución', 'detalle_devolucion'],
    ];

    static function programar(): void
    {
        //error_log("BITACORA programar: " . ($_SERVER['REQUEST_METHOD'] ?? '') . " " . $_SERVER['SCRIPT_NAME']);
        
         if (self::$programado) {
            return;
        }
        self::$programado = true;

        $metodo = $_SERVER['REQUEST_METHOD'] ?? '';
        if (!isset(self::ACCIONES[$metodo])) {
            return;                                   // los GET no se registran
        }

        $entrada = json_decode(file_get_contents("php://input"), true);
        $id = (int) ($entrada['id'] ?? 0);            // solo viene en PUT y DELETE
        $accion = self::ACCIONES[$metodo];
        $idUsuario = $_SESSION['idUsuario'] ?? null;
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;

        $script = strtolower(basename($_SERVER['SCRIPT_NAME'], '.php'));
        [$modulo, $aplicacion, $tabla] = self::APLICACIONES[$script] ?? [null, $script, null];

        register_shutdown_function(function () use ($accion, $id, $idUsuario, $ip, $modulo, $aplicacion, $tabla) {
            if (http_response_code() >= 400) {
                return;                               // la operación falló: no se registra
            }

            $descripcion = match ($accion) {
                'INSERTAR' => "Se agregó un registro en $aplicacion",
                'EDITAR'   => "Se editó el registro $id en $aplicacion",
                default    => "Se eliminó el registro $id en $aplicacion",
            };

            try {
                // conexión propia: la clase Connection imprime JSON si falla y arruinaría la respuesta
                $pdo = new PDO("mysql:host=" . HOST_DB . ";dbname=" . DATABASE . ";charset=" . CHARSET,
                               USER_DB, PASSWORD_DB, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
                $pdo->prepare(
                    "INSERT INTO tblbitacora (idUsuario, modulo, aplicacion, accion, tablaAfectada, idRegistro, descripcion, ipOrigen)
                     VALUES (:usuario, :modulo, :aplicacion, :accion, :tabla, :registro, :descripcion, :ip)")
                    ->execute([':usuario' => $idUsuario, ':modulo' => $modulo, ':aplicacion' => $aplicacion,
                               ':accion' => $accion, ':tabla' => $tabla, ':registro' => $id ?: null,
                               ':descripcion' => $descripcion, ':ip' => $ip]);
            } catch (\Throwable $e) {
                error_log("BITACORA ERROR: " . $e->getMessage());
                // si la bitácora falla no se rompe la operación que ya se hizo
            }
        });
    }
}