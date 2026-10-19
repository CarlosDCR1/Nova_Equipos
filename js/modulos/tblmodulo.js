window.Modulos = window.Modulos || {};

window.Modulos.tblmodulo = Crud.crear({
    titulo: "Mantenimiento de Módulos",
    api: "api/admin/Modulo.php",
    id: "idModulo",
    singular: "módulo",
    plural: "módulos",

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
        { campo: "idModulo", titulo: "Id" },
        { campo: "nombreModulo", titulo: "Nombre" },
        { campo: "descripcionModulo", titulo: "Descripción" },
        { campo: "is_active", titulo: "Estado", formato: Crud.badge },
        { campo: "created_at", titulo: "Creado" },
        { campo: "updated_at", titulo: "Actualizado" }
    ],

    campos: [
        { name: "nombreModulo", etiqueta: "Nombre", maxlength: 100, required: true, autocomplete: "off" },
        { name: "descripcionModulo", etiqueta: "Descripción", maxlength: 255, required: true, autocomplete: "off" },
        { name: "is_active", etiqueta: "Estado", defecto: "1",
          opciones: [{ valor: "1", texto: "Activo" }, { valor: "0", texto: "Inactivo" }] }
    ]
});