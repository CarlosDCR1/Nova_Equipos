window.Modulos = window.Modulos || {};

const crudUsuarios = Crud.crear({
    titulo: "Mantenimiento de Usuarios",
    api: "api/admin/Usuario.php",
    id: "idUsuario",
    singular: "usuario",
    plural: "usuarios",

    fuentes: {
        empleado: {
            api: "api/admin/Empleado.php",
            valor: "id_empleado",
            texto: (e) => `${e.nombre} ${e.apellidos ?? ""}`.trim()
        },
        estudiante: {
            api: "api/admin/Estudiante.php",
            valor: "id_estudiante",
            texto: (e) => `${e.nombre_estudiante ?? e.nombre ?? ""} ${e.apellidos_estudiante ?? e.apellidos ?? ""}`.trim()
        }
    },

    busqueda: [
        { valor: "nombre", texto: "Usuario" }
    ],

    filtros: [
        { clave: "tipo", etiqueta: "Tipo", vacio: "Todos",
          opciones: [{ valor: "empleado", texto: "Empleado" }, { valor: "estudiante", texto: "Estudiante" }] },
        { clave: "is_active", etiqueta: "Estado", vacio: "Todos",
          opciones: [{ valor: "1", texto: "Activo" }, { valor: "0", texto: "Inactivo" }] }
    ],

    columnas: [
        { campo: "idUsuario", titulo: "Id" },
        { campo: "persona", titulo: "Nombre" },
        { campo: "tipo", titulo: "Tipo" },
        { campo: "nombreUsuario", titulo: "Usuario" },
        { campo: "is_active", titulo: "Estado", formato: Crud.badge },
        { campo: "ultimoAccesoUsuario", titulo: "Último acceso" },
        { campo: "intentosFallidosUsuario", titulo: "Intentos fallidos" },
        { campo: "created_at", titulo: "Creado" }
    ],

    // idEmpleado e idEstudiante son los campos que se envían al servidor,
    // pero se ocultan: la persona se elige en el combo único de abajo (ver personaUnica)
    campos: [
        { name: "idEmpleado", etiqueta: "Empleado", fuente: "empleado" },
        { name: "idEstudiante", etiqueta: "Estudiante", fuente: "estudiante" },
        { name: "nombreUsuario", etiqueta: "Usuario", maxlength: 50, required: true, autocomplete: "off" },
        { name: "contrasenaUsuario", etiqueta: "Contraseña", tipo: "password", autocomplete: "new-password" },
        { name: "is_active", etiqueta: "Estado", defecto: "1",
          opciones: [{ valor: "1", texto: "Activo" }, { valor: "0", texto: "Inactivo" }] }
    ]
});

//para un solo combo y con botones arriba para diferenciar el filtrado
function personaUnica(raiz) {
    const selEmpleado = raiz.querySelector("#f-idEmpleado");
    const selEstudiante = raiz.querySelector("#f-idEstudiante");
    const campos = raiz.querySelector(".crud-campos");
    const form = raiz.querySelector(".crud-form");
    const tbody = raiz.querySelector(".crud-tbody");

    const campoEmpleado = selEmpleado.closest(".field");
    campoEmpleado.style.display = "none";
    selEstudiante.closest(".field").style.display = "none";

    const campo = document.createElement("div");
    campo.className = "field";
    campo.innerHTML = `
        <label for="f-persona">Persona</label>
        <div style="display:flex; gap:6px; margin-bottom:6px;">
            <button type="button" class="btn btn-primary" data-tipo="empleado">Empleado</button>
            <button type="button" class="btn btn-light" data-tipo="estudiante">Estudiante</button>
        </div>
        <select id="f-persona" required></select>`;
    campos.insertBefore(campo, campoEmpleado);

    const combo = campo.querySelector("#f-persona");
    const pestanas = campo.querySelectorAll("[data-tipo]");
    let tipo = "empleado";

    const origen = () => (tipo === "empleado" ? selEmpleado : selEstudiante);

    function pintar() {
        pestanas.forEach((p) => {
            const activa = p.dataset.tipo === tipo;
            p.classList.toggle("btn-primary", activa);
            p.classList.toggle("btn-light", !activa);
        });
        combo.innerHTML = "";
        [...origen().options].forEach((o) => combo.add(new Option(o.text, o.value)));
        combo.value = origen().value;
    }

    pestanas.forEach((p) => p.addEventListener("click", () => {
        tipo = p.dataset.tipo;
        selEmpleado.value = "";
        selEstudiante.value = "";
        pintar();
    }));

    combo.addEventListener("change", () => { origen().value = combo.value; });

 
    tbody.addEventListener("click", () => {
        tipo = selEstudiante.value ? "estudiante" : "empleado";
        pintar();
    });

    form.addEventListener("reset", () => setTimeout(() => { tipo = "empleado"; pintar(); }, 0));

    pintar();
}

window.Modulos.usuarios = {
    async init(raiz) {
        await crudUsuarios.init(raiz);
        personaUnica(raiz);
    }
};