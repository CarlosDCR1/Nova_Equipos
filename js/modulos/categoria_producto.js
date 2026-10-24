window.Modulos = window.Modulos || {};

window.Modulos.categoria_producto = Crud.crear({
    titulo: "Mantenimiento de Categorias de Producto",
    api: "api/admin/Categoria_Producto.php",
    id: "id_categoria_producto",
    singular: "categoria de producto",
    plural: "categorias de producto",

    busqueda: [
        { valor: "nombre", texto: "Nombre" }
    ],

    filtros: [
        { clave: "is_active", etiqueta: "Estado", vacio: "Todos",
          opciones: [{ valor: "1", texto: "Activo" }, { valor: "0", texto: "Inactivo" }] }
    ],

    columnas: [
        { campo: "id_categoria_producto", titulo: "Id" },
        { campo: "nombre_categoria_producto", titulo: "Nombre" },
        { campo: "descripcion_categoria_producto", titulo: "Descripción" },
        { campo: "is_active", titulo: "Estado", formato: Crud.badge }
    ],

    campos: [
        { name: "nombre_categoria_producto", etiqueta: "Nombre", maxlength: 80, required: true, autocomplete: "off" },
        { name: "descripcion_categoria_producto", etiqueta: "Descripción", maxlength: 500, autocomplete: "off" },
        { name: "is_active", etiqueta: "Estado", defecto: "1",
          opciones: [{ valor: "1", texto: "Activo" }, { valor: "0", texto: "Inactivo" }] }
    ]
});