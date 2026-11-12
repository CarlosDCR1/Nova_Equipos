(function () {
    const esc = (t) => String(t ?? "").replace(/[&<>"']/g, (c) =>
        ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]));

    const tarjeta = (titulo, valor, color = "", nota = "") => `
        <div class="inicio-tarjeta ${color}">
            <span class="inicio-tarjeta-titulo">${esc(titulo)}</span>
            <strong class="inicio-tarjeta-numero">${esc(valor)}</strong>
            ${nota ? `<span class="inicio-tarjeta-nota">${esc(nota)}</span>` : ""}
        </div>`;

    function tarjetaCategoria(c, i) {
        const cuerpo = `
            <strong class="inicio-tarjeta-numero">${esc(c.disponibles)}</strong>
            <span class="inicio-tarjeta-titulo">${esc(c.categoria)}</span>
            <span class="inicio-tarjeta-nota">${c.disponibles === 0 ? "Sin disponibilidad" : `de ${esc(c.total)} en total`}</span>`;

        if (c.disponibles === 0) {
            return `<div class="inicio-tarjeta alerta">${cuerpo}</div>`;
        }
        return `<a class="inicio-tarjeta tono-${i % 4}" href="#apartar" data-categoria="${esc(c.id_categoria)}"
                   title="Ver modelos de ${esc(c.categoria)}">${cuerpo}</a>`;
    }

    function vistaEstudiante(d) {
        if (!d.carrera) {
            return `<p class="empty-state">No se encontró tu carrera. Contacta al administrador.</p>`;
        }
        const categorias = d.categorias.length
            ? d.categorias.map(tarjetaCategoria).join("")
            : `<p class="empty-state">Aún no hay equipos asignados a tu carrera.</p>`;

        return `
            <header class="inicio-banner">
                <h2 class="inicio-titulo">Equipos disponibles para ti</h2>
                <p class="inicio-subtitulo">${esc(d.carrera)} | ${esc(d.total_disponibles)} equipos disponibles ahora</p>
            </header>
            <div class="inicio-panel">
                <div class="inicio-panel-cabecera"><span class="inicio-pestana">Resumen General</span></div>
                <div class="inicio-tarjetas">${categorias}</div>
            </div>`;
    }

    const MESES = ["Ene", "Feb", "Mar", "Abr", "May", "Jun", "Jul", "Ago", "Sep", "Oct", "Nov", "Dic"];
    const etiquetaMes = (m) => MESES[Number(String(m).slice(5, 7)) - 1] || m;

    const COLOR_ESTADO = {
        Solicitado: "#F5A524", Apartado: "#2563EB", Activo: "#00C292", Devuelto: "#94A3B8",
        Vencido: "#EF4444", Cancelado: "#CBD5E1", Rechazado: "#AB8CE4"
    };

    function escala(max) {
        const bruto = Math.max(max, 1) / 4;
        const exp = Math.pow(10, Math.floor(Math.log10(bruto)));
        const f = bruto / exp;
        const paso = (f <= 1 ? 1 : f <= 2 ? 2 : f <= 5 ? 5 : 10) * exp;
        return { paso, tope: paso * 4 };
    }

    const fechaCorta = (f) => String(f).split("-").reverse().join("/");

    function etiquetaPeriodo(fecha, porDia, n) {
        if (porDia) return String(Number(fecha.slice(8, 10)));
        const m = MESES[Number(fecha.slice(5, 7)) - 1];
        return n > 12 ? `${m} ${fecha.slice(2, 4)}` : m;
    }

    function tituloPeriodo(fecha, porDia) {
        return porDia ? fechaCorta(fecha) : `${MESES[Number(fecha.slice(5, 7)) - 1]} ${fecha.slice(0, 4)}`;
    }

    function graficaPeriodos(g) {
        const periodos = g.periodos;
        const porDia = g.agrupacion === "dia";
        const n = periodos.length;
        const W = 620, H = 270, izq = 44, der = 10, arriba = 14, abajo = 30;
        const ancho = W - izq - der, alto = H - arriba - abajo;
        const { paso, tope } = escala(Math.max(...periodos.map((m) => Math.max(m.prestamos, m.devoluciones)), 0));
        const y = (v) => arriba + alto - (v / tope) * alto;
        const grupo = ancho / n;
        const barra = Math.max(2, Math.min(22, grupo * 0.3));
        const salto = porDia ? Math.ceil(n / 16) : Math.ceil(n / (n > 12 ? 9 : 12));

        let lineas = "";
        for (let i = 0; i <= 4; i++) {
            const v = paso * i;
            lineas += `<line class="graf-linea" x1="${izq}" x2="${W - der}" y1="${y(v)}" y2="${y(v)}"/>
                       <text class="graf-eje" x="${izq - 8}" y="${y(v) + 4}" text-anchor="end">${v}</text>`;
        }

        const columnas = periodos.map((m, i) => {
            const cx = izq + grupo * i + grupo / 2;
            const titulo = tituloPeriodo(m.fecha, porDia);
            const rect = (x, v, color, nombre) =>
                `<rect x="${x}" y="${y(v)}" width="${barra}" height="${Math.max(arriba + alto - y(v), 0)}" rx="1.5" fill="${color}"><title>${titulo} · ${nombre}: ${v}</title></rect>`;
            const etiqueta = i % salto === 0
                ? `<text class="graf-eje" x="${cx}" y="${H - 10}" text-anchor="middle">${etiquetaPeriodo(m.fecha, porDia, n)}</text>`
                : "";
            return rect(cx - barra - 1, m.prestamos, "#2563EB", "Préstamos") +
                   rect(cx + 1, m.devoluciones, "#00C292", "Devoluciones") + etiqueta;
        }).join("");

        return `<svg class="graf-svg" viewBox="0 0 ${W} ${H}" role="img" aria-label="Préstamos y devoluciones">${lineas}${columnas}</svg>`;
    }

    function dona(segmentos, centro, sub) {
        const R = 70, C = 2 * Math.PI * R;
        const total = segmentos.reduce((a, s) => a + s.valor, 0);
        let acum = 0;
        const arcos = segmentos.filter((s) => s.valor > 0).map((s) => {
            const largo = (s.valor / total) * C;
            const arco = `<circle cx="100" cy="100" r="${R}" fill="none" stroke="${s.color}" stroke-width="22"
                stroke-dasharray="${largo} ${C - largo}" stroke-dashoffset="${-acum}" transform="rotate(-90 100 100)"><title>${esc(s.etiqueta)}: ${s.valor}</title></circle>`;
            acum += largo;
            return arco;
        }).join("");

        return `<svg class="graf-dona" viewBox="0 0 200 200" role="img">
                  <circle cx="100" cy="100" r="${R}" fill="none" stroke="#EEF2FA" stroke-width="22"/>
                  ${arcos}
                  <text class="graf-dona-num" x="100" y="106" text-anchor="middle">${esc(centro)}</text>
                  <text class="graf-dona-sub" x="100" y="128" text-anchor="middle">${esc(sub)}</text>
                </svg>`;
    }

    function leyenda(segmentos) {
        return `<ul class="graf-lista">${segmentos.map((s) =>
            `<li><span><i class="graf-punto" style="background:${s.color}"></i>${esc(s.etiqueta)}</span><strong>${s.valor}</strong></li>`).join("")}</ul>`;
    }

    function barrasCategorias(lista) {
        if (!lista.length) return `<p class="empty-state">Sin datos.</p>`;
        const max = Math.max(...lista.map((c) => c.total), 1);
        const filas = lista.map((c) => {
            const ancho = Math.round((c.total / max) * 1000) / 10;
            const disp = c.total ? Math.round((c.disponibles / c.total) * 1000) / 10 : 0;
            return `<li>
                <span class="graf-barra-nombre" title="${esc(c.categoria)}">${esc(c.categoria)}</span>
                <span class="graf-barra-caja"><span class="graf-barra-pista" style="width:${ancho}%"><span class="graf-barra-relleno" style="width:${disp}%"></span></span></span>
                <span class="graf-barra-valor">${c.disponibles} / ${c.total}</span>
            </li>`;
        }).join("");
        return `<ul class="graf-barras">${filas}</ul>`;
    }

    function cuerpoGraficas(d) {
        const g = d.graficas;
        const e = d.equipos;

        const segEquipos = [
            { etiqueta: "Disponibles", valor: e.disponibles, color: "#00C292" },
            { etiqueta: "Prestados", valor: e.prestados, color: "#F5A524" },
            { etiqueta: "Fuera de servicio", valor: e.no_disponibles, color: "#AB8CE4" }
        ];
        const pctDisp = e.total ? Math.round((e.disponibles / e.total) * 100) : 0;

        const segEstados = g.estados.map((x) => ({ etiqueta: x.estado, valor: x.total, color: COLOR_ESTADO[x.estado] || "#94A3B8" }));
        const totalPrestamos = segEstados.reduce((a, s) => a + s.valor, 0);

        return `
            <div class="inicio-graficas g-2-1">
                <div class="inicio-panel">
                    <div class="inicio-panel-cabecera"><span class="inicio-pestana">Préstamos y devoluciones</span></div>
                    <div class="graf-cuerpo">
                        <div class="graf-leyenda">
                            <span><i class="graf-punto" style="background:#2563EB"></i>Préstamos</span>
                            <span><i class="graf-punto" style="background:#00C292"></i>Devoluciones</span>
                            <span class="graf-nota">${fechaCorta(g.rango.desde)} al ${fechaCorta(g.rango.hasta)} · ${g.agrupacion === "dia" ? "por día" : "por mes"}</span>
                        </div>
                        ${graficaPeriodos(g)}
                    </div>
                </div>
                <div class="inicio-panel">
                    <div class="inicio-panel-cabecera"><span class="inicio-pestana">Disponibilidad de equipos</span></div>
                    <div class="graf-cuerpo">
                        ${dona(segEquipos, pctDisp + "%", "disponibles")}
                        ${leyenda(segEquipos)}
                    </div>
                </div>
            </div>
            <div class="inicio-graficas g-1-2">
                <div class="inicio-panel">
                    <div class="inicio-panel-cabecera"><span class="inicio-pestana">Estado de los préstamos</span></div>
                    <div class="graf-cuerpo">
                        ${dona(segEstados, String(totalPrestamos), "préstamos")}
                        ${leyenda(segEstados)}
                    </div>
                </div>
                <div class="inicio-panel">
                    <div class="inicio-panel-cabecera"><span class="inicio-pestana">Equipos por categoría</span></div>
                    <div class="graf-cuerpo">
                        <div class="graf-leyenda">
                            <span><i class="graf-punto" style="background:#00C292"></i>Disponibles</span>
                            <span><i class="graf-punto" style="background:#DCE6F7"></i>Prestados o fuera de servicio</span>
                        </div>
                        ${barrasCategorias(g.categorias)}
                    </div>
                </div>
            </div>`;
    }

    function seccionGraficas(d) {
        if (!d.graficas) return "";
        const hoy = new Date().toLocaleDateString("en-CA");
        return `
            <form class="inicio-panel graf-filtro">
                <div class="graf-filtro-campos">
                    <label class="graf-campo"><span>Desde</span><input type="date" name="desde" value="${esc(d.graficas.rango.desde)}" max="${hoy}" required></label>
                    <label class="graf-campo"><span>Hasta</span><input type="date" name="hasta" value="${esc(d.graficas.rango.hasta)}" max="${hoy}" required></label>
                    <button type="submit" class="btn btn-primary">Aplicar</button>
                    <button type="button" class="btn btn-light" data-accion="restablecer">Restablecer</button>
                </div>
                <p class="graf-error" role="alert"></p>
            </form>
            <div class="graf-zona">${cuerpoGraficas(d)}</div>`;
    }

    function activarFiltro(contenido) {
        const barra = contenido.querySelector(".graf-filtro");
        const zona = contenido.querySelector(".graf-zona");
        if (!barra || !zona) return;

        const desde = barra.querySelector("[name=desde]");
        const hasta = barra.querySelector("[name=hasta]");
        const error = barra.querySelector(".graf-error");
        const botones = barra.querySelectorAll("button");

        async function cargar(parametros) {
            error.textContent = "";
            botones.forEach((b) => (b.disabled = true));
            try {
                const r = await fetch("api/admin/dashboard.php" + parametros, { headers: { Accept: "application/json" } });
                const json = await r.json();
                if (!r.ok) throw new Error(json.message || "No se pudieron cargar las gráficas");
                zona.innerHTML = cuerpoGraficas(json.data);
                desde.value = json.data.graficas.rango.desde;
                hasta.value = json.data.graficas.rango.hasta;
            } catch (e) {
                error.textContent = e.message;
            } finally {
                botones.forEach((b) => (b.disabled = false));
            }
        }

        barra.addEventListener("submit", (ev) => {
            ev.preventDefault();
            if (!desde.value || !hasta.value) {
                error.textContent = "Elige la fecha inicial y la final";
                return;
            }
            if (desde.value > hasta.value) {
                error.textContent = "La fecha inicial no puede ser posterior a la final";
                return;
            }
            cargar(`?desde=${encodeURIComponent(desde.value)}&hasta=${encodeURIComponent(hasta.value)}`);
        });

        barra.querySelector("[data-accion=restablecer]").addEventListener("click", () => cargar(""));
    }

    function vistaPersonal(d) {
        const e = d.equipos, p = d.prestamos;
        const pct = (n) => (e.total ? `${Math.round((n / e.total) * 100)}% del total` : "");

        let tarjetas =
            tarjeta("Equipos en total", e.total, "", "En inventario") +
            tarjeta("Disponibles", e.disponibles, "disponible", pct(e.disponibles)) +
            tarjeta("Prestados ahora", e.prestados, "prestado", pct(e.prestados)) +
            tarjeta("Fuera de servicio", e.no_disponibles, e.no_disponibles ? "mantenimiento" : "", pct(e.no_disponibles)) +
            tarjeta("Préstamos activos", p.activos, "azul", "En curso ahora") +
            tarjeta("Préstamos vencidos", p.vencidos, p.vencidos ? "alerta" : "disponible", p.vencidos ? "Requieren devolución" : "Todo al día");

        if (d.usuarios) {
            tarjetas += tarjeta("Usuarios activos", d.usuarios.activos, "cian", `${d.usuarios.empleados} empleados · ${d.usuarios.estudiantes} estudiantes`);
        }

        const filas = d.stock.map((s) => {
            const estado = s.disponibles === 0 ? `<span class="inicio-etiqueta alerta">Sin stock</span>`
                         : s.disponibles <= 2 ? `<span class="inicio-etiqueta prestado">Stock bajo</span>`
                         : `<span class="inicio-etiqueta disponible">Disponible</span>`;
            return `<tr><td>${esc(s.modelo)}</td><td>${esc(s.categoria)}</td>
                    <td class="num">${s.disponibles}</td><td class="num">${s.total}</td><td>${estado}</td></tr>`;
        }).join("");

        const rol = d.tipo === "administrador" ? "Administrador" : "Empleado";

        return `
            <header class="inicio-banner">
                <h2 class="inicio-titulo">Resumen general</h2>
                <p class="inicio-subtitulo">${rol} | ${esc(e.disponibles)} equipos disponibles ahora</p>
            </header>
            <div class="inicio-panel">
                <div class="inicio-panel-cabecera"><span class="inicio-pestana">Equipos y préstamos</span></div>
                <div class="inicio-tarjetas">${tarjetas}</div>
            </div>
            ${seccionGraficas(d)}
            <div class="inicio-panel">
                <div class="inicio-panel-cabecera"><span class="inicio-pestana">Stock por modelo</span></div>
                <div class="inicio-tabla-caja">
                  <table class="inicio-tabla">
                    <thead><tr><th>Modelo</th><th>Categoría</th><th class="num">Disponibles</th><th class="num">Total</th><th>Estado</th></tr></thead>
                    <tbody>${filas || `<tr><td colspan="5">Sin modelos registrados.</td></tr>`}</tbody>
                  </table>
                </div>
            </div>`;
    }

    window.Modulos = window.Modulos || {};
    window.Modulos.inicio = {
        async init(contenido) {
            contenido.innerHTML = `<p class="empty-state">Cargando panel...</p>`;
            try {
                const r = await fetch("api/admin/dashboard.php", { headers: { Accept: "application/json" } });
                const json = await r.json();
                if (!r.ok) throw new Error(json.message || "Error al cargar el panel");
                const d = json.data;
                const esEstudiante = d.tipo === "estudiante";
                contenido.innerHTML = `<section class="inicio inicio-vista">${esEstudiante ? vistaEstudiante(d) : vistaPersonal(d)}</section>`;

                activarFiltro(contenido);
                contenido.querySelectorAll(".inicio-tarjeta[data-categoria]").forEach((a) => {
                    a.addEventListener("click", () => { window.categoriaInicialApartar = Number(a.dataset.categoria); });
                });
            } catch (e) {
                contenido.innerHTML = `<p class="error-state">${esc(e.message)}</p>`;
            }
        },
    };
})();