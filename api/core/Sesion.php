<?php
require_once(__DIR__ . "/Configuration.php");
require_once(__DIR__ . "/Response.php");

class Sesion
{
    // 30 minutos sin actividad (en segundos)
    const INACTIVIDAD = 1800;

    static function activa(): bool
    {
        if (empty($_SESSION['idUsuario'])) {
            return false;
        }
        if (time() - ($_SESSION['ultimaActividad'] ?? 0) > self::INACTIVIDAD) {
            self::destruir();
            return false;
        }
        $_SESSION['ultimaActividad'] = time();
        return true;
    }

    // para las páginas si no hay sesión, manda al login
    static function requerirPagina(): void
    {
        if (!self::activa()) {
            header("Location: login.php");
            exit;
        }
    }

    // para los endpoints del controlador si no hay sesión, responde 401
    static function requerirApi(): void
    {
        if (!self::activa()) {
            Response::error("Sesión no iniciada o expirada", 401, 401);
        }
    }

    // para login.php: si ya inició sesión, no vuelve a ver el login
    static function redirigirSiAutenticado(): void
    {
        if (self::activa()) {
            header("Location: index.php");
            exit;
        }
    }

    // ---------- PERMISOS ----------

    static function esEstudiante(): bool
    {
        return ($_SESSION['tipoUsuario'] ?? '') === 'estudiante';
    }

    // para mostrar o ocultar el grupo del menú si tiene permisos a algunas de esas aplicaciones 
    static function puedeVerModulo(string $modulo): bool
    {
        return !empty($_SESSION['permisos'][$modulo]);
    }

    // aqui gestione los derechos por botones del CRUD aparecen o desparecen segun el nivel de permiso
    static function puede(string $modulo, string $aplicacion, string $derecho = 'ver'): bool
    {
        $p = $_SESSION['permisos'][$modulo][$aplicacion] ?? null;
        if ($p === null) {
            return false;
        }
        return $derecho === 'ver' ? true : !empty($p[$derecho]);
    }

    // para los endpoints de la capa controladora 401 si no hay sesión, 403 si no tiene el permiso
    static function requerirPermiso(string $modulo, string $aplicacion, string $derecho = 'ver'): void
    {
        self::requerirApi();
        if (!self::puede($modulo, $aplicacion, $derecho)) {
            Response::error("No tienes permiso para realizar esta acción", 403, 403);
        }
    }

    static function destruir(): void
    {
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }
}