window.Modulos = window.Modulos || {};

window.Modulos.carrera = Crud.crear({
    titulo: "Mantenimiento de Carreras",
    api: "api/admin/Carrera.php",
    id: "id_carrera",
    singular: "carrera",
    plural: "carreras",

    fuentes: {
        facultad: { api: "api/admin/Facultad.php", valor: "id_facultad", texto: "nombre_facultad" }
    },

   
    busqueda: [
        { valor: "nombre", texto: "Nombre de la carrera" }
    ],

    
    filtros: [
        { clave: "id_facultad", etiqueta: "Facultad", fuente: "facultad", vacio: "Todas" }
    ],

    columnas: [
        { campo: "id_carrera",  titulo: "Id" },
        { campo: "nombre_facultad", titulo: "Facultad"},
        { campo: "nombre_carrera", titulo: "Carrera" }
    ],

   
    campos: [
        { name: "id_facultad",    etiqueta: "Facultad", fuente: "facultad", required: true },
        { name: "nombre_carrera", etiqueta: "Nombre de la carrera", maxlength: 100, required: true }
    ]
});