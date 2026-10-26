window.Modulos = window.Modulos || {};

window.Modulos.modelo_producto = Crud.crear({
    titulo: "Mantenimiento de Modelos de Producto",
    api: "api/admin/Modelo_Producto.php",
    id: "id_modelo_producto",
    singular: "modelo de producto",
    plural: "modelos de producto",

    fuentes: {
        marca: { api: "api/admin/Marca.php", valor: "id_marca", texto: "nombre_marca" },
        categoria: { api: "api/admin/Categoria_Producto.php", valor: "id_categoria_producto", texto: "nombre_categoria_producto" }
    },

    busqueda: [
        { valor: "nombre", texto: "Nombre" }
    ],

    filtros: [
        { clave: "id_marca", etiqueta: "Marca", fuente: "marca", vacio: "Todas" },
        { clave: "id_categoria_producto", etiqueta: "Categoría", fuente: "categoria", vacio: "Todas" },
        { clave: "is_active", etiqueta: "Estado", vacio: "Todos",
          opciones: [{ valor: "1", texto: "Activo" }, { valor: "0", texto: "Inactivo" }] }
    ],

    columnas: [
        { campo: "id_modelo_producto", titulo: "Id" },
        { campo: "id_marca", titulo: "Marca", fuente: "marca" },
        { campo: "id_categoria_producto", titulo: "Categoría", fuente: "categoria" },
        { campo: "nombre_modelo_producto", titulo: "Nombre" },
        { campo: "descripcion_modelo_producto", titulo: "Descripción" },
        { campo: "is_active", titulo: "Estado", formato: Crud.badge }
    ],

    campos: [
        { name: "id_marca", etiqueta: "Marca", fuente: "marca", required: true },
        { name: "id_categoria_producto", etiqueta: "Categoría", fuente: "categoria", required: true },
        { name: "nombre_modelo_producto", etiqueta: "Nombre", maxlength: 100, required: true, autocomplete: "off" },
        { name: "descripcion_modelo_producto", etiqueta: "Descripción", maxlength: 300, autocomplete: "off" },
        { name: "is_active", etiqueta: "Estado", defecto: "1",
          opciones: [{ valor: "1", texto: "Activo" }, { valor: "0", texto: "Inactivo" }] }
    ]
});