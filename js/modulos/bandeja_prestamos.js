
window.Modulos = window.Modulos || {};

window.Modulos.bandeja_prestamos = (function () {
    const API = "api/admin/Apartado.php";
    const URGENTE_MS = 2 * 60 * 60 * 1000; 

   
    async function peticion(metodo, body) {
        const res = await fetch(API, {
            method: metodo,
            headers: { "Content-Type": "application/json" },
            body: body ? JSON.stringify(body) : undefined
        });
        const json = await res.json();
        if (json.code !== 1) throw new Error(json.message || "Ocurrió un error inesperado.");
        return json;
    }

    function el(tag, props, ...hijos) {
        const nodo = Object.assign(document.createElement(tag), props || {});
        nodo.append(...hijos.filter((h) => h != null && h !== false));
        return nodo;
    }

    const aFecha = (s) => (s ? new Date(String(s).replace(" ", "T")) : null);

    function fmtFecha(s) {
        const d = aFecha(s);
        return d ? d.toLocaleString("es-GT", { day: "2-digit", month: "2-digit", year: "numeric", hour: "2-digit", minute: "2-digit" }) : "";
    }

    function quedan(ms) {
        if (ms <= 0) return "vencido";
        const min = Math.floor(ms / 60000);
        const h = Math.floor(min / 60);
        if (h >= 24) return `quedan ${Math.floor(h / 24)} d ${h % 24} h`;
        return h ? `quedan ${h} h ${min % 60} min` : `quedan ${min} min`;
    }

    // ---------- módulo ----------
    return {
        async init(raiz) {
            const aviso = el("div", { className: "ap-aviso", hidden: true });
            const cuerpo = el("div");
            const actualizar = el("button", { type: "button", className: "btn btn-light", textContent: "Actualizar", onclick: () => cargar() });

            raiz.replaceChildren(
                el("h1", { className: "page-title", textContent: "Solicitudes y apartados" }),
                aviso,
                el("div", { className: "filter-actions ap-barra" }, actualizar),
                cuerpo);

            function mostrarAviso(texto, tipo) {
                aviso.textContent = texto;
                aviso.className = "ap-aviso ap-aviso-" + tipo;
                aviso.hidden = false;
            }

            async function cargar() {
                try {
                    const { data } = await peticion("GET");
                    pintar(data.pendientes || []);
                } catch (e) {
                    cuerpo.replaceChildren(el("p", { className: "error-state", textContent: "Error al cargar: " + e.message }));
                }
            }

            // Una acción sobre un préstamo; mientras corre se bloquean los botones para evitar doble clic
            async function accionar(id, accion, textoOk, extra) {
                cuerpo.querySelectorAll("button").forEach((b) => (b.disabled = true));
                try {
                    await peticion("PUT", { id, accion, ...extra });
                    mostrarAviso(textoOk, "ok");
                } catch (e) {
                    mostrarAviso(e.message, "error");
                }
                await cargar();
            }

            function botones(p) {
                const nombre = (p.estudiante || "").trim();
                const aprobar = el("button", {
                    type: "button", className: "btn btn-primary", textContent: "Aprobar",
                    onclick: () => accionar(p.id_prestamo, "aprobar", "Solicitud aprobada: el estudiante tiene 24 horas para recoger.")
                });
                const entregar = el("button", {
                    type: "button", className: "btn btn-primary", textContent: "Entregar",
                    onclick: () => {
                        if (confirm(`¿Entregar los equipos a ${nombre}?`)) {
                            accionar(p.id_prestamo, "entregar", "Equipos entregados. El préstamo quedó activo.");
                        }
                    }
                });
                const rechazar = el("button", {
                    type: "button", className: "btn btn-danger", textContent: "Rechazar",
                    onclick: () => {
                        const motivo = prompt("Motivo del rechazo (opcional):");
                        if (motivo === null) return; // canceló el cuadro
                        accionar(p.id_prestamo, "rechazar", "Préstamo rechazado.", { motivo: motivo.trim() });
                    }
                });
                return el("div", { className: "ap-acciones" },
                    ...(p.estado === "Solicitado" ? [aprobar, rechazar] : [entregar, rechazar]));
            }

            function pintar(pendientes) {
                const ahora = Date.now();

                const filas = pendientes.map((p) => {
                    const limite = aFecha(p.apartado_hasta);
                    const ms = limite ? limite.getTime() - ahora : 0;
                    const etiqueta = p.estado === "Solicitado" ? "Responder antes de " : "Recoger antes de ";

                    return el("tr", { className: ms < URGENTE_MS ? "ap-urgente" : "" },
                        el("td", { textContent: (p.estudiante || "").trim() }),
                        el("td", { textContent: p.equipos || "" }),
                        el("td", {}, el("span", { className: "ap-badge ap-" + p.estado.toLowerCase(), textContent: p.estado })),
                        el("td", {},
                            el("div", { textContent: etiqueta + fmtFecha(p.apartado_hasta) }),
                            el("small", { className: "ap-restante", textContent: quedan(ms) })),
                        el("td", {}, botones(p)));
                });

                const tabla = el("div", { className: "table-card" },
                    el("table", { className: "data-table" },
                        el("thead", {}, el("tr", {},
                            ...["Estudiante", "Equipos", "Estado", "Plazo", "Acciones"].map((t) => el("th", { textContent: t })))),
                        el("tbody", {}, ...(filas.length
                            ? filas
                            : [el("tr", {}, el("td", { colSpan: 5, className: "empty-state", textContent: "No hay solicitudes ni apartados pendientes." }))]))));

                cuerpo.replaceChildren(tabla);
            }

            cuerpo.replaceChildren(el("p", { className: "empty-state", textContent: "Cargando solicitudes..." }));
            await cargar();
        }
    };
})();