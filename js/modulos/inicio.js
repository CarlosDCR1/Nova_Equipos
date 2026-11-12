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

               
                contenido.querySelectorAll(".inicio-tarjeta[data-categoria]").forEach((a) => {
                    a.addEventListener("click", () => { window.categoriaInicialApartar = Number(a.dataset.categoria); });
                });
            } catch (e) {
                contenido.innerHTML = `<p class="error-state">${esc(e.message)}</p>`;
            }
        },
    };
})();