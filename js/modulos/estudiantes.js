window.Modulos = window.Modulos || {};

window.Modulos.estudiantes = Crud.crear({
    titulo: "Mantenimiento de Estudiantes",
    api: "api/admin/Estudiante.php",
    id: "id_estudiante",
    singular: "estudiante",
    plural: "estudiantes",

    fuentes: {
        carrera: { api: "api/admin/Carrera.php", valor: "id_carrera", texto: "nombre_carrera" }
    },

    busqueda: [
        { valor: "nombre",texto: "Nombre" },
        { valor: "apellidos", texto: "Apellidos" },
        { valor: "carnet", texto: "Carnet" },
        { valor: "numero",  texto: "Número" },
        { valor: "correo",  texto: "Correo institucional" }
    ],

    filtros: [
        { clave: "id_carrera", etiqueta: "Carrera", fuente: "carrera", vacio: "Todas" },
        { clave: "estado", etiqueta: "Estado", vacio: "Todos",
          opciones: [{ valor: "ACTIVO", texto: "Activo" }, { valor: "INACTIVO", texto: "Inactivo" }] }
    ],

    columnas: [
        { campo: "id_estudiante",  titulo: "Id" },
        { campo: "id_carrera",  titulo: "Carrera", fuente: "carrera" },
        { campo: "nombre_estudiante", titulo: "Nombre" },
        { campo: "apellidos_estudiante", titulo: "Apellidos" },
        { campo: "carnet_estudiante", titulo: "Carnet" },
        { campo: "numero_estudiante", titulo: "Número" },
        { campo: "correo_institucional", titulo: "Correo institucional" },
        { campo: "estado_estudiante", titulo: "Estado", formato: Crud.badge },
        { campo: "fecha_creacion",titulo: "Creado" }
    ],

    campos: [
        { name: "id_carrera", etiqueta: "Carrera", fuente: "carrera", required: true },
        { name: "nombre_estudiante", etiqueta: "Nombre", maxlength: 100, required: true },
        { name: "apellidos_estudiante", etiqueta: "Apellidos", maxlength: 100 },
        { name: "carnet_estudiante", etiqueta: "Carnet", maxlength: 30, required: true },
        { name: "numero_estudiante",  etiqueta: "Número", maxlength: 8, required: true },
        { name: "correo_institucional", etiqueta: "Correo institucional", tipo: "email", maxlength: 150, required: true },
        { name: "estado_estudiante", etiqueta: "Estado", defecto: "ACTIVO",
          opciones: [{ valor: "ACTIVO", texto: "Activo" }, { valor: "INACTIVO", texto: "Inactivo" }] }
    ]
});