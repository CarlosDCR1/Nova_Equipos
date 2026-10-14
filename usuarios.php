<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mantenimiento de Usuarios | Nova Equipos</title>
    <link rel="stylesheet" href="css/sidebar.css">
    <link rel="stylesheet" href="css/empleados.css">
</head>
<body>

<div class="app-shell">

    <!-- SIDEBAR -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <div class="brand-mark">N</div>
            <div class="brand-text">NOVA<br>EQUIPOS</div>
        </div>

        <nav class="nav-group">
            <div class="nav-group-title">Académico y personal</div>
            <ul class="nav-list">
                <li><a class="nav-link" href="jornada.php">Jornada</a></li>
                <li><a class="nav-link" href="facultad.php">Facultad</a></li>
                <li><a class="nav-link" href="carrera.php">Carrera</a></li>
                <li><a class="nav-link active" href="estudiantes.php">Estudiante</a></li>
                <li><a class="nav-link" href="empleados.php">Empleado</a></li>
            </ul>
        </nav>

        <nav class="nav-group">
            <div class="nav-group-title">Seguridad</div>
            <ul class="nav-list">
                <li><a class="nav-link" href="tblrol.php">Rol</a></li>
                <li><a class="nav-link" href="tblmodulo.php">Módulo</a></li>
                <li><a class="nav-link" href="tblaplicacion.php">Aplicación</a></li>
                <li><a class="nav-link active" href="usuarios.php">Usuario</a></li>
                <li><a class="nav-link" href="tblusuariorol.php">Usuario - Rol</a></li>
                <li><a class="nav-link" href="tblrolmoduloaplicacion.php">Rol - Módulo - Aplicación</a></li>
                <li><a class="nav-link" href="tblintentologin.php">Intentos de login</a></li>
                <li><a class="nav-link" href="tblbitacora.php">Bitácora</a></li>
            </ul>
        </nav>

        <nav class="nav-group">
            <div class="nav-group-title">Inventario</div>
            <ul class="nav-list">
                <li><a class="nav-link" href="categoria_producto.php">Categoría de producto</a></li>
                <li><a class="nav-link" href="marca.php">Marca</a></li>
                <li><a class="nav-link" href="estado_equipo.php">Estado de equipo</a></li>
                <li><a class="nav-link" href="modelo_producto.php">Modelo de producto</a></li>
                <li><a class="nav-link" href="equipo.php">Equipo</a></li>
                <li><a class="nav-link" href="inventario_equipo.php">Inventario de equipo</a></li>
                <li><a class="nav-link" href="movimiento_inventario.php">Movimiento de inventario</a></li>
            </ul>
        </nav>

        <nav class="nav-group">
            <div class="nav-group-title">Préstamos</div>
            <ul class="nav-list">
                <li><a class="nav-link" href="estado_prestamo.php">Estado de préstamo</a></li>
                <li><a class="nav-link" href="prestamo.php">Préstamo</a></li>
                <li><a class="nav-link" href="detalle_prestamo.php">Detalle de préstamo</a></li>
            </ul>
        </nav>

        <nav class="nav-group">
            <div class="nav-group-title">Devoluciones</div>
            <ul class="nav-list">
                <li><a class="nav-link" href="tipo_incidente.php">Tipo de incidente</a></li>
                <li><a class="nav-link" href="devolucion.php">Devolución</a></li>
                <li><a class="nav-link" href="detalle_devolucion.php">Detalle de devolución</a></li>
            </ul>
        </nav>
    </aside>

    <!-- CONTENIDO -->
    <div class="main-area">

        <header class="topbar">
            <button class="topbar-menu-btn" id="menuBtn" aria-label="Abrir menú">☰</button>
            <span class="topbar-badge">🧪 Laboratorio de Ingeniería en Sistemas</span>
            <div class="topbar-user">👤</div>
        </header>

        <main class="page-content">
            <h1 class="page-title">Mantenimiento de Usuarios</h1>

            <!-- FORMULARIO -->
            <form id="form-usuario" class="filter-card">
                <input type="hidden" id="id_usuario">

                <div class="filter-grid">
                    <div class="field">
                        <label for="f-empleado">Empleado</label>
                        <select id="f-empleado" name="idEmpleado" required>
                            <option value="">Selecciona un empleado...</option>
                        </select>
                    </div>
                    <div class="field">
                        <label for="f-usuario">Usuario</label>
                        <input id="f-usuario" name="nombreUsuario" maxlength="50" required>
                    </div>
                    <div class="field">
                        <label for="f-password">Contraseña</label>
                        <input id="f-password" name="contrasenaUsuario" type="password" autocomplete="new-password">
                        <small id="pass-hint" style="display:none; color: var(--muted); font-size: 0.75rem;">Déjalo en blanco para no cambiar la contraseña actual.</small>
                    </div>
                    <div class="field">
                        <label for="f-estado">Estado</label>
                        <select id="f-estado" name="is_active">
                            <option value="1">Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                    </div>
                </div>

                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary" id="btn-guardar">+ Agregar</button>
                    <button type="button" class="btn btn-light" id="btn-limpiar">Limpiar</button>
                </div>
            </form>

            <!-- TABLA -->
            <div class="table-card">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Id</th>
                            <th>Empleado</th>
                            <th>Usuario</th>
                            <th>Estado</th>
                            <th>Último acceso</th>
                            <th>Intentos fallidos</th>
                            <th>Creado</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="tabla-usuarios">
                        <tr><td colspan="8" class="empty-state">Cargando usuarios...</td></tr>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</div>

<script src="js/usuarios.js"></script>
</body>
</html>
