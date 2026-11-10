
window.Modulos = window.Modulos || {};

window.Modulos.apartar = (function () {
    const API = "api/admin/Apartado.php";
    const MAX_POR_DEFECTO = 3;


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

    // Activo con la fecha ya pasada también cuenta como vencido 
    function esVencido(p) {
        if (p.estado === "Vencido") return true;
        const limite = aFecha(p.fecha_devolucion_programada);
        return p.estado === "Activo" && limite && limite < new Date();
    }

    // Equipos que el estudiante tiene 
    function contarAbiertos(data) {
        if (Number.isInteger(data.abiertos)) return data.abiertos;
        const ahora = new Date();
        return (data.mis_prestamos || [])
            .filter((p) =>
                ["Activo", "Vencido"].includes(p.estado) ||
                (["Solicitado", "Apartado"].includes(p.estado) && aFecha(p.apartado_hasta) >= ahora))
            .reduce((n, p) => n + (String(p.equipos || "").match(/\([^()]*\)(?=,\s|$)/g) || []).length, 0);
    }

    function badgeEstado(p) {
        const vencido = esVencido(p);
        const texto = vencido ? "Vencido" : p.estado;
        return el("span", { className: "ap-badge ap-" + texto.toLowerCase(), textContent: texto });
    }

    function plazo(p) {
        switch (p.estado) {
            case "Apartado":  return "Recoger antes de " + fmtFecha(p.apartado_hasta);
            case "Solicitado": return "Esperando aprobación";
            case "Activo":
            case "Vencido":   return "Devolver antes de " + fmtFecha(p.fecha_devolucion_programada);
            default:          return "";
        }
    }


    return {
        async init(raiz) {
            const seleccion = new Set();

            const aviso = el("div", { className: "ap-aviso", hidden: true });
            const cuerpo = el("div");
            raiz.replaceChildren(el("h1", { className: "page-title", textContent: "Apartar equipos" }), aviso, cuerpo);

            function mostrarAviso(texto, tipo) {
                aviso.textContent = texto;
                aviso.className = "ap-aviso ap-aviso-" + tipo;
                aviso.hidden = false;
            }

            async function cargar() {
                try {
                    const { data } = await peticion("GET");
                    pintar(data);
                } catch (e) {
                    cuerpo.replaceChildren(el("p", { className: "error-state", textContent: "Error al cargar: " + e.message }));
                }
            }

            async function apartar() {
                try {
                    const { message } = await peticion("POST", { equipos: [...seleccion] });
                    seleccion.clear();
                    mostrarAviso(message, "ok");
                } catch (e) {
                    mostrarAviso(e.message, "error");
                }
                await cargar(); // si otro estudiante se llevó el equipo, el catálogo se actualiza
            }

            async function cancelar(id) {
                if (!confirm("¿Cancelar este préstamo?")) return;
                try {
                    await peticion("PUT", { id, accion: "cancelar" });
                    mostrarAviso("Préstamo cancelado.", "ok");
                } catch (e) {
                    mostrarAviso(e.message, "error");
                }
                await cargar();
            }

            function pintar(data) {
                const disponibles = data.disponibles || [];
                const prestamos = data.mis_prestamos || [];
                const max = data.max ?? MAX_POR_DEFECTO;
                const abiertos = contarAbiertos(data);
                const restante = Math.max(0, max - abiertos);
                const hayVencido = prestamos.some(esVencido);
                const puedeApartar = restante > 0 && !hayVencido;

                // lo seleccionado que ya no está disponible se descarta
                const idsDisponibles = new Set(disponibles.map((e) => e.id_equipo));
                [...seleccion].forEach((id) => { if (!idsDisponibles.has(id)) seleccion.delete(id); });

                // --- contador ---
                const contador = el("div", { className: "filter-card ap-contador" },
                    el("strong", { textContent: `Tienes ${abiertos} de ${max} equipos` }),
                    el("span", {
                        textContent: restante
                            ? ` · puedes apartar ${restante} más`
                            : " · llegaste al límite: devuelve o cancela alguno para pedir más"
                    }));

                const secciones = [contador];
                if (hayVencido) {
                    secciones.push(el("div", {
                        className: "ap-aviso ap-aviso-error",
                        textContent: "Tienes un préstamo vencido. Devuélvelo antes de apartar otro equipo."
                    }));
                }

                // --- catálogo ---
                const checks = [];
                const boton = el("button", { type: "button", className: "btn btn-primary", onclick: apartar });

                function actualizar() {
                    const lleno = seleccion.size >= restante;
                    checks.forEach((c) => { c.disabled = !puedeApartar || (lleno && !c.checked); });
                    boton.disabled = !puedeApartar || seleccion.size === 0;
                    boton.textContent = seleccion.size ? `Apartar (${seleccion.size})` : "Apartar";
                }

                const catalogo = el("div", { className: "filter-card" },
                    el("h2", { className: "ap-subtitulo", textContent: "Equipos disponibles para tu carrera" }));

                if (!disponibles.length) {
                    catalogo.append(el("p", { className: "empty-state", textContent: "No hay equipos disponibles por ahora." }));
                }

                const porCategoria = new Map();
                disponibles.forEach((e) => {
                    if (!porCategoria.has(e.categoria)) porCategoria.set(e.categoria, []);
                    porCategoria.get(e.categoria).push(e);
                });

                porCategoria.forEach((equipos, categoria) => {
                    const rejilla = el("div", { className: "ap-rejilla" });
                    equipos.forEach((e) => {
                        const check = el("input", { type: "checkbox", checked: seleccion.has(e.id_equipo) });
                        check.addEventListener("change", () => {
                            check.checked ? seleccion.add(e.id_equipo) : seleccion.delete(e.id_equipo);
                            actualizar();
                        });
                        checks.push(check);

                        const tarjeta = el("label", { className: "ap-equipo" },
                            check,
                            el("span", { className: "ap-equipo-texto" },
                                el("strong", { textContent: e.modelo }),
                                el("small", { textContent: "Código " + e.codigo_inventario })));
                        if (e.requiere_aprobacion) {
                            tarjeta.append(el("span", { className: "ap-etiqueta", textContent: "Requiere aprobación" }));
                        }
                        rejilla.append(tarjeta);
                    });
                    catalogo.append(el("h3", { className: "ap-categoria", textContent: categoria }), rejilla);
                });

                catalogo.append(el("div", { className: "filter-actions" }, boton));
                secciones.push(catalogo);

                // --- mis préstamos ---
                const filas = prestamos.map((p) => {
                    const puedeCancelar = ["Solicitado", "Apartado"].includes(p.estado);
                    return el("tr", {},
                        el("td", { textContent: p.id_prestamo }),
                        el("td", {}, badgeEstado(p)),
                        el("td", { textContent: p.equipos || "" }),
                        el("td", { textContent: plazo(p) }),
                        el("td", {}, puedeCancelar
                            ? el("button", { type: "button", className: "btn btn-danger", textContent: "Cancelar", onclick: () => cancelar(p.id_prestamo) })
                            : ""));
                });

                const tabla = el("div", { className: "table-card" },
                    el("table", { className: "data-table" },
                        el("thead", {}, el("tr", {},
                            ...["Id", "Estado", "Equipos", "Plazo", ""].map((t) => el("th", { textContent: t })))),
                        el("tbody", {}, ...(filas.length
                            ? filas
                            : [el("tr", {}, el("td", { colSpan: 5, className: "empty-state", textContent: "Todavía no tienes préstamos." }))]))));

                secciones.push(el("h2", { className: "ap-subtitulo", textContent: "Mis préstamos" }), tabla);

                cuerpo.replaceChildren(...secciones);
                actualizar();
            }

            cuerpo.replaceChildren(el("p", { className: "empty-state", textContent: "Cargando equipos..." }));
            await cargar();
        }
    };
})();