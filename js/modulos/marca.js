window.Modulos = window.Modulos || {};

window.Modulos.marca = Crud.crear({
    titulo: "Mantenimiento de Marcas",
    api: "api/admin/Marca.php",
    id: "id_marca",
    singular: "marca",
    plural: "marcas",

    busqueda: [
        { valor: "nombre", texto: "Nombre" }
    ],

    filtros: [
        { clave: "is_active", etiqueta: "Estado", vacio: "Todos",
          opciones: [{ valor: "1", texto: "Activo" }, { valor: "0", texto: "Inactivo" }] }
    ],

    columnas: [
        { campo: "id_marca", titulo: "Id" },
        { campo: "nombre_marca", titulo: "Nombre" },
        { campo: "is_active", titulo: "Estado", formato: Crud.badge }
    ],

    campos: [
        { name: "nombre_marca", etiqueta: "Nombre", maxlength: 80, required: true, autocomplete: "off" },
        { name: "is_active", etiqueta: "Estado", defecto: "1",
          opciones: [{ valor: "1", texto: "Activo" }, { valor: "0", texto: "Inactivo" }] }
    ]
});