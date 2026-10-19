window.Modulos = window.Modulos || {};

window.Modulos.tblaplicacion = Crud.crear({
    titulo: "Mantenimiento de Aplicaciones",
    api: "api/admin/Aplicacion.php",
    id: "idAplicacion",
    singular: "aplicación",
    plural: "aplicaciones",

    fuentes: {
        modulo: { api: "api/admin/Modulo.php", valor: "idModulo", texto: "nombreModulo" }
    },

    busqueda: [
        { valor: "nombre", texto: "Nombre" }
    ],

    filtros: [
        { clave: "idModulo", etiqueta: "Módulo", fuente: "modulo", vacio: "Todos" },
        { clave: "is_active", etiqueta: "Estado", vacio: "Todos",
          opciones: [{ valor: "1", texto: "Activo" }, { valor: "0", texto: "Inactivo" }] }
    ],

    columnas: [
        { campo: "idAplicacion", titulo: "Id" },
        { campo: "idModulo", titulo: "Módulo", fuente: "modulo" },
        { campo: "nombreAplicacion", titulo: "Nombre" },
        { campo: "descripcionAplicacion", titulo: "Descripción" },
        { campo: "is_active", titulo: "Estado", formato: Crud.badge },
        { campo: "created_at", titulo: "Creado" },
        { campo: "updated_at", titulo: "Actualizado" }
    ],

    campos: [
        { name: "idModulo", etiqueta: "Módulo", fuente: "modulo", required: true },
        { name: "nombreAplicacion", etiqueta: "Nombre", maxlength: 100, required: true, autocomplete: "off" },
        { name: "descripcionAplicacion", etiqueta: "Descripción", maxlength: 255, required: true, autocomplete: "off" },
        { name: "is_active", etiqueta: "Estado", defecto: "1",
          opciones: [{ valor: "1", texto: "Activo" }, { valor: "0", texto: "Inactivo" }] }
    ]
});