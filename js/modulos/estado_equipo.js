window.Modulos = window.Modulos || {};

window.Modulos.estado_equipo = Crud.crear({
    titulo: "Mantenimiento de Estados de Equipo",
    api: "api/admin/Estado_Equipo.php",
    id: "id_estado_equipo",
    singular: "estado de equipo",
    plural: "estados de equipo",

    busqueda: [
        { valor: "nombre", texto: "Nombre" }
    ],

    filtros: [
        { clave: "permite_prestamo", etiqueta: "Permite préstamo", vacio: "Todos",
          opciones: [{ valor: "1", texto: "Sí" }, { valor: "0", texto: "No" }] },
        { clave: "is_active", etiqueta: "Estado", vacio: "Todos",
          opciones: [{ valor: "1", texto: "Activo" }, { valor: "0", texto: "Inactivo" }] }
    ],

    columnas: [
        { campo: "id_estado_equipo", titulo: "Id" },
        { campo: "nombre_estado", titulo: "Nombre" },
        { campo: "descripcion", titulo: "Descripción" },
        { campo: "permite_prestamo", titulo: "Permite préstamo", formato: Crud.badge },
        { campo: "is_active", titulo: "Estado", formato: Crud.badge }
    ],

    campos: [
        { name: "nombre_estado", etiqueta: "Nombre", maxlength: 50, required: true, autocomplete: "off" },
        { name: "descripcion", etiqueta: "Descripción", maxlength: 200, autocomplete: "off" },
        { name: "permite_prestamo", etiqueta: "Permite préstamo", defecto: "1",
          opciones: [{ valor: "1", texto: "Sí" }, { valor: "0", texto: "No" }] },
        { name: "is_active", etiqueta: "Estado", defecto: "1",
          opciones: [{ valor: "1", texto: "Activo" }, { valor: "0", texto: "Inactivo" }] }
    ]
});