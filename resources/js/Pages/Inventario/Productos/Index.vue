<script setup>
import { ref, reactive, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    productos: Object,
    catalogos: Object,
    filtros: Object,
});

const filtroForm = reactive({
    buscar: props.filtros?.buscar || '',
    id_categoria: props.filtros?.id_categoria || '',
    id_marca: props.filtros?.id_marca || '',
    bajo_stock: props.filtros?.bajo_stock || false,
});

const Filtrar = () =>
{
    router.get(route('inventario.productos.index'), filtroForm, {
        preserveState: true,
        replace: true,
    });
};

const LimpiarFiltros = () =>
{
    filtroForm.buscar = '';
    filtroForm.id_categoria = '';
    filtroForm.id_marca = '';
    filtroForm.bajo_stock = false;
    Filtrar();
};

// Modal Crear / Editar Producto
const modalAbierto = ref(false);
const editando = ref(false);
const idProductoEditando = ref(null);
const ImagenVisualizador = ref(null);
const ImagenesExistentes = ref([]);
const NuevasImagenes = ref([]);

const form = useForm({
    sku: '',
    codigo_barras: '',
    nombre: '',
    modelo: '',
    descripcion: '',
    id_marca: '',
    id_categoria: '',
    id_unidad: '',
    id_unidad_secundaria: '',
    equivalencia_unidad: 1.0,
    equivalencia_unidad_secundaria: 1.0,
    precio_costo: 0,
    precio_venta: 0,
    stock_actual: 0,
    stock_minimo: 5,
    activo: true,
    imagen_principal: null,
    imagenes: [],
    imagenes_eliminar: [],
});

const ResolverUrlImagen = (ruta) =>
{
    if (!ruta)
    {
        return '';
    }

    if (ruta.startsWith('blob:') || ruta.startsWith('data:'))
    {
        return ruta;
    }

    let subfolder = '';
    if (typeof window !== 'undefined' && window.location?.pathname)
    {
        const match = window.location.pathname.match(/^(\/[^\/]+\/public)/i);
        if (match)
        {
            subfolder = match[1];
        }
    }

    if (ruta.startsWith('http://') || ruta.startsWith('https://'))
    {
        try
        {
            const parsed = new URL(ruta);
            if (subfolder && parsed.pathname.startsWith('/storage') && !parsed.pathname.startsWith(subfolder))
            {
                parsed.pathname = `${subfolder}${parsed.pathname}`;
                return parsed.toString();
            }
        }
        catch (e)
        {
            // Continuar con fallback
        }
        return ruta;
    }

    const cleanPath = ruta.startsWith('/') ? ruta : '/' + ruta;
    if (subfolder && cleanPath.startsWith('/storage') && !cleanPath.startsWith(subfolder))
    {
        return `${subfolder}${cleanPath}`;
    }

    return cleanPath;
};

const AbrirVisorImagen = (ruta) =>
{
    ImagenVisualizador.value = ResolverUrlImagen(ruta);
};

const OnSeleccionarImagenes = (evento) =>
{
    const archivos = Array.from(evento.target.files || []);
    for (const archivo of archivos)
    {
        if (archivo.type.startsWith('image/'))
        {
            const urlPreview = URL.createObjectURL(archivo);
            NuevasImagenes.value.push({
                archivo: archivo,
                urlPreview: urlPreview,
            });
        }
    }
    evento.target.value = '';
};

const EliminarImagenNueva = (index) =>
{
    const item = NuevasImagenes.value[index];
    if (item && item.urlPreview)
    {
        URL.revokeObjectURL(item.urlPreview);
    }
    NuevasImagenes.value.splice(index, 1);
};

const EliminarImagenExistente = (idImagen) =>
{
    if (!form.imagenes_eliminar.includes(idImagen))
    {
        form.imagenes_eliminar.push(idImagen);
    }
    ImagenesExistentes.value = ImagenesExistentes.value.filter(
        img => img.id_producto_imagen !== idImagen
    );
};

const AbrirModalNuevo = () =>
{
    editando.value = false;
    idProductoEditando.value = null;
    ImagenesExistentes.value = [];
    for (const item of NuevasImagenes.value)
    {
        if (item.urlPreview)
        {
            URL.revokeObjectURL(item.urlPreview);
        }
    }
    NuevasImagenes.value = [];
    form.reset();
    form.clearErrors();
    form.id_unidad = props.catalogos?.unidades?.[0]?.id_unidad || '';
    form.equivalencia_unidad = 1.0;
    form.equivalencia_unidad_secundaria = 1.0;
    form.stock_minimo = 5;
    form.activo = true;
    form.imagen_principal = null;
    form.imagenes = [];
    form.imagenes_eliminar = [];
    modalAbierto.value = true;
};

const AbrirModalEditar = (producto) =>
{
    editando.value = true;
    idProductoEditando.value = producto.id_producto;
    for (const item of NuevasImagenes.value)
    {
        if (item.urlPreview)
        {
            URL.revokeObjectURL(item.urlPreview);
        }
    }
    NuevasImagenes.value = [];
    ImagenesExistentes.value = Array.isArray(producto.imagenes) ? [...producto.imagenes] : [];
    form.reset();
    form.clearErrors();
    form.sku = producto.sku || '';
    form.codigo_barras = producto.codigo_barras || '';
    form.nombre = producto.nombre || '';
    form.modelo = producto.modelo || '';
    form.descripcion = producto.descripcion || '';
    form.id_marca = producto.id_marca || '';
    form.id_categoria = producto.id_categoria || '';
    form.id_unidad = producto.id_unidad;
    form.id_unidad_secundaria = producto.id_unidad_secundaria || '';
    form.equivalencia_unidad = Number(producto.equivalencia_unidad) || 1.0;
    form.equivalencia_unidad_secundaria = Number(producto.equivalencia_unidad_secundaria) || 1.0;
    form.precio_costo = Number(producto.precio_costo) || 0;
    form.precio_venta = Number(producto.precio_venta) || 0;
    form.stock_actual = Number(producto.stock_actual) || 0;
    form.stock_minimo = Number(producto.stock_minimo) || 0;
    form.activo = Boolean(producto.activo);
    form.imagen_principal = producto.imagen_principal || null;
    form.imagenes = [];
    form.imagenes_eliminar = [];
    modalAbierto.value = true;
};

const CerrarModal = () =>
{
    modalAbierto.value = false;
    for (const item of NuevasImagenes.value)
    {
        if (item.urlPreview)
        {
            URL.revokeObjectURL(item.urlPreview);
        }
    }
    NuevasImagenes.value = [];
    ImagenesExistentes.value = [];
    form.reset();
    form.clearErrors();
};

const GuardarProducto = () =>
{
    form.imagenes = NuevasImagenes.value.map(item => item.archivo);

    if (editando.value)
    {
        form.transform((data) => ({
            ...data,
            _method: 'put',
        })).post(route('inventario.productos.update', idProductoEditando.value), {
            forceFormData: true,
            onSuccess: () =>
            {
                CerrarModal();
            },
        });
    }
    else
    {
        form.post(route('inventario.productos.store'), {
            forceFormData: true,
            onSuccess: () =>
            {
                CerrarModal();
            },
        });
    }
};

// Modal de Movimientos de Inventario
const modalMovimientosAbierto = ref(false);
const productoSeleccionado = ref(null);
const movimientos = ref([]);
const cargandoMovimientos = ref(false);

const VerMovimientos = async (producto) =>
{
    productoSeleccionado.value = producto;
    modalMovimientosAbierto.value = true;
    cargandoMovimientos.value = true;
    try
    {
        const respuesta = await fetch(route('inventario.productos.movimientos', producto.id_producto));
        const datos = await respuesta.json();
        movimientos.value = datos.data || [];
    }
    catch (error)
    {
        console.error(error);
        movimientos.value = [];
    }
    finally
    {
        cargandoMovimientos.value = false;
    }
};
</script>

<template>
    <Head title="Catálogo de Productos e Inventario" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-100 flex items-center gap-2">
                        <span class="p-2 bg-indigo-100 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-400 rounded-lg">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                        </span>
                        Catálogo de Productos e Inventario
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Control de existencias, precios multimoneda, códigos de barra y unidades multiescala.
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <button
                        @click="AbrirModalNuevo"
                        class="inline-flex items-center px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl shadow-sm transition duration-150 gap-2"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Nuevo Producto
                    </button>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

                <!-- Barra de Filtros -->
                <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700">
                    <form @submit.prevent="Filtrar" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 items-end">
                        <div class="lg:col-span-2">
                            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider mb-1">
                                Buscar
                            </label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                    </svg>
                                </span>
                                <input
                                    type="text"
                                    v-model="filtroForm.buscar"
                                    placeholder="Nombre, SKU, código de barras o modelo..."
                                    class="w-full pl-10 pr-4 py-2 bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 rounded-xl text-sm text-gray-800 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                />
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider mb-1">
                                Categoría
                            </label>
                            <select
                                v-model="filtroForm.id_categoria"
                                class="w-full py-2 bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 rounded-xl text-sm text-gray-800 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500"
                            >
                                <option value="">Todas las Categorías</option>
                                <option v-for="cat in catalogos.categorias" :key="cat.id_categoria" :value="cat.id_categoria">
                                    {{ cat.nombre }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider mb-1">
                                Marca
                            </label>
                            <select
                                v-model="filtroForm.id_marca"
                                class="w-full py-2 bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 rounded-xl text-sm text-gray-800 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500"
                            >
                                <option value="">Todas las Marcas</option>
                                <option v-for="marca in catalogos.marcas" :key="marca.id_marca" :value="marca.id_marca">
                                    {{ marca.nombre }}
                                </option>
                            </select>
                        </div>

                        <div class="flex items-center gap-2">
                            <button
                                type="submit"
                                class="w-full py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-semibold transition"
                            >
                                Filtrar
                            </button>
                            <button
                                type="button"
                                @click="LimpiarFiltros"
                                class="px-3 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-xl text-sm font-semibold transition"
                                title="Limpiar filtros"
                            >
                                Limpiar
                            </button>
                        </div>
                    </form>

                    <div class="mt-3 flex items-center gap-4">
                        <label class="inline-flex items-center text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
                            <input
                                type="checkbox"
                                v-model="filtroForm.bajo_stock"
                                @change="Filtrar"
                                class="rounded border-gray-300 text-rose-600 shadow-sm focus:ring-rose-500"
                            />
                            <span class="ms-2 text-xs font-medium text-rose-600 dark:text-rose-400">
                                Solo productos con stock bajo / agotándose
                            </span>
                        </label>
                    </div>
                </div>

                <!-- Tabla de Productos -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700/50">
                                <tr>
                                    <th class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Producto
                                    </th>
                                    <th class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Categoría / Marca
                                    </th>
                                    <th class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Unidad
                                    </th>
                                    <th class="px-6 py-3.5 text-right text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Costo USD
                                    </th>
                                    <th class="px-6 py-3.5 text-right text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Precio Venta
                                    </th>
                                    <th class="px-6 py-3.5 text-center text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Stock
                                    </th>
                                    <th class="px-6 py-3.5 text-center text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Acciones
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                <tr
                                    v-for="producto in productos.data"
                                    :key="producto.id_producto"
                                    class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition"
                                >
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="w-12 h-12 rounded-xl bg-gray-100 dark:bg-gray-700 flex items-center justify-center font-bold text-gray-600 dark:text-gray-300 shrink-0 overflow-hidden border border-gray-200 dark:border-gray-600/60 shadow-xs cursor-pointer group/img relative"
                                                @click="producto.imagen_principal ? AbrirVisorImagen(producto.imagen_principal) : (producto.imagenes?.[0]?.ruta_imagen ? AbrirVisorImagen(producto.imagenes[0].ruta_imagen) : null)"
                                                :title="producto.imagen_principal || producto.imagenes?.[0]?.ruta_imagen ? 'Ver imagen ampliada' : 'Sin imagen'"
                                            >
                                                <img
                                                    v-if="producto.imagen_principal || producto.imagenes?.[0]?.ruta_imagen"
                                                    :src="ResolverUrlImagen(producto.imagen_principal || producto.imagenes[0].ruta_imagen)"
                                                    :alt="producto.nombre"
                                                    class="w-full h-full object-cover group-hover/img:scale-110 transition duration-200"
                                                />
                                                <svg v-else class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                                </svg>
                                            </div>
                                            <div class="min-w-0">
                                                <div class="font-bold text-gray-900 dark:text-gray-100 text-sm">
                                                    {{ producto.nombre }}
                                                </div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-2 mt-0.5">
                                                    <span class="font-mono bg-gray-100 dark:bg-gray-700 px-1.5 py-0.5 rounded">SKU: {{ producto.sku }}</span>
                                                    <span v-if="producto.codigo_barras" class="font-mono text-gray-400">| EAN: {{ producto.codigo_barras }}</span>
                                                </div>
                                                <div v-if="producto.descripcion" class="text-[11px] text-gray-400 dark:text-gray-500 line-clamp-1 mt-0.5 max-w-xs" :title="producto.descripcion">
                                                    {{ producto.descripcion }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-800 dark:text-gray-200">
                                            {{ producto.categoria?.nombre || 'Sin Categoría' }}
                                        </div>
                                        <div class="text-xs text-gray-400">
                                            {{ producto.marca?.nombre || 'Genérica' }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-xs text-gray-600 dark:text-gray-300">
                                        <span class="font-semibold">{{ producto.unidad?.nombre || 'UND' }}</span>
                                        <div v-if="producto.unidad_secundaria" class="text-gray-400 mt-0.5">
                                            1 {{ producto.unidad_secundaria.nombre }} = {{ Number(producto.equivalencia_unidad_secundaria) }} {{ producto.unidad?.nombre }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-right font-mono text-sm text-gray-600 dark:text-gray-300">
                                        ${{ Number(producto.precio_costo).toFixed(2) }}
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="text-sm font-bold text-emerald-600 dark:text-emerald-400 font-mono">
                                            ${{ Number(producto.precio_venta).toFixed(2) }}
                                        </div>
                                        <div class="text-xs text-gray-400 font-mono">
                                            Bs. {{ Number(producto.precio_venta_ves).toFixed(2) }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span
                                            :class="[
                                                'inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold font-mono',
                                                producto.es_stock_bajo
                                                    ? 'bg-rose-100 dark:bg-rose-900/50 text-rose-700 dark:text-rose-300 border border-rose-300 dark:border-rose-700'
                                                    : 'bg-emerald-100 dark:bg-emerald-900/50 text-emerald-700 dark:text-emerald-300'
                                            ]"
                                        >
                                            <span v-if="producto.es_stock_bajo" class="w-1.5 h-1.5 rounded-full bg-rose-500 mr-1.5 animate-ping"></span>
                                            {{ producto.stock_actual }} {{ producto.unidad?.abreviatura || 'UND' }}
                                        </span>
                                        <div v-if="producto.es_stock_bajo" class="text-[10px] text-rose-500 font-semibold mt-0.5">
                                            Mín: {{ producto.stock_minimo }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <button
                                                @click="VerMovimientos(producto)"
                                                class="p-1.5 text-gray-500 hover:text-indigo-600 dark:hover:text-indigo-400 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition"
                                                title="Historial de movimientos"
                                            >
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                            </button>
                                            <button
                                                @click="AbrirModalEditar(producto)"
                                                class="p-1.5 text-gray-500 hover:text-amber-600 dark:hover:text-amber-400 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition"
                                                title="Editar producto"
                                            >
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="productos.data.length === 0">
                                    <td colspan="7" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400 text-sm">
                                        No se encontraron productos con los criterios seleccionados.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginación -->
                    <div v-if="productos.links && productos.links.length > 3" class="px-6 py-4 border-t border-gray-100 dark:border-gray-700 flex justify-between items-center">
                        <div class="text-xs text-gray-500">
                            Mostrando {{ productos.from || 0 }} - {{ productos.to || 0 }} de {{ productos.total }} productos
                        </div>
                        <div class="flex gap-1">
                            <template v-for="(link, index) in productos.links" :key="index">
                                <button
                                    v-if="link.url"
                                    @click="router.get(link.url, {}, { preserveState: true })"
                                    v-html="link.label"
                                    :class="[
                                        'px-3 py-1 rounded-lg text-xs font-semibold transition',
                                        link.active
                                            ? 'bg-indigo-600 text-white'
                                            : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200'
                                    ]"
                                />
                            </template>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Modal Crear / Editar -->
        <div v-if="modalAbierto" class="fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white dark:bg-gray-800 rounded-3xl max-w-3xl w-full p-6 shadow-2xl border border-gray-100 dark:border-gray-700 max-h-[92vh] flex flex-col">
                <div class="flex justify-between items-center pb-4 border-b border-gray-100 dark:border-gray-700 shrink-0">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">
                        {{ editando ? 'Editar Producto' : 'Registrar Nuevo Producto' }}
                    </h3>
                    <button @click="CerrarModal" class="text-gray-400 hover:text-gray-500 text-xl font-bold">
                        ✕
                    </button>
                </div>

                <form @submit.prevent="GuardarProducto" class="mt-5 space-y-4 overflow-y-auto pr-1">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Código SKU *</label>
                            <input
                                type="text"
                                v-model="form.sku"
                                required
                                class="w-full py-2 px-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-sm"
                            />
                            <p v-if="form.errors.sku" class="text-xs text-rose-500 mt-1">{{ form.errors.sku }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Código de Barras (EAN/UPC)</label>
                            <input
                                type="text"
                                v-model="form.codigo_barras"
                                placeholder="Escanee o ingrese código"
                                class="w-full py-2 px-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-sm"
                            />
                            <p v-if="form.errors.codigo_barras" class="text-xs text-rose-500 mt-1">{{ form.errors.codigo_barras }}</p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Nombre del Producto *</label>
                        <input
                            type="text"
                            v-model="form.nombre"
                            required
                            class="w-full py-2 px-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-sm"
                        />
                        <p v-if="form.errors.nombre" class="text-xs text-rose-500 mt-1">{{ form.errors.nombre }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Descripción del Producto (Opcional)</label>
                        <textarea
                            v-model="form.descripcion"
                            rows="3"
                            placeholder="Detalles, especificaciones técnicas, características del producto..."
                            class="w-full py-2 px-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-sm text-gray-800 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition"
                        ></textarea>
                        <p v-if="form.errors.descripcion" class="text-xs text-rose-500 mt-1">{{ form.errors.descripcion }}</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Categoría</label>
                            <select
                                v-model="form.id_categoria"
                                class="w-full py-2 px-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-sm"
                            >
                                <option value="">Seleccione Categoría</option>
                                <option v-for="cat in catalogos.categorias" :key="cat.id_categoria" :value="cat.id_categoria">
                                    {{ cat.nombre }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Marca</label>
                            <select
                                v-model="form.id_marca"
                                class="w-full py-2 px-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-sm"
                            >
                                <option value="">Seleccione Marca</option>
                                <option v-for="marca in catalogos.marcas" :key="marca.id_marca" :value="marca.id_marca">
                                    {{ marca.nombre }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Modelo</label>
                            <input
                                type="text"
                                v-model="form.modelo"
                                class="w-full py-2 px-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-sm"
                            />
                        </div>
                    </div>

                    <!-- Sección de Fotografías del Producto -->
                    <div class="p-4 bg-gray-50 dark:bg-gray-750 rounded-2xl border border-gray-200 dark:border-gray-700 space-y-3">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                            <div>
                                <h4 class="text-xs font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    Fotografías del Producto
                                </h4>
                                <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">
                                    JPG, PNG, WEBP (hasta 5MB). La primera foto se guardará como portada.
                                </p>
                            </div>
                            <label class="cursor-pointer inline-flex items-center justify-center gap-1.5 px-3 py-1.5 bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-300 hover:bg-indigo-100 rounded-xl text-xs font-semibold transition border border-indigo-200 dark:border-indigo-800 shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                <span>Agregar Fotos</span>
                                <input
                                    type="file"
                                    multiple
                                    accept="image/*"
                                    class="hidden"
                                    @change="OnSeleccionarImagenes"
                                />
                            </label>
                        </div>

                        <!-- Previsualización de imágenes -->
                        <div v-if="ImagenesExistentes.length > 0 || NuevasImagenes.length > 0" class="grid grid-cols-2 sm:grid-cols-4 gap-3 pt-2">
                            <!-- Imágenes Existentes -->
                            <div
                                v-for="(img, idx) in ImagenesExistentes"
                                :key="'existente-' + img.id_producto_imagen"
                                class="relative group rounded-xl overflow-hidden border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 aspect-square flex items-center justify-center shadow-xs"
                            >
                                <img
                                    :src="ResolverUrlImagen(img.ruta_imagen)"
                                    alt="Producto"
                                    class="w-full h-full object-cover cursor-pointer transition group-hover:scale-105"
                                    @click="AbrirVisorImagen(img.ruta_imagen)"
                                />
                                <span
                                    v-if="img.es_principal || (idx === 0 && NuevasImagenes.length === 0)"
                                    class="absolute top-1.5 left-1.5 bg-indigo-600 text-white text-[10px] font-bold px-1.5 py-0.5 rounded shadow-sm"
                                >
                                    Principal
                                </span>
                                <button
                                    type="button"
                                    @click="EliminarImagenExistente(img.id_producto_imagen)"
                                    class="absolute top-1.5 right-1.5 bg-rose-600 hover:bg-rose-700 text-white w-6 h-6 rounded-full flex items-center justify-center text-xs opacity-90 hover:opacity-100 shadow transition"
                                    title="Eliminar imagen"
                                >
                                    ✕
                                </button>
                            </div>

                            <!-- Nuevas Imágenes Seleccionadas -->
                            <div
                                v-for="(item, idx) in NuevasImagenes"
                                :key="'nueva-' + idx"
                                class="relative group rounded-xl overflow-hidden border-2 border-dashed border-indigo-400 dark:border-indigo-500 bg-indigo-50/50 dark:bg-indigo-950/20 aspect-square flex items-center justify-center shadow-xs"
                            >
                                <img
                                    :src="item.urlPreview"
                                    alt="Nueva imagen"
                                    class="w-full h-full object-cover cursor-pointer transition group-hover:scale-105"
                                    @click="AbrirVisorImagen(item.urlPreview)"
                                />
                                <span
                                    v-if="ImagenesExistentes.length === 0 && idx === 0"
                                    class="absolute top-1.5 left-1.5 bg-emerald-600 text-white text-[10px] font-bold px-1.5 py-0.5 rounded shadow-sm"
                                >
                                    Nueva Principal
                                </span>
                                <button
                                    type="button"
                                    @click="EliminarImagenNueva(idx)"
                                    class="absolute top-1.5 right-1.5 bg-rose-600 hover:bg-rose-700 text-white w-6 h-6 rounded-full flex items-center justify-center text-xs opacity-90 hover:opacity-100 shadow transition"
                                    title="Quitar foto"
                                >
                                    ✕
                                </button>
                            </div>
                        </div>

                        <div
                            v-else
                            class="py-6 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl flex flex-col items-center justify-center text-gray-400 dark:text-gray-500 hover:border-indigo-400 transition cursor-pointer"
                            @click="$el.querySelector('input[type=file]')?.click()"
                        >
                            <svg class="w-8 h-8 mb-1 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <span class="text-xs font-medium">Haga clic o use el botón de arriba para subir imágenes</span>
                        </div>

                        <p v-if="form.errors['imagenes.0'] || form.errors.imagenes || form.errors.imagen_principal" class="text-xs text-rose-500">
                            {{ form.errors['imagenes.0'] || form.errors.imagenes || form.errors.imagen_principal }}
                        </p>
                    </div>

                    <!-- Unidades y Equivalencias -->
                    <div class="p-4 bg-gray-50 dark:bg-gray-750 rounded-2xl border border-gray-200 dark:border-gray-700 space-y-3">
                        <h4 class="text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                            Unidades de Medida y Venta Multiescala
                        </h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Unidad Principal *</label>
                                <select
                                    v-model="form.id_unidad"
                                    required
                                    class="w-full py-2 px-3 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-sm"
                                >
                                    <option v-for="u in catalogos.unidades" :key="u.id_unidad" :value="u.id_unidad">
                                        {{ u.nombre }} ({{ u.abreviatura }})
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Unidad Secundaria (Opcional)</label>
                                <select
                                    v-model="form.id_unidad_secundaria"
                                    class="w-full py-2 px-3 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-sm"
                                >
                                    <option value="">Ninguna</option>
                                    <option v-for="u in catalogos.unidades" :key="u.id_unidad" :value="u.id_unidad">
                                        {{ u.nombre }} ({{ u.abreviatura }})
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div v-if="form.id_unidad_secundaria" class="pt-2">
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                Factor de Conversión (Equivalencia en unidades principales)
                            </label>
                            <div class="flex items-center gap-2">
                                <span class="text-xs text-gray-500">1 Unidad Secundaria contiene:</span>
                                <input
                                    type="number"
                                    step="0.0001"
                                    min="0.0001"
                                    v-model="form.equivalencia_unidad_secundaria"
                                    class="w-32 py-1 px-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-sm font-mono"
                                />
                                <span class="text-xs text-gray-500">Unidades Principales</span>
                            </div>
                        </div>
                    </div>

                    <!-- Precios y Existencias -->
                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Costo (USD) *</label>
                            <input
                                type="number"
                                step="0.01"
                                min="0"
                                v-model="form.precio_costo"
                                required
                                class="w-full py-2 px-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-sm font-mono"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Venta (USD) *</label>
                            <input
                                type="number"
                                step="0.01"
                                min="0"
                                v-model="form.precio_venta"
                                required
                                class="w-full py-2 px-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-sm font-mono"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Stock Mínimo *</label>
                            <input
                                type="number"
                                min="0"
                                v-model="form.stock_minimo"
                                required
                                class="w-full py-2 px-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-sm font-mono"
                            />
                        </div>

                        <div v-if="!editando">
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Stock Inicial</label>
                            <input
                                type="number"
                                min="0"
                                v-model="form.stock_actual"
                                class="w-full py-2 px-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-sm font-mono"
                            />
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-700">
                        <button
                            type="button"
                            @click="CerrarModal"
                            class="px-4 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-xl text-sm font-semibold transition"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-semibold transition disabled:opacity-50"
                        >
                            {{ form.processing ? 'Guardando...' : (editando ? 'Actualizar Producto' : 'Guardar Producto') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Movimientos de Inventario -->
        <div v-if="modalMovimientosAbierto" class="fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white dark:bg-gray-800 rounded-3xl max-w-3xl w-full p-6 shadow-2xl border border-gray-100 dark:border-gray-700">
                <div class="flex justify-between items-center pb-4 border-b border-gray-100 dark:border-gray-700">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">
                            Historial de Movimientos de Inventario
                        </h3>
                        <p class="text-xs text-gray-500 mt-0.5">
                            {{ productoSeleccionado?.nombre }} (SKU: {{ productoSeleccionado?.sku }})
                        </p>
                    </div>
                    <button @click="modalMovimientosAbierto = false" class="text-gray-400 hover:text-gray-500 text-xl font-bold">
                        ✕
                    </button>
                </div>

                <div class="mt-4 max-h-96 overflow-y-auto">
                    <div v-if="cargandoMovimientos" class="text-center py-10 text-gray-400 text-sm">
                        Cargando movimientos...
                    </div>
                    <table v-else class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-xs">
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <th class="px-4 py-2.5 text-left font-bold text-gray-500 uppercase">Fecha</th>
                                <th class="px-4 py-2.5 text-left font-bold text-gray-500 uppercase">Tipo</th>
                                <th class="px-4 py-2.5 text-right font-bold text-gray-500 uppercase">Cantidad</th>
                                <th class="px-4 py-2.5 text-center font-bold text-gray-500 uppercase">Anterior</th>
                                <th class="px-4 py-2.5 text-center font-bold text-gray-500 uppercase">Nuevo</th>
                                <th class="px-4 py-2.5 text-left font-bold text-gray-500 uppercase">Doc. Ref</th>
                                <th class="px-4 py-2.5 text-left font-bold text-gray-500 uppercase">Usuario</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="mov in movimientos" :key="mov.id_movimiento_inventario">
                                <td class="px-4 py-2.5 text-gray-600 dark:text-gray-300 font-mono">
                                    {{ new Date(mov.created_at).toLocaleString() }}
                                </td>
                                <td class="px-4 py-2.5">
                                    <span
                                        :class="[
                                            'px-2 py-0.5 rounded-full font-bold text-[10px]',
                                            mov.tipo_movimiento?.naturaleza === 'ENTRADA'
                                                ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300'
                                                : (mov.tipo_movimiento?.naturaleza === 'SALIDA'
                                                    ? 'bg-rose-100 text-rose-700 dark:bg-rose-900/50 dark:text-rose-300'
                                                    : 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-300')
                                        ]"
                                    >
                                        {{ mov.tipo_movimiento?.nombre || mov.tipo_movimiento?.codigo }}
                                    </span>
                                </td>
                                <td class="px-4 py-2.5 text-right font-mono font-bold">
                                    {{ mov.cantidad }}
                                </td>
                                <td class="px-4 py-2.5 text-center font-mono text-gray-500">
                                    {{ mov.stock_anterior }}
                                </td>
                                <td class="px-4 py-2.5 text-center font-mono font-bold text-gray-900 dark:text-gray-100">
                                    {{ mov.nuevo_stock }}
                                </td>
                                <td class="px-4 py-2.5 text-gray-500 font-mono">
                                    {{ mov.documento_referencia || '—' }}
                                </td>
                                <td class="px-4 py-2.5 text-gray-500">
                                    {{ mov.usuario?.nombre || 'Sistema' }}
                                </td>
                            </tr>
                            <tr v-if="movimientos.length === 0">
                                <td colspan="7" class="px-4 py-8 text-center text-gray-400">
                                    No hay movimientos registrados para este producto.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-700 text-right">
                    <button
                        @click="modalMovimientosAbierto = false"
                        class="px-4 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl text-xs font-semibold"
                    >
                        Cerrar
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal Visor de Imagen Ampliada -->
        <div
            v-if="ImagenVisualizador"
            class="fixed inset-0 z-[100] bg-black/85 backdrop-blur-md flex items-center justify-center p-4 cursor-pointer"
            style="z-index: 100;"
            @click="ImagenVisualizador = null"
        >
            <div class="relative max-w-3xl max-h-[85vh] p-2 bg-white dark:bg-gray-900 rounded-3xl shadow-2xl overflow-hidden" @click.stop>
                <button
                    @click="ImagenVisualizador = null"
                    class="absolute top-4 right-4 z-10 w-9 h-9 rounded-full bg-black/60 text-white flex items-center justify-center hover:bg-black/80 transition font-bold"
                >
                    ✕
                </button>
                <img
                    :src="ResolverUrlImagen(ImagenVisualizador)"
                    alt="Vista ampliada"
                    class="max-w-full max-h-[80vh] rounded-2xl object-contain"
                />
            </div>
        </div>

    </AuthenticatedLayout>
</template>
