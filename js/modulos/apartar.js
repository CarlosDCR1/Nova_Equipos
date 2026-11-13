
window.Modulos = window.Modulos || {};

window.Modulos.apartar = (function () {
    const API = "api/admin/Apartado.php";
    const MAX_POR_DEFECTO = 3;
    const POR_PAGINA = 12; 

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

    const normalizar = (s) => String(s || "").normalize("NFD").replace(/[\u0300-\u036f]/g, "").toLowerCase().trim();
    const aFecha = (s) => (s ? new Date(String(s).replace(" ", "T")) : null);

    function fmtFecha(s) {
        const d = aFecha(s);
        return d ? d.toLocaleString("es-GT", { day: "2-digit", month: "2-digit", year: "numeric", hour: "2-digit", minute: "2-digit" }) : "";
    }

    function esVencido(p) {
        if (p.estado === "Vencido") return true;
        const limite = aFecha(p.fecha_devolucion_programada);
        return p.estado === "Activo" && limite && limite < new Date();
    }

    function badgeEstado(p) {
        const texto = esVencido(p) ? "Vencido" : p.estado;
        return el("span", { className: "ap-badge ap-" + texto.toLowerCase(), textContent: texto });
    }

    function plazo(p) {
        switch (p.estado) {
            case "Apartado":   return "Recoger antes de " + fmtFecha(p.apartado_hasta);
            case "Solicitado": return "Esperando aprobación";
            case "Activo":
            case "Vencido":    return "Devolver antes de " + fmtFecha(p.fecha_devolucion_programada);
            default:           return "";
        }
    }

    const plural = (n, uno, varios) => `${n} ${n === 1 ? uno : varios}`;

   
    return {
        async init(raiz) {
            const carrito = new Map();                             
            const vista = { categoria: window.categoriaInicialApartar ?? null, texto: "", mostrar: POR_PAGINA };
            delete window.categoriaInicialApartar; // se usa una sola vez
            let modelos = [];                                   
            let restante = 0;                                      
            let puedeApartar = false;

            const aviso = el("div", { className: "ap-aviso", hidden: true });
            const cuerpo = el("div");
            raiz.replaceChildren(el("h1", { className: "page-title", textContent: "Apartar equipos" }), aviso, cuerpo);

           
            const catalogo = el("div", { className: "ap-catalogo" });
            const barra = el("div", { className: "ap-carrito", hidden: true });

            function mostrarAviso(texto, tipo) {
                aviso.textContent = texto;
                aviso.className = "ap-aviso ap-aviso-" + tipo;
                aviso.hidden = false;
            }

            const totalCarrito = () => [...carrito.values()].reduce((a, v) => a + v.cantidad, 0);
            const clave = (m, apr) => `${m.id_modelo_producto}:${apr ? 1 : 0}`;
            
            const unidades = (m, apr) => (apr ? m.con_aprobacion : m.disponibles - m.con_aprobacion);

            async function cargar() {
                try {
                    const { data } = await peticion("GET");
                    pintar(data);
                } catch (e) {
                    cuerpo.replaceChildren(el("p", { className: "error-state", textContent: "Error al cargar: " + e.message }));
                }
            }

            async function apartar() {
                const pedidos = [...carrito.values()].map((v) => ({ id_modelo_producto: v.id, requiere_aprobacion: v.apr, cantidad: v.cantidad }));
                barra.querySelectorAll("button").forEach((b) => (b.disabled = true));
                try {
                    const { message } = await peticion("POST", { modelos: pedidos });
                    carrito.clear();
                    mostrarAviso(message, "ok");
                } catch (e) {
                    mostrarAviso(e.message, "error");
                }
                await cargar(); 
                window.scrollTo({ top: 0, behavior: "smooth" });
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

         
            function cambiar(m, apr, delta) {
                const k = clave(m, apr);
                const n = Math.max(0, (carrito.get(k)?.cantidad || 0) + delta);
                n ? carrito.set(k, { id: m.id_modelo_producto, apr, cantidad: n }) : carrito.delete(k);
                renderCatalogo();
                renderBarra();
            }

            function renderBarra() {
                const total = totalCarrito();
                barra.hidden = total === 0;
                if (!total) return;

                let pedirAprobacion = false;
                const lineas = [...carrito.values()].map((v) => {
                    const m = modelos.find((x) => x.id_modelo_producto === v.id);
                    pedirAprobacion = pedirAprobacion || v.apr;
                    return el("li", {}, `${m.modelo} × ${v.cantidad}`,
                        el("small", { textContent: v.apr ? " · requiere aprobación" : " · entrega inmediata" }));
                });

                barra.replaceChildren(
                    el("div", { className: "ap-carrito-info" },
                        el("strong", { textContent: `${plural(total, "equipo seleccionado", "equipos seleccionados")} · puedes apartar hasta ${restante}` }),
                        el("ul", {}, ...lineas),
                        pedirAprobacion
                            ? el("small", { className: "ap-nota", textContent: "Lo que requiere aprobación espera a que un empleado la apruebe; el resto se aparta al instante." })
                            : null),
                    el("div", { className: "ap-carrito-acciones" },
                        el("button", { type: "button", className: "btn btn-light", textContent: "Vaciar", onclick: () => { carrito.clear(); renderCatalogo(); renderBarra(); } }),
                        el("button", { type: "button", className: "btn btn-primary", textContent: `Apartar (${total})`, onclick: apartar })));
            }

          
            function renderCatalogo() {
                if (!modelos.length) {
                    catalogo.replaceChildren(el("p", { className: "empty-state", textContent: "No hay equipos disponibles por ahora." }));
                    return;
                }

                const texto = normalizar(vista.texto);

                
                if (!vista.categoria && !texto) {
                    const categorias = new Map();
                    modelos.forEach((m) => {
                        const c = categorias.get(m.id_categoria_producto) || { id: m.id_categoria_producto, nombre: m.categoria, equipos: 0, modelos: 0 };
                        c.equipos += m.disponibles;
                        c.modelos += 1;
                        categorias.set(c.id, c);
                    });
                    catalogo.replaceChildren(
                        el("div", { className: "ap-rejilla-categorias" }, ...[...categorias.values()].map((c) =>
                            el("button", {
                                type: "button", className: "ap-categoria-card",
                                onclick: () => { vista.categoria = c.id; vista.mostrar = POR_PAGINA; renderCatalogo(); }
                            },
                            el("strong", { textContent: c.nombre }),
                            el("span", { textContent: `${plural(c.equipos, "equipo disponible", "equipos disponibles")} · ${plural(c.modelos, "modelo", "modelos")}` })))));
                    return;
                }

                
                const filtrados = modelos.filter((m) =>
                    (!vista.categoria || m.id_categoria_producto === vista.categoria) &&
                    (!texto || normalizar(m.modelo + " " + m.categoria).includes(texto)));

                const nombreCategoria = vista.categoria ? (modelos.find((m) => m.id_categoria_producto === vista.categoria) || {}).categoria : null;
                const titulo = nombreCategoria
                    ? `${nombreCategoria} · ${plural(filtrados.length, "modelo", "modelos")}`
                    : `${plural(filtrados.length, "resultado", "resultados")} para «${vista.texto.trim()}»`;

                const encabezado = el("div", { className: "ap-encabezado" },
                    el("button", {
                        type: "button", className: "btn btn-light", textContent: "← Categorías",
                        onclick: () => { vista.categoria = null; vista.texto = ""; buscador.value = ""; vista.mostrar = POR_PAGINA; renderCatalogo(); }
                    }),
                    el("strong", { textContent: titulo }));

                if (!filtrados.length) {
                    catalogo.replaceChildren(encabezado, el("p", { className: "empty-state", textContent: "No encontramos modelos con ese criterio." }));
                    return;
                }

                const visibles = filtrados.slice(0, vista.mostrar);
                const total = totalCarrito();

               
                const variante = (m, apr) => {
                    const libres = unidades(m, apr);
                    if (!libres) return null;
                    const cantidad = carrito.get(clave(m, apr))?.cantidad || 0;
                    return el("div", { className: "ap-variante" },
                        el("div", { className: "ap-variante-texto" },
                            el("span", { className: apr ? "ap-etiqueta" : "ap-inmediata", textContent: apr ? "Requiere aprobación" : "Entrega inmediata" }),
                            el("small", { textContent: plural(libres, "disponible", "disponibles") })),
                        el("div", { className: "ap-cantidad" },
                            el("button", { type: "button", className: "ap-paso", textContent: "−", disabled: cantidad === 0, title: "Quitar uno", onclick: () => cambiar(m, apr, -1) }),
                            el("span", { className: "ap-cantidad-valor", textContent: cantidad }),
                            el("button", {
                                type: "button", className: "ap-paso", textContent: "+", title: "Agregar uno",
                                disabled: !puedeApartar || cantidad >= libres || total >= restante,
                                onclick: () => cambiar(m, apr, 1)
                            })));
                };

                const rejilla = el("div", { className: "ap-rejilla" }, ...visibles.map((m) => {
                    const elegido = carrito.has(clave(m, false)) || carrito.has(clave(m, true));
                    const descripcion = (m.descripcion || "").trim();
                    return el("div", { className: "ap-modelo" + (elegido ? " ap-modelo-elegido" : "") },
                        el("div", { className: "ap-modelo-texto" },
                            m.marca ? el("small", { className: "ap-marca", textContent: m.marca }) : null,
                            el("strong", { textContent: m.modelo }),
                            descripcion ? el("small", { className: "ap-descripcion", textContent: descripcion, title: descripcion }) : null),
                        el("div", { className: "ap-variantes" }, variante(m, false), variante(m, true)));
                }));

                const pie = filtrados.length > visibles.length
                    ? el("div", { className: "filter-actions" },
                        el("button", {
                            type: "button", className: "btn btn-light",
                            textContent: `Ver más (${filtrados.length - visibles.length} restantes)`,
                            onclick: () => { vista.mostrar += POR_PAGINA; renderCatalogo(); }
                        }))
                    : null;

               
                catalogo.replaceChildren(...[encabezado, rejilla, pie].filter(Boolean));
            }

           
            const buscador = el("input", {
                type: "search", className: "ap-buscador", placeholder: "Buscar equipo o categoría...", autocomplete: "off",
                oninput: () => { vista.texto = buscador.value; vista.mostrar = POR_PAGINA; renderCatalogo(); }
            });

           
            function pintar(data) {
                const prestamos = data.mis_prestamos || [];
                const max = data.max ?? MAX_POR_DEFECTO;
                const abiertos = Number.isInteger(data.abiertos) ? data.abiertos : 0;
                restante = Math.max(0, max - abiertos);
                const hayVencido = prestamos.some(esVencido);
                puedeApartar = restante > 0 && !hayVencido;

                modelos = (data.modelos || []).map((m) => ({
                    ...m,
                    id_modelo_producto: Number(m.id_modelo_producto),
                    id_categoria_producto: Number(m.id_categoria_producto),
                    disponibles: Number(m.disponibles),
                    con_aprobacion: Number(m.con_aprobacion)
                }));

                
                carrito.forEach((v, k) => {
                    const m = modelos.find((x) => x.id_modelo_producto === v.id);
                    const libres = m ? unidades(m, v.apr) : 0;
                    if (!libres) return carrito.delete(k);
                    if (v.cantidad > libres) v.cantidad = libres;
                });
                if (vista.categoria && !modelos.some((m) => m.id_categoria_producto === vista.categoria)) vista.categoria = null;

                const secciones = [
                    el("div", { className: "filter-card ap-contador" },
                        el("strong", { textContent: `Tienes ${abiertos} de ${max} equipos` }),
                        el("span", {
                            textContent: restante
                                ? ` · puedes apartar ${restante} más`
                                : " · llegaste al límite: devuelve o cancela alguno para pedir más"
                        }))
                ];
                if (hayVencido) {
                    secciones.push(el("div", {
                        className: "ap-aviso ap-aviso-error",
                        textContent: "Tienes un préstamo vencido. Devuélvelo antes de apartar otro equipo."
                    }));
                }

                secciones.push(
                    el("div", { className: "filter-card" },
                        el("div", { className: "ap-barra-busqueda" },
                            el("h2", { className: "ap-subtitulo", textContent: "Equipos disponibles para tu carrera" }),
                            buscador),
                        catalogo));

                
                const filas = prestamos.map((p) => {
                    const puedeCancelar = ["Solicitado", "Apartado"].includes(p.estado);
                    return el("tr", {},
                        el("td", { textContent: p.id_prestamo }),
                        el("td", {}, badgeEstado(p)),
                        el("td", {}, ...(p.equipos || "").split(/(?<=\)), /).map((t) => el("div", { textContent: t }))),
                        el("td", { textContent: plazo(p) }),
                        el("td", {}, puedeCancelar
                            ? el("button", { type: "button", className: "btn btn-danger", textContent: "Cancelar", onclick: () => cancelar(p.id_prestamo) })
                            : ""));
                });

                secciones.push(
                    el("h2", { className: "ap-subtitulo", textContent: "Mis préstamos" }),
                    el("div", { className: "table-card" },
                        el("table", { className: "data-table" },
                            el("thead", {}, el("tr", {}, ...["Id", "Estado", "Equipos", "Plazo", ""].map((t) => el("th", { textContent: t })))),
                            el("tbody", {}, ...(filas.length
                                ? filas
                                : [el("tr", {}, el("td", { colSpan: 5, className: "empty-state", textContent: "Todavía no tienes préstamos." }))])))),
                    barra);

                cuerpo.replaceChildren(...secciones);
                buscador.value = vista.texto;
                renderCatalogo();
                renderBarra();
            }

            cuerpo.replaceChildren(el("p", { className: "empty-state", textContent: "Cargando equipos..." }));
            await cargar();
        }
    };
})();