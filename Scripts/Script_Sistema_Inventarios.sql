
CREATE DATABASE IF NOT EXISTS laboratorio_inventario CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE laboratorio_inventario;
SET default_storage_engine = InnoDB;

CREATE TABLE jornada (
    id_jornada INT NOT NULL AUTO_INCREMENT,
    nombre_jornada VARCHAR(100) NOT NULL,
    horario_jornada VARCHAR(100),
    PRIMARY KEY (id_jornada),
    UNIQUE KEY uq_jornada_nombre (nombre_jornada)
);

CREATE TABLE facultad (
    id_facultad INT NOT NULL AUTO_INCREMENT,
    id_jornada INT NOT NULL,
    nombre_facultad VARCHAR(100) NOT NULL,
    PRIMARY KEY (id_facultad),
    KEY idx_facultad_jornada (id_jornada),
    UNIQUE KEY uq_facultad_nombre (nombre_facultad),
    CONSTRAINT fk_facultad_jornada FOREIGN KEY (id_jornada) REFERENCES jornada(id_jornada) ON DELETE RESTRICT ON UPDATE CASCADE
);

CREATE TABLE carrera (
    id_carrera INT NOT NULL AUTO_INCREMENT,
    id_facultad INT NOT NULL,
    nombre_carrera VARCHAR(100) NOT NULL,
    PRIMARY KEY (id_carrera),
    KEY idx_carrera_facultad (id_facultad),
    UNIQUE KEY uq_carrera_nombre_facultad (id_facultad, nombre_carrera),
    CONSTRAINT fk_carrera_facultad FOREIGN KEY (id_facultad) REFERENCES facultad(id_facultad) ON DELETE RESTRICT ON UPDATE CASCADE
);

CREATE TABLE estudiante (
    id_estudiante INT NOT NULL AUTO_INCREMENT,
    id_carrera INT NOT NULL,
    nombre_estudiante VARCHAR(100) NOT NULL,
    apellidos_estudiante VARCHAR(100),
    carnet_estudiante VARCHAR(30) NOT NULL,
    numero_estudiante VARCHAR(8) NOT NULL,
    correo_institucional VARCHAR(150) NOT NULL,
    estado_estudiante ENUM('ACTIVO','INACTIVO') NOT NULL DEFAULT 'ACTIVO',
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_estudiante),
    KEY idx_estudiante_carrera (id_carrera),
    UNIQUE KEY uq_estudiante_carnet (carnet_estudiante),
    UNIQUE KEY uq_estudiante_correo (correo_institucional),
    UNIQUE KEY uq_estudiante_telefono (numero_estudiante),
    CONSTRAINT fk_estudiante_carrera FOREIGN KEY (id_carrera) REFERENCES carrera(id_carrera) ON DELETE RESTRICT ON UPDATE CASCADE
);

CREATE TABLE empleado (
    id_empleado INT NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    apellidos VARCHAR(100),
    fecha_nacimiento DATE,
    telefono_empleado VARCHAR(9),
    dpi VARCHAR(20),
    estado_civil VARCHAR(30),
    correo_institucional VARCHAR(150) NOT NULL,
    estado_empleado ENUM('ACTIVO','INACTIVO') NOT NULL DEFAULT 'ACTIVO',
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_empleado),
    UNIQUE KEY uq_empleado_dpi (dpi),
    UNIQUE KEY uq_empleado_correo (correo_institucional)
);

CREATE TABLE tblrol (
    idRol INT NOT NULL AUTO_INCREMENT,
    nombreRol VARCHAR(100) NOT NULL,
    descripcionRol VARCHAR(255) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (idRol),
    UNIQUE KEY uqNombreRol (nombreRol)
);

CREATE TABLE tblmodulo (
    idModulo INT NOT NULL AUTO_INCREMENT,
    nombreModulo VARCHAR(100) NOT NULL,
    descripcionModulo VARCHAR(255) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (idModulo),
    UNIQUE KEY uqNombreModulo (nombreModulo)
);

CREATE TABLE tblaplicacion (
    idAplicacion INT NOT NULL AUTO_INCREMENT,
    idModulo INT NOT NULL,
    nombreAplicacion VARCHAR(100) NOT NULL,
    descripcionAplicacion VARCHAR(255) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (idAplicacion),
    KEY idxAplicacionIdModulo (idModulo),
    UNIQUE KEY uqAplicacionModulo (idModulo, nombreAplicacion),
    UNIQUE KEY uqAplicacionIdModulo (idAplicacion, idModulo),
    CONSTRAINT fk_aplicacion_modulo FOREIGN KEY (idModulo) REFERENCES tblmodulo(idModulo) ON DELETE RESTRICT ON UPDATE CASCADE
);

CREATE TABLE tblusuario (
    idUsuario INT NOT NULL AUTO_INCREMENT,
    idEmpleado INT NOT NULL,
    nombreUsuario VARCHAR(50) NOT NULL,
    contrasenaUsuario VARCHAR(255) NOT NULL,
    ultimoAccesoUsuario DATETIME NULL,
    intentosFallidosUsuario INT NOT NULL DEFAULT 0,
    fechaBloqueoUsuario DATETIME NULL,
    fechaUltimaContrasena DATETIME NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (idUsuario),
    UNIQUE KEY uqIdEmpleadoUsuario (idEmpleado),
    UNIQUE KEY uqUsuarioUsuario (nombreUsuario),
    CONSTRAINT fk_usuario_empleado FOREIGN KEY (idEmpleado) REFERENCES empleado(id_empleado) ON DELETE RESTRICT ON UPDATE CASCADE
);

CREATE TABLE tblusuariorol (
    idUsuario INT NOT NULL,
    idRol INT NOT NULL,
    fechaAsignacionUsuarioRol DATE NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (idUsuario, idRol),
    KEY idxUsuarioRolIdRol (idRol),
    CONSTRAINT fk_usuariorol_usuario FOREIGN KEY (idUsuario) REFERENCES tblusuario(idUsuario) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_usuariorol_rol FOREIGN KEY (idRol) REFERENCES tblrol(idRol) ON DELETE RESTRICT ON UPDATE CASCADE
);

CREATE TABLE tblrolmoduloaplicacion (
    idRol INT NOT NULL,
    idModulo INT NOT NULL,
    idAplicacion INT NOT NULL,
    derInsertarRolModuloAplicacion TINYINT(1) NOT NULL DEFAULT 0,
    derEditarRolModuloAplicacion TINYINT(1) NOT NULL DEFAULT 0,
    derEliminarRolModuloAplicacion TINYINT(1) NOT NULL DEFAULT 0,
    derImprimirRolModuloAplicacion TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (idRol, idModulo, idAplicacion),
    KEY idxRolModAplIdModulo (idModulo),
    KEY idxRolModAplIdAplicacion (idAplicacion),
    CONSTRAINT fk_rolmoduloaplicacion_rol FOREIGN KEY (idRol) REFERENCES tblrol(idRol) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_rolmoduloaplicacion_modulo FOREIGN KEY (idModulo) REFERENCES tblmodulo(idModulo) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_rolmoduloaplicacion_aplicacion FOREIGN KEY (idAplicacion) REFERENCES tblaplicacion(idAplicacion) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_rolmoduloaplicacion_modulo_aplicacion FOREIGN KEY (idAplicacion, idModulo) REFERENCES tblaplicacion(idAplicacion, idModulo)
);

CREATE TABLE tblintentologin (
    idIntentoLogin BIGINT NOT NULL AUTO_INCREMENT,
    idUsuario INT NULL,
    nombreUsuarioIngresado VARCHAR(50) NOT NULL,
    fechaIntento DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    exitoso TINYINT(1) NOT NULL DEFAULT 0,
    ipOrigen VARCHAR(45),
    userAgent VARCHAR(500),
    motivo VARCHAR(150),
    PRIMARY KEY (idIntentoLogin),
    KEY idxIntentoUsuario (idUsuario),
    KEY idxIntentoFecha (fechaIntento),
    KEY idxIntentoUsuarioFecha (nombreUsuarioIngresado, fechaIntento),
    CONSTRAINT fk_intento_login_usuario FOREIGN KEY (idUsuario) REFERENCES tblusuario(idUsuario) ON DELETE SET NULL ON UPDATE CASCADE
);

CREATE TABLE tblbitacora (
    idBitacora BIGINT NOT NULL AUTO_INCREMENT,
    idUsuario INT NULL,
    modulo VARCHAR(100),
    aplicacion VARCHAR(100),
    accion VARCHAR(50) NOT NULL,
    tablaAfectada VARCHAR(100),
    idRegistro BIGINT NULL,
    descripcion VARCHAR(500),
    fechaEvento DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ipOrigen VARCHAR(45),
    PRIMARY KEY (idBitacora),
    KEY idxBitacoraUsuario (idUsuario),
    KEY idxBitacoraFecha (fechaEvento),
    KEY idxBitacoraAccion (accion),
    CONSTRAINT fk_bitacora_usuario FOREIGN KEY (idUsuario) REFERENCES tblusuario(idUsuario) ON DELETE SET NULL ON UPDATE CASCADE
);

/* ===================== INVENTARIO ===================== */

CREATE TABLE categoria_producto (
    id_categoria_producto INT NOT NULL AUTO_INCREMENT,
    nombre_categoria_producto VARCHAR(80) NOT NULL,
    descripcion_categoria_producto VARCHAR(500),
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id_categoria_producto),
    UNIQUE KEY uq_categoria_producto (nombre_categoria_producto)
);

CREATE TABLE marca (
    id_marca INT NOT NULL AUTO_INCREMENT,
    nombre_marca VARCHAR(80) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id_marca),
    UNIQUE KEY uq_marca_nombre (nombre_marca)
);

CREATE TABLE estado_equipo (
    id_estado_equipo INT NOT NULL AUTO_INCREMENT,
    nombre_estado VARCHAR(50) NOT NULL,
    descripcion VARCHAR(200),
    permite_prestamo TINYINT(1) NOT NULL DEFAULT 1,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id_estado_equipo),
    UNIQUE KEY uq_estado_equipo_nombre (nombre_estado)
);

CREATE TABLE modelo_producto (
    id_modelo_producto INT NOT NULL AUTO_INCREMENT,
    id_marca INT NOT NULL,
    id_categoria_producto INT NOT NULL,
    nombre_modelo_producto VARCHAR(100) NOT NULL,
    descripcion_modelo_producto VARCHAR(300),
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id_modelo_producto),
    KEY idx_modelo_marca (id_marca),
    KEY idx_modelo_categoria (id_categoria_producto),
    UNIQUE KEY uq_modelo_marca (id_marca, nombre_modelo_producto),
    CONSTRAINT fk_modelo_marca FOREIGN KEY (id_marca) REFERENCES marca(id_marca) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_modelo_categoria FOREIGN KEY (id_categoria_producto) REFERENCES categoria_producto(id_categoria_producto) ON DELETE RESTRICT ON UPDATE CASCADE
);

CREATE TABLE equipo (
    id_equipo INT NOT NULL AUTO_INCREMENT,
    id_modelo_producto INT NOT NULL,
    id_estado_equipo INT NOT NULL,
    codigo_inventario VARCHAR(50) NOT NULL,
    numero_serie VARCHAR(100),
    descripcion_equipo VARCHAR(300),
    fecha_adquisicion DATE,
    valor_adquisicion DECIMAL(12,2),
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_equipo),
    KEY idx_equipo_modelo (id_modelo_producto),
    KEY idx_equipo_estado (id_estado_equipo),
    UNIQUE KEY uq_equipo_codigo (codigo_inventario),
    UNIQUE KEY uq_equipo_serie (numero_serie),
    CONSTRAINT fk_equipo_modelo FOREIGN KEY (id_modelo_producto) REFERENCES modelo_producto(id_modelo_producto) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_equipo_estado FOREIGN KEY (id_estado_equipo) REFERENCES estado_equipo(id_estado_equipo) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT chk_equipo_valor CHECK (valor_adquisicion IS NULL OR valor_adquisicion >= 0)
);

CREATE TABLE inventario_equipo (
    id_inventario INT NOT NULL AUTO_INCREMENT,
    id_equipo INT NOT NULL,
    ubicacion_actual VARCHAR(150),
    fecha_ingreso DATE NOT NULL,
    fecha_ultimo_mov DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    observaciones VARCHAR(500),
    PRIMARY KEY (id_inventario),
    UNIQUE KEY uq_inventario_equipo (id_equipo),
    CONSTRAINT fk_inventario_equipo FOREIGN KEY (id_equipo) REFERENCES equipo(id_equipo) ON DELETE RESTRICT ON UPDATE CASCADE
);

/* ===================== PRÉSTAMOS ===================== */

CREATE TABLE estado_prestamo (
    id_estado_prestamo INT NOT NULL AUTO_INCREMENT,
    nombre_estado VARCHAR(50) NOT NULL,
    descripcion VARCHAR(200),
    PRIMARY KEY (id_estado_prestamo),
    UNIQUE KEY uq_estado_prestamo (nombre_estado)
);

CREATE TABLE prestamo (
    id_prestamo INT NOT NULL AUTO_INCREMENT,
    id_estudiante INT NOT NULL,
    id_estado_prestamo INT NOT NULL,
    id_usuario_registro INT NOT NULL,
    fecha_prestamo DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_devolucion_programada DATETIME NOT NULL,
    fecha_devolucion_real DATETIME NULL,
    observaciones VARCHAR(500),
    PRIMARY KEY (id_prestamo),
    KEY idx_prestamo_estudiante (id_estudiante),
    KEY idx_prestamo_estado (id_estado_prestamo),
    KEY idx_prestamo_usuario (id_usuario_registro),
    KEY idx_prestamo_fecha_devolucion (fecha_devolucion_programada),
    CONSTRAINT fk_prestamo_estudiante FOREIGN KEY (id_estudiante) REFERENCES estudiante(id_estudiante) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_prestamo_estado FOREIGN KEY (id_estado_prestamo) REFERENCES estado_prestamo(id_estado_prestamo) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_prestamo_usuario FOREIGN KEY (id_usuario_registro) REFERENCES tblusuario(idUsuario) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT chk_prestamo_fechas CHECK (fecha_devolucion_programada >= fecha_prestamo)
);

CREATE TABLE detalle_prestamo (
    id_detalle_prestamo INT NOT NULL AUTO_INCREMENT,
    id_prestamo INT NOT NULL,
    id_equipo INT NOT NULL,
    estado_entrega ENUM('ENTREGADO','DEVUELTO','DANADO','PERDIDO') NOT NULL DEFAULT 'ENTREGADO',
    observaciones_entrega VARCHAR(500),
    PRIMARY KEY (id_detalle_prestamo),
    KEY idx_detalle_prestamo (id_prestamo),
    KEY idx_detalle_equipo (id_equipo),
    UNIQUE KEY uq_prestamo_equipo (id_prestamo, id_equipo),
    CONSTRAINT fk_detalle_prestamo FOREIGN KEY (id_prestamo) REFERENCES prestamo(id_prestamo) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_detalle_equipo FOREIGN KEY (id_equipo) REFERENCES equipo(id_equipo) ON DELETE RESTRICT ON UPDATE CASCADE
);

/* ===================== DEVOLUCIONES ===================== */

CREATE TABLE tipo_incidente (
    id_tipo_incidente INT NOT NULL AUTO_INCREMENT,
    nombre_tipo_incidente VARCHAR(100) NOT NULL,
    descripcion VARCHAR(300),
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id_tipo_incidente),
    UNIQUE KEY uq_tipo_incidente (nombre_tipo_incidente)
);

CREATE TABLE devolucion (
    id_devolucion INT NOT NULL AUTO_INCREMENT,
    id_prestamo INT NOT NULL,
    id_usuario INT NOT NULL,
    fecha_devolucion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    observaciones VARCHAR(500),
    PRIMARY KEY (id_devolucion),
    UNIQUE KEY uq_devolucion_prestamo (id_prestamo),
    KEY idx_devolucion_usuario (id_usuario),
    CONSTRAINT fk_devolucion_prestamo FOREIGN KEY (id_prestamo) REFERENCES prestamo(id_prestamo) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_devolucion_usuario FOREIGN KEY (id_usuario) REFERENCES tblusuario(idUsuario) ON DELETE RESTRICT ON UPDATE CASCADE
);

CREATE TABLE detalle_devolucion (
    id_detalle_devolucion INT NOT NULL AUTO_INCREMENT,
    id_devolucion INT NOT NULL,
    id_equipo INT NOT NULL,
    id_tipo_incidente INT NULL,
    estado_devolucion ENUM('BUENO','DANADO','PERDIDO') NOT NULL DEFAULT 'BUENO',
    observaciones VARCHAR(500),
    PRIMARY KEY (id_detalle_devolucion),
    KEY idx_detalle_devolucion (id_devolucion),
    KEY idx_detalle_devolucion_equipo (id_equipo),
    KEY idx_detalle_devolucion_incidente (id_tipo_incidente),
    CONSTRAINT fk_detalle_devolucion FOREIGN KEY (id_devolucion) REFERENCES devolucion(id_devolucion) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_detalle_devolucion_equipo FOREIGN KEY (id_equipo) REFERENCES equipo(id_equipo) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_detalle_devolucion_incidente FOREIGN KEY (id_tipo_incidente) REFERENCES tipo_incidente(id_tipo_incidente) ON DELETE RESTRICT ON UPDATE CASCADE
);

/* ===================== HISTORIAL DE MOVIMIENTOS ===================== */

CREATE TABLE movimiento_inventario (
    id_movimiento BIGINT NOT NULL AUTO_INCREMENT,
    id_equipo INT NOT NULL,
    id_usuario INT NULL,
    tipo_movimiento ENUM('INGRESO','PRESTAMO','DEVOLUCION','MANTENIMIENTO','BAJA','TRASLADO') NOT NULL,
    fecha_movimiento DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    descripcion VARCHAR(500),
    ubicacion_origen VARCHAR(150),
    ubicacion_destino VARCHAR(150),
    PRIMARY KEY (id_movimiento),
    KEY idx_movimiento_equipo (id_equipo),
    KEY idx_movimiento_usuario (id_usuario),
    KEY idx_movimiento_fecha (fecha_movimiento),
    KEY idx_movimiento_tipo (tipo_movimiento),
    CONSTRAINT fk_movimiento_equipo FOREIGN KEY (id_equipo) REFERENCES equipo(id_equipo) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_movimiento_usuario FOREIGN KEY (id_usuario) REFERENCES tblusuario(idUsuario) ON DELETE SET NULL ON UPDATE CASCADE
);