<?php
require_once(__DIR__ . "/Configuration.php");
require_once(__DIR__ . "/Response.php");

class Sesion
{
    //Para que se cierre la sesion cuando se pasa 30 minutos sin actividad 
    const INACTIVIDAD = 1300;

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

    // para los endpoints api/admin/*: si no hay sesión, responde 401
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