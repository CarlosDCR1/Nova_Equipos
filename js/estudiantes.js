const API = "api/admin/Estudiante.php";
const API_CARRERA = "api/admin/Carrera.php";

const form = document.getElementById("form-estudiante");
const tabla = document.getElementById("tabla-estudiantes");
const btnAgregar = document.getElementById("btn-agregar");
const btnEditar = document.getElementById("btn-editar");
const btnEliminar = document.getElementById("btn-eliminar");
const btnLimpiar = document.getElementById("btn-limpiar");
const btnBuscar = document.getElementById("btn-buscar");
const btnLimpiarBusqueda = document.getElementById("btn-limpiar-busqueda");

const idInput = document.getElementById("id_estudiante");
const bCampo = document.getElementById("b-campo");
const bTexto = document.getElementById("b-texto");
const bCarrera = document.getElementById("b-carrera");
const bEstado = document.getElementById("b-estado");
const fCarrera = document.getElementById("f-carrera");

const campos = ["id_carrera", "nombre_estudiante", "apellidos_estudiante", "carnet_estudiante", "numero_estudiante", "correo_institucional", "estado_estudiante"];

let carreras = {};     
let filaActiva = null;  

async function peticion(metodo, body, params) {
    const url = params && Object.keys(params).length ? `${API}?${new URLSearchParams(params)}` : API;
    const res = await fetch(url, {
        method: metodo,
        headers: { "Content-Type": "application/json" },
        body: body ? JSON.stringify(body) : undefined
    });
    const json = await res.json();
    if (json.code !== 1) throw new Error(json.message || "Ocurrió un error inesperado.");
    return json;
}

function celda(texto) {
    const td = document.createElement("td");
    td.textContent = texto ?? "";
    return td;
}

function badgeEstado(estado) {
    const span = document.createElement("span");
    const esActivo = (estado || "").toUpperCase() === "ACTIVO";
    span.className = "badge " + (esActivo ? "activo" : "inactivo");
    span.textContent = esActivo ? "Activo" : "Inactivo";
    return span;
}

function mensajeTabla(clase, texto) {
    tabla.innerHTML = "";
    const tr = document.createElement("tr");
    const td = document.createElement("td");
    td.colSpan = 9;
    td.className = clase;
    td.textContent = texto;
    tr.appendChild(td);
    tabla.appendChild(tr);
}

// ---------- CARRERAS ----------
async function cargarCarreras() {
    try {
        const res = await fetch(API_CARRERA);
        const json = await res.json();
        (json.data || []).forEach((c) => {
            carreras[c.id_carrera] = c.nombre_carrera;
            [fCarrera, bCarrera].forEach((sel) => sel.add(new Option(c.nombre_carrera, c.id_carrera)));
        });
    } catch (e) {
        console.error("No se pudieron cargar las carreras:", e.message);
    }
}

// ---------- BÚSQUEDA / LISTADO ----------
function obtenerFiltros() {
    const filtros = {};
    if (bTexto.value.trim()) filtros[bCampo.value] = bTexto.value.trim();
    if (bCarrera.value) filtros.id_carrera = bCarrera.value;
    if (bEstado.value) filtros.estado = bEstado.value;
    return filtros;
}

async function cargar() {
    mensajeTabla("empty-state", "Cargando estudiantes...");
    try {
        const { data } = await peticion("GET", null, obtenerFiltros());
        tabla.innerHTML = "";
        filaActiva = null;

        if (!data.length) return mensajeTabla("empty-state", "No se encontraron estudiantes.");

        data.forEach((est) => {
            const tr = document.createElement("tr");
            tr.style.cursor = "pointer";
            tr.appendChild(celda(est.id_estudiante));
            tr.appendChild(celda(carreras[est.id_carrera] ?? est.id_carrera));
            tr.appendChild(celda(est.nombre_estudiante));
            tr.appendChild(celda(est.apellidos_estudiante));
            tr.appendChild(celda(est.carnet_estudiante));
            tr.appendChild(celda(est.numero_estudiante));
            tr.appendChild(celda(est.correo_institucional));
            const tdEstado = document.createElement("td");
            tdEstado.appendChild(badgeEstado(est.estado_estudiante));
            tr.appendChild(tdEstado);
            tr.appendChild(celda(est.fecha_creacion));

            tr.addEventListener("click", () => seleccionar(est, tr));
            tabla.appendChild(tr);
        });
    } catch (e) {
        mensajeTabla("error-state", "Error al cargar: " + e.message);
    }
}

// ---------- SELECCIÓN ----------
function seleccionar(est, tr) {
    if (filaActiva) filaActiva.classList.remove("selected");
    filaActiva = tr;
    tr.classList.add("selected");

    idInput.value = est.id_estudiante ?? "";
    document.getElementById("f-carrera").value = est.id_carrera ?? "";
    document.getElementById("f-nombre").value = est.nombre_estudiante ?? "";
    document.getElementById("f-apellidos").value = est.apellidos_estudiante ?? "";
    document.getElementById("f-carnet").value = est.carnet_estudiante ?? "";
    document.getElementById("f-numero").value = est.numero_estudiante ?? "";
    document.getElementById("f-correo").value = est.correo_institucional ?? "";
    document.getElementById("f-estado").value = est.estado_estudiante ?? "ACTIVO";

    btnAgregar.disabled = true;
    btnEditar.disabled = false;
    btnEliminar.disabled = false;
}

function limpiar() {
    form.reset();
    idInput.value = "";
    document.getElementById("f-estado").value = "ACTIVO";
    if (filaActiva) filaActiva.classList.remove("selected");
    filaActiva = null;
    btnAgregar.disabled = false;
    btnEditar.disabled = true;
    btnEliminar.disabled = true;
}

function leerFormulario() {
    const datos = {};
    campos.forEach((c) => (datos[c] = form.querySelector(`[name="${c}"]`).value));
    return datos;
}

// ---------- ACCIONES ----------
async function agregar() {
    if (!form.reportValidity()) return;
    try {
        await peticion("POST", leerFormulario());
        limpiar();
        await cargar();
    } catch (e) {
        alert(e.message);
    }
}

async function editar() {
    if (!idInput.value) return alert("Selecciona un estudiante de la tabla.");
    if (!form.reportValidity()) return;
    try {
        await peticion("PUT", { id: idInput.value, ...leerFormulario() });
        limpiar();
        await cargar();
    } catch (e) {
        alert(e.message);
    }
}

async function eliminar() {
    if (!idInput.value) return alert("Selecciona un estudiante de la tabla.");
    if (!confirm("¿Eliminar este estudiante?")) return;
    try {
        await peticion("DELETE", { id: idInput.value });
        limpiar();
        await cargar();
    } catch (e) {
        alert(e.message);
    }
}

// ---------- EVENTOS ----------
form.addEventListener("submit", (e) => e.preventDefault()); 

btnAgregar.addEventListener("click", agregar);
btnEditar.addEventListener("click", editar);
btnEliminar.addEventListener("click", eliminar);
btnLimpiar.addEventListener("click", limpiar);

btnBuscar.addEventListener("click", cargar);
btnLimpiarBusqueda.addEventListener("click", () => {
    bCampo.selectedIndex = 0;
    bTexto.value = "";
    bCarrera.value = "";
    bEstado.value = "";
    cargar();
});


let temporizador;
bTexto.addEventListener("input", () => {
    clearTimeout(temporizador);
    temporizador = setTimeout(cargar, 350);
});
[bCampo, bCarrera, bEstado].forEach((el) => el.addEventListener("change", cargar));


const menuBtn = document.getElementById("menuBtn");
const sidebar = document.getElementById("sidebar");
menuBtn?.addEventListener("click", () => sidebar.classList.toggle("open"));

// Inicio
limpiar();
cargarCarreras().then(cargar);