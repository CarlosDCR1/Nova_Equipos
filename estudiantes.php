<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mantenimiento de Estudiantes | Nova Equipos</title>
    <link rel="stylesheet" href="css/sidebar.css">
    <link rel="stylesheet" href="css/empleados.css">
</head>
<body>

<div class="app-shell">

    <!-- SIDEBAR -->
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
                <li><a class="nav-link" href="empleados.php">Empleado</a></li>
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
            <span class="topbar-badge">Nova Equipos Sistemas </span>
        </header>

        <main class="page-content">
            <h1 class="page-title">Mantenimiento de Estudiantes</h1>

            <!-- BUSCADOR -->
            <div class="filter-card" id="buscador">
                <div class="filter-grid">
                    <div class="field">
                        <label for="b-campo">Buscar por</label>
                        <select id="b-campo">
                            <option value="nombre">Nombre</option>
                            <option value="apellidos">Apellidos</option>
                            <option value="carnet">Carnet</option>
                            <option value="numero">Número</option>
                            <option value="correo">Correo institucional</option>
                        </select>
                    </div>
                    <div class="field">
                        <label for="b-texto">Texto a buscar</label>
                        <input id="b-texto" type="search" placeholder="Escribe para buscar...">
                    </div>
                    <div class="field">
                        <label for="b-carrera">Carrera</label>
                        <select id="b-carrera">
                            <option value="">Todas</option>
                        </select>
                    </div>
                    <div class="field">
                        <label for="b-estado">Estado</label>
                        <select id="b-estado">
                            <option value="">Todos</option>
                            <option value="ACTIVO">Activo</option>
                            <option value="INACTIVO">Inactivo</option>
                        </select>
                    </div>
                </div>
                <div class="filter-actions">
                    <button type="button" class="btn btn-primary" id="btn-buscar">Buscar</button>
                    <button type="button" class="btn btn-light" id="btn-limpiar-busqueda">Limpiar búsqueda</button>
                </div>
            </div>


            <div class="table-card">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Id</th>
                            <th>Carrera</th>
                            <th>Nombre</th>
                            <th>Apellidos</th>
                            <th>Carnet</th>
                            <th>Número</th>
                            <th>Correo institucional</th>
                            <th>Estado</th>
                            <th>Creado</th>
                        </tr>
                    </thead>
                    <tbody id="tabla-estudiantes">
                        <tr><td colspan="9" class="empty-state">Cargando estudiantes...</td></tr>
                    </tbody>
                </table>
            </div>

            
            <form id="form-estudiante" class="filter-card">
                <input type="hidden" id="id_estudiante">

                <div class="filter-grid">
                    <div class="field">
                        <label for="f-carrera">Carrera</label>
                        <select id="f-carrera" name="id_carrera" required>
                            <option value="">Selecciona una carrera...</option>
                        </select>
                    </div>
                    <div class="field">
                        <label for="f-nombre">Nombre</label>
                        <input id="f-nombre" name="nombre_estudiante" maxlength="100" required>
                    </div>
                    <div class="field">
                        <label for="f-apellidos">Apellidos</label>
                        <input id="f-apellidos" name="apellidos_estudiante" maxlength="100">
                    </div>
                    <div class="field">
                        <label for="f-carnet">Carnet</label>
                        <input id="f-carnet" name="carnet_estudiante" maxlength="30" required>
                    </div>
                    <div class="field">
                        <label for="f-numero">Número</label>
                        <input id="f-numero" name="numero_estudiante" maxlength="8" required>
                    </div>
                    <div class="field">
                        <label for="f-correo">Correo institucional</label>
                        <input id="f-correo" name="correo_institucional" type="email" maxlength="150" required>
                    </div>
                    <div class="field">
                        <label for="f-estado">Estado</label>
                        <select id="f-estado" name="estado_estudiante">
                            <option value="ACTIVO">Activo</option>
                            <option value="INACTIVO">Inactivo</option>
                        </select>
                    </div>
                </div>

                <div class="filter-actions">
                    <button type="button" class="btn btn-primary" id="btn-agregar">+ Agregar</button>
                    <button type="button" class="btn btn-primary" id="btn-editar" disabled>Editar</button>
                    <button type="button" class="btn btn-danger" id="btn-eliminar" disabled>Eliminar</button>
                    <button type="button" class="btn btn-light" id="btn-limpiar">Limpiar</button>
                </div>
            </form>
        </main>
    </div>
</div>

<script src="js/estudiantes.js"></script>
</body>
</html>