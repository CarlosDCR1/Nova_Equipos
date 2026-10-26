window.Modulos = window.Modulos || {};

window.Modulos.equipo = Crud.crear({
    titulo: "Mantenimiento de Equipos",
    api: "api/admin/Equipo.php",
    id: "id_equipo",
    singular: "equipo",
    plural: "equipos",

    fuentes: {
        modelo: { api: "api/admin/Modelo_Producto.php", valor: "id_modelo_producto", texto: "nombre_modelo_producto" },
        estado: { api: "api/admin/Estado_Equipo.php", valor: "id_estado_equipo", texto: "nombre_estado" }
    },

    busqueda: [
        { valor: "codigo", texto: "Código de inventario" },
        { valor: "serie", texto: "Número de serie" }
    ],

    filtros: [
        { clave: "id_modelo_producto", etiqueta: "Modelo", fuente: "modelo", vacio: "Todos" },
        { clave: "id_estado_equipo", etiqueta: "Estado", fuente: "estado", vacio: "Todos" }
    ],

    columnas: [
        { campo: "id_equipo", titulo: "Id" },
        { campo: "id_modelo_producto", titulo: "Modelo", fuente: "modelo" },
        { campo: "id_estado_equipo", titulo: "Estado", fuente: "estado" },
        { campo: "codigo_inventario", titulo: "Código" },
        { campo: "numero_serie", titulo: "Serie" },
        { campo: "descripcion_equipo", titulo: "Descripción" },
        { campo: "fecha_adquisicion", titulo: "Adquisición" },
        { campo: "valor_adquisicion", titulo: "Valor" },
        { campo: "fecha_creacion", titulo: "Creado" },
        { campo: "fecha_actualizacion", titulo: "Actualizado" }
    ],

    campos: [
        { name: "id_modelo_producto", etiqueta: "Modelo", fuente: "modelo", required: true },
        { name: "id_estado_equipo", etiqueta: "Estado", fuente: "estado", required: true },
        { name: "codigo_inventario", etiqueta: "Código de inventario", maxlength: 50, required: true, autocomplete: "off" },
        { name: "numero_serie", etiqueta: "Número de serie", maxlength: 100, autocomplete: "off" },
        { name: "descripcion_equipo", etiqueta: "Descripción", maxlength: 300, autocomplete: "off" },
        { name: "fecha_adquisicion", etiqueta: "Fecha de adquisición", type: "date" },
        { name: "valor_adquisicion", etiqueta: "Valor de adquisición", type: "number", step: "0.01", min: "0" }
    ]
});