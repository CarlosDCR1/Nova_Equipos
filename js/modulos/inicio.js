(function () {
    const esc = (t) => String(t ?? "").replace(/[&<>"']/g, (c) =>
        ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]));

    const tarjeta = (titulo, valor, color = "", nota = "") => `
        <div class="inicio-tarjeta ${color}">
            <span class="inicio-tarjeta-titulo">${esc(titulo)}</span>
            <strong class="inicio-tarjeta-numero">${esc(valor)}</strong>
            ${nota ? `<span class="inicio-tarjeta-nota">${esc(nota)}</span>` : ""}
        </div>`;

    function vistaEstudiante(d) {
        if (!d.carrera) {
            return `<p class="empty-state">No se encontró tu carrera. Contacta al administrador.</p>`;
        }
        const categorias = d.categorias.length
            ? d.categorias.map((c) => tarjeta(
                c.categoria, c.disponibles,
                c.disponibles === 0 ? "alerta" : "disponible",
                c.disponibles === 0 ? "Sin disponibilidad" : `de ${c.total} en total`
              )).join("")
            : `<p class="empty-state">Aún no hay equipos asignados a tu carrera.</p>`;

        return `
            <h2 class="inicio-titulo">Equipos disponibles para ti</h2>
            <p class="inicio-subtitulo">${esc(d.carrera)} | ${esc(d.total_disponibles)} equipos disponibles ahora</p>
            <div class="inicio-tarjetas">${categorias}</div>`;
    }

    function vistaPersonal(d) {
        const e = d.equipos, p = d.prestamos;
        let tarjetas =
            tarjeta("Equipos en total", e.total) +
            tarjeta("Disponibles", e.disponibles, "disponible") +
            tarjeta("Prestados ahora", e.prestados, "prestado") +
            tarjeta("Fuera de servicio", e.no_disponibles, e.no_disponibles ? "mantenimiento" : "") +
            tarjeta("Préstamos activos", p.activos, "azul") +
            tarjeta("Préstamos vencidos", p.vencidos, p.vencidos ? "alerta" : "disponible");

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

        return `
            <h2 class="inicio-titulo">Resumen general</h2>
            <div class="inicio-tarjetas">${tarjetas}</div>
            <h3 class="inicio-seccion">Stock por modelo</h3>
            <div class="inicio-tabla-caja">
              <table class="inicio-tabla">
                <thead><tr><th>Modelo</th><th>Categoría</th><th class="num">Disponibles</th><th class="num">Total</th><th>Estado</th></tr></thead>
                <tbody>${filas || `<tr><td colspan="5">Sin modelos registrados.</td></tr>`}</tbody>
              </table>
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
                contenido.innerHTML = `<section class="inicio">${d.tipo === "estudiante" ? vistaEstudiante(d) : vistaPersonal(d)}</section>`;
            } catch (e) {
                contenido.innerHTML = `<p class="error-state">${esc(e.message)}</p>`;
            }
        },
    };
})();