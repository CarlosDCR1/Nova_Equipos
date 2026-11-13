window.Modulos = window.Modulos || {};

const devolucionCrud = Crud.crear({
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


window.Modulos.devolucion = {
    async init(raiz) {
        await devolucionCrud.init(raiz);

        const selPrestamo = raiz.querySelector("#f-id_prestamo");
        const form = raiz.querySelector(".crud-form");
        const tbody = raiz.querySelector(".crud-tbody");
        if (!selPrestamo) return;

        let disponibles = null;  
        let enEdicion = null;    
        let temporizador;

        async function cargar() {
            try {
                const json = await fetch("api/admin/Devolucion.php?lista=prestamos").then((r) => r.json());
                disponibles = new Set((json.data || [])
                    .filter((p) => Number(p.disponible_devolucion) === 1)
                    .map((p) => String(p.id_prestamo)));
            } catch (e) {
               
                console.error("No se pudo preparar el filtro de préstamos:", e.message);
            }
        }

       
        function filtrar() {
            if (!disponibles) return;
            Array.from(selPrestamo.options).forEach((op) => {
                if (op.value === "") return; // opción "Selecciona..."
                const permitido = disponibles.has(op.value) || op.value === enEdicion;
                op.hidden = !permitido;
                op.disabled = !permitido;
            });
            if (selPrestamo.selectedOptions[0]?.disabled) selPrestamo.value = "";
        }

        async function refrescar() {
            await cargar();
            filtrar();
        }

       
        tbody.addEventListener("click", () => {
            enEdicion = selPrestamo.value || null;
            filtrar();
        });

      
        form.addEventListener("reset", () => setTimeout(() => {
            enEdicion = null;
            filtrar();
        }, 0));

      
        new MutationObserver(() => {
            clearTimeout(temporizador);
            temporizador = setTimeout(refrescar, 150);
        }).observe(tbody, { childList: true });

        await refrescar();

        const selUsuario = raiz.querySelector("#f-id_usuario");
        if (!selUsuario) return;

        let idConectado = null;
        try {
            const json = await fetch("api/admin/Devolucion.php?lista=usuarios").then((r) => r.json());
            const actual = (json.data || []).find((u) => Number(u.es_actual) === 1);
            idConectado = actual ? String(actual.id_usuario) : null;
        } catch (e) {
            console.error("No se pudo identificar al usuario conectado:", e.message);
        }

        if (idConectado === null) return;

        let usuarioEnEdicion = null; // quien registró la devolución seleccionada en la tabla
        function aplicarUsuario() {
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
            aplicarUsuario();
            selUsuario.value = idConectado;
        }

        tbody.addEventListener("click", () => {
            usuarioEnEdicion = selUsuario.value || null;
            aplicarUsuario();
        });

        form.addEventListener("reset", () => setTimeout(nuevoRegistro, 0));

        nuevoRegistro();

    }
};