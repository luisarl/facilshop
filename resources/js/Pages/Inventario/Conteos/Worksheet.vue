<script setup>
import { ref, computed, nextTick } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    conteo: Object,
});

const inputScanner = ref(null);
const codigoEscaneado = ref('');
const mensajeEscaneo = ref(null);
const filtroDiscrepancia = ref('TODOS'); // 'TODOS', 'DISCREPANCIAS', 'EXACTOS'
const busquedaTexto = ref('');

// Clon reactivo de detalles para manipulación en vivo
const items = ref(
    props.conteo.detalles.map(d => ({
        id_conteo_detalle: d.id_conteo_detalle,
        id_producto: d.id_producto,
        nombre: d.producto?.nombre,
        sku: d.producto?.sku,
        codigo_barras: d.producto?.codigo_barras,
        unidad: d.producto?.unidad?.nombre || 'UND',
        stock_teorico: Number(d.stock_teorico),
        stock_fisico: Number(d.stock_fisico),
        costo_unitario: Number(d.costo_unitario),
        observaciones: d.observaciones || '',
        resaltado: false,
    }))
);

// Cálculos dinámicos
const itemsCalculados = computed(() => {
    return items.value.map(item => {
        const diferencia = Number(item.stock_fisico) - Number(item.stock_teorico);
        const valor_diferencia = Number((diferencia * item.costo_unitario).toFixed(2));
        return {
            ...item,
            diferencia,
            valor_diferencia,
        };
    });
});

const itemsFiltrados = computed(() => {
    return itemsCalculados.value.filter(item => {
        // Filtro por texto
        if (busquedaTexto.value) {
            const t = busquedaTexto.value.toLowerCase();
            const matchTexto = item.nombre.toLowerCase().includes(t) ||
                item.sku.toLowerCase().includes(t) ||
                (item.codigo_barras && item.codigo_barras.toLowerCase().includes(t));
            if (!matchTexto) return false;
        }

        // Filtro por discrepancia
        if (filtroDiscrepancia.value === 'DISCREPANCIAS') {
            return item.diferencia !== 0;
        }
        if (filtroDiscrepancia.value === 'EXACTOS') {
            return item.diferencia === 0;
        }
        return true;
    });
});

const totalItemsContados = computed(() => {
    return itemsCalculados.value.reduce((total, i) => total + Number(i.stock_fisico || 0), 0);
});

const totalDiferenciaUnidades = computed(() => {
    return itemsCalculados.value.reduce((total, i) => total + Number(i.diferencia || 0), 0);
});

const totalDiferenciaCosto = computed(() => {
    return itemsCalculados.value.reduce((total, i) => total + Number(i.valor_diferencia || 0), 0);
});

const totalDiscrepancias = computed(() => {
    return itemsCalculados.value.filter(i => i.diferencia !== 0).length;
});

// Manejador del Escáner de Código de Barras
const ProcesarCodigoBarras = () => {
    const codigo = codigoEscaneado.value.trim();
    if (!codigo) return;

    const itemEncontrado = items.value.find(i =>
        i.codigo_barras === codigo || i.sku === codigo
    );

    if (itemEncontrado) {
        itemEncontrado.stock_fisico += 1;
        itemEncontrado.resaltado = true;
        mensajeEscaneo.value = {
            tipo: 'exito',
            texto: `+1 contado: ${itemEncontrado.nombre} (Total: ${itemEncontrado.stock_fisico})`
        };

        setTimeout(() => {
            itemEncontrado.resaltado = false;
        }, 1200);
    } else {
        mensajeEscaneo.value = {
            tipo: 'error',
            texto: `Código "${codigo}" no pertenece a este conteo o no está registrado.`
        };
    }

    codigoEscaneado.value = '';
    setTimeout(() => {
        mensajeEscaneo.value = null;
    }, 3000);
};

// Guardar Avance (Borrador)
const guardando = ref(false);
const GuardarProgreso = () => {
    guardando.value = true;
    const payload = items.value.map(i => ({
        id_conteo_detalle: i.id_conteo_detalle,
        stock_fisico: Number(i.stock_fisico),
        observaciones: i.observaciones,
    }));

    router.post(route('inventario.conteos.detalles', props.conteo.id_conteo), {
        items: payload,
    }, {
        preserveScroll: true,
        onFinish: () => {
            guardando.value = false;
        },
    });
};

// Aplicar Conciliación
const modalAplicarAbierto = ref(false);
const aplicando = ref(false);

const ConfirmarAplicar = () => {
    // Primero guardar el estado actual
    const payload = items.value.map(i => ({
        id_conteo_detalle: i.id_conteo_detalle,
        stock_fisico: Number(i.stock_fisico),
        observaciones: i.observaciones,
    }));

    aplicando.value = true;
    router.post(route('inventario.conteos.detalles', props.conteo.id_conteo), {
        items: payload,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            router.post(route('inventario.conteos.aplicar', props.conteo.id_conteo), {}, {
                onFinish: () => {
                    aplicando.value = false;
                    modalAplicarAbierto.value = false;
                },
            });
        },
    });
};

const CancelarConteo = () => {
    if (confirm('¿Está seguro de cancelar esta sesión de toma física?')) {
        router.post(route('inventario.conteos.cancelar', props.conteo.id_conteo));
    }
};

const Imprimir = () => {
    window.print();
};
</script>

<template>
    <Head :title="`Toma Física ${conteo.codigo_conteo}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 no-print">
                <div>
                    <div class="flex items-center gap-2">
                        <Link
                            :href="route('inventario.conteos.index')"
                            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 text-sm font-semibold flex items-center gap-1"
                        >
                            ← Conteos
                        </Link>
                        <span class="text-gray-300">/</span>
                        <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-100 flex items-center gap-2">
                            Hoja de Toma Física {{ conteo.codigo_conteo }}
                        </h2>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        {{ conteo.descripcion }} | Responsable: {{ conteo.usuario?.nombre }}
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <button
                        @click="Imprimir"
                        class="px-3.5 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 text-xs font-semibold rounded-xl transition flex items-center gap-1.5"
                    >
                        🖨️ Imprimir
                    </button>

                    <template v-if="conteo.estado === 'EN_PROCESO'">
                        <button
                            @click="GuardarProgreso"
                            :disabled="guardando"
                            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-xl transition shadow-sm disabled:opacity-50"
                        >
                            {{ guardando ? 'Guardando...' : '💾 Guardar Avance' }}
                        </button>
                        <button
                            @click="modalAplicarAbierto = true"
                            class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-xl transition shadow-sm"
                        >
                            ⚖️ Conciliar Inventario
                        </button>
                        <button
                            @click="CancelarConteo"
                            class="px-3 py-2 text-rose-500 hover:text-rose-700 text-xs font-semibold rounded-xl transition"
                        >
                            Cancelar
                        </button>
                    </template>

                    <span
                        v-else
                        :class="[
                            'px-3 py-1.5 rounded-xl text-xs font-bold uppercase',
                            conteo.estado === 'APLICADO'
                                ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300'
                                : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300'
                        ]"
                    >
                        ESTADO: {{ conteo.estado }}
                    </span>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

                <!-- TARJETAS DE MÉTRICAS -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 no-print">
                    <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm">
                        <div class="text-xs font-bold text-gray-400 uppercase tracking-wider">
                            Total Unidades Contadas
                        </div>
                        <div class="text-2xl font-black text-gray-900 dark:text-gray-100 font-mono mt-1">
                            {{ totalItemsContados }}
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm">
                        <div class="text-xs font-bold text-gray-400 uppercase tracking-wider">
                            Discrepancias Detectadas
                        </div>
                        <div class="text-2xl font-black font-mono mt-1 text-amber-500">
                            {{ totalDiscrepancias }} productos
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm">
                        <div class="text-xs font-bold text-gray-400 uppercase tracking-wider">
                            Diferencia Neta Unidades
                        </div>
                        <div
                            :class="[
                                'text-2xl font-black font-mono mt-1',
                                totalDiferenciaUnidades === 0
                                    ? 'text-emerald-600'
                                    : (totalDiferenciaUnidades > 0 ? 'text-blue-600' : 'text-rose-600')
                            ]"
                        >
                            {{ totalDiferenciaUnidades > 0 ? '+' : '' }}{{ totalDiferenciaUnidades }}
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm">
                        <div class="text-xs font-bold text-gray-400 uppercase tracking-wider">
                            Impacto en Costo USD
                        </div>
                        <div
                            :class="[
                                'text-2xl font-black font-mono mt-1',
                                totalDiferenciaCosto === 0
                                    ? 'text-gray-700 dark:text-gray-200'
                                    : (totalDiferenciaCosto > 0 ? 'text-blue-600' : 'text-rose-600')
                            ]"
                        >
                            ${{ totalDiferenciaCosto.toFixed(2) }}
                        </div>
                    </div>
                </div>

                <!-- LECTOR RÁPIDO DE CÓDIGO DE BARRAS (SCANNER INPUT) -->
                <div v-if="conteo.estado === 'EN_PROCESO'" class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-indigo-100 dark:border-indigo-900/50 shadow-sm no-print">
                    <form @submit.prevent="ProcesarCodigoBarras" class="flex flex-col sm:flex-row items-center gap-3">
                        <div class="relative w-full">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-indigo-500">
                                📶
                            </span>
                            <input
                                ref="inputScanner"
                                type="text"
                                v-model="codigoEscaneado"
                                autofocus
                                placeholder="Escanee con el lector o ingrese el código de barras / SKU y presione Enter..."
                                class="w-full pl-10 pr-4 py-3 bg-indigo-50/50 dark:bg-gray-700/50 border border-indigo-200 dark:border-indigo-800 rounded-xl text-sm font-mono text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent placeholder:text-gray-400"
                            />
                        </div>
                        <button
                            type="submit"
                            class="w-full sm:w-auto px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-semibold transition whitespace-nowrap"
                        >
                            Registrar Lectura
                        </button>
                    </form>

                    <!-- Feedback Visual del Escaneo -->
                    <div
                        v-if="mensajeEscaneo"
                        :class="[
                            'mt-3 p-3 rounded-xl text-xs font-semibold flex items-center gap-2 transition',
                            mensajeEscaneo.tipo === 'exito'
                                ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300'
                                : 'bg-rose-100 text-rose-800 dark:bg-rose-950/40 dark:text-rose-300'
                        ]"
                    >
                        <span>{{ mensajeEscaneo.tipo === 'exito' ? '✅' : '❌' }}</span>
                        <span>{{ mensajeEscaneo.texto }}</span>
                    </div>
                </div>

                <!-- CONTROLES Y FILTROS DE TABLA -->
                <div class="bg-white dark:bg-gray-800 p-4 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm flex flex-col sm:flex-row justify-between items-center gap-4 no-print">
                    <div class="flex flex-wrap items-center gap-2">
                        <button
                            type="button"
                            @click="filtroDiscrepancia = 'TODOS'"
                            :class="[
                                'px-3 py-1.5 rounded-xl text-xs font-semibold transition',
                                filtroDiscrepancia === 'TODOS'
                                    ? 'bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900'
                                    : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300'
                            ]"
                        >
                            Todos ({{ items.length }})
                        </button>
                        <button
                            type="button"
                            @click="filtroDiscrepancia = 'DISCREPANCIAS'"
                            :class="[
                                'px-3 py-1.5 rounded-xl text-xs font-semibold transition',
                                filtroDiscrepancia === 'DISCREPANCIAS'
                                    ? 'bg-amber-500 text-white'
                                    : 'bg-amber-50 text-amber-800 dark:bg-amber-950/40 dark:text-amber-300'
                            ]"
                        >
                            Solo Discrepancias ({{ totalDiscrepancias }})
                        </button>
                        <button
                            type="button"
                            @click="filtroDiscrepancia = 'EXACTOS'"
                            :class="[
                                'px-3 py-1.5 rounded-xl text-xs font-semibold transition',
                                filtroDiscrepancia === 'EXACTOS'
                                    ? 'bg-emerald-600 text-white'
                                    : 'bg-emerald-50 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300'
                            ]"
                        >
                            Exactos ({{ items.length - totalDiscrepancias }})
                        </button>
                    </div>

                    <div class="w-full sm:w-64">
                        <input
                            type="text"
                            v-model="busquedaTexto"
                            placeholder="Buscar en la hoja..."
                            class="w-full py-1.5 px-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-xs"
                        />
                    </div>
                </div>

                <!-- GRILLA INTERACTIVA DE TOMA FÍSICA -->
                <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-xs">
                            <thead class="bg-gray-50 dark:bg-gray-700/50">
                                <tr>
                                    <th class="px-4 py-3 text-left font-bold text-gray-500 uppercase">Producto / SKU / EAN</th>
                                    <th class="px-4 py-3 text-center font-bold text-gray-500 uppercase">Unidad</th>
                                    <th class="px-4 py-3 text-center font-bold text-gray-500 uppercase">Stock Teórico</th>
                                    <th class="px-4 py-3 text-center font-bold text-gray-500 uppercase">Conteo Físico</th>
                                    <th class="px-4 py-3 text-center font-bold text-gray-500 uppercase">Diferencia</th>
                                    <th class="px-4 py-3 text-right font-bold text-gray-500 uppercase">Costo Unitario</th>
                                    <th class="px-4 py-3 text-right font-bold text-gray-500 uppercase">Impacto USD</th>
                                    <th class="px-4 py-3 text-left font-bold text-gray-500 uppercase no-print">Observación</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700 font-mono">
                                <tr
                                    v-for="fila in itemsFiltrados"
                                    :key="fila.id_conteo_detalle"
                                    :class="[
                                        'transition',
                                        fila.resaltado ? 'bg-indigo-100 dark:bg-indigo-900/50' : 'hover:bg-gray-50/50 dark:hover:bg-gray-700/30'
                                    ]"
                                >
                                    <td class="px-4 py-3 font-sans">
                                        <div class="font-bold text-gray-900 dark:text-gray-100">{{ fila.nombre }}</div>
                                        <div class="text-[10px] text-gray-400 font-mono flex items-center gap-2 mt-0.5">
                                            <span>SKU: {{ fila.sku }}</span>
                                            <span v-if="fila.codigo_barras">| EAN: {{ fila.codigo_barras }}</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-center text-gray-600 dark:text-gray-300 font-sans">
                                        {{ fila.unidad }}
                                    </td>
                                    <td class="px-4 py-3 text-center font-bold text-gray-500">
                                        {{ fila.stock_teorico }}
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <input
                                            v-if="conteo.estado === 'EN_PROCESO'"
                                            type="number"
                                            min="0"
                                            v-model="fila.stock_fisico"
                                            class="w-24 py-1 px-2 text-center bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-xs font-bold font-mono focus:ring-2 focus:ring-indigo-500"
                                        />
                                        <span v-else class="font-bold text-gray-800 dark:text-gray-200">
                                            {{ fila.stock_fisico }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span
                                            :class="[
                                                'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold',
                                                fila.diferencia === 0
                                                    ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300'
                                                    : (fila.diferencia > 0
                                                        ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300'
                                                        : 'bg-rose-100 text-rose-800 dark:bg-rose-900/50 dark:text-rose-300')
                                            ]"
                                        >
                                            {{ fila.diferencia > 0 ? '+' : '' }}{{ fila.diferencia }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right text-gray-500">
                                        ${{ fila.costo_unitario.toFixed(2) }}
                                    </td>
                                    <td class="px-4 py-3 text-right font-bold">
                                        <span
                                            :class="[
                                                fila.valor_diferencia === 0
                                                    ? 'text-gray-500'
                                                    : (fila.valor_diferencia > 0 ? 'text-blue-600' : 'text-rose-600')
                                            ]"
                                        >
                                            ${{ fila.valor_diferencia.toFixed(2) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 font-sans no-print">
                                        <input
                                            v-if="conteo.estado === 'EN_PROCESO'"
                                            type="text"
                                            v-model="fila.observaciones"
                                            placeholder="Nota opcional..."
                                            class="w-full py-1 px-2 bg-transparent border-0 border-b border-gray-200 dark:border-gray-600 focus:ring-0 text-xs"
                                        />
                                        <span v-else class="text-gray-400 text-[11px]">
                                            {{ fila.observaciones || '—' }}
                                        </span>
                                    </td>
                                </tr>
                                <tr v-if="itemsFiltrados.length === 0">
                                    <td colspan="8" class="px-4 py-8 text-center text-gray-400">
                                        No hay productos que coincidan con el filtro actual.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>

        <!-- MODAL CONFIRMACIÓN DE CONCILIACIÓN -->
        <div v-if="modalAplicarAbierto" class="fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white dark:bg-gray-800 rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-gray-100 dark:border-gray-700">
                <div class="flex items-center gap-3 text-emerald-600 mb-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-100 dark:bg-emerald-950/50 flex items-center justify-center font-bold text-lg">
                        ⚖️
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">
                        Conciliar y Aplicar Toma Física
                    </h3>
                </div>

                <p class="text-xs text-gray-600 dark:text-gray-300 leading-relaxed">
                    Al confirmar, el sistema actualizará de manera definitiva las existencias en inventario al valor del conteo físico. Se generarán movimientos automáticos con el tipo <strong>Conciliación por Conteo Físico (AJUSTE_CONTEO)</strong> para cada producto que tenga diferencia.
                </p>

                <div class="my-4 p-4 bg-gray-50 dark:bg-gray-750 rounded-2xl border border-gray-200 dark:border-gray-700 space-y-2 text-xs">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Productos con ajuste:</span>
                        <span class="font-bold text-gray-800 dark:text-gray-200">{{ totalDiscrepancias }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Diferencia neta unidades:</span>
                        <span class="font-bold font-mono">{{ totalDiferenciaUnidades > 0 ? '+' : '' }}{{ totalDiferenciaUnidades }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Impacto total costo:</span>
                        <span class="font-bold font-mono text-emerald-600 dark:text-emerald-400">${{ totalDiferenciaCosto.toFixed(2) }}</span>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <button
                        type="button"
                        @click="modalAplicarAbierto = false"
                        class="px-4 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl text-xs font-semibold"
                    >
                        Revisar Conteo
                    </button>
                    <button
                        type="button"
                        @click="ConfirmarAplicar"
                        :disabled="aplicando"
                        class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-semibold transition disabled:opacity-50"
                    >
                        {{ aplicando ? 'Aplicando conciliación...' : 'Confirmar y Actualizar Inventario' }}
                    </button>
                </div>
            </div>
        </div>

    </AuthenticatedLayout>
</template>

<style scoped>
@media print {
    .no-print {
        display: none !important;
    }
}
</style>
