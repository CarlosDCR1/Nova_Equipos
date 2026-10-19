
const hoy = new Date();
const fmt = (d) => d.toLocaleDateString("en-CA"); 

const fechaMax = fmt(new Date(hoy.getFullYear() - 18, hoy.getMonth(), hoy.getDate()));
const fechaMin = fmt(new Date(hoy.getFullYear() - 66, hoy.getMonth(), hoy.getDate() + 1));
window.Modulos = window.Modulos || {};


window.Modulos.empleados = Crud.crear({
    titulo: "Mantenimiento de Empleados",
    api: "api/admin/Empleado.php",
    id: "id_empleado",
    singular: "empleado",
    plural: "empleados",

    


    busqueda: [
        { valor: "nombre", texto: "Nombre" },
        { valor: "apellidos",texto: "Apellidos" },
        { valor: "correo", texto: "Correo institucional" },
        { valor: "dpi", texto: "DPI" },
        { valor: "telefono",  texto: "Teléfono" }
    ],

    filtros: [
        { clave: "estado", etiqueta: "Estado", vacio: "Todos",
          opciones: [{ valor: "ACTIVO", texto: "Activo" }, { valor: "INACTIVO", texto: "Inactivo" }] }
    ],

    columnas: [
        { campo: "id_empleado", titulo: "Id" },
        { campo: "nombre",  titulo: "Nombre" },
        { campo: "apellidos",  titulo: "Apellidos" },
        { campo: "correo_institucional", titulo: "Correo institucional" },
        { campo: "telefono_empleado",titulo: "Teléfono" },
        { campo: "dpi",  titulo: "DPI" },
        { campo: "estado_empleado",   titulo: "Estado", formato: Crud.badge },
        { campo: "fecha_creacion",   titulo: "Creado" }
    ],

    campos: [
        { name: "nombre",  etiqueta: "Nombre", maxlength: 100, required: true },
        { name: "apellidos",   etiqueta: "Apellidos", maxlength: 50, required: true },
        { name: "correo_institucional", etiqueta: "Correo institucional", tipo: "email", maxlength: 150, required: true },
        { name: "telefono_empleado", etiqueta: "Teléfono", maxlength: 8 },
        { name: "dpi",   etiqueta: "DPI", maxlength: 13 },
        { name: "estado_civil",   etiqueta: "Estado civil", vacio: "Selecciona...",
          opciones: [
              { valor: "SOLTERO", texto: "Soltero/a" },
              { valor: "CASADO",  texto: "Casado/a" },
              { valor: "DIVORCIADO", texto: "Divorciado/a" },
              { valor: "VIUDO",  texto: "Viudo/a" }
          ] },
        { name: "fecha_nacimiento", etiqueta: "Fecha de nacimiento", tipo: "date",
        min: fechaMin, max: fechaMax, mensaje: "La edad debe estar entre 18 y 65 años" },
        { name: "estado_empleado",      etiqueta: "Estado", defecto: "ACTIVO",
          opciones: [{ valor: "ACTIVO", texto: "Activo" }, { valor: "INACTIVO", texto: "Inactivo" }] }
    ]
});