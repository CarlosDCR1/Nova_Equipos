const API = "api/admin/Empleado.php";

const form = document.getElementById("form-empleado");
const tabla = document.getElementById("tabla-empleados");
const btnGuardar = document.getElementById("btn-guardar");
const btnLimpiar = document.getElementById("btn-limpiar");

const campos = [
    "nombre", "apellidos", "correo_institucional", "telefono_empleado",
    "dpi", "estado_civil", "fecha_nacimiento", "estado_empleado"
];

async function peticion(metodo, body) {
    const res = await fetch(API, {
        method: metodo,
        headers: { "Content-Type": "application/json" },
        body: body ? JSON.stringify(body) : undefined
    });

    const json = await res.json();
    if (json.code !== 1) {
        throw new Error(json.message || "Ocurrió un error inesperado.");
    }
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

async function cargar() {
    tabla.innerHTML = `<tr><td colspan="8" class="empty-state">Cargando empleados...</td></tr>`;

    try {
        const { data } = await peticion("GET");

        tabla.innerHTML = "";

        if (!data.length) {
            tabla.innerHTML = `<tr><td colspan="8" class="empty-state">No hay empleados registrados.</td></tr>`;
            return;
        }

        data.forEach((emp) => {
            const tr = document.createElement("tr");

            tr.appendChild(celda(emp.id_empleado));
            tr.appendChild(celda(emp.nombre));
            tr.appendChild(celda(emp.apellidos));
            tr.appendChild(celda(emp.correo_institucional));
            tr.appendChild(celda(emp.telefono_empleado));

            const tdEstado = document.createElement("td");
            tdEstado.appendChild(badgeEstado(emp.estado_empleado));
            tr.appendChild(tdEstado);

            tr.appendChild(celda(emp.fecha_creacion));

            const tdAcciones = document.createElement("td");
            tdAcciones.className = "row-actions";

            const bEditar = document.createElement("button");
            bEditar.textContent = "Editar";
            bEditar.onclick = () => editar(emp);

            const bEliminar = document.createElement("button");
            bEliminar.textContent = "Eliminar";
            bEliminar.className = "delete";
            bEliminar.onclick = () => eliminar(emp.id_empleado);

            tdAcciones.append(bEditar, bEliminar);
            tr.appendChild(tdAcciones);

            tabla.appendChild(tr);
        });

    } catch (e) {
        tabla.innerHTML = `<tr><td colspan="8" class="error-state">Error al cargar: ${e.message}</td></tr>`;
    }
}

function editar(emp) {
    document.getElementById("id_empleado").value = emp.id_empleado ?? "";
    document.getElementById("f-nombre").value = emp.nombre ?? "";
    document.getElementById("f-apellidos").value = emp.apellidos ?? "";
    document.getElementById("f-correo").value = emp.correo_institucional ?? "";
    document.getElementById("f-telefono").value = emp.telefono_empleado ?? "";
    document.getElementById("f-dpi").value = emp.dpi ?? "";
    document.getElementById("f-estado-civil").value = emp.estado_civil ?? "";
    document.getElementById("f-fecha-nac").value = emp.fecha_nacimiento ?? "";
    document.getElementById("f-estado").value = emp.estado_empleado ?? "ACTIVO";

    btnGuardar.textContent = "Guardar cambios";

    window.scrollTo({ top: 0, behavior: "smooth" });
}

function limpiar() {
    form.reset();
    document.getElementById("id_empleado").value = "";
    document.getElementById("f-estado").value = "ACTIVO";
    btnGuardar.textContent = "+ Agregar";
}

async function eliminar(id) {
    if (!confirm("¿Eliminar este empleado?")) return;

    try {
        await peticion("DELETE", { id });
        await cargar();
    } catch (e) {
        alert(e.message);
    }
}

form.addEventListener("submit", async (e) => {
    e.preventDefault();

    const datos = { id: document.getElementById("id_empleado").value };
    campos.forEach((c) => {
        datos[c] = document.querySelector(`[name="${c}"]`).value;
    });

    const metodo = datos.id ? "PUT" : "POST";

    try {
        await peticion(metodo, datos);
        limpiar();
        await cargar();
    } catch (err) {
        alert(err.message);
    }
});

btnLimpiar.addEventListener("click", limpiar);

// Menú hamburguesa en móvil
const menuBtn = document.getElementById("menuBtn");
const sidebar = document.getElementById("sidebar");
menuBtn?.addEventListener("click", () => sidebar.classList.toggle("open"));

cargar();
