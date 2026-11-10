window.Modulos = window.Modulos || {};

window.Modulos.tblbitacora = Crud.crear({
    titulo: "Bitácora del Sistema",
    api: "api/admin/Bitacora.php",
    id: "idBitacora",
    singular: "registro",
    plural: "registros de bitácora",

    fuentes: {
        usuario: { api: "api/admin/Usuario.php", valor: "idUsuario", texto: "nombreUsuario" }
    },

    busqueda: [
        { valor: "descripcion", texto: "Descripción" },
        { valor: "aplicacion", texto: "Aplicación" }
    ],

    filtros: [
        { clave: "idUsuario", etiqueta: "Usuario", fuente: "usuario", vacio: "Todos" },
        { clave: "accion", etiqueta: "Acción", vacio: "Todas",
          opciones: [{ valor: "LOGIN", texto: "Inicio de sesión" }, { valor: "INSERTAR", texto: "Insertar" },
                     { valor: "EDITAR", texto: "Editar" }, { valor: "ELIMINAR", texto: "Eliminar" }] },
        { clave: "fecha", etiqueta: "Fecha", tipo: "date" }
    ],

    columnas: [
        { campo: "idBitacora", titulo: "Id" },
        { campo: "fechaEvento", titulo: "Fecha" },
        { campo: "nombreUsuario", titulo: "Usuario" },
        { campo: "modulo", titulo: "Módulo" },
        { campo: "aplicacion", titulo: "Aplicación" },
        { campo: "accion", titulo: "Acción" },
        { campo: "tablaAfectada", titulo: "Tabla" },
        { campo: "idRegistro", titulo: "Registro" },
        { campo: "descripcion", titulo: "Descripción" },
        { campo: "ipOrigen", titulo: "IP" }
    ],

    campos: []
});

// bitacora solo puede consultar por lo tanto creamos la pantalla y le borramos los botones 
{
    const iniciar = window.Modulos.tblbitacora.init;
    window.Modulos.tblbitacora.init = async (raiz) => {
        await iniciar(raiz);
        raiz.querySelector(".crud-form")?.remove();
    };
}