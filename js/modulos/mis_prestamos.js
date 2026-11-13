window.Modulos = window.Modulos || {};

window.Modulos.mis_prestamos = (function () {
    const API = "api/admin/Mis_Prestamos.php";
    const POR_PAGINA = 6;
    const EN_CURSO = ["Solicitado", "Apartado", "Activo", "Vencido"];

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

    function fmtDia(s) {
        const d = aFecha(s);
        return d ? d.toLocaleDateString("es-GT", { day: "2-digit", month: "2-digit", year: "numeric" }) : "";
    }

    const plural = (n, uno, varios) => `${n} ${n === 1 ? uno : varios}`;

    function tiempo(ms) {
        const min = Math.max(0, Math.floor(ms / 60000));
        const h = Math.floor(min / 60);
        const d = Math.floor(h / 24);
        if (d >= 1) return `${d} d ${h % 24} h`;
        if (h >= 1) return `${h} h ${min % 60} min`;
        return `${min} min`;
    }

    function estadoReal(p) {
        if (p.estado === "Vencido") return "Vencido";
        const limite = aFecha(p.fecha_devolucion_programada);
        return p.estado === "Activo" && limite && limite < new Date() ? "Vencido" : p.estado;
    }

    function limites(p, estado) {
        if (estado === "Solicitado" || estado === "Apartado") return [aFecha(p.fecha_prestamo), aFecha(p.apartado_hasta)];
        if (estado === "Activo" || estado === "Vencido") return [aFecha(p.fecha_entrega) || aFecha(p.fecha_prestamo), aFecha(p.fecha_devolucion_programada)];
        return [null, null];
    }

    function progreso(p, estado) {
        const [ini, fin] = limites(p, estado);
        if (!ini || !fin || fin <= ini) return null;
        return Math.min(100, Math.max(0, ((Date.now() - ini.getTime()) / (fin.getTime() - ini.getTime())) * 100));
    }

    function mensaje(p, estado) {
        const [, fin] = limites(p, estado);
        switch (estado) {
            case "Solicitado":
                return [`Esperando la aprobación de un empleado. Si no responden antes del ${fmtFecha(p.apartado_hasta)}, se cancela.`, fin && fin > new Date() ? `Quedan ${tiempo(fin - Date.now())}` : ""];
            case "Apartado":
                return [`Recógelo en el mostrador antes del ${fmtFecha(p.apartado_hasta)}.`, fin && fin > new Date() ? `Quedan ${tiempo(fin - Date.now())}` : ""];
            case "Activo":
                return [`Devuélvelo antes del ${fmtFecha(p.fecha_devolucion_programada)}.`, fin ? `Quedan ${tiempo(fin - Date.now())}` : ""];
            case "Vencido":
                return [`Debías devolverlo el ${fmtFecha(p.fecha_devolucion_programada)}. Llévalo al mostrador lo antes posible.`, fin ? `Vencido hace ${tiempo(Date.now() - fin)}` : ""];
            case "Devuelto":
                return [p.fecha_devolucion ? `Devuelto el ${fmtFecha(p.fecha_devolucion)}.` : "Devuelto.", ""];
            case "Cancelado":
                return [p.observaciones ? p.observaciones : "Este préstamo fue cancelado.", ""];
            case "Rechazado":
                return [p.observaciones ? p.observaciones : "Un empleado rechazó esta solicitud.", ""];
            default:
                return ["", ""];
        }
    }

    const ETIQUETA_DEVOLUCION = { BUENO: ["Devuelto", "mp-ok"], DANADO: ["Devuelto con daños", "mp-aviso"], PERDIDO: ["Perdido", "mp-error"] };

    function equipoFila(e) {
        const etiqueta = e.devuelto ? ETIQUETA_DEVOLUCION[e.estado_devolucion] || ["Devuelto", "mp-ok"] : null;
        return el("div", { className: "mp-equipo" },
            el("div", { className: "mp-equipo-texto" },
                e.marca ? el("small", { className: "mp-marca", textContent: e.marca }) : null,
                el("strong", { textContent: e.modelo }),
                el("small", { textContent: "Código " + e.codigo })),
            etiqueta ? el("span", { className: "mp-pastilla " + etiqueta[1], textContent: etiqueta[0] }) : null);
    }

    return {
        async init(raiz) {
            const vista = { pestana: "curso", mostrar: POR_PAGINA };
            let datos = { prestamos: [], abiertos: 0, max: 3 };

            const aviso = el("div", { className: "ap-aviso", hidden: true });
            const resumen = el("div", { className: "mp-resumen" });
            const pestanas = el("div", { className: "mp-pestanas" });
            const lista = el("div", { className: "mp-lista" });

            raiz.replaceChildren(
                el("div", { className: "mp-encabezado" },
                    el("h1", { className: "page-title", textContent: "Mis préstamos" }),
                    el("a", { className: "btn btn-primary mp-enlace", href: "#apartar", textContent: "Apartar equipo" })),
                aviso, resumen, pestanas, lista);

            function mostrarAviso(texto, tipo) {
                aviso.textContent = texto;
                aviso.className = "ap-aviso ap-aviso-" + tipo;
                aviso.hidden = false;
            }

            async function cargar() {
                try {
                    const { data } = await peticion("GET");
                    datos = data;
                    pintar();
                } catch (e) {
                    resumen.replaceChildren();
                    pestanas.replaceChildren();
                    lista.replaceChildren(el("p", { className: "error-state", textContent: "Error al cargar: " + e.message }));
                }
            }

            async function cancelar(id) {
                if (!confirm("¿Cancelar este préstamo?")) return;
                lista.querySelectorAll("button").forEach((b) => (b.disabled = true));
                try {
                    await peticion("PUT", { id, accion: "cancelar" });
                    mostrarAviso("Préstamo cancelado.", "ok");
                } catch (e) {
                    mostrarAviso(e.message, "error");
                }
                await cargar();
            }

            function equiposPendientes(estados) {
                return datos.prestamos
                    .filter((p) => estados.includes(estadoReal(p)))
                    .reduce((n, p) => n + p.equipos.filter((e) => !e.devuelto).length, 0);
            }

            function dato(clase, numero, titulo, nota) {
                return el("div", { className: "mp-dato " + clase },
                    el("strong", { textContent: numero }),
                    el("span", { textContent: titulo }),
                    el("small", { textContent: nota }));
            }

            function pintarResumen() {
                const enPoder = equiposPendientes(["Activo", "Vencido"]);
                const porRecoger = equiposPendientes(["Apartado"]);
                const enRevision = equiposPendientes(["Solicitado"]);
                const vencidos = equiposPendientes(["Vencido"]);
                resumen.replaceChildren(
                    dato("mp-dato-azul", enPoder, "En tu poder", `Tienes ${datos.abiertos} de ${datos.max} equipos`),
                    dato("mp-dato-verde", porRecoger, "Por recoger", "Apartados vigentes"),
                    dato("mp-dato-ambar", enRevision, "En revisión", "Esperan aprobación"),
                    dato(vencidos ? "mp-dato-rojo" : "mp-dato-gris", vencidos, "Vencidos", vencidos ? "Devuélvelos lo antes posible" : "Todo al día"));
            }

            function pintarPestanas() {
                const enCurso = datos.prestamos.filter((p) => EN_CURSO.includes(estadoReal(p))).length;
                const historial = datos.prestamos.length - enCurso;
                const boton = (clave, texto, total) => el("button", {
                    type: "button",
                    className: "mp-pestana" + (vista.pestana === clave ? " mp-pestana-activa" : ""),
                    textContent: `${texto} (${total})`,
                    onclick: () => { vista.pestana = clave; vista.mostrar = POR_PAGINA; pintarPestanas(); pintarLista(); }
                });
                pestanas.replaceChildren(boton("curso", "En curso", enCurso), boton("historial", "Historial", historial));
            }

            function tarjeta(p) {
                const estado = estadoReal(p);
                const [texto, tiempoTexto] = mensaje(p, estado);
                const pct = progreso(p, estado);
                const puedeCancelar = ["Solicitado", "Apartado"].includes(p.estado);

                return el("article", { className: "mp-prestamo mp-borde-" + estado.toLowerCase() },
                    el("header", { className: "mp-prestamo-cabecera" },
                        el("div", { className: "mp-prestamo-titulo" },
                            el("strong", { textContent: "Préstamo #" + p.id_prestamo }),
                            el("span", { className: "ap-badge ap-" + estado.toLowerCase(), textContent: estado })),
                        el("small", { textContent: "Registrado el " + fmtDia(p.fecha_prestamo) + " · " + plural(p.equipos.length, "equipo", "equipos") })),
                    el("div", { className: "mp-equipos" }, ...p.equipos.map(equipoFila)),
                    el("footer", { className: "mp-prestamo-pie" },
                        el("div", { className: "mp-prestamo-estado" },
                            el("p", { textContent: texto }),
                            tiempoTexto ? el("strong", { className: "mp-tiempo" + (estado === "Vencido" ? " mp-tiempo-rojo" : ""), textContent: tiempoTexto }) : null,
                            pct !== null ? el("div", { className: "mp-barra" }, el("div", { className: "mp-barra-relleno mp-relleno-" + estado.toLowerCase(), style: `width:${pct}%` })) : null),
                        puedeCancelar
                            ? el("button", { type: "button", className: "btn btn-danger", textContent: "Cancelar", onclick: () => cancelar(p.id_prestamo) })
                            : null));
            }

            function pintarLista() {
                const enCurso = vista.pestana === "curso";
                const filtrados = datos.prestamos.filter((p) => EN_CURSO.includes(estadoReal(p)) === enCurso);

                if (!filtrados.length) {
                    lista.replaceChildren(el("div", { className: "mp-vacio" },
                        el("strong", { textContent: enCurso ? "No tienes préstamos en curso" : "Todavía no tienes historial" }),
                        el("p", { textContent: enCurso ? "Cuando apartes un equipo, aparecerá aquí con su plazo y su estado." : "Aquí verás los préstamos que ya devolviste, cancelaste o que fueron rechazados." }),
                        enCurso ? el("a", { className: "btn btn-primary", href: "#apartar", textContent: "Apartar equipo" }) : null));
                    return;
                }

                const visibles = enCurso ? filtrados : filtrados.slice(0, vista.mostrar);
                const pie = !enCurso && filtrados.length > visibles.length
                    ? el("div", { className: "filter-actions" },
                        el("button", {
                            type: "button", className: "btn btn-light",
                            textContent: `Ver más (${filtrados.length - visibles.length} restantes)`,
                            onclick: () => { vista.mostrar += POR_PAGINA; pintarLista(); }
                        }))
                    : null;

                lista.replaceChildren(...visibles.map(tarjeta), ...(pie ? [pie] : []));
            }

            function pintar() {
                pintarResumen();
                pintarPestanas();
                pintarLista();
            }

            lista.replaceChildren(el("p", { className: "empty-state", textContent: "Cargando tus préstamos..." }));
            await cargar();
        }
    };
})();