<?php


class ReglaApartado extends Exception
{
}

class Apartado
{
    const HORAS_APARTADO   = 24;     // plazo para recoger un apartado
    const HORAS_SOLICITUD  = 48;     // plazo para que un empleado responda una solicitud
    const MAX_EQUIPOS      = 3;      // equipos que un estudiante puede tener a la vez (apartados + entregados)
    const MONTO_APROBACION = 8000;
    const DIAS_PRESTAMO    = 7;

    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    // ------------------------------------------------------------------
    // SQL compartido con el panel (Dashboard) para que los dos cuenten igual
    // ------------------------------------------------------------------

    // El equipo (alias e) tiene una línea de préstamo SIN devolución en alguno de esos estados.
    // Una solicitud o un apartado solo cuentan mientras no pase su plazo (apartado_hasta).
    public static function enPrestamo(array $estados): string
    {
        $lista = "'" . implode("','", $estados) . "'";
        return "EXISTS (
            SELECT 1 FROM detalle_prestamo dp
            JOIN prestamo p ON p.id_prestamo = dp.id_prestamo
            JOIN estado_prestamo ep ON ep.id_estado_prestamo = p.id_estado_prestamo
            WHERE dp.id_equipo = e.id_equipo
              AND ep.nombre_estado IN ($lista)
              AND (ep.nombre_estado NOT IN ('Solicitado', 'Apartado') OR p.apartado_hasta >= NOW())
              AND NOT EXISTS (SELECT 1 FROM detalle_devolucion dd WHERE dd.id_detalle_prestamo = dp.id_detalle_prestamo))";
    }

    // ya se entregó al estudiante
    public static function prestado(): string
    {
        return self::enPrestamo(['Activo', 'Vencido']);
    }

    // entregado o apartado: no se puede ofrecer a otro estudiante
    public static function ocupado(): string
    {
        return self::enPrestamo(['Solicitado', 'Apartado', 'Activo', 'Vencido']);
    }

    // línea del préstamo (alias p) que todavía no tiene devolución
    private const LINEA_SIN_DEVOLVER = "EXISTS (
            SELECT 1 FROM detalle_prestamo dp
            WHERE dp.id_prestamo = p.id_prestamo
              AND NOT EXISTS (SELECT 1 FROM detalle_devolucion dd WHERE dd.id_detalle_prestamo = dp.id_detalle_prestamo))";

    // ------------------------------------------------------------------
    // ESTUDIANTE: apartar, cancelar, ver
    // ------------------------------------------------------------------

    // Una sola solicitud del estudiante, que el sistema divide en hasta dos préstamos:
    // - equipos de hasta 8,000: préstamo "Apartado" al instante, con 24 horas para recoger
    // - equipos de más de 8,000: préstamo "Solicitado", espera la aprobación de un empleado
    // Así el equipo barato no espera por el caro, y si rechazan el caro no se pierde el otro.
    public function apartar(int $idEstudiante, int $idUsuario, array $idEquipos): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $idEquipos), fn ($i) => $i > 0)));
        sort($ids); // siempre en el mismo orden, así dos estudiantes no se bloquean entre sí
        if (!$ids) {
            throw new ReglaApartado("Selecciona al menos un equipo");
        }

        return $this->enTransaccion(function () use ($idEstudiante, $idUsuario, $ids) {
            $this->liberarVencidos();

            // se bloquea al estudiante: dos peticiones suyas a la vez no pueden pasar del límite
            $st = $this->connection->prepare(
                "SELECT id_carrera, estado_estudiante FROM estudiante WHERE id_estudiante = :id FOR UPDATE");
            $st->execute([':id' => $idEstudiante]);
            $est = $st->fetch(PDO::FETCH_ASSOC);
            if (!$est || $est['estado_estudiante'] !== 'ACTIVO') {
                throw new ReglaApartado("Tu cuenta de estudiante no está activa");
            }

            // el límite cuenta EQUIPOS, no préstamos (si no, dividir la solicitud lo esquivaría)
            $abiertos = $this->contarEquiposAbiertos($idEstudiante);
            if ($abiertos + count($ids) > self::MAX_EQUIPOS) {
                throw new ReglaApartado("Puedes tener hasta " . self::MAX_EQUIPOS . " equipos a la vez y ya tienes $abiertos. Devuelve o cancela alguno para pedir más");
            }
            if ($this->tieneVencido($idEstudiante)) {
                throw new ReglaApartado("Tienes un préstamo vencido. Devuélvelo antes de pedir otro equipo");
            }

            $baratos = [];
            $caros = [];
            foreach ($ids as $idEquipo) {
                // se bloquea el equipo: dos estudiantes no pueden apartar la misma unidad
                $st = $this->connection->prepare(
                    "SELECT codigo_inventario, id_modelo_producto, id_estado_equipo, valor_adquisicion
                     FROM equipo WHERE id_equipo = :id FOR UPDATE");
                $st->execute([':id' => $idEquipo]);
                $eq = $st->fetch(PDO::FETCH_ASSOC);
                if (!$eq) {
                    throw new ReglaApartado("El equipo seleccionado no existe");
                }

                $st = $this->connection->prepare("SELECT permite_prestamo FROM estado_equipo WHERE id_estado_equipo = :e");
                $st->execute([':e' => $eq['id_estado_equipo']]);
                if (!$st->fetchColumn()) {
                    throw new ReglaApartado("El equipo {$eq['codigo_inventario']} no está disponible");
                }

                // solo equipos de las categorías de su carrera
                $st = $this->connection->prepare(
                    "SELECT 1 FROM carrera_categoria cc
                     JOIN categoria_producto cp ON cp.id_categoria_producto = cc.id_categoria_producto AND cp.is_active = 1
                     JOIN modelo_producto mp ON mp.id_categoria_producto = cp.id_categoria_producto AND mp.is_active = 1
                     WHERE cc.id_carrera = :carrera AND mp.id_modelo_producto = :modelo");
                $st->execute([':carrera' => $est['id_carrera'], ':modelo' => $eq['id_modelo_producto']]);
                if (!$st->fetchColumn()) {
                    throw new ReglaApartado("El equipo {$eq['codigo_inventario']} no corresponde a tu carrera");
                }

                $st = $this->connection->prepare("SELECT 1 FROM equipo e WHERE e.id_equipo = :id AND " . self::ocupado());
                $st->execute([':id' => $idEquipo]);
                if ($st->fetchColumn()) {
                    throw new ReglaApartado("El equipo {$eq['codigo_inventario']} ya fue apartado o está prestado");
                }

                if ($eq['valor_adquisicion'] !== null && (float) $eq['valor_adquisicion'] > self::MONTO_APROBACION) {
                    $caros[] = $idEquipo;
                } else {
                    $baratos[] = $idEquipo;
                }
            }

            $prestamos = [];
            if ($baratos) {
                $prestamos[] = $this->crearPrestamo($idEstudiante, $idUsuario, $baratos, 'Apartado', self::HORAS_APARTADO);
            }
            if ($caros) {
                $prestamos[] = $this->crearPrestamo($idEstudiante, $idUsuario, $caros, 'Solicitado', self::HORAS_SOLICITUD);
            }

            return ['prestamos' => $prestamos, 'requiere_aprobacion' => (bool) $caros];
        });
    }

        public function abiertos(int $idEstudiante): int
    {
        $this->liberarVencidos();
        return $this->contarEquiposAbiertos($idEstudiante);
    }

    // Crea un préstamo con sus líneas. Se usa solo dentro de la transacción de apartar().
    private function crearPrestamo(int $idEstudiante, int $idUsuario, array $idEquipos, string $estado, int $horas): array
    {
        $h = (int) $horas;
        $d = (int) self::DIAS_PRESTAMO;

        // fecha_devolucion_programada es provisional: al entregar se recalcula desde ese día
        $st = $this->connection->prepare(
            "INSERT INTO prestamo (id_estudiante, id_estado_prestamo, id_usuario_registro, fecha_prestamo,
                                   apartado_hasta, fecha_devolucion_programada)
             VALUES (:est, :estado, :usr, NOW(), DATE_ADD(NOW(), INTERVAL $h HOUR),
                     DATE_ADD(DATE_ADD(NOW(), INTERVAL $h HOUR), INTERVAL $d DAY))");
        $st->execute([':est' => $idEstudiante, ':estado' => $this->idEstadoPrestamo($estado), ':usr' => $idUsuario]);
        $idPrestamo = (int) $this->connection->lastInsertId();

        $st = $this->connection->prepare("INSERT INTO detalle_prestamo (id_prestamo, id_equipo) VALUES (:p, :e)");
        foreach ($idEquipos as $idEquipo) {
            $st->execute([':p' => $idPrestamo, ':e' => $idEquipo]);
        }

        $st = $this->connection->prepare("SELECT apartado_hasta FROM prestamo WHERE id_prestamo = :id");
        $st->execute([':id' => $idPrestamo]);

        return [
            'id_prestamo'         => $idPrestamo,
            'estado'              => $estado,
            'requiere_aprobacion' => $estado === 'Solicitado',
            'apartado_hasta'      => $st->fetchColumn(),
        ];
    }

    // El estudiante cancela su propio apartado o solicitud ($idEstudiante = null: lo cancela un empleado)
    public function cancelar(int $idPrestamo, ?int $idEstudiante = null): array
    {
        return $this->enTransaccion(function () use ($idPrestamo, $idEstudiante) {
            $this->liberarVencidos();
            $p = $this->bloquear($idPrestamo);

            if ($idEstudiante !== null && (int) $p['id_estudiante'] !== $idEstudiante) {
                throw new ReglaApartado("Ese préstamo no es tuyo");
            }
            if (!in_array($p['estado'], ['Solicitado', 'Apartado'], true)) {
                throw new ReglaApartado("Solo se puede cancelar un apartado o solicitud vigente (estado actual: {$p['estado']})");
            }

            $this->cambiarEstado($idPrestamo, 'Cancelado');
            return ['id_prestamo' => $idPrestamo, 'estado' => 'Cancelado'];
        });
    }

    // Equipos que el estudiante puede apartar ahora (de las categorías de su carrera)
    public function disponibles(int $idEstudiante): array
    {
        $st = $this->connection->prepare(
            "SELECT e.id_equipo, e.codigo_inventario, cp.nombre_categoria_producto AS categoria,
                    mp.nombre_modelo_producto AS modelo,
                    (e.valor_adquisicion IS NOT NULL AND e.valor_adquisicion > " . (float) self::MONTO_APROBACION . ") AS requiere_aprobacion
             FROM estudiante s
             JOIN carrera_categoria cc ON cc.id_carrera = s.id_carrera
             JOIN categoria_producto cp ON cp.id_categoria_producto = cc.id_categoria_producto AND cp.is_active = 1
             JOIN modelo_producto mp ON mp.id_categoria_producto = cp.id_categoria_producto AND mp.is_active = 1
             JOIN equipo e ON e.id_modelo_producto = mp.id_modelo_producto
             JOIN estado_equipo ee ON ee.id_estado_equipo = e.id_estado_equipo AND ee.permite_prestamo = 1
             WHERE s.id_estudiante = :id AND NOT " . self::ocupado() . "
             ORDER BY cp.nombre_categoria_producto, mp.nombre_modelo_producto, e.codigo_inventario");
        $st->execute([':id' => $idEstudiante]);
        $filas = $st->fetchAll(PDO::FETCH_ASSOC);
        foreach ($filas as &$f) {
            $f['id_equipo'] = (int) $f['id_equipo'];
            $f['requiere_aprobacion'] = (bool) $f['requiere_aprobacion'];
        }
        return $filas;
    }

    // Préstamos del estudiante, el más nuevo primero
    public function misPrestamos(int $idEstudiante): array
    {
        $this->liberarVencidos();
        $st = $this->connection->prepare(
            "SELECT p.id_prestamo, ep.nombre_estado AS estado, p.fecha_prestamo, p.apartado_hasta,
                    p.fecha_entrega, p.fecha_devolucion_programada, " . self::EQUIPOS_TEXTO . " AS equipos
             FROM prestamo p JOIN estado_prestamo ep ON ep.id_estado_prestamo = p.id_estado_prestamo
             WHERE p.id_estudiante = :id ORDER BY p.id_prestamo DESC");
        $st->execute([':id' => $idEstudiante]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    // ------------------------------------------------------------------
    // EMPLEADO: aprobar, rechazar, entregar, ver pendientes
    // ------------------------------------------------------------------

    // Aprueba una solicitud (equipo de más de 8,000). Desde aquí corren otras 24 horas para recoger.
    public function aprobar(int $idPrestamo, int $idUsuario): array
    {
        return $this->enTransaccion(function () use ($idPrestamo, $idUsuario) {
            $this->liberarVencidos();
            $p = $this->bloquear($idPrestamo);
            if ($p['estado'] !== 'Solicitado') {
                throw new ReglaApartado("Solo se aprueban solicitudes pendientes (estado actual: {$p['estado']})");
            }

            $h = (int) self::HORAS_APARTADO;
            $st = $this->connection->prepare(
                "UPDATE prestamo SET id_estado_prestamo = :estado, id_usuario_aprueba = :usr,
                        apartado_hasta = DATE_ADD(NOW(), INTERVAL $h HOUR)
                 WHERE id_prestamo = :id");
            $st->execute([':estado' => $this->idEstadoPrestamo('Apartado'), ':usr' => $idUsuario, ':id' => $idPrestamo]);
            return ['id_prestamo' => $idPrestamo, 'estado' => 'Apartado'];
        });
    }

    // Rechaza una solicitud o un apartado (por ejemplo, al recoger el equipo).
    public function rechazar(int $idPrestamo, ?string $motivo = null): array
    {
        return $this->enTransaccion(function () use ($idPrestamo, $motivo) {
            $this->liberarVencidos();
            $p = $this->bloquear($idPrestamo);
            if (!in_array($p['estado'], ['Solicitado', 'Apartado'], true)) {
                throw new ReglaApartado("Solo se rechazan solicitudes o apartados vigentes (estado actual: {$p['estado']})");
            }

            $this->cambiarEstado($idPrestamo, 'Rechazado', trim((string) $motivo) ?: 'Rechazado por un empleado');
            return ['id_prestamo' => $idPrestamo, 'estado' => 'Rechazado'];
        });
    }

    // El estudiante llegó por el equipo: el empleado de la sesión queda como quien lo entregó.
    public function entregar(int $idPrestamo, int $idUsuario): array
    {
        return $this->enTransaccion(function () use ($idPrestamo, $idUsuario) {
            $this->liberarVencidos();
            $p = $this->bloquear($idPrestamo);
            if ($p['estado'] !== 'Apartado') {
                throw new ReglaApartado("Solo se entregan préstamos apartados (estado actual: {$p['estado']})");
            }

            $st = $this->connection->prepare("SELECT id_equipo FROM detalle_prestamo WHERE id_prestamo = :id ORDER BY id_equipo");
            $st->execute([':id' => $idPrestamo]);
            $equipos = $st->fetchAll(PDO::FETCH_COLUMN);
            if (!$equipos) {
                throw new ReglaApartado("El préstamo no tiene equipos");
            }

            foreach ($equipos as $idEquipo) {
                $st = $this->connection->prepare(
                    "SELECT e.codigo_inventario, ee.permite_prestamo
                     FROM equipo e JOIN estado_equipo ee ON ee.id_estado_equipo = e.id_estado_equipo
                     WHERE e.id_equipo = :id FOR UPDATE");
                $st->execute([':id' => $idEquipo]);
                $eq = $st->fetch(PDO::FETCH_ASSOC);
                if (!$eq || !$eq['permite_prestamo']) {
                    throw new ReglaApartado("El equipo " . ($eq['codigo_inventario'] ?? $idEquipo) . " ya no está disponible. Rechaza el préstamo");
                }
            }

            $d = (int) self::DIAS_PRESTAMO;
            $st = $this->connection->prepare(
                "UPDATE prestamo SET id_estado_prestamo = :estado, id_usuario_entrega = :usr, fecha_entrega = NOW(),
                        fecha_devolucion_programada = DATE_ADD(NOW(), INTERVAL $d DAY)
                 WHERE id_prestamo = :id");
            $st->execute([':estado' => $this->idEstadoPrestamo('Activo'), ':usr' => $idUsuario, ':id' => $idPrestamo]);

            // los equipos pasan a "Prestado"
            $idPrestado = $this->idEstadoEquipo('Prestado');
            $st = $this->connection->prepare("UPDATE equipo SET id_estado_equipo = :estado WHERE id_equipo = :id");
            foreach ($equipos as $idEquipo) {
                $st->execute([':estado' => $idPrestado, ':id' => $idEquipo]);
            }

            $st = $this->connection->prepare("SELECT fecha_devolucion_programada FROM prestamo WHERE id_prestamo = :id");
            $st->execute([':id' => $idPrestamo]);
            return ['id_prestamo' => $idPrestamo, 'estado' => 'Activo', 'fecha_devolucion_programada' => $st->fetchColumn()];
        });
    }

    // Solicitudes por aprobar y apartados por recoger (solo los vigentes)
    public function pendientes(): array
    {
        $this->liberarVencidos();
        return $this->connection->query(
            "SELECT p.id_prestamo, ep.nombre_estado AS estado,
                    CONCAT(s.nombre_estudiante, ' ', COALESCE(s.apellidos_estudiante, '')) AS estudiante,
                    p.fecha_prestamo, p.apartado_hasta, " . self::EQUIPOS_TEXTO . " AS equipos
             FROM prestamo p
             JOIN estado_prestamo ep ON ep.id_estado_prestamo = p.id_estado_prestamo
             JOIN estudiante s ON s.id_estudiante = p.id_estudiante
             WHERE ep.nombre_estado IN ('Solicitado', 'Apartado') AND p.apartado_hasta >= NOW()
             ORDER BY p.apartado_hasta ASC"
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    // ------------------------------------------------------------------
    // Soporte
    // ------------------------------------------------------------------

    // Cancela las solicitudes y apartados cuyo plazo ya pasó (así el equipo vuelve a quedar libre)
    public function liberarVencidos(): int
    {
        $st = $this->connection->prepare(
            "UPDATE prestamo p
             JOIN estado_prestamo ep ON ep.id_estado_prestamo = p.id_estado_prestamo
                SET p.id_estado_prestamo = :cancelado,
                    p.observaciones = LEFT(CONCAT_WS(' | ', p.observaciones, 'Cancelado automáticamente: venció el plazo'), 500)
              WHERE ep.nombre_estado IN ('Solicitado', 'Apartado') AND p.apartado_hasta < NOW()");
        $st->execute([':cancelado' => $this->idEstadoPrestamo('Cancelado')]);
        return $st->rowCount();
    }

    // texto con los equipos de un préstamo (alias p): "Lenovo Slim (1152), Pantalla hd (01001)"
    private const EQUIPOS_TEXTO = "(SELECT GROUP_CONCAT(CONCAT(mp.nombre_modelo_producto, ' (', e.codigo_inventario, ')') SEPARATOR ', ')
            FROM detalle_prestamo dp
            JOIN equipo e ON e.id_equipo = dp.id_equipo
            JOIN modelo_producto mp ON mp.id_modelo_producto = e.id_modelo_producto
            WHERE dp.id_prestamo = p.id_prestamo)";

    // equipos que el estudiante tiene ahora: apartados/solicitados vigentes y entregados sin devolver
    private function contarEquiposAbiertos(int $idEstudiante): int
    {
        $st = $this->connection->prepare(
            "SELECT COUNT(*) FROM detalle_prestamo dp
             JOIN prestamo p ON p.id_prestamo = dp.id_prestamo
             JOIN estado_prestamo ep ON ep.id_estado_prestamo = p.id_estado_prestamo
             WHERE p.id_estudiante = :id
               AND ((ep.nombre_estado IN ('Solicitado', 'Apartado') AND p.apartado_hasta >= NOW())
                    OR ep.nombre_estado IN ('Activo', 'Vencido'))
               AND NOT EXISTS (SELECT 1 FROM detalle_devolucion dd WHERE dd.id_detalle_prestamo = dp.id_detalle_prestamo)");
        $st->execute([':id' => $idEstudiante]);
        return (int) $st->fetchColumn();
    }

    private function tieneVencido(int $idEstudiante): bool
    {
        $st = $this->connection->prepare(
            "SELECT COUNT(*) FROM prestamo p
             JOIN estado_prestamo ep ON ep.id_estado_prestamo = p.id_estado_prestamo
             WHERE p.id_estudiante = :id AND ep.nombre_estado IN ('Activo', 'Vencido')
               AND p.fecha_devolucion_programada < NOW() AND " . self::LINEA_SIN_DEVOLVER);
        $st->execute([':id' => $idEstudiante]);
        return (int) $st->fetchColumn() > 0;
    }

    // trae el préstamo bloqueado para que nadie más lo cambie a la vez
    private function bloquear(int $idPrestamo): array
    {
        $st = $this->connection->prepare(
            "SELECT p.id_prestamo, p.id_estudiante, ep.nombre_estado AS estado
             FROM prestamo p JOIN estado_prestamo ep ON ep.id_estado_prestamo = p.id_estado_prestamo
             WHERE p.id_prestamo = :id FOR UPDATE");
        $st->execute([':id' => $idPrestamo]);
        $p = $st->fetch(PDO::FETCH_ASSOC);
        if (!$p) {
            throw new ReglaApartado("El préstamo no existe");
        }
        return $p;
    }

    private function cambiarEstado(int $idPrestamo, string $estado, ?string $nota = null): void
    {
        $st = $this->connection->prepare(
            "UPDATE prestamo SET id_estado_prestamo = :estado,
                    observaciones = LEFT(CONCAT_WS(' | ', observaciones, :nota), 500)
             WHERE id_prestamo = :id");
        $st->execute([':estado' => $this->idEstadoPrestamo($estado), ':nota' => $nota, ':id' => $idPrestamo]);
    }

    private function idEstadoPrestamo(string $nombre): int
    {
        $st = $this->connection->prepare("SELECT id_estado_prestamo FROM estado_prestamo WHERE nombre_estado = :n");
        $st->execute([':n' => $nombre]);
        $id = $st->fetchColumn();
        if (!$id) {
            throw new RuntimeException("Falta el estado de préstamo '$nombre'. Corre el script 1_flujo_apartado.sql");
        }
        return (int) $id;
    }

    private function idEstadoEquipo(string $nombre): int
    {
        $st = $this->connection->prepare("SELECT id_estado_equipo FROM estado_equipo WHERE nombre_estado = :n");
        $st->execute([':n' => $nombre]);
        $id = $st->fetchColumn();
        if (!$id) {
            throw new RuntimeException("Falta el estado de equipo '$nombre'");
        }
        return (int) $id;
    }

    private function enTransaccion(callable $accion)
    {
        // READ COMMITTED: cada consulta ve lo ultimo confirmado. Con el nivel por defecto, una consulta normal
        // posterior a esperar el bloqueo de un equipo podria no ver el apartado que otro estudiante acaba de confirmar.
        $this->connection->exec("SET TRANSACTION ISOLATION LEVEL READ COMMITTED");
        $this->connection->beginTransaction();
        try {
            $resultado = $accion();
            $this->connection->commit();
            return $resultado;
        } catch (\Throwable $th) {
            $this->connection->rollBack();
            throw $th;
        }
    }
}