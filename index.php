<?php
require_once "api/core/Sesion.php";
Sesion::requerirPagina();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nova Equipos</title>
    <link rel="stylesheet" href="css/panel_navegador.css?v=<?= filemtime('css/panel_navegador.css') ?>">
    <link rel="stylesheet" href="css/cruds.css?v=<?= filemtime('css/cruds.css') ?>">
    <link rel="stylesheet" href="css/dashboard.css?v=<?= filemtime('css/dashboard.css') ?>">
</head>
<body>

<div class="app-shell">
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <div class="brand-mark" id="brandLogo" role="button" tabindex="0" aria-label="Logo Nova Equipos"></div>
        </div>

        <?php if (Sesion::puedeVerModulo('Academico y personal')): ?>
        <nav class="nav-group">
            <div class="nav-group-title">Académico y personal</div>
            <ul class="nav-list">
                <li><a class="nav-link" href="#carrera">Carrera</a></li>
                <li><a class="nav-link" href="#estudiantes">Estudiante</a></li>
                <li><a class="nav-link" href="#empleados">Empleado</a></li>
            </ul>
        </nav>
        <?php endif; ?>

        <?php if (Sesion::puedeVerModulo('Seguridad')): ?>
        <nav class="nav-group">
            <div class="nav-group-title">Seguridad</div>
            <ul class="nav-list">
                <li><a class="nav-link" href="#tblmodulo">Módulo</a></li>
                <li><a class="nav-link" href="#tblaplicacion">Aplicación</a></li>
                <li><a class="nav-link" href="#usuarios">Usuario</a></li>
                <li><a class="nav-link" href="#tblintentologin">Intentos de login</a></li>
                <li><a class="nav-link" href="#tblbitacora">Bitácora</a></li>
            </ul>
        </nav>
        <?php endif; ?>

        <?php if (Sesion::puedeVerModulo('Inventario')): ?>
        <nav class="nav-group">
            <div class="nav-group-title">Inventario</div>
            <ul class="nav-list">
                <li><a class="nav-link" href="#categoria_producto">Categoría de producto</a></li>
                <li><a class="nav-link" href="#marca">Marca</a></li>
                <li><a class="nav-link" href="#estado_equipo">Estado de equipo</a></li>
                <li><a class="nav-link" href="#modelo_producto">Modelo de producto</a></li>
                <li><a class="nav-link" href="#equipo">Equipo</a></li>
                <li><a class="nav-link" href="#inventario_equipo">Inventario de equipo</a></li>
                <li><a class="nav-link" href="#movimiento_inventario">Movimiento de inventario</a></li>
            </ul>
        </nav>
        <?php endif; ?>

        <?php if (Sesion::puedeVerModulo('Prestamos')): ?>
        <nav class="nav-group">
            <div class="nav-group-title">Préstamos</div>
            <ul class="nav-list">
                <li><a class="nav-link" href="#estado_prestamo">Estado de préstamo</a></li>
                <li><a class="nav-link" href="#prestamo">Préstamo</a></li>
                <li><a class="nav-link" href="#detalle_prestamo">Detalle de préstamo</a></li>
            </ul>
        </nav>
        <?php endif; ?>

        <?php if (Sesion::puedeVerModulo('Devoluciones')): ?>
        <nav class="nav-group">
            <div class="nav-group-title">Devoluciones</div>
            <ul class="nav-list">
                <li><a class="nav-link" href="#tipo_incidente">Tipo de incidente</a></li>
                <li><a class="nav-link" href="#devolucion">Devolución</a></li>
                <li><a class="nav-link" href="#detalle_devolucion">Detalle de devolución</a></li>
            </ul>
        </nav>
        <?php endif; ?>
    </aside>

    <div class="main-area">
        <header class="topbar">
            <button class="topbar-menu-btn" id="menuBtn" aria-label="Abrir menú">☰</button>
            <!--<span class="topbar-badge">Nova Equipos Sistemas</span> -->
            <a href="#inicio"><img src="./Resources/pagina-de-inicio.ico" alt="Icono" width="30px"></a>
            <span class="usuario-sesion">
                <?= htmlspecialchars($_SESSION['nombreMostrar'] ?? '') ?> · <a href="logout.php">Cerrar sesión</a>
            </span>
        </header>

        <main class="page-content" id="contenido">
            <p class="empty-state">Selecciona una opción del menú.</p>
        </main>
    </div>
</div>

<script src="js/cruds.js?v=<?= filemtime('js/cruds.js') ?>"></script>
<script src="js/panel_navegador.js?v=<?= filemtime('js/panel_navegador.js') ?>"></script>
</body>
</html>