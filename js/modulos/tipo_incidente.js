window.Modulos = window.Modulos || {};

window.Modulos.tipo_incidente = Crud.crear({
    titulo: "Mantenimiento de Tipos de Incidente",
    api: "api/admin/tipo_incidente.php",
    id: "id_tipo_incidente",
    singular: "tipo de incidente",
    plural: "tipos de incidente",

    busqueda: [
        { valor: "nombre", texto: "Nombre" }
    ],

    filtros: [
        { clave: "is_active", etiqueta: "Estado", vacio: "Todos",
          opciones: [{ valor: "1", texto: "Activo" }, { valor: "0", texto: "Inactivo" }] }
    ],

    columnas: [
        { campo: "id_tipo_incidente", titulo: "Id" },
        { campo: "nombre_tipo_incidente", titulo: "Nombre" },
        { campo: "descripcion", titulo: "Descripción" },
        { campo: "is_active", titulo: "Estado", formato: Crud.badge }
    ],

    campos: [
        { name: "nombre_tipo_incidente", etiqueta: "Nombre", maxlength: 100, required: true, autocomplete: "off" },
        { name: "descripcion", etiqueta: "Descripción", maxlength: 300, autocomplete: "off" },
        { name: "is_active", etiqueta: "Estado", defecto: "1",
          opciones: [{ valor: "1", texto: "Activo" }, { valor: "0", texto: "Inactivo" }] }
    ]
});