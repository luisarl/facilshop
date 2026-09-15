<script setup>
import { ref, computed, watch } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    tipos_entrada: Array,
    tipos_salida: Array,
    codigo_sugerido: String,
    productos: Array,
});

const naturalezaSeleccionada = ref('ENTRADA');

const tiposMovimientoDisponibles = computed(() => {
    return naturalezaSeleccionada.value === 'ENTRADA'
        ? props.tipos_entrada
        : props.tipos_salida;
});

const form = useForm({
    naturaleza: 'ENTRADA',
    id_tipo_movimiento: props.tipos_entrada?.[0]?.id_tipo_movimiento || '',
    motivo: '',
    documento_referencia: '',
    detalles: [],
});

watch(naturalezaSeleccionada, (nuevaNaturaleza) => {
    form.naturaleza = nuevaNaturaleza;
    const tipos = nuevaNaturaleza === 'ENTRADA' ? props.tipos_entrada : props.tipos_salida;
    form.id_tipo_movimiento = tipos?.[0]?.id_tipo_movimiento || '';
});

// Selector de Productos
const busquedaProducto = ref('');
const idProductoSeleccionado = ref('');

const productosFiltrados = computed(() => {
    if (!busquedaProducto.value) return props.productos.slice(0, 30);
    const termino = busquedaProducto.value.toLowerCase();
    return props.productos.filter(p =>
        p.nombre.toLowerCase().includes(termino) ||
        p.sku.toLowerCase().includes(termino) ||
        (p.codigo_barras && p.codigo_barras.toLowerCase().includes(termino))
    ).slice(0, 30);
});

const AgregarProducto = (producto) => {
    if (!producto) return;

    // Verificar si ya está en la lista
    const existente = form.detalles.find(d => d.id_producto === producto.id_producto);
    if (existente) {
        existente.cantidad += 1;
        ActualizarCalculosFila(existente);
        return;
    }

    const nuevaFila = {
        id_producto: producto.id_producto,
        sku: producto.sku,
        nombre: producto.nombre,
        stock_actual: producto.stock_actual,
        id_unidad: producto.id_unidad,
        unidad_principal: producto.unidad,
        unidad_secundaria: producto.unidad_secundaria,
        equivalencia_unidad: Number(producto.equivalencia_unidad) || 1,
        equivalencia_unidad_secundaria: Number(producto.equivalencia_unidad_secundaria) || 1,
        costo_unitario: Number(producto.precio_costo),
        cantidad: 1,
        cantidad_base: 1,
        costo_total: Number(producto.precio_costo),
        observaciones: '',
        error_stock: false,
    };

    ActualizarCalculosFila(nuevaFila);
    form.detalles.push(nuevaFila);
    busquedaProducto.value = '';
};

const ActualizarCalculosFila = (fila) => {
    let factor = 1.0;
    if (fila.id_unidad === fila.unidad_principal?.id_unidad) {
        factor = fila.equivalencia_unidad;
    } else if (fila.unidad_secundaria && fila.id_unidad === fila.unidad_secundaria.id_unidad) {
        factor = fila.equivalencia_unidad_secundaria;
    }

    fila.cantidad_base = Math.round(Number(fila.cantidad || 0) * factor);
    fila.costo_total = Number((Number(fila.costo_unitario || 0) * Number(fila.cantidad || 0)).toFixed(2));

    // Validar si es salida y excede existencia
    if (naturalezaSeleccionada.value === 'SALIDA') {
        fila.error_stock = fila.cantidad_base > fila.stock_actual;
    } else {
        fila.error_stock = false;
    }
};

const EliminarFila = (index) => {
    form.detalles.splice(index, 1);
};

// Resúmenes y Totales
const totalItemsBase = computed(() => {
    return form.detalles.reduce((total, d) => total + (Number(d.cantidad_base) || 0), 0);
});

const totalCosto = computed(() => {
    return form.detalles.reduce((total, d) => total + (Number(d.costo_total) || 0), 0);
});

const tieneErroresStock = computed(() => {
    return naturalezaSeleccionada.value === 'SALIDA' && form.detalles.some(d => d.error_stock);
});

const RegistrarAjuste = () => {
    if (tieneErroresStock.value) {
        alert('No puede registrar un ajuste de salida con productos que excedan el stock disponible.');
        return;
    }
    form.post(route('inventario.ajustes.store'));
};
</script>

<template>
    <Head title="Nuevo Ajuste de Inventario" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <div class="flex items-center gap-2">
                        <Link
                            :href="route('inventario.ajustes.index')"
                            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 text-sm font-semibold flex items-center gap-1"
                        >
                            ← Ajustes
                        </Link>
                        <span class="text-gray-300">/</span>
                        <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-100">
                            Registrar Documento de Ajuste
                        </h2>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Genere ajustes de inventario con cabecera, detalles y control estricto de naturaleza (Entrada o Salida).
                    </p>
                </div>

                <div class="font-mono bg-gray-100 dark:bg-gray-700 px-3 py-1.5 rounded-xl text-sm font-bold text-gray-700 dark:text-gray-200 border border-gray-200 dark:border-gray-600">
                    Doc: {{ codigo_sugerido }}
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

                <form @submit.prevent="RegistrarAjuste" class="space-y-6">

                    <!-- SECCIÓN 1: CABECERA DEL AJUSTE -->
                    <div class="bg-white dark:bg-gray-800 p-6 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 space-y-5">
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 pb-4 border-b border-gray-100 dark:border-gray-700">
                            <div>
                                <h3 class="text-base font-bold text-gray-900 dark:text-gray-100">
                                    Paso 1: Naturaleza y Tipo de Movimiento
                                </h3>
                                <p class="text-xs text-gray-500">
                                    Seleccione si la operación agregará o deducirá existencias físicas.
                                </p>
                            </div>

                            <!-- Selector de Naturaleza (Toggle Grande) -->
                            <div class="inline-flex p-1 bg-gray-100 dark:bg-gray-700 rounded-2xl">
                                <button
                                    type="button"
                                    @click="naturalezaSeleccionada = 'ENTRADA'"
                                    :class="[
                                        'px-5 py-2 rounded-xl text-sm font-bold transition flex items-center gap-2',
                                        naturalezaSeleccionada === 'ENTRADA'
                                            ? 'bg-emerald-600 text-white shadow-md'
                                            : 'text-gray-600 dark:text-gray-300 hover:text-gray-900'
                                    ]"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4"/>
                                    </svg>
                                    ENTRADA (Incremento)
                                </button>
                                <button
                                    type="button"
                                    @click="naturalezaSeleccionada = 'SALIDA'"
                                    :class="[
                                        'px-5 py-2 rounded-xl text-sm font-bold transition flex items-center gap-2',
                                        naturalezaSeleccionada === 'SALIDA'
                                            ? 'bg-rose-600 text-white shadow-md'
                                            : 'text-gray-600 dark:text-gray-300 hover:text-gray-900'
                                    ]"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M20 12H4"/>
                                    </svg>
                                    SALIDA (Deducción)
                                </button>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">
                                    Tipo de Movimiento * (Filtrado por {{ naturalezaSeleccionada }})
                                </label>
                                <select
                                    v-model="form.id_tipo_movimiento"
                                    required
                                    class="w-full py-2.5 px-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-sm font-semibold"
                                >
                                    <option v-for="tipo in tiposMovimientoDisponibles" :key="tipo.id_tipo_movimiento" :value="tipo.id_tipo_movimiento">
                                        {{ tipo.nombre }}
                                    </option>
                                </select>
                                <p v-if="form.errors.id_tipo_movimiento" class="text-xs text-rose-500 mt-1">
                                    {{ form.errors.id_tipo_movimiento }}
                                </p>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">
                                    Documento de Referencia (Opcional)
                                </label>
                                <input
                                    type="text"
                                    v-model="form.documento_referencia"
                                    placeholder="Ej: Factura Proveedor F-4920, Guía Nro. 12"
                                    class="w-full py-2.5 px-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-sm"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">
                                    Motivo / Justificación *
                                </label>
                                <input
                                    type="text"
                                    v-model="form.motivo"
                                    required
                                    placeholder="Explique el motivo del ajuste..."
                                    class="w-full py-2.5 px-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-sm"
                                />
                                <p v-if="form.errors.motivo" class="text-xs text-rose-500 mt-1">
                                    {{ form.errors.motivo }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- SECCIÓN 2: DETALLES DE PRODUCTOS -->
                    <div class="bg-white dark:bg-gray-800 p-6 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 space-y-5">
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 pb-4 border-b border-gray-100 dark:border-gray-700">
                            <div>
                                <h3 class="text-base font-bold text-gray-900 dark:text-gray-100">
                                    Paso 2: Detalle de Productos a Ajustar
                                </h3>
                                <p class="text-xs text-gray-500">
                                    Busque y agregue productos a la grilla, seleccione la unidad (con factor multiescala automático) y especifique cantidades.
                                </p>
                            </div>

                            <!-- Buscador Rápido de Producto para Agregar -->
                            <div class="w-full sm:w-80">
                                <div class="relative">
                                    <input
                                        type="text"
                                        v-model="busquedaProducto"
                                        placeholder="Buscar producto a agregar..."
                                        class="w-full pl-9 pr-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-xs"
                                    />
                                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                        🔍
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Lista Desplegable si hay búsqueda -->
                        <div v-if="busquedaProducto" class="max-h-48 overflow-y-auto bg-gray-50 dark:bg-gray-700/50 p-2 rounded-2xl border border-gray-200 dark:border-gray-600 grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <div
                                v-for="prod in productosFiltrados"
                                :key="prod.id_producto"
                                @click="AgregarProducto(prod)"
                                class="p-2.5 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 hover:border-indigo-500 cursor-pointer flex justify-between items-center transition"
                            >
                                <div>
                                    <div class="text-xs font-bold text-gray-900 dark:text-gray-100">{{ prod.nombre }}</div>
                                    <div class="text-[10px] text-gray-400 font-mono">SKU: {{ prod.sku }} | Stock: {{ prod.stock_actual }} {{ prod.unidad?.abreviatura || 'UND' }}</div>
                                </div>
                                <span class="px-2 py-1 bg-indigo-50 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-300 text-[10px] font-bold rounded-lg">
                                    + Agregar
                                </span>
                            </div>
                        </div>

                        <!-- Grilla de Detalles -->
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-xs">
                                <thead class="bg-gray-50 dark:bg-gray-700/50">
                                    <tr>
                                        <th class="px-4 py-3 text-left font-bold text-gray-500 uppercase">Producto</th>
                                        <th class="px-4 py-3 text-center font-bold text-gray-500 uppercase">Stock Actual</th>
                                        <th class="px-4 py-3 text-left font-bold text-gray-500 uppercase">Unidad</th>
                                        <th class="px-4 py-3 text-right font-bold text-gray-500 uppercase">Cantidad</th>
                                        <th class="px-4 py-3 text-center font-bold text-gray-500 uppercase">Unidades Base</th>
                                        <th class="px-4 py-3 text-right font-bold text-gray-500 uppercase">Costo Unitario USD</th>
                                        <th class="px-4 py-3 text-right font-bold text-gray-500 uppercase">Total USD</th>
                                        <th class="px-4 py-3 text-left font-bold text-gray-500 uppercase">Observación</th>
                                        <th class="px-4 py-3 text-center font-bold text-gray-500 uppercase">Quitar</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr
                                        v-for="(fila, index) in form.detalles"
                                        :key="fila.id_producto"
                                        :class="{'bg-rose-50/60 dark:bg-rose-950/20': fila.error_stock}"
                                    >
                                        <td class="px-4 py-3">
                                            <div class="font-bold text-gray-900 dark:text-gray-100">{{ fila.nombre }}</div>
                                            <div class="text-[10px] text-gray-400 font-mono">SKU: {{ fila.sku }}</div>
                                            <div v-if="fila.error_stock" class="text-[10px] font-bold text-rose-600 mt-1">
                                                ⚠️ Stock insuficiente para salida (Disponible: {{ fila.stock_actual }})
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-center font-mono font-bold text-gray-700 dark:text-gray-300">
                                            {{ fila.stock_actual }}
                                        </td>
                                        <td class="px-4 py-3">
                                            <select
                                                v-model="fila.id_unidad"
                                                @change="ActualizarCalculosFila(fila)"
                                                class="py-1 px-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-xs"
                                            >
                                                <option :value="fila.unidad_principal?.id_unidad">
                                                    {{ fila.unidad_principal?.nombre }} (1x)
                                                </option>
                                                <option v-if="fila.unidad_secundaria" :value="fila.unidad_secundaria?.id_unidad">
                                                    {{ fila.unidad_secundaria?.nombre }} ({{ fila.equivalencia_unidad_secundaria }}x)
                                                </option>
                                            </select>
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <input
                                                type="number"
                                                step="0.001"
                                                min="0.001"
                                                v-model="fila.cantidad"
                                                @input="ActualizarCalculosFila(fila)"
                                                class="w-20 py-1 px-2 text-right bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-xs font-mono font-bold"
                                            />
                                        </td>
                                        <td class="px-4 py-3 text-center font-mono font-bold text-indigo-600 dark:text-indigo-400">
                                            {{ fila.cantidad_base }}
                                        </td>
                                        <td class="px-4 py-3 text-right font-mono">
                                            <input
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                v-model="fila.costo_unitario"
                                                @input="ActualizarCalculosFila(fila)"
                                                class="w-24 py-1 px-2 text-right bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-xs font-mono"
                                            />
                                        </td>
                                        <td class="px-4 py-3 text-right font-mono font-bold text-gray-900 dark:text-gray-100">
                                            ${{ fila.costo_total.toFixed(2) }}
                                        </td>
                                        <td class="px-4 py-3">
                                            <input
                                                type="text"
                                                v-model="fila.observaciones"
                                                placeholder="Comentario de línea"
                                                class="w-36 py-1 px-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-xs"
                                            />
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <button
                                                type="button"
                                                @click="EliminarFila(index)"
                                                class="text-rose-500 hover:text-rose-700 font-bold text-sm"
                                            >
                                                ✕
                                            </button>
                                        </td>
                                    </tr>
                                    <tr v-if="form.detalles.length === 0">
                                        <td colspan="9" class="px-4 py-8 text-center text-gray-400">
                                            Aún no ha agregado productos al ajuste. Utilice el buscador superior para agregar ítems.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Resumen y Totales -->
                        <div class="flex flex-col sm:flex-row justify-between items-center p-5 bg-gray-50 dark:bg-gray-750 rounded-2xl border border-gray-200 dark:border-gray-700 gap-4">
                            <div class="space-y-1">
                                <div class="text-xs text-gray-500">
                                    Líneas de producto: <span class="font-bold text-gray-800 dark:text-gray-200">{{ form.detalles.length }}</span>
                                </div>
                                <div class="text-xs text-gray-500">
                                    Total unidades base a {{ naturalezaSeleccionada === 'ENTRADA' ? 'sumar' : 'restar' }}:
                                    <span class="font-bold text-gray-800 dark:text-gray-200 font-mono">{{ totalItemsBase }}</span>
                                </div>
                            </div>

                            <div class="text-right">
                                <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    Costo Total Estimado
                                </div>
                                <div class="text-2xl font-black text-gray-900 dark:text-gray-100 font-mono">
                                    ${{ totalCosto.toFixed(2) }}
                                </div>
                            </div>
                        </div>

                        <p v-if="form.errors.detalles" class="text-xs text-rose-500">
                            {{ form.errors.detalles }}
                        </p>
                    </div>

                    <!-- Botones de Acción -->
                    <div class="flex items-center justify-end gap-3">
                        <Link
                            :href="route('inventario.ajustes.index')"
                            class="px-5 py-2.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl text-sm font-semibold transition"
                        >
                            Cancelar
                        </Link>
                        <button
                            type="submit"
                            :disabled="form.processing || form.detalles.length === 0 || tieneErroresStock"
                            :class="[
                                'px-6 py-2.5 rounded-xl text-sm font-semibold text-white transition shadow-sm disabled:opacity-50',
                                naturalezaSeleccionada === 'ENTRADA'
                                    ? 'bg-emerald-600 hover:bg-emerald-700'
                                    : 'bg-rose-600 hover:bg-rose-700'
                            ]"
                        >
                            {{ form.processing ? 'Aplicando Ajuste...' : `Aplicar Ajuste de ${naturalezaSeleccionada}` }}
                        </button>
                    </div>

                </form>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
