const contenido = document.getElementById("contenido");
const sidebar = document.getElementById("sidebar");
const enlaces = sidebar.querySelectorAll(".nav-link");


window.Modulos = window.Modulos || {};
const cargados = new Set();

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