<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mantenimiento de Empleados | Nova Equipos</title>
    <link rel="stylesheet" href="css/sidebar.css">
    <link rel="stylesheet" href="css/empleados.css">
</head>
<body>

<div class="app-shell">


    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <div class="brand-mark">N</div>
        </div>

        <nav class="nav-group">
            <div class="nav-group-title">Académico y personal</div>
            <ul class="nav-list">
                <li><a class="nav-link" href="jornada.php">Jornada</a></li>
                <li><a class="nav-link" href="facultad.php">Facultad</a></li>
                <li><a class="nav-link" href="carrera.php">Carrera</a></li>
                <li><a class="nav-link active" href="estudiantes.php">Estudiante</a></li>
                <li><a class="nav-link active" href="empleados.php">Empleado</a></li>
            </ul>
        </nav>

        <nav class="nav-group">
            <div class="nav-group-title">Seguridad</div>
            <ul class="nav-list">
                <li><a class="nav-link" href="tblrol.php">Rol</a></li>
                <li><a class="nav-link" href="tblmodulo.php">Módulo</a></li>
                <li><a class="nav-link" href="tblaplicacion.php">Aplicación</a></li>
                <li><a class="nav-link" href="usuarios.php">Usuario</a></li>
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
            <span class="topbar-badge"></span>
            <div class="topbar-user"></div>
        </header>

        <main class="page-content">
            <h1 class="page-title">Mantenimiento de Empleados</h1>

            <!-- FILTROS + FORMULARIO -->
            <form id="form-empleado" class="filter-card">
                <input type="hidden" id="id_empleado">

                <div class="filter-grid">
                    <div class="field">
                        <label for="f-nombre">Nombre</label>
                        <input id="f-nombre" name="nombre" required>
                    </div>
                    <div class="field">
                        <label for="f-apellidos">Apellidos</label>
                        <input id="f-apellidos" name="apellidos">
                    </div>
                    <div class="field">
                        <label for="f-correo">Correo institucional</label>
                        <input id="f-correo" name="correo_institucional" type="email" required>
                    </div>
                    <div class="field">
                        <label for="f-telefono">Teléfono</label>
                        <input id="f-telefono" name="telefono_empleado" maxlength="9">
                    </div>

                    <div class="field">
                        <label for="f-dpi">DPI</label>
                        <input id="f-dpi" name="dpi" maxlength="20">
                    </div>
                    <div class="field">
                        <label for="f-estado-civil">Estado civil</label>
                        <input id="f-estado-civil" name="estado_civil">
                    </div>
                    <div class="field">
                        <label for="f-fecha-nac">Fecha de nacimiento</label>
                        <input id="f-fecha-nac" name="fecha_nacimiento" type="date">
                    </div>
                    <div class="field">
                        <label for="f-estado">Estado</label>
                        <select id="f-estado" name="estado_empleado">
                            <option value="ACTIVO">Activo</option>
                            <option value="INACTIVO">Inactivo</option>
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
                            <th>Nombre</th>
                            <th>Apellidos</th>
                            <th>Correo</th>
                            <th>Teléfono</th>
                            <th>Estado</th>
                            <th>Fecha creación</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="tabla-empleados">
                        <tr><td colspan="8" class="empty-state">Cargando empleados...</td></tr>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</div>

<script src="js/empleados.js"></script>
</body>
</html>
