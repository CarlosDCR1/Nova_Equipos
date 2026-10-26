window.Modulos = window.Modulos || {};

window.Modulos.estado_prestamo = Crud.crear({
    titulo: "Mantenimiento de Estados de Préstamo",
    api: "api/admin/Estado_Prestamo.php",
    id: "id_estado_prestamo",
    singular: "estado de préstamo",
    plural: "estados de préstamo",

    busqueda: [
        { valor: "nombre", texto: "Nombre" }
    ],

    columnas: [
        { campo: "id_estado_prestamo", titulo: "Id" },
        { campo: "nombre_estado", titulo: "Nombre" },
        { campo: "descripcion", titulo: "Descripción" }
    ],

    campos: [
        { name: "nombre_estado", etiqueta: "Nombre", maxlength: 50, required: true, autocomplete: "off" },
        { name: "descripcion", etiqueta: "Descripción", maxlength: 200, autocomplete: "off" }
    ]
});