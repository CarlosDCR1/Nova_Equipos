window.Crud = {
   
    badge(valor) {
        const span = document.createElement("span");
        const v = String(valor ?? "").toUpperCase();
        const activo = v === "ACTIVO" || v === "1";
        //const activo = (valor || "").toUpperCase() === "ACTIVO";
        span.className = "badge " + (activo ? "activo" : "inactivo");
        span.textContent = activo ? "Activo" : "Inactivo";
        return span;
    },

    crear(opcionesCrud) {
        const PLANTILLA = `
            <h1 class="page-title crud-titulo"></h1>

            <div class="filter-card">
                <div class="filter-grid crud-buscador"></div>
                <div class="filter-actions">
                    <button type="button" class="btn btn-primary" data-accion="buscar">Buscar</button>
                    <button type="button" class="btn btn-light" data-accion="limpiar-busqueda">Limpiar búsqueda</button>
                </div>
            </div>

            <div class="table-card">
                <table class="data-table">
                    <thead><tr class="crud-thead"></tr></thead>
                    <tbody class="crud-tbody"></tbody>
                </table>
            </div>

            <div class="crud-paginacion" style="display:none; align-items:center; justify-content:flex-end; flex-wrap:wrap; gap:12px; margin:12px 0 20px;">
                <span class="crud-pag-info"></span>
                <button type="button" class="btn btn-light" data-accion="pag-anterior">Anterior</button>
                <span class="crud-pag-actual"></span>
                <button type="button" class="btn btn-light" data-accion="pag-siguiente">Siguiente</button>
            </div>

            <form class="filter-card crud-form">
                <div class="filter-grid crud-campos"></div>
                <div class="filter-actions">
                    <button type="button" class="btn btn-primary" data-accion="agregar">+ Agregar</button>
                    <button type="button" class="btn btn-primary" data-accion="editar" disabled>Editar</button>
                    <button type="button" class="btn btn-danger" data-accion="eliminar" disabled>Eliminar</button>
                    <button type="button" class="btn btn-light" data-accion="limpiar">Limpiar</button>
                </div>
            </form>`;

        return {
            async init(raiz) {
                raiz.innerHTML = PLANTILLA;

                const $ = (s) => raiz.querySelector(s);
                const btn = (a) => raiz.querySelector(`[data-accion="${a}"]`);

                const form = $(".crud-form");
                const tbody = $(".crud-tbody");
                const contBuscador = $(".crud-buscador");
                const contCampos = $(".crud-campos");
                const contPaginacion = $(".crud-paginacion");

                const fuentes = {};      
                let idActual = "";
                let filaActiva = null;
                let temporizador;

                // --------------------------------Paginacion-------------------- ----------
                // aqui definimos de cuantos registros estara la paginacion
                const POR_PAGINA = opcionesCrud.porPagina || 10;
                let registros = [];   // todos los registros 
                let pagina = 1;       // pagina actual

                $(".crud-titulo").textContent = opcionesCrud.titulo;

                // ---------- API ----------
                async function peticion(metodo, body, params) {
                    const url = params && Object.keys(params).length
                        ? `${opcionesCrud.api}?${new URLSearchParams(params)}` : opcionesCrud.api;
                    const res = await fetch(url, {
                        method: metodo,
                        headers: { "Content-Type": "application/json" },
                        body: body ? JSON.stringify(body) : undefined
                    });
                    const json = await res.json();
                    if (json.code !== 1) throw new Error(json.message || "Ocurrió un error inesperado.");
                    return json;
                }

                // aqui cargamos las fuentes de datos de todos los cruds para los select
                async function cargarFuentes() {
                   
                    await Promise.all(Object.entries(opcionesCrud.fuentes || {}).map(async ([clave, fuente]) => {
                        try {
                            const res = await fetch(fuente.api);
                            const json = await res.json();
                            if (json.code !== 1) throw new Error(json.message);    
                            fuentes[clave] = json.data || [];
                        } catch (e) {
                            fuentes[clave] = [];
                            console.error(`No se pudo cargar ${clave}:`, e.message);
                        }
                    }));
                }

                const leerTexto = (f, r) => typeof f.texto === "function" ? f.texto(r) : r[f.texto];

                function opcionesDe(def) {
                    if (def.fuente) {
                        const f = opcionesCrud.fuentes[def.fuente];
                        return (fuentes[def.fuente] || []).map((r) => ({ valor: r[f.valor], texto: leerTexto(f, r) }));
                        //return (fuentes[def.fuente] || []).map((r) => ({ valor: r[f.valor], texto: r[f.texto] }));
                    }
                    return def.opciones || [];
                   
                }

                function textoDeFuente(clave, id) {
                const f = opcionesCrud.fuentes[clave];
                const r = (fuentes[clave] || []).find((x) => String(x[f.valor]) === String(id));
                return r ? leerTexto(f, r) : id;
            }

                // Awui creamos ya lo que va en el html pero manipulado de forma general para todos los crud

                function crearCampo(def, prefijo) {
                    const id = `${prefijo}-${def.name || def.clave}`;
                    const wrap = document.createElement("div");
                    wrap.className = "field";

                    const label = document.createElement("label");
                    label.htmlFor = id;
                    label.textContent = def.etiqueta;

                    let control;
                    if (def.tipo === "select" || def.fuente || def.opciones) {
                        control = document.createElement("select");
                        if (def.vacio !== undefined) control.add(new Option(def.vacio, ""));
                        opcionesDe(def).forEach((o) => control.add(new Option(o.texto, o.valor)));
                    } else {
                        control = document.createElement("input");
                        control.type = def.tipo || "text";
                        if (def.maxlength) control.maxLength = def.maxlength;
                        if (def.placeholder) control.placeholder = def.placeholder;
                       
                        if (def.pattern) control.pattern = def.pattern;
                        if (def.min) control.min = def.min;
                        if (def.max) control.max = def.max;
                        if (def.mensaje) control.title = def.mensaje;
                    }
                    control.id = id;
                    if (def.name) control.name = def.name;
                    if (def.required) control.required = true;

                    wrap.append(label, control);
                    return { wrap, control };
                }

                // para el buscador y los filtros definidos en el admin o controlador 
                const bCampo = document.createElement("select");
                const bTexto = document.createElement("input");
                const filtrosExtra = [];

                function construirBuscador() {
                    const wCampo = document.createElement("div");
                    wCampo.className = "field";
                    wCampo.innerHTML = `<label>Buscar por</label>`;
                    (opcionesCrud.busqueda || []).forEach((o) => bCampo.add(new Option(o.texto, o.valor)));
                    wCampo.appendChild(bCampo);

                    const wTexto = document.createElement("div");
                    wTexto.className = "field";
                    wTexto.innerHTML = `<label>Texto a buscar</label>`;
                    bTexto.type = "search";
                    bTexto.placeholder = "Escribe para buscar...";
                    wTexto.appendChild(bTexto);

                    contBuscador.append(wCampo, wTexto);

                    (opcionesCrud.filtros || []).forEach((def) => {
                        const { wrap, control } = crearCampo({ ...def, name: def.clave }, "b");
                        control.dataset.clave = def.clave;
                        filtrosExtra.push(control);
                        contBuscador.appendChild(wrap);
                    });
                }

                // Formulario
                const controlesForm = [];
                function construirFormulario() {
                    opcionesCrud.campos.forEach((def) => {
                        const d = { ...def };
                        if (d.fuente && d.vacio === undefined) d.vacio = `Selecciona ${d.etiqueta.toLowerCase()}...`;
                        const { wrap, control } = crearCampo(d, "f");
                        controlesForm.push({ def, control });
                        contCampos.appendChild(wrap);
                    });
                }

                // encabezado 
                function construirCabecera() {
                    const thead = $(".crud-thead");
                    opcionesCrud.columnas.forEach((c) => {
                        const th = document.createElement("th");
                        th.textContent = c.titulo;
                        thead.appendChild(th);
                    });
                }

                // tabla 
                function mensaje(clase, texto) {
                    tbody.innerHTML = "";
                    contPaginacion.style.display = "none";
                    const tr = document.createElement("tr");
                    const td = document.createElement("td");
                    td.colSpan = opcionesCrud.columnas.length;
                    td.className = clase;
                    td.textContent = texto;
                    tr.appendChild(td);
                    tbody.appendChild(tr);
                }

                function celda(col, fila) {
                    const td = document.createElement("td");
                    let valor = fila[col.campo];
                    if (col.fuente) valor = textoDeFuente(col.fuente, valor);
                    if (col.formato) {
                        const r = col.formato(valor, fila);
                        r instanceof Node ? td.appendChild(r) : (td.textContent = r ?? "");
                    } else {
                        td.textContent = valor ?? "";
                    }
                    return td;
                }

                function filtros() {
                    const f = {};
                    if (bTexto.value.trim()) f[bCampo.value] = bTexto.value.trim();
                    filtrosExtra.forEach((c) => { if (c.value) f[c.dataset.clave] = c.value; });
                    return f;
                }

                // ---------- PAGINACIÓN: funciones ----------
                const totalPaginas = () => Math.max(1, Math.ceil(registros.length / POR_PAGINA));

                // Actualiza los textos y botones
                // si la tablano tiene tantos registros los va a ocultar 
                function actualizarPaginacion() {
                    const total = registros.length;
                    if (total <= POR_PAGINA) {
                        contPaginacion.style.display = "none";
                        return;
                    }
                    const desde = (pagina - 1) * POR_PAGINA + 1;
                    const hasta = Math.min(pagina * POR_PAGINA, total);
                    $(".crud-pag-info").textContent = `Mostrando ${desde}–${hasta} de ${total}`;
                    $(".crud-pag-actual").textContent = `Página ${pagina} de ${totalPaginas()}`;
                    btn("pag-anterior").disabled = pagina <= 1;
                    btn("pag-siguiente").disabled = pagina >= totalPaginas();
                    contPaginacion.style.display = "flex";
                }

                // Coloca en la tabla solo los registros de la página actual
                function dibujarPagina() {
                    tbody.innerHTML = "";
                    filaActiva = null;
                    if (!registros.length) return mensaje("empty-state", `No se encontraron ${opcionesCrud.plural}.`);

                    const inicio = (pagina - 1) * POR_PAGINA;
                    registros.slice(inicio, inicio + POR_PAGINA).forEach((fila) => {
                        const tr = document.createElement("tr");
                        tr.style.cursor = "pointer";
                        opcionesCrud.columnas.forEach((c) => tr.appendChild(celda(c, fila)));
                        tr.addEventListener("click", () => seleccionar(fila, tr));
                        tbody.appendChild(tr);
                    });
                    actualizarPaginacion();
                }

                function irAPagina(n) {
                    const nueva = Math.min(Math.max(1, n), totalPaginas());
                    if (nueva === pagina) return;
                    pagina = nueva;
                    if (idActual) limpiar(); // la fila seleccionada ya no se ve: se limpia el formulario
                    dibujarPagina();
                }

                // en una búsqueda vuelve a la página 1
                async function cargar(conservarPagina = false) {
                    mensaje("empty-state", `Cargando ${opcionesCrud.plural}...`);
                    try {
                        const { data } = await peticion("GET", null, filtros());
                        registros = data;
                        pagina = conservarPagina ? Math.min(pagina, totalPaginas()) : 1;
                        dibujarPagina();
                    } catch (e) {
                        mensaje("error-state", "Error al cargar: " + e.message);
                    }
                }

                // --------LIMPIAR ----------
                function estadoBotones(haySeleccion) {
                    btn("agregar").disabled = haySeleccion;
                    btn("editar").disabled = !haySeleccion;
                    btn("eliminar").disabled = !haySeleccion;
                }

                function seleccionar(fila, tr) {
                    if (filaActiva) filaActiva.classList.remove("selected");
                    filaActiva = tr;
                    tr.classList.add("selected");

                    idActual = fila[opcionesCrud.id] ?? "";
                    controlesForm.forEach(({ def, control }) => {
                        control.value = fila[def.name] ?? def.defecto ?? "";
                    });
                    estadoBotones(true);
                }

                function limpiar() {
                    form.reset();
                    idActual = "";
                    controlesForm.forEach(({ def, control }) => {
                        if (def.defecto !== undefined) control.value = def.defecto;
                    });
                    if (filaActiva) filaActiva.classList.remove("selected");
                    filaActiva = null;
                    estadoBotones(false);
                }

                function leerFormulario() {
                    const datos = {};
                    controlesForm.forEach(({ def, control }) => (datos[def.name] = control.value));
                    return datos;
                }

                // ---------- ACCIONES ----------
                async function ejecutar(metodo, body) {
                    try {
                        await peticion(metodo, body);
                        limpiar();
                        await cargar(true);
                    } catch (e) {
                        alert(e.message);
                    }
                }

                function agregar() {
                    if (!form.reportValidity()) return;
                    ejecutar("POST", leerFormulario());
                }

                function editar() {
                    if (!idActual) return alert(`Selecciona un registro de la tabla.`);
                    if (!form.reportValidity()) return;
                    ejecutar("PUT", { id: idActual, ...leerFormulario() });
                }

                function eliminar() {
                    if (!idActual) return alert(`Selecciona un registro de la tabla.`);
                    if (!confirm(`¿Eliminar este ${opcionesCrud.singular}?`)) return;
                    ejecutar("DELETE", { id: idActual });
                }

                // ---------- EVENTOS ----------
                form.addEventListener("submit", (e) => e.preventDefault());
                
                btn("agregar").addEventListener("click", agregar);
                btn("editar").addEventListener("click", editar);
                btn("eliminar").addEventListener("click", eliminar);
                btn("limpiar").addEventListener("click", limpiar);
                btn("buscar").addEventListener("click", () => cargar());
                btn("pag-anterior").addEventListener("click", () => irAPagina(pagina - 1));
                btn("pag-siguiente").addEventListener("click", () => irAPagina(pagina + 1));
                btn("limpiar-busqueda").addEventListener("click", () => {
                    bCampo.selectedIndex = 0;
                    bTexto.value = "";
                    filtrosExtra.forEach((c) => (c.value = ""));
                    cargar();
                });

                bTexto.addEventListener("input", () => {
                    clearTimeout(temporizador);
                    temporizador = setTimeout(() => cargar(), 350);
                });

                // ---------- INICIO ----------
                mensaje("empty-state", `Cargando ${opcionesCrud.plural}...`);
                
                await cargarFuentes();
                construirBuscador();
                construirFormulario();
                construirCabecera();
                [bCampo, ...filtrosExtra].forEach((c) => c.addEventListener("change", () => cargar()));
                limpiar();
                await cargar();
            }
        };
    }
};