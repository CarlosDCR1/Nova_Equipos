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
        { clave: "id_prestamo", etiqueta: "Préstamo", fuente: "prestamo", vacio: "Todos", buscable: true },
        { clave: "id_equipo", etiqueta: "Equipo", fuente: "equipo", vacio: "Todos", buscable: true }
    ],

    columnas: [
        { campo: "id_detalle_prestamo", titulo: "Id" },
        { campo: "id_prestamo", titulo: "Préstamo", fuente: "prestamo" },
        { campo: "id_equipo", titulo: "Equipo", fuente: "equipo" },
        { campo: "observaciones_entrega", titulo: "Observaciones de entrega" }
    ],

    campos: [
        { name: "id_prestamo", etiqueta: "Préstamo", fuente: "prestamo", required: true, buscable: true },
        { name: "id_equipo", etiqueta: "Equipo", fuente: "equipo", required: true, buscable: true },
        { name: "observaciones_entrega", etiqueta: "Observaciones de entrega", maxlength: 500, autocomplete: "off" }
    ]
});

window.Modulos.detalle_prestamo = {
    async init(raiz) {
        await detallePrestamoCrud.init(raiz);

        const selEquipo = raiz.querySelector("#f-id_equipo");
        const form = raiz.querySelector(".crud-form");
        const tbody = raiz.querySelector(".crud-tbody");
        if (!selEquipo) return;

        const ESTADO_DISPONIBLE = "1";

        let estadoDeEquipo = {};
        let equipoEnEdicion = null;
        let mapaListo = false;
        let temporizador;

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

        function filtrar() {
            if (!mapaListo) return;
            Crud.filtrarOpciones(
                selEquipo,
                (v) => estadoDeEquipo[v] === ESTADO_DISPONIBLE || v === equipoEnEdicion,
                false
            );
        }

        async function refrescar() {
            await cargarEstados();
            filtrar();
        }

        tbody.addEventListener("click", () => {
            equipoEnEdicion = selEquipo.value || null;
            filtrar();
        });

        form.addEventListener("reset", () => setTimeout(() => {
            equipoEnEdicion = null;
            filtrar();
        }, 0));

        new MutationObserver(() => {
            clearTimeout(temporizador);
            temporizador = setTimeout(refrescar, 150);
        }).observe(tbody, { childList: true });

        await refrescar();
    }
};