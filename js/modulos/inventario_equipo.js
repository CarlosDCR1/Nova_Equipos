window.Modulos = window.Modulos || {};

window.Modulos.inventario_equipo = Crud.crear({
    titulo: "Mantenimiento de Inventario de Equipo",
    api: "api/admin/inventario_equipo.php",
    id: "id_inventario",
    singular: "registro de inventario",
    plural: "registros de inventario",

    fuentes: {
        equipo: { api: "api/admin/Equipo.php", valor: "id_equipo", texto: "codigo_inventario" }
    },

    busqueda: [
        { valor: "ubicacion", texto: "Ubicación" }
    ],

    filtros: [
        { clave: "id_equipo", etiqueta: "Equipo", fuente: "equipo", vacio: "Todos" }
    ],

    columnas: [
        { campo: "id_inventario", titulo: "Id" },
        { campo: "id_equipo", titulo: "Equipo", fuente: "equipo" },
        { campo: "ubicacion_actual", titulo: "Ubicación" },
        { campo: "fecha_ingreso", titulo: "Ingreso" },
        { campo: "fecha_ultimo_mov", titulo: "Último movimiento" },
        { campo: "observaciones", titulo: "Observaciones" }
    ],

    campos: [
        { name: "id_equipo", etiqueta: "Equipo", fuente: "equipo", required: true },
        { name: "ubicacion_actual", etiqueta: "Ubicación actual", maxlength: 150, autocomplete: "off" },
        { name: "fecha_ingreso", etiqueta: "Fecha de ingreso", type: "date", required: true },
        { name: "observaciones", etiqueta: "Observaciones", maxlength: 500, autocomplete: "off" }
    ]
});