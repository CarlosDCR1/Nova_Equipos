const contenido = document.getElementById("contenido");
const sidebar = document.getElementById("sidebar");
const enlaces = sidebar.querySelectorAll(".nav-link");
const brandLogo = document.getElementById('brandLogo');
// menu para un boton
const menuBtn = document.getElementById("menuBtn");
const mqMovil = window.matchMedia("(max-width: 960px)");



window.Modulos = window.Modulos || {};
const cargados = new Set();

document.querySelectorAll(".nav-group-title").forEach((titulo) => {
    titulo.setAttribute("role", "button");
    titulo.setAttribute("tabindex", "0");
    titulo.setAttribute("aria-expanded", "true");

    const alternarGrupo = () => {
        const grupo = titulo.closest(".nav-group");
        const cerrado = grupo.classList.toggle("collapsed");
        titulo.setAttribute("aria-expanded", String(!cerrado));
    };

    titulo.addEventListener("click", alternarGrupo);
    titulo.addEventListener("keydown", (e) => {
        if (e.key === "Enter" || e.key === " ") {
            e.preventDefault();
            alternarGrupo();
        }
    });
});

function alternarMenu() {
    if (mqMovil.matches) {
        sidebar.classList.toggle("open");
    } else {
        sidebar.classList.toggle("mini");
        try {
            localStorage.setItem("sidebarMini", sidebar.classList.contains("mini") ? "1" : "0");
        } catch {}
    }
}

// Recordar el estado entre recargas
    try {
        if (localStorage.getItem("sidebarMini") === "1") sidebar.classList.add("mini");
    } catch {}

    brandLogo.addEventListener("click", alternarMenu);
    brandLogo.addEventListener("keydown", (e) => {
        if (e.key === "Enter" || e.key === " ") {
            e.preventDefault();
            alternarMenu();
    }
});

menuBtn.addEventListener("click", alternarMenu);

function cargarScript(nombre) {
    return new Promise((resolve, reject) => {
        if (cargados.has(nombre)) return resolve();
        const s = document.createElement("script");
        s.src = `js/modulos/${nombre}.js?v=${Date.now()}`; 
        s.onload = () => { cargados.add(nombre); resolve(); };
        s.onerror = () => reject(new Error(`No existe js/modulos/${nombre}.js`));
        document.body.appendChild(s);
    });
}

function marcarActivo(nombre) {
    enlaces.forEach((a) => a.classList.toggle("active", a.getAttribute("href") === "#" + nombre));
}

async function navegar() {
    const nombre = location.hash.slice(1);
    if (!nombre) return;

    marcarActivo(nombre);
    contenido.innerHTML = `<p class="empty-state">Cargando...</p>`;

    try {
        await cargarScript(nombre);
        if (!window.Modulos[nombre]) throw new Error(`El módulo "${nombre}" no se registró en window.Modulos.`);
        window.Modulos[nombre].init(contenido);  
    } catch (e) {
        contenido.innerHTML = `<p class="error-state">${e.message}</p>`;
    }

    sidebar.classList.remove("open"); 
}

window.addEventListener("hashchange", navegar);

document.getElementById("menuBtn")?.addEventListener("click", () => sidebar.classList.toggle("open"));

navegar();