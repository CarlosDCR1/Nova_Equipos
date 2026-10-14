const API = "api/admin/Usuario.php";
const API_EMPLEADOS = "api/admin/Empleado.php";

const form = document.getElementById("form-usuario");
const tabla = document.getElementById("tabla-usuarios");
const selectEmpleado = document.getElementById("f-empleado");
const btnGuardar = document.getElementById("btn-guardar");
const btnLimpiar = document.getElementById("btn-limpiar");
const passHint = document.getElementById("pass-hint");

async function peticion(url, metodo, body) {
    const res = await fetch(url, {
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
    td.textContent = texto ?? "—";
    return td;
}

function badgeEstado(activo) {
    const span = document.createElement("span");
    const esActivo = Number(activo) === 1;
    span.className = "badge " + (esActivo ? "activo" : "inactivo");
    span.textContent = esActivo ? "Activo" : "Inactivo";
    return span;
}


async function cargarEmpleados() {
    try {
        const { data } = await peticion(API_EMPLEADOS, "GET");
        selectEmpleado.innerHTML = '<option value="">Selecciona un empleado...</option>' +
            data.map(e => `<option value="${e.id_empleado}">${e.nombre} ${e.apellidos ?? ""}</option>`).join("");
    } catch (e) {
        selectEmpleado.innerHTML = '<option value="">No se pudieron cargar los empleados</option>';
    }
}

async function cargar() {
    tabla.innerHTML = `<tr><td colspan="8" class="empty-state">Cargando usuarios...</td></tr>`;

    try {
        const { data } = await peticion(API, "GET");

        tabla.innerHTML = "";

        if (!data.length) {
            tabla.innerHTML = `<tr><td colspan="8" class="empty-state">No hay usuarios registrados.</td></tr>`;
            return;
        }

        data.forEach((u) => {
            const tr = document.createElement("tr");

            tr.appendChild(celda(u.idUsuario));
            tr.appendChild(celda(`${u.empleado_nombre} ${u.empleado_apellidos ?? ""}`));
            tr.appendChild(celda(u.nombreUsuario));

            const tdEstado = document.createElement("td");
            tdEstado.appendChild(badgeEstado(u.is_active));
            tr.appendChild(tdEstado);

            tr.appendChild(celda(u.ultimoAccesoUsuario));
            tr.appendChild(celda(u.intentosFallidosUsuario));
            tr.appendChild(celda(u.created_at));

            const tdAcciones = document.createElement("td");
            tdAcciones.className = "row-actions";

            const bEditar = document.createElement("button");
            bEditar.textContent = "Editar";
            bEditar.onclick = () => editar(u);

            const bEliminar = document.createElement("button");
            bEliminar.textContent = "Eliminar";
            bEliminar.className = "delete";
            bEliminar.onclick = () => eliminar(u.idUsuario);

            tdAcciones.append(bEditar, bEliminar);
            tr.appendChild(tdAcciones);

            tabla.appendChild(tr);
        });

    } catch (e) {
        tabla.innerHTML = `<tr><td colspan="8" class="error-state">Error al cargar: ${e.message}</td></tr>`;
    }
}

function editar(u) {
    document.getElementById("id_usuario").value = u.idUsuario ?? "";
    selectEmpleado.value = u.idEmpleado ?? "";
    document.getElementById("f-usuario").value = u.nombreUsuario ?? "";
    document.getElementById("f-password").value = "";
    document.getElementById("f-estado").value = String(u.is_active ?? 1);

    passHint.style.display = "block";
    btnGuardar.textContent = "Guardar cambios";

    window.scrollTo({ top: 0, behavior: "smooth" });
}

function limpiar() {
    form.reset();
    document.getElementById("id_usuario").value = "";
    document.getElementById("f-estado").value = "1";
    passHint.style.display = "none";
    btnGuardar.textContent = "+ Agregar";
}

async function eliminar(id) {
    if (!confirm("¿Eliminar este usuario?")) return;

    try {
        await peticion(API, "DELETE", { id });
        await cargar();
    } catch (e) {
        alert(e.message);
    }
}

form.addEventListener("submit", async (e) => {
    e.preventDefault();

    const esEdicion = !!document.getElementById("id_usuario").value;

    if (!esEdicion && !document.getElementById("f-password").value) {
        alert("La contraseña es obligatoria para un usuario nuevo.");
        return;
    }

    const datos = {
        id: document.getElementById("id_usuario").value,
        idEmpleado: selectEmpleado.value,
        nombreUsuario: document.getElementById("f-usuario").value,
        contrasenaUsuario: document.getElementById("f-password").value,
        is_active: document.getElementById("f-estado").value,
    };

    const metodo = datos.id ? "PUT" : "POST";

    try {
        await peticion(API, metodo, datos);
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

cargarEmpleados();
cargar();
