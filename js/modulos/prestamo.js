window.Modulos = window.Modulos || {};

// Fecha mínima del calendario: mañana (hora local), en formato AAAA-MM-DD
const fechaManana = (() => {
    const hoy = new Date();
    const m = new Date(hoy.getFullYear(), hoy.getMonth(), hoy.getDate() + 1);
    return m.getFullYear() + "-" + String(m.getMonth() + 1).padStart(2, "0") + "-" + String(m.getDate()).padStart(2, "0");
})();

window.Modulos.prestamo = Crud.crear({
    titulo: "Mantenimiento de Préstamos",
    api: "api/admin/Prestamo.php",
    id: "id_prestamo",
    singular: "préstamo",
    plural: "préstamos",

    fuentes: {
        // OJO: verifica que estos endpoints y columnas existan con esos nombres en tu proyecto
        estudiante: { api: "api/admin/Prestamo.php?lista=estudiantes", valor: "id_estudiante", texto: "nombre_completo" },
        estado: { api: "api/admin/Estado_Prestamo.php", valor: "id_estado_prestamo", texto: "nombre_estado" },
        usuario: { api: "api/admin/Prestamo.php?lista=usuarios", valor: "id_usuario", texto: "nombre_usuario" }
    },

    busqueda: [
        { valor: "observaciones", texto: "Observaciones" }
    ],

    filtros: [
        { clave: "id_estudiante", etiqueta: "Estudiante", fuente: "estudiante", vacio: "Todos" },
        { clave: "id_estado_prestamo", etiqueta: "Estado", fuente: "estado", vacio: "Todos" }
    ],

    columnas: [
        { campo: "id_prestamo", titulo: "Id" },
        { campo: "id_estudiante", titulo: "Estudiante", fuente: "estudiante" },
        { campo: "id_estado_prestamo", titulo: "Estado", fuente: "estado" },
        { campo: "id_usuario_registro", titulo: "Registrado por", fuente: "usuario" },
        { campo: "fecha_prestamo", titulo: "Fecha préstamo" },
        { campo: "fecha_devolucion_programada", titulo: "Devolución programada" },
        { campo: "fecha_devolucion_real", titulo: "Devolución real" },
        { campo: "observaciones", titulo: "Observaciones" }
    ],

    campos: [
        { name: "id_estudiante", etiqueta: "Estudiante", fuente: "estudiante", required: true },
        { name: "id_estado_prestamo", etiqueta: "Estado", fuente: "estado", required: true },
        { name: "id_usuario_registro", etiqueta: "Registrado por", fuente: "usuario", required: true },
        { name: "fecha_devolucion_programada", etiqueta: "Devolución programada", tipo: "date", min: fechaManana, required: true },
        //{ name: "fecha_devolucion_real", etiqueta: "Devolución real", tipo: "date" },
        { name: "observaciones", etiqueta: "Observaciones", maxlength: 500, autocomplete: "off" }
    ]
});