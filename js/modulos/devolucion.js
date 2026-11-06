window.Modulos = window.Modulos || {};

window.Modulos.devolucion = Crud.crear({
    titulo: "Mantenimiento de Devoluciones",
    api: "api/admin/Devolucion.php",
    id: "id_devolucion",
    singular: "devolución",
    plural: "devoluciones",

    fuentes: {
        prestamo: { api: "api/admin/Devolucion.php?lista=prestamos", valor: "id_prestamo", texto: "texto_prestamo" },
        usuario: { api: "api/admin/Devolucion.php?lista=usuarios", valor: "id_usuario", texto: "nombre_usuario" }
    },

    busqueda: [
        { valor: "observaciones", texto: "Observaciones" }
    ],

    filtros: [
        { clave: "id_prestamo", etiqueta: "Préstamo", fuente: "prestamo", vacio: "Todos" }
    ],

    columnas: [
        { campo: "id_devolucion", titulo: "Id" },
        { campo: "id_prestamo", titulo: "Préstamo", fuente: "prestamo" },
        { campo: "id_usuario", titulo: "Registrado por", fuente: "usuario" },
        { campo: "fecha_devolucion", titulo: "Fecha de devolución" },
        { campo: "observaciones", titulo: "Observaciones" }
    ],

    campos: [
        { name: "id_prestamo", etiqueta: "Préstamo", fuente: "prestamo", required: true },
        { name: "id_usuario", etiqueta: "Registrado por", fuente: "usuario", required: true },
        { name: "observaciones", etiqueta: "Observaciones", maxlength: 500, autocomplete: "off" }
    ]
});