window.Modulos = window.Modulos || {};

window.Modulos.usuarios = Crud.crear({
    titulo: "Mantenimiento de Usuarios",
    api: "api/admin/Usuario.php",
    id: "idUsuario",
    singular: "usuario",
    plural: "usuarios",

    fuentes: {
        empleado: {
            api: "api/admin/Empleado.php",
            valor: "id_empleado",
            texto: (e) => `${e.nombre} ${e.apellidos ?? ""}`.trim()
        }
    },

    busqueda: [
        //{ valor: "usuario", texto: "Usuario" }
        { valor: "nombreUsuario", texto: "Usuario" }
    ],

    filtros: [
        { clave: "idEmpleado", etiqueta: "Empleado", fuente: "empleado", vacio: "Todos" },
        { clave: "is_active", etiqueta: "Estado", vacio: "Todos",
        opciones: [{ valor: "1", texto: "Activo" }, { valor: "0", texto: "Inactivo" }] }
    ],

    columnas: [
        { campo: "idUsuario", titulo: "Id" },
        { campo: "empleado_nombre", titulo: "Empleado",
          formato: (v, f) => `${f.empleado_nombre ?? ""} ${f.empleado_apellidos ?? ""}`.trim() },
        { campo: "nombreUsuario", titulo: "Usuario" },
        { campo: "is_active", titulo: "Estado", formato: Crud.badge },
        { campo: "ultimoAccesoUsuario", titulo: "Último acceso" },
        { campo: "intentosFallidosUsuario", titulo: "Intentos fallidos" },
        { campo: "created_at", titulo: "Creado" }
    ],

    campos: [
        { name: "idEmpleado", etiqueta: "Empleado", fuente: "empleado", required: true },
        { name: "nombreUsuario", etiqueta: "Usuario", maxlength: 50, required: true },
        { name: "contrasenaUsuario", etiqueta: "Contraseña", tipo: "password",
          autocomplete: "new-password",
          requeridoAlCrear: true,
          ayudaEditar: "Déjalo en blanco para no cambiar la contraseña actual." },
        { name: "is_active", etiqueta: "Estado", defecto: "1",
          opciones: [{ valor: "1", texto: "Activo" }, { valor: "0", texto: "Inactivo" }] }
    ]
});