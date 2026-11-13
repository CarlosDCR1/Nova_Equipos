window.Modulos = window.Modulos || {};

const fechaManana = (() => {
    const hoy = new Date();
    const m = new Date(hoy.getFullYear(), hoy.getMonth(), hoy.getDate() + 1);
    return m.getFullYear() + "-" + String(m.getMonth() + 1).padStart(2, "0") + "-" + String(m.getDate()).padStart(2, "0");
})();

const prestamoCrud = Crud.crear({
    titulo: "Mantenimiento de Préstamos",
    api: "api/admin/Prestamo.php",
    id: "id_prestamo",
    singular: "préstamo",
    plural: "préstamos",

    fuentes: {
        estudiante: { api: "api/admin/Prestamo.php?lista=estudiantes", valor: "id_estudiante", texto: "nombre_completo" },
        estado: { api: "api/admin/Estado_Prestamo.php", valor: "id_estado_prestamo", texto: "nombre_estado" },
        usuario: { api: "api/admin/Prestamo.php?lista=usuarios", valor: "id_usuario", texto: "nombre_usuario" }
    },

    busqueda: [
        { valor: "observaciones", texto: "Observaciones" }
    ],

    filtros: [
        { clave: "id_estudiante", etiqueta: "Estudiante", fuente: "estudiante", vacio: "Todos", buscable: true },
        { clave: "id_estado_prestamo", etiqueta: "Estado", fuente: "estado", vacio: "Todos" }
    ],

    columnas: [
        { campo: "id_prestamo", titulo: "Id" },
        { campo: "id_estudiante", titulo: "Estudiante", fuente: "estudiante" },
        { campo: "id_estado_prestamo", titulo: "Estado", fuente: "estado" },
        { campo: "id_usuario_registro", titulo: "Registrado por", fuente: "usuario" },
        { campo: "fecha_prestamo", titulo: "Fecha préstamo" },
        { campo: "fecha_devolucion_programada", titulo: "Devolución programada" },
        { campo: "observaciones", titulo: "Observaciones" }
    ],

    campos: [
        { name: "id_estudiante", etiqueta: "Estudiante", fuente: "estudiante", required: true, buscable: true },
        { name: "id_estado_prestamo", etiqueta: "Estado", fuente: "estado", required: true },
        { name: "id_usuario_registro", etiqueta: "Registrado por", fuente: "usuario", required: true },
        { name: "fecha_devolucion_programada", etiqueta: "Devolución programada", tipo: "date", min: fechaManana, required: true },
        { name: "observaciones", etiqueta: "Observaciones", maxlength: 500, autocomplete: "off" }
    ]
});

window.Modulos.prestamo = {
    async init(raiz) {
        await prestamoCrud.init(raiz);

        const selUsuario = raiz.querySelector("#f-id_usuario_registro");
        const form = raiz.querySelector(".crud-form");
        const tbody = raiz.querySelector(".crud-tbody");
        if (!selUsuario) return;

        // Averigua cuál de los usuarios de la lista es el conectado
        let idConectado = null;
        try {
            const json = await fetch("api/admin/Prestamo.php?lista=usuarios").then((r) => r.json());
            const actual = (json.data || []).find((u) => Number(u.es_actual) === 1);
            idConectado = actual ? String(actual.id_usuario) : null;
        } catch (e) {
            console.error("No se pudo identificar al usuario conectado:", e.message);
        }
        // Sin ese dato no se bloquea nada; el servidor igual registra al usuario de la sesión
        if (idConectado === null) return;

        let usuarioEnEdicion = null; // quien registró el préstamo seleccionado en la tabla

        // Deja visible solo al usuario conectado (y al que registró el préstamo que se está editando)
        function aplicar() {
            Array.from(selUsuario.options).forEach((op) => {
                if (op.value === "") return; // opción "Selecciona..."
                const permitido = op.value === idConectado || op.value === usuarioEnEdicion;
                op.hidden = !permitido;
                op.disabled = !permitido;
            });
            selUsuario.disabled = true;
        }

        function nuevoRegistro() {
            usuarioEnEdicion = null;
            aplicar();
            selUsuario.value = idConectado;
        }

        // Al seleccionar una fila de la tabla (para editar): se muestra quien registró ese préstamo
        tbody.addEventListener("click", () => {
            usuarioEnEdicion = selUsuario.value || null;
            aplicar();
        });

        // Al limpiar el formulario (también después de agregar, editar o eliminar)
        form.addEventListener("reset", () => setTimeout(nuevoRegistro, 0));

        nuevoRegistro();
    }
};