window.Modulos = window.Modulos || {};

const detalleDevolucionCrud = Crud.crear({
    titulo: "Mantenimiento de Detalle de Devolución",
    api: "api/admin/Detalle_Devolucion.php",
    id: "id_detalle_devolucion",
    singular: "detalle de devolución",
    plural: "detalles de devolución",

    fuentes: {
        devolucion: { api: "api/admin/Detalle_Devolucion.php?lista=devoluciones", valor: "id_devolucion", texto: "texto_devolucion" },
        producto: { api: "api/admin/Detalle_Devolucion.php?lista=productos", valor: "id_detalle_prestamo", texto: "texto_producto" },
        incidente: { api: "api/admin/Tipo_Incidente.php", valor: "id_tipo_incidente", texto: "nombre_tipo_incidente" }
    },

    busqueda: [
        { valor: "observaciones", texto: "Observaciones" }
    ],

    filtros: [
        { clave: "id_devolucion", etiqueta: "Devolución", fuente: "devolucion", vacio: "Todas" },
        { clave: "estado_devolucion", etiqueta: "Estado", vacio: "Todos",
          opciones: [{ valor: "BUENO", texto: "Bueno" }, { valor: "DANADO", texto: "Dañado" }, { valor: "PERDIDO", texto: "Perdido" }] }
    ],

    columnas: [
        { campo: "id_detalle_devolucion", titulo: "Id" },
        { campo: "id_devolucion", titulo: "Devolución", fuente: "devolucion" },
        { campo: "id_detalle_prestamo", titulo: "Producto prestado", fuente: "producto" },
        { campo: "id_tipo_incidente", titulo: "Tipo de incidente", fuente: "incidente" },
        { campo: "estado_devolucion", titulo: "Estado",
          formato: (v) => ({ BUENO: "Bueno", DANADO: "Dañado", PERDIDO: "Perdido" })[v] ?? v },
        { campo: "observaciones", titulo: "Observaciones" }
    ],

    campos: [
        { name: "id_devolucion", etiqueta: "Devolución", fuente: "devolucion", required: true },
        { name: "id_detalle_prestamo", etiqueta: "Producto prestado", fuente: "producto", required: true },
        { name: "id_tipo_incidente", etiqueta: "Tipo de incidente", fuente: "incidente", vacio: "Sin incidente" },
        { name: "estado_devolucion", etiqueta: "Estado del equipo", defecto: "BUENO",
          opciones: [{ valor: "BUENO", texto: "Bueno" }, { valor: "DANADO", texto: "Dañado" }, { valor: "PERDIDO", texto: "Perdido" }] },
        { name: "observaciones", etiqueta: "Observaciones", maxlength: 500, autocomplete: "off" }
    ]
});

window.Modulos.detalle_devolucion = {
    async init(raiz) {
        await detalleDevolucionCrud.init(raiz);

        const selDevolucion = raiz.querySelector("#f-id_devolucion");
        const selProducto = raiz.querySelector("#f-id_detalle_prestamo");
        const form = raiz.querySelector(".crud-form");
        const tbody = raiz.querySelector(".crud-tbody");
        if (!selDevolucion || !selProducto) return;

        let prestamoDeDevolucion = {};   
        let prestamoDeProducto = {};      
        let productoPendiente = {};       
        let prestamosConPendientes = new Set();
        let productoEnEdicion = null;     
        let devolucionEnEdicion = null; 
        let mapasListos = false;
        let temporizador;

        // Consultamos las listas actuales para saber a qué préstamo pertenece cada cosa y qué sigue pendiente :)
        async function cargarMapas() {
            try {
                const [devoluciones, productos] = await Promise.all([
                    fetch("api/admin/Detalle_Devolucion.php?lista=devoluciones").then((r) => r.json()),
                    fetch("api/admin/Detalle_Devolucion.php?lista=productos").then((r) => r.json())
                ]);
                prestamoDeDevolucion = {};
                prestamoDeProducto = {};
                productoPendiente = {};
                prestamosConPendientes = new Set();

                (devoluciones.data || []).forEach((d) => (prestamoDeDevolucion[d.id_devolucion] = String(d.id_prestamo)));
                (productos.data || []).forEach((p) => {
                    const idPrestamo = String(p.id_prestamo);
                    // si el modelo no manda prestamo_abierto (versión vieja), se asume abierto
                    const abierto = p.prestamo_abierto === undefined || Number(p.prestamo_abierto) === 1;
                    const pendiente = abierto && Number(p.ya_devuelto) !== 1;
                    prestamoDeProducto[p.id_detalle_prestamo] = idPrestamo;
                    productoPendiente[p.id_detalle_prestamo] = pendiente;
                    if (pendiente) prestamosConPendientes.add(idPrestamo);
                });
                mapasListos = true;
            } catch (e) {
                
                console.error("No se pudo preparar el filtro de productos:", e.message);
            }
        }

        function ocultar(op, permitido) {
            op.hidden = !permitido;
            op.disabled = !permitido;
        }

        
        function filtrar(limpiarSiInvalido) {
            if (!mapasListos) return;

            //devoluciones con productos pendientes
            Array.from(selDevolucion.options).forEach((op) => {
                if (op.value === "") return; // opción "Selecciona..."
                const conPendientes = prestamosConPendientes.has(prestamoDeDevolucion[op.value]);
                ocultar(op, conPendientes || op.value === devolucionEnEdicion);
            });
            if (limpiarSiInvalido && selDevolucion.selectedOptions[0]?.disabled) selDevolucion.value = "";

            // productos pendientes del mismo préstamo que la devolución elegida
            const idPrestamo = prestamoDeDevolucion[selDevolucion.value];
            Array.from(selProducto.options).forEach((op) => {
                if (op.value === "") return;
                const delPrestamo = idPrestamo !== undefined && prestamoDeProducto[op.value] === idPrestamo;
                const disponible = productoPendiente[op.value] || op.value === productoEnEdicion;
                ocultar(op, delPrestamo && disponible);
            });
            if (limpiarSiInvalido && selProducto.selectedOptions[0]?.disabled) selProducto.value = "";
        }

        async function refrescar() {
            await cargarMapas();
            filtrar(false);
        }

        selDevolucion.addEventListener("change", () => filtrar(true));

        // Al seleccionar una fila de la tabla su devolución y su producto siguen disponibles
        tbody.addEventListener("click", () => {
            devolucionEnEdicion = selDevolucion.value || null;
            productoEnEdicion = selProducto.value || null;
            filtrar(false);
        });

        // Al limpiar el formulario 
        form.addEventListener("reset", () => setTimeout(() => {
            devolucionEnEdicion = null;
            productoEnEdicion = null;
            filtrar(false);
        }, 0));

        // Cada vez que la tabla se recarga se actualiza qué sigue pendiente
        new MutationObserver(() => {
            clearTimeout(temporizador);
            temporizador = setTimeout(refrescar, 150);
        }).observe(tbody, { childList: true });

        await refrescar();
    }
};