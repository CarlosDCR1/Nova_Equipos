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

// Envoltorio: después de dibujar el CRUD, el desplegable "Producto prestado" solo muestra
//   1) los productos del mismo préstamo que la devolución elegida, y
//   2) los productos que todavía NO se han devuelto (el que se está editando sí se conserva).
window.Modulos.detalle_devolucion = {
    async init(raiz) {
        await detalleDevolucionCrud.init(raiz);

        const selDevolucion = raiz.querySelector("#f-id_devolucion");
        const selProducto = raiz.querySelector("#f-id_detalle_prestamo");
        const form = raiz.querySelector(".crud-form");
        const tbody = raiz.querySelector(".crud-tbody");
        if (!selDevolucion || !selProducto) return;

        let prestamoDeDevolucion = {}; // id_devolucion -> id_prestamo
        let prestamoDeProducto = {};   // id_detalle_prestamo -> id_prestamo
        let productoDevuelto = {};     // id_detalle_prestamo -> true si ya tiene devolución
        let productoEnEdicion = null;  // producto del registro seleccionado en la tabla
        let mapasListos = false;
        let temporizador;

        // Consulta las listas actuales para saber a qué préstamo pertenece cada cosa y qué ya se devolvió
        async function cargarMapas() {
            try {
                const [devoluciones, productos] = await Promise.all([
                    fetch("api/admin/Detalle_Devolucion.php?lista=devoluciones").then((r) => r.json()),
                    fetch("api/admin/Detalle_Devolucion.php?lista=productos").then((r) => r.json())
                ]);
                prestamoDeDevolucion = {};
                prestamoDeProducto = {};
                productoDevuelto = {};
                (devoluciones.data || []).forEach((d) => (prestamoDeDevolucion[d.id_devolucion] = String(d.id_prestamo)));
                (productos.data || []).forEach((p) => {
                    prestamoDeProducto[p.id_detalle_prestamo] = String(p.id_prestamo);
                    productoDevuelto[p.id_detalle_prestamo] = Number(p.ya_devuelto) === 1;
                });
                mapasListos = true;
            } catch (e) {
                // Sin los mapas no se filtra; el servidor igual valida y rechaza los casos incorrectos
                console.error("No se pudo preparar el filtro de productos:", e.message);
            }
        }

        // Oculta (y deshabilita) los productos que no se pueden elegir
        function filtrar(limpiarSiInvalido) {
            if (!mapasListos) return;
            const idPrestamo = prestamoDeDevolucion[selDevolucion.value];
            Array.from(selProducto.options).forEach((op) => {
                if (op.value === "") return; // opción "Selecciona..."
                const delPrestamo = idPrestamo !== undefined && prestamoDeProducto[op.value] === idPrestamo;
                const disponible = !productoDevuelto[op.value] || op.value === productoEnEdicion;
                const permitido = delPrestamo && disponible;
                op.hidden = !permitido;
                op.disabled = !permitido;
            });
            if (limpiarSiInvalido && selProducto.selectedOptions[0]?.disabled) selProducto.value = "";
        }

        async function refrescar() {
            await cargarMapas();
            filtrar(false);
        }

        selDevolucion.addEventListener("change", () => filtrar(true));

        // Al seleccionar una fila de la tabla (para editar): su producto sigue disponible
        tbody.addEventListener("click", () => {
            productoEnEdicion = selProducto.value || null;
            filtrar(false);
        });

        // Al limpiar el formulario (también después de agregar, editar o eliminar)
        form.addEventListener("reset", () => setTimeout(() => {
            productoEnEdicion = null;
            filtrar(false);
        }, 0));

        // Cada vez que la tabla se recarga, se actualiza qué productos ya fueron devueltos
        new MutationObserver(() => {
            clearTimeout(temporizador);
            temporizador = setTimeout(refrescar, 150);
        }).observe(tbody, { childList: true });

        await refrescar();
    }
};