const API = "api/admin/Carrera.php";
const API_FACULTAD = "api/admin/Facultad.php";

const form = document.getElementById("form-carrera");
const tabla = document.getElementById("tabla-carreras");
const btnAgregar = document.getElementById("btn-agregar");
const btnEditar = document.getElementById("btn-editar");
const btnEliminar = document.getElementById("btn-eliminar");
const btnLimpiar = document.getElementById("btn-limpiar");
const btnBuscar = document.getElementById("btn-buscar");
const btnLimpiarBusqueda = document.getElementById("btn-limpiar-busqueda");

const idInput = document.getElementById("id_carrera");
const bTexto = document.getElementById("b-texto");
const bFacultad = document.getElementById("b-facultad");
const fFacultad = document.getElementById("f-facultad");

const campos = ["id_facultad", "nombre_carrera"];

let facultades = {};   
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

function mensajeTabla(clase, texto) {
    tabla.innerHTML = "";
    const tr = document.createElement("tr");
    const td = document.createElement("td");
    td.colSpan = 3;
    td.className = clase;
    td.textContent = texto;
    tr.appendChild(td);
    tabla.appendChild(tr);
}

// ---------- FACULTADES ----------
async function cargarFacultades() {
    try {
        const res = await fetch(API_FACULTAD);
        const json = await res.json();
        (json.data || []).forEach((f) => {
            facultades[f.id_facultad] = f.nombre_facultad;
            [fFacultad, bFacultad].forEach((sel) => sel.add(new Option(f.nombre_facultad, f.id_facultad)));
        });
    } catch (e) {
        console.error("No se pudieron cargar las facultades:", e.message);
    }
}

// ---------- BÚSQUEDA / LISTADO ----------
function obtenerFiltros() {
    const filtros = {};
    if (bTexto.value.trim()) filtros.nombre = bTexto.value.trim();
    if (bFacultad.value) filtros.id_facultad = bFacultad.value;
    return filtros;
}

async function cargar() {
    mensajeTabla("empty-state", "Cargando carreras...");
    try {
        const { data } = await peticion("GET", null, obtenerFiltros());
        tabla.innerHTML = "";
        filaActiva = null;

        if (!data.length) return mensajeTabla("empty-state", "No se encontraron carreras.");

        data.forEach((car) => {
            const tr = document.createElement("tr");
            tr.style.cursor = "pointer";
            tr.appendChild(celda(car.id_carrera));
            tr.appendChild(celda(facultades[car.id_facultad] ?? car.id_facultad));
            tr.appendChild(celda(car.nombre_carrera));
            tr.addEventListener("click", () => seleccionar(car, tr));
            tabla.appendChild(tr);
        });
    } catch (e) {
        mensajeTabla("error-state", "Error al cargar: " + e.message);
    }
}

// ---------- SELECCIÓN ----------
function seleccionar(car, tr) {
    if (filaActiva) filaActiva.classList.remove("selected");
    filaActiva = tr;
    tr.classList.add("selected");

    idInput.value = car.id_carrera ?? "";
    fFacultad.value = car.id_facultad ?? "";
    document.getElementById("f-nombre").value = car.nombre_carrera ?? "";

    btnAgregar.disabled = true;
    btnEditar.disabled = false;
    btnEliminar.disabled = false;
}

function limpiar() {
    form.reset();
    idInput.value = "";
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
    if (!idInput.value) return alert("Selecciona una carrera de la tabla.");
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
    if (!idInput.value) return alert("Selecciona una carrera de la tabla.");
    if (!confirm("¿Eliminar esta carrera?")) return;
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
    bTexto.value = "";
    bFacultad.value = "";
    cargar();
});

let temporizador;
bTexto.addEventListener("input", () => {
    clearTimeout(temporizador);
    temporizador = setTimeout(cargar, 350);
});
bFacultad.addEventListener("change", cargar);

// Menú hamburguesa en móvil
const menuBtn = document.getElementById("menuBtn");
const sidebar = document.getElementById("sidebar");
menuBtn?.addEventListener("click", () => sidebar.classList.toggle("open"));

// Inicio
limpiar();
cargarFacultades().then(cargar);