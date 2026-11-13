<?php


class ReglaApartado extends Exception
{
}

class Apartado
{
    const HORAS_APARTADO   = 24;  
    const HORAS_SOLICITUD  = 48;  
    const MAX_EQUIPOS      = 3;      
    const MONTO_APROBACION = 8000;
    const DIAS_PRESTAMO    = 7;
    const ESTADO_EQUIPO_DISPONIBLE = 1;
    const ESTADO_EQUIPO_RESERVADO  = 8;

    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

   
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

    
    public static function prestado(): string
    {
        return self::enPrestamo(['Activo', 'Vencido']);
    }

    
    public static function ocupado(): string
    {
        return self::enPrestamo(['Solicitado', 'Apartado', 'Activo', 'Vencido']);
    }

   
    private const LINEA_SIN_DEVOLVER = "EXISTS (
            SELECT 1 FROM detalle_prestamo dp
            WHERE dp.id_prestamo = p.id_prestamo
              AND NOT EXISTS (SELECT 1 FROM detalle_devolucion dd WHERE dd.id_detalle_prestamo = dp.id_detalle_prestamo))";

    public function apartar(int $idEstudiante, int $idUsuario, array $idEquipos): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $idEquipos), fn ($i) => $i > 0)));
        sort($ids);
        if (!$ids) {
            throw new ReglaApartado("Selecciona al menos un equipo");
        }

        return $this->enTransaccion(fn () => $this->apartarEquipos($idEstudiante, $idUsuario, $ids));
    }

    
    private function apartarEquipos(int $idEstudiante, int $idUsuario, array $ids): array
    {
        $this->liberarVencidos();

        
        $st = $this->connection->prepare(
            "SELECT id_carrera, estado_estudiante FROM estudiante WHERE id_estudiante = :id FOR UPDATE");
        $st->execute([':id' => $idEstudiante]);
        $est = $st->fetch(PDO::FETCH_ASSOC);
        if (!$est || $est['estado_estudiante'] !== 'ACTIVO') {
            throw new ReglaApartado("Tu cuenta de estudiante no está activa");
        }

        
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
    }

  
    private function crearPrestamo(int $idEstudiante, int $idUsuario, array $idEquipos, string $estado, int $horas): array
    {
        $h = (int) $horas;
        $d = (int) self::DIAS_PRESTAMO;

       
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

        $this->reservarEquipos($idEquipos);

        $st = $this->connection->prepare("SELECT apartado_hasta FROM prestamo WHERE id_prestamo = :id");
        $st->execute([':id' => $idPrestamo]);

        return [
            'id_prestamo'         => $idPrestamo,
            'estado'              => $estado,
            'requiere_aprobacion' => $estado === 'Solicitado',
            'apartado_hasta'      => $st->fetchColumn(),
        ];
    }

    
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
            $this->liberarEquiposDePrestamo($idPrestamo); 
            return ['id_prestamo' => $idPrestamo, 'estado' => 'Cancelado'];
        });
    }

   
    public function disponibles(int $idEstudiante): array
    {
        $this->liberarVencidos();

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


    public function apartarPorModelo(int $idEstudiante, int $idUsuario, array $pedidos): array
    {
        $cantidades = []; 
        foreach ($pedidos as $p) {
            $idModelo = (int) ($p['id_modelo_producto'] ?? 0);
            $cantidad = (int) ($p['cantidad'] ?? 0);
            $apr = !empty($p['requiere_aprobacion']) ? 1 : 0;
            if ($idModelo > 0 && $cantidad > 0) {
                $k = "$idModelo|$apr";
                $cantidades[$k] = ['modelo' => $idModelo, 'apr' => $apr, 'cantidad' => ($cantidades[$k]['cantidad'] ?? 0) + $cantidad];
            }
        }
        if (!$cantidades) {
            throw new ReglaApartado("Selecciona al menos un equipo");
        }
        if (array_sum(array_column($cantidades, 'cantidad')) > self::MAX_EQUIPOS) {
            throw new ReglaApartado("Puedes apartar hasta " . self::MAX_EQUIPOS . " equipos a la vez");
        }

        return $this->enTransaccion(function () use ($idEstudiante, $idUsuario, $cantidades) {
            $this->liberarVencidos();

            $st = $this->connection->prepare("SELECT id_carrera FROM estudiante WHERE id_estudiante = :id FOR UPDATE");
            $st->execute([':id' => $idEstudiante]);
            $carrera = $st->fetchColumn();
            if (!$carrera) {
                throw new ReglaApartado("No se encontró tu cuenta de estudiante");
            }

            $ids = [];
            foreach ($cantidades as $c) {
                $n = (int) $c['cantidad'];
                $idModelo = (int) $c['modelo'];
                $apr = (int) $c['apr'];
                $st = $this->connection->prepare(
                    "SELECT e.id_equipo
                     FROM equipo e
                     JOIN estado_equipo ee ON ee.id_estado_equipo = e.id_estado_equipo AND ee.permite_prestamo = 1
                     JOIN modelo_producto mp ON mp.id_modelo_producto = e.id_modelo_producto AND mp.is_active = 1
                     JOIN categoria_producto cp ON cp.id_categoria_producto = mp.id_categoria_producto AND cp.is_active = 1
                     JOIN carrera_categoria cc ON cc.id_categoria_producto = cp.id_categoria_producto AND cc.id_carrera = :carrera
                     WHERE e.id_modelo_producto = :modelo AND NOT " . self::ocupado() . "
                       AND (e.valor_adquisicion IS NOT NULL AND e.valor_adquisicion > " . (float) self::MONTO_APROBACION . ") = $apr
                     ORDER BY RAND()
                     LIMIT $n");
                $st->execute([':carrera' => $carrera, ':modelo' => $idModelo]);
                $libres = $st->fetchAll(PDO::FETCH_COLUMN);

                if (count($libres) < $n) {
                    $st = $this->connection->prepare("SELECT nombre_modelo_producto FROM modelo_producto WHERE id_modelo_producto = :id");
                    $st->execute([':id' => $idModelo]);
                    $nombre = $st->fetchColumn();
                    if ($nombre === false) {
                        throw new ReglaApartado("El modelo seleccionado no existe");
                    }
                    $tipo = $apr ? "que requieren aprobación" : "de entrega inmediata";
                    throw new ReglaApartado("Solo quedan " . count($libres) . " unidades de $nombre $tipo. Actualiza la lista e inténtalo de nuevo");
                }
                foreach ($libres as $idEquipo) {
                    $ids[] = (int) $idEquipo;
                }
            }

            sort($ids);
            return $this->apartarEquipos($idEstudiante, $idUsuario, $ids);
        });
    }

   
    public function modelosDisponibles(int $idEstudiante): array
    {
        $this->liberarVencidos();

        $st = $this->connection->prepare(
            "SELECT mp.id_modelo_producto, mp.nombre_modelo_producto AS modelo,
                    ma.nombre_marca AS marca, mp.descripcion_modelo_producto AS descripcion,
                    cp.id_categoria_producto, cp.nombre_categoria_producto AS categoria,
                    COUNT(*) AS disponibles,
                    COALESCE(SUM(e.valor_adquisicion IS NOT NULL AND e.valor_adquisicion > " . (float) self::MONTO_APROBACION . "), 0) AS con_aprobacion
             FROM estudiante s
             JOIN carrera_categoria cc ON cc.id_carrera = s.id_carrera
             JOIN categoria_producto cp ON cp.id_categoria_producto = cc.id_categoria_producto AND cp.is_active = 1
             JOIN modelo_producto mp ON mp.id_categoria_producto = cp.id_categoria_producto AND mp.is_active = 1
             JOIN marca ma ON ma.id_marca = mp.id_marca
             JOIN equipo e ON e.id_modelo_producto = mp.id_modelo_producto
             JOIN estado_equipo ee ON ee.id_estado_equipo = e.id_estado_equipo AND ee.permite_prestamo = 1
             WHERE s.id_estudiante = :id AND NOT " . self::ocupado() . "
             GROUP BY mp.id_modelo_producto, mp.nombre_modelo_producto, ma.nombre_marca, mp.descripcion_modelo_producto,
                      cp.id_categoria_producto, cp.nombre_categoria_producto
             ORDER BY cp.nombre_categoria_producto, mp.nombre_modelo_producto");
        $st->execute([':id' => $idEstudiante]);
        $filas = $st->fetchAll(PDO::FETCH_ASSOC);
        foreach ($filas as &$f) {
            $f['id_modelo_producto'] = (int) $f['id_modelo_producto'];
            $f['id_categoria_producto'] = (int) $f['id_categoria_producto'];
            $f['disponibles'] = (int) $f['disponibles'];
            $f['con_aprobacion'] = (int) $f['con_aprobacion'];
        }
        return $filas;
    }

    public function abiertos(int $idEstudiante): int
    {
        $this->liberarVencidos();
        return $this->contarEquiposAbiertos($idEstudiante);
    }


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
                    "SELECT e.codigo_inventario, e.id_estado_equipo, ee.permite_prestamo
                     FROM equipo e JOIN estado_equipo ee ON ee.id_estado_equipo = e.id_estado_equipo
                     WHERE e.id_equipo = :id FOR UPDATE");
                $st->execute([':id' => $idEquipo]);
                $eq = $st->fetch(PDO::FETCH_ASSOC);
                $reservado = $eq && (int) $eq['id_estado_equipo'] === self::ESTADO_EQUIPO_RESERVADO;
                if (!$eq || (!$reservado && !$eq['permite_prestamo'])) {
                    throw new ReglaApartado("El equipo " . ($eq['codigo_inventario'] ?? $idEquipo) . " ya no está disponible. Rechaza el préstamo");
                }
            }

            $d = (int) self::DIAS_PRESTAMO;
            $st = $this->connection->prepare(
                "UPDATE prestamo SET id_estado_prestamo = :estado, id_usuario_entrega = :usr, fecha_entrega = NOW(),
                        fecha_devolucion_programada = DATE_ADD(NOW(), INTERVAL $d DAY)
                 WHERE id_prestamo = :id");
            $st->execute([':estado' => $this->idEstadoPrestamo('Activo'), ':usr' => $idUsuario, ':id' => $idPrestamo]);

            
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

        public function liberarVencidos(): int
    {
        $st = $this->connection->prepare(
            "UPDATE equipo e
             JOIN detalle_prestamo dp ON dp.id_equipo = e.id_equipo
             JOIN prestamo p ON p.id_prestamo = dp.id_prestamo
             JOIN estado_prestamo ep ON ep.id_estado_prestamo = p.id_estado_prestamo
                SET e.id_estado_equipo = :disponible
              WHERE e.id_estado_equipo = :reservado
                AND ep.nombre_estado IN ('Solicitado', 'Apartado') AND p.apartado_hasta < NOW()");
        $st->execute([
            ':disponible' => self::ESTADO_EQUIPO_DISPONIBLE,
            ':reservado'  => self::ESTADO_EQUIPO_RESERVADO
        ]);

        $st = $this->connection->prepare(
            "UPDATE prestamo p
             JOIN estado_prestamo ep ON ep.id_estado_prestamo = p.id_estado_prestamo
                SET p.id_estado_prestamo = :cancelado,
                    p.observaciones = LEFT(CONCAT_WS(' | ', p.observaciones, 'Cancelado automáticamente: venció el plazo'), 500)
              WHERE ep.nombre_estado IN ('Solicitado', 'Apartado') AND p.apartado_hasta < NOW()");
        $st->execute([':cancelado' => $this->idEstadoPrestamo('Cancelado')]);
        return $st->rowCount();
    }


    private const EQUIPOS_TEXTO = "(SELECT GROUP_CONCAT(CONCAT(mp.nombre_modelo_producto, ' (', e.codigo_inventario, ')') SEPARATOR ', ')
            FROM detalle_prestamo dp
            JOIN equipo e ON e.id_equipo = dp.id_equipo
            JOIN modelo_producto mp ON mp.id_modelo_producto = e.id_modelo_producto
            WHERE dp.id_prestamo = p.id_prestamo)";

    private function reservarEquipos(array $idEquipos): void
    {
        $st = $this->connection->prepare("UPDATE equipo SET id_estado_equipo = :reservado WHERE id_equipo = :id");
        foreach ($idEquipos as $idEquipo) {
            $st->execute([':reservado' => self::ESTADO_EQUIPO_RESERVADO, ':id' => $idEquipo]);
        }
    }

    // Devuelve a "Disponible" los equipos de un préstamo que sigan "Reservados" (al cancelar o rechazar).
    // Si un equipo ya está en otro estado, no se toca.
    private function liberarEquiposDePrestamo(int $idPrestamo): void
    {
        $st = $this->connection->prepare(
            "UPDATE equipo e
             JOIN detalle_prestamo dp ON dp.id_equipo = e.id_equipo
                SET e.id_estado_equipo = :disponible
              WHERE dp.id_prestamo = :id AND e.id_estado_equipo = :reservado");
        $st->execute([
            ':disponible' => self::ESTADO_EQUIPO_DISPONIBLE,
            ':id'         => $idPrestamo,
            ':reservado'  => self::ESTADO_EQUIPO_RESERVADO
        ]);
    }
    
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