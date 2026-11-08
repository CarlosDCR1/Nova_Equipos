window.Modulos = window.Modulos || {};

const detallePrestamoCrud = Crud.crear({
    titulo: "Mantenimiento de Detalle de Préstamo",
    api: "api/admin/Detalle_Prestamo.php",
    id: "id_detalle_prestamo",
    singular: "detalle de préstamo",
    plural: "detalles de préstamo",

    fuentes: {
        prestamo: { api: "api/admin/Detalle_Prestamo.php?lista=prestamos", valor: "id_prestamo", texto: "texto_prestamo" },
        equipo: { api: "api/admin/Detalle_Prestamo.php?lista=equipos", valor: "id_equipo", texto: "texto_equipo" }
    },

    busqueda: [
        { valor: "observaciones", texto: "Observaciones" }
    ],

    filtros: [
        { clave: "id_prestamo", etiqueta: "Préstamo", fuente: "prestamo", vacio: "Todos" },
        { clave: "id_equipo", etiqueta: "Equipo", fuente: "equipo", vacio: "Todos" }
    ],

    columnas: [
        { campo: "id_detalle_prestamo", titulo: "Id" },
        { campo: "id_prestamo", titulo: "Préstamo", fuente: "prestamo" },
        { campo: "id_equipo", titulo: "Equipo", fuente: "equipo" },
        { campo: "observaciones_entrega", titulo: "Observaciones de entrega" }
    ],

    campos: [
        { name: "id_prestamo", etiqueta: "Préstamo", fuente: "prestamo", required: true },
        { name: "id_equipo", etiqueta: "Equipo", fuente: "equipo", required: true },
        { name: "observaciones_entrega", etiqueta: "Observaciones de entrega", maxlength: 500, autocomplete: "off" }
    ]
});


// Solo se muestran los equipos disponible 
window.Modulos.detalle_prestamo = {
    async init(raiz) {
        await detallePrestamoCrud.init(raiz);

        const selEquipo = raiz.querySelector("#f-id_equipo");
        const form = raiz.querySelector(".crud-form");
        const tbody = raiz.querySelector(".crud-tbody");
        if (!selEquipo) return;

        const ESTADO_DISPONIBLE = "1"; // id del estado "Disponible" en estado_equipo

        let estadoDeEquipo = {};   // id_equipo -> id_estado_equipo
        let equipoEnEdicion = null; // equipo del registro seleccionado en la tabla
        let mapaListo = false;
        let temporizador;

        // Consulta el estado actual de cada equipo
        async function cargarEstados() {
            try {
                const json = await fetch("api/admin/Detalle_Prestamo.php?lista=equipos").then((r) => r.json());
                estadoDeEquipo = {};
                (json.data || []).forEach((e) => (estadoDeEquipo[e.id_equipo] = String(e.id_estado_equipo)));
                mapaListo = true;
            } catch (e) {

                console.error("No se pudo preparar el filtro de equipos:", e.message);
            }
        }

        // Oculta los equipos que no están disponibles
        function filtrar() {
            if (!mapaListo) return;
            Array.from(selEquipo.options).forEach((op) => {
                if (op.value === "") return; // opción "Selecciona..."
                const permitido = estadoDeEquipo[op.value] === ESTADO_DISPONIBLE || op.value === equipoEnEdicion;
                op.hidden = !permitido;
                op.disabled = !permitido;
            });
        }

        async function refrescar() {
            await cargarEstados();
            filtrar();
        }

        // Al seleccionar una fila de la tabla (para editar): su equipo sigue disponible en la lista
        tbody.addEventListener("click", () => {
            equipoEnEdicion = selEquipo.value || null;
            filtrar();
        });

        // Al limpiar el formulario (también después de agregar, editar o eliminar)
        form.addEventListener("reset", () => setTimeout(() => {
            equipoEnEdicion = null;
            filtrar();
        }, 0));

        // Cada vez que la tabla se recarga, se actualiza qué equipos están disponibles
        new MutationObserver(() => {
            clearTimeout(temporizador);
            temporizador = setTimeout(refrescar, 150);
        }).observe(tbody, { childList: true });

        await refrescar();
    }
};