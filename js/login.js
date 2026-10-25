const form = document.getElementById("formLogin");
const mensaje = document.getElementById("mensaje");
const boton = document.getElementById("btnIngresar");

document.getElementById("verClave").addEventListener("change", (e) => {
    form.contrasena.type = e.target.checked ? "text" : "password";
});

form.addEventListener("submit", async (e) => {
    e.preventDefault();
    mensaje.textContent = "";
    boton.disabled = true;

    try {
        const res = await fetch("api/auth/Login.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ usuario: form.usuario.value, contrasena: form.contrasena.value })
        });
        const json = await res.json();
        if (json.code !== 1) throw new Error(json.message || "No se pudo iniciar sesión.");
        window.location.href = json.data.redirect;
    } catch (err) {
        mensaje.textContent = err.message;
        form.contrasena.value = "";
        form.contrasena.focus();
    } finally {
        boton.disabled = false;
    }
});