const form = document.getElementById("formRegistro");
const mensaje = document.getElementById("mensaje");
const boton = document.getElementById("btnRegistrar");

// Muestra u oculta ambas contraseñas.
document.getElementById("verClave").addEventListener("change", (e) => {
    const tipo = e.target.checked ? "text" : "password";

    form.contrasenaUsuario.type = tipo;
    form.confirmarContrasena.type = tipo;
});


form.addEventListener("submit", async (e) => {
    e.preventDefault();

    if (boton.disabled) return;

    mensaje.textContent = "";
    mensaje.classList.remove("exito");

    if (!form.reportValidity()) return;

    if (
        form.contrasenaUsuario.value !==
        form.confirmarContrasena.value
    ) {
        mensaje.textContent = "Las contraseñas no coinciden.";
        form.confirmarContrasena.focus();
        return;
    }

    boton.disabled = true;
    boton.textContent = "Creando usuario...";

    try {
        const datos = Object.fromEntries(new FormData(form));

        const res = await fetch("api/auth/Registro.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify(datos)
        });

        const json = await res.json();

        if (!res.ok || json.code !== 1) {
            throw new Error(
                json.message || "No se pudo crear el usuario."
            );
        }

        mensaje.textContent = json.message;
        mensaje.classList.add("exito");

        // Impide registrar nuevamente después de una respuesta exitosa.
        form.querySelectorAll("input").forEach((campo) => {
            campo.disabled = true;
        });

        boton.textContent = "Usuario creado";

        // Permite volver al login para ingresar con la cuenta nueva.
        const enlaceLogin = document.querySelector(
            ".acceso-registro a"
        );

        enlaceLogin.textContent = "Iniciar sesión";

    } catch (err) {
        if (err instanceof SyntaxError) {
            mensaje.textContent =
                "No se pudo procesar la respuesta del servidor. Intente nuevamente.";

        } else if (err instanceof TypeError) {
            mensaje.textContent =
                "No se pudo conectar con el servidor. Revise su conexión e intente nuevamente.";

        } else {
            mensaje.textContent = err.message;
        }

        boton.disabled = false;
        boton.textContent = "Crear usuario";

    } finally {
        // Limpia ambas contraseñas después del intento.
        form.contrasenaUsuario.value = "";
        form.confirmarContrasena.value = "";
    }
});