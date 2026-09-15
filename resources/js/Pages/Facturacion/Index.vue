<script setup>
import { ref, watch, nextTick } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import QRCode from 'qrcode';

const props = defineProps({
    ventas: Object,
    metricas: Object,
    filtros: Object,
    metodosPago: Array,
});

// Filtros
const buscar = ref(props.filtros?.buscar || '');
const tipoComprobante = ref(props.filtros?.tipo_comprobante || '');
const estado = ref(props.filtros?.estado || '');
const fechaDesde = ref(props.filtros?.fecha_desde || '');
const fechaHasta = ref(props.filtros?.fecha_hasta || '');
const soloCashea = ref(props.filtros?.solo_cashea === 'true' || false);

const AplicarFiltros = () => {
    router.get(route('facturacion.index'), {
        buscar: buscar.value || undefined,
        tipo_comprobante: tipoComprobante.value || undefined,
        estado: estado.value || undefined,
        fecha_desde: fechaDesde.value || undefined,
        fecha_hasta: fechaHasta.value || undefined,
        solo_cashea: soloCashea.value ? 'true' : undefined,
    }, {
        preserveState: true,
        replace: true,
    });
};

const LimpiarFiltros = () => {
    buscar.value = '';
    tipoComprobante.value = '';
    estado.value = '';
    fechaDesde.value = '';
    fechaHasta.value = '';
    soloCashea.value = false;
    AplicarFiltros();
};

// Modal Detalle / Factura con QR
const modalDetalleAbierto = ref(false);
const ventaSeleccionada = ref(null);
const qrDataUrl = ref('');

const AbrirModalDetalle = async (venta) => {
    ventaSeleccionada.value = venta;
    modalDetalleAbierto.value = true;

    // Generar código QR verificador
    const qrPayload = JSON.stringify({
        facilshop: true,
        comprobante: venta.numero_comprobante,
        fecha: venta.created_at,
        cliente: venta.cliente?.identificacion || 'CONSUMIDOR_FINAL',
        total_usd: venta.total,
        estado: venta.estado,
    });

    try {
        qrDataUrl.value = await QRCode.toDataURL(qrPayload, {
            width: 160,
            margin: 1,
            color: {
                dark: '#0f172a',
                light: '#ffffff',
            },
        });
    } catch (e) {
        console.error('Error al generar QR:', e);
    }
};

const ImprimirFactura = () => {
    window.print();
};

// Modal Anular Venta
const modalAnularAbierto = ref(false);
const ventaAnular = ref(null);

const formAnular = useForm({
    motivo: '',
});

const AbrirModalAnular = (venta) => {
    ventaAnular.value = venta;
    formAnular.reset();
    formAnular.clearErrors();
    modalAnularAbierto.value = true;
};

const ProcesarAnulacion = () => {
    if (!ventaAnular.value) return;

    formAnular.post(route('facturacion.anular', ventaAnular.value.id_venta), {
        onSuccess: () => {
            modalAnularAbierto.value = false;
        },
    });
};
</script>

<template>
    <Head title="Historial de Ventas y Facturación" />

    <AuthenticatedLayout>
        <div class="py-6 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto space-y-6">

            <!-- Cabecera -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <span>📑</span> Facturación Interna y Registro de Ventas
                    </h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Historial correlativo de comprobantes, reimpresión térmica ESC/POS, facturas con QR y anulación.
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <a
                        :href="route('pos.index')"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg shadow-sm transition"
                    >
                        <span>🛒</span> Ir al Punto de Venta (POS)
                    </a>
                </div>
            </div>

            <!-- KPIs de Facturación -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-100 dark:border-gray-700 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Facturado Hoy (USD)
                        </span>
                        <span class="p-2 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 rounded-lg text-sm">
                            💰
                        </span>
                    </div>
                    <div class="mt-2 flex items-baseline gap-2">
                        <span class="text-2xl font-bold text-gray-900 dark:text-white">
                            ${{ Number(metricas.total_hoy_usd).toFixed(2) }}
                        </span>
                        <span class="text-xs text-gray-500">USD</span>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        ≈ Bs. {{ Number(metricas.total_hoy_ves).toLocaleString('es-VE', { minimumFractionDigits: 2 }) }}
                    </p>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-100 dark:border-gray-700 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Comprobantes Emitidos
                        </span>
                        <span class="p-2 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 rounded-lg text-sm">
                            🧾
                        </span>
                    </div>
                    <div class="mt-2 flex items-baseline gap-2">
                        <span class="text-2xl font-bold text-gray-900 dark:text-white">
                            {{ metricas.comprobantes_hoy }}
                        </span>
                        <span class="text-xs text-gray-500">hoy</span>
                    </div>
                    <p class="text-xs text-gray-400 mt-1">
                        Tickets, Boletas y Facturas
                    </p>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-100 dark:border-gray-700 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Colocado con Cashea
                        </span>
                        <span class="p-2 bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 rounded-lg text-sm">
                            💛
                        </span>
                    </div>
                    <div class="mt-2 flex items-baseline gap-2">
                        <span class="text-2xl font-bold text-amber-600 dark:text-amber-400">
                            ${{ Number(metricas.total_cashea_usd).toFixed(2) }}
                        </span>
                        <span class="text-xs text-gray-500 font-mono font-bold">{{ metricas.ventas_cashea_hoy }} ventas</span>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Financiado en 3 cuotas quincenales
                    </p>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-100 dark:border-gray-700 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Ventas Anuladas
                        </span>
                        <span class="p-2 bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400 rounded-lg text-sm">
                            🚫
                        </span>
                    </div>
                    <div class="mt-2 flex items-baseline gap-2">
                        <span class="text-2xl font-bold text-red-600 dark:text-red-400">
                            {{ metricas.anuladas_hoy }}
                        </span>
                        <span class="text-xs text-gray-500">hoy</span>
                    </div>
                    <p class="text-xs text-emerald-600 dark:text-emerald-400 mt-1">
                        Stock revertido automáticamente
                    </p>
                </div>
            </div>

            <!-- Barra de Filtros -->
            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700 shadow-sm space-y-3">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
                    <div class="lg:col-span-2">
                        <label class="block text-[11px] font-semibold text-gray-500 uppercase mb-1">Buscar</label>
                        <input
                            v-model="buscar"
                            @keyup.enter="AplicarFiltros"
                            type="text"
                            placeholder="Nro. Comprobante, Cliente o Cédula..."
                            class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        />
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-gray-500 uppercase mb-1">Comprobante</label>
                        <select
                            v-model="tipoComprobante"
                            @change="AplicarFiltros"
                            class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        >
                            <option value="">Todos</option>
                            <option value="TICKET">Ticket</option>
                            <option value="FACTURA">Factura</option>
                            <option value="BOLETA">Boleta</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-gray-500 uppercase mb-1">Estado</label>
                        <select
                            v-model="estado"
                            @change="AplicarFiltros"
                            class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        >
                            <option value="">Todos</option>
                            <option value="COMPLETADA">Completada</option>
                            <option value="ANULADA">Anulada</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-gray-500 uppercase mb-1">Desde</label>
                        <input
                            v-model="fechaDesde"
                            @change="AplicarFiltros"
                            type="date"
                            class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        />
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-gray-500 uppercase mb-1">Hasta</label>
                        <input
                            v-model="fechaHasta"
                            @change="AplicarFiltros"
                            type="date"
                            class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        />
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row justify-between items-center pt-2 border-t border-gray-100 dark:border-gray-700 gap-2">
                    <label class="inline-flex items-center gap-2 cursor-pointer text-xs text-gray-700 dark:text-gray-300">
                        <input
                            type="checkbox"
                            v-model="soloCashea"
                            @change="AplicarFiltros"
                            class="rounded border-gray-300 text-amber-500 focus:ring-amber-400"
                        />
                        <span class="font-semibold text-amber-600 dark:text-amber-400">💛 Solo ventas con Cashea (BNPL)</span>
                    </label>

                    <div class="flex items-center gap-2">
                        <button
                            @click="AplicarFiltros"
                            class="px-4 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg transition"
                        >
                            Filtrar
                        </button>
                        <button
                            v-if="buscar || tipoComprobante || estado || fechaDesde || fechaHasta || soloCashea"
                            @click="LimpiarFiltros"
                            class="px-3 py-1.5 text-xs text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition"
                        >
                            Limpiar
                        </button>
                    </div>
                </div>
            </div>

            <!-- Tabla de Ventas -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300">
                        <thead class="bg-gray-50 dark:bg-gray-900/50 text-xs uppercase font-semibold text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
                            <tr>
                                <th class="px-5 py-3.5">Comprobante</th>
                                <th class="px-5 py-3.5">Fecha y Hora</th>
                                <th class="px-5 py-3.5">Cliente</th>
                                <th class="px-5 py-3.5">Métodos de Pago</th>
                                <th class="px-5 py-3.5 text-right">Total ($ USD)</th>
                                <th class="px-5 py-3.5 text-right">Total (Bs. VES)</th>
                                <th class="px-5 py-3.5 text-center">Estado</th>
                                <th class="px-5 py-3.5 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr
                                v-for="v in ventas.data"
                                :key="v.id_venta"
                                class="hover:bg-gray-50/70 dark:hover:bg-gray-700/40 transition text-xs"
                            >
                                <td class="px-5 py-3.5">
                                    <div class="font-bold text-gray-900 dark:text-white font-mono flex items-center gap-1.5">
                                        <span>{{ v.numero_comprobante }}</span>
                                        <span v-if="v.cashea_transaccion" title="Financiado con Cashea" class="text-amber-500 text-xs">💛</span>
                                    </div>
                                    <div class="text-[10px] text-gray-500 uppercase tracking-wider">
                                        {{ v.tipo_comprobante }}
                                    </div>
                                </td>
                                <td class="px-5 py-3.5 whitespace-nowrap">
                                    <div class="text-gray-800 dark:text-gray-200">
                                        {{ new Date(v.created_at).toLocaleDateString() }}
                                    </div>
                                    <div class="text-[10px] text-gray-400 font-mono">
                                        {{ new Date(v.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) }}
                                    </div>
                                </td>
                                <td class="px-5 py-3.5">
                                    <div class="font-semibold text-gray-900 dark:text-white">
                                        {{ v.cliente?.nombre || 'Cliente Mostrador' }}
                                    </div>
                                    <div class="text-[10px] text-gray-500 font-mono">
                                        {{ v.cliente?.identificacion || 'V-00000000' }}
                                    </div>
                                </td>
                                <td class="px-5 py-3.5">
                                    <div class="flex flex-wrap gap-1">
                                        <span
                                            v-for="p in v.pagos"
                                            :key="p.id_pago_venta"
                                            class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300"
                                        >
                                            {{ p.metodo_pago?.nombre || 'Pago' }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-5 py-3.5 text-right font-mono font-bold text-gray-900 dark:text-white text-sm">
                                    ${{ Number(v.total).toFixed(2) }}
                                </td>
                                <td class="px-5 py-3.5 text-right font-mono text-gray-600 dark:text-gray-300">
                                    Bs. {{ (Number(v.total) * Number(v.tasa_cambio)).toLocaleString('es-VE', { minimumFractionDigits: 2 }) }}
                                </td>
                                <td class="px-5 py-3.5 text-center">
                                    <span
                                        :class="[
                                            'inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold',
                                            v.estado === 'COMPLETADA'
                                                ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300'
                                                : 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300'
                                        ]"
                                    >
                                        {{ v.estado }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <!-- Ver Factura Digital con QR -->
                                        <button
                                            @click="AbrirModalDetalle(v)"
                                            title="Ver Factura con QR"
                                            class="p-1 text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 rounded transition"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                        </button>

                                        <!-- Descargar Archivo ESC/POS -->
                                        <a
                                            :href="route('facturacion.escpos', v.id_venta)"
                                            title="Descargar Formato Térmico ESC/POS"
                                            class="p-1 text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-900/30 rounded transition"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                            </svg>
                                        </a>

                                        <!-- Anular Venta -->
                                        <button
                                            v-if="v.estado === 'COMPLETADA'"
                                            @click="AbrirModalAnular(v)"
                                            title="Anular Comprobante"
                                            class="p-1 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 rounded transition"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="ventas.data.length === 0">
                                <td colspan="8" class="px-6 py-12 text-center text-gray-400">
                                    No se encontraron comprobantes con los filtros seleccionados.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                <div v-if="ventas.links && ventas.links.length > 3" class="px-6 py-4 bg-gray-50 dark:bg-gray-900/50 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <span class="text-xs text-gray-500">
                        Mostrando {{ ventas.from }} - {{ ventas.to }} de {{ ventas.total }} comprobantes
                    </span>
                    <div class="flex gap-1">
                        <template v-for="(link, idx) in ventas.links" :key="idx">
                            <button
                                v-if="link.url"
                                @click="router.get(link.url, {}, { preserveState: true })"
                                v-html="link.label"
                                :class="[
                                    'px-3 py-1 text-xs rounded-md transition',
                                    link.active
                                        ? 'bg-indigo-600 text-white font-bold'
                                        : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-100'
                                ]"
                            />
                        </template>
                    </div>
                </div>
            </div>

            <!-- MODAL: Factura Formal con QR -->
            <div
                v-if="modalDetalleAbierto"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs"
            >
                <div class="bg-white dark:bg-gray-800 w-full max-w-2xl rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700 overflow-hidden flex flex-col max-h-[90vh]">
                    <!-- Barra Superior Modal -->
                    <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900/50 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center print:hidden">
                        <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <span>🧾</span> Comprobante Electrónico: {{ ventaSeleccionada?.numero_comprobante }}
                        </h3>
                        <div class="flex items-center gap-2">
                            <button
                                @click="ImprimirFactura"
                                class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg flex items-center gap-1.5 transition"
                            >
                                🖨️ Imprimir
                            </button>
                            <button @click="modalDetalleAbierto = false" class="text-gray-400 hover:text-gray-600 text-lg px-2">✕</button>
                        </div>
                    </div>

                    <!-- Contenido Imprimible de la Factura -->
                    <div class="p-6 overflow-y-auto space-y-6 text-gray-800 dark:text-gray-200 print:p-0">
                        <!-- Cabecera Empresa / Factura -->
                        <div class="flex justify-between items-start border-b border-gray-200 dark:border-gray-700 pb-4">
                            <div>
                                <h2 class="text-xl font-extrabold text-indigo-600 dark:text-indigo-400 tracking-tight">FÁCIL SHOP C.A.</h2>
                                <p class="text-xs text-gray-500 font-mono">RIF: J-50012345-0</p>
                                <p class="text-xs text-gray-500">Av. Principal, Edif. Fácil Shop, Piso 1</p>
                                <p class="text-xs text-gray-500">Tel: (0212) 555-0199 / ventas@facilshop.com</p>
                            </div>
                            <div class="text-right">
                                <span class="inline-block px-3 py-1 bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 font-mono font-bold text-sm rounded-lg">
                                    {{ ventaSeleccionada?.tipo_comprobante }}: {{ ventaSeleccionada?.numero_comprobante }}
                                </span>
                                <p class="text-xs text-gray-500 mt-1">
                                    Fecha: {{ new Date(ventaSeleccionada?.created_at).toLocaleString() }}
                                </p>
                                <p class="text-xs text-gray-500">
                                    Cajero: {{ ventaSeleccionada?.caja_turno?.usuario?.name || 'Caja' }}
                                </p>
                            </div>
                        </div>

                        <!-- Datos del Cliente -->
                        <div class="p-3 bg-gray-50 dark:bg-gray-900/40 rounded-xl border border-gray-200 dark:border-gray-700 grid grid-cols-2 text-xs">
                            <div>
                                <span class="text-gray-400 text-[10px] uppercase font-semibold">Cliente:</span>
                                <div class="font-bold text-gray-900 dark:text-white">{{ ventaSeleccionada?.cliente?.nombre }}</div>
                                <div class="text-gray-500 font-mono">ID: {{ ventaSeleccionada?.cliente?.identificacion }}</div>
                            </div>
                            <div class="text-right">
                                <span class="text-gray-400 text-[10px] uppercase font-semibold">Contacto:</span>
                                <div class="text-gray-700 dark:text-gray-300">{{ ventaSeleccionada?.cliente?.telefono || 'No registrado' }}</div>
                                <div class="text-gray-500">{{ ventaSeleccionada?.cliente?.email || '' }}</div>
                            </div>
                        </div>

                        <!-- Tabla de Ítems -->
                        <table class="w-full text-xs text-left">
                            <thead class="border-b border-gray-300 dark:border-gray-700 uppercase text-[10px] text-gray-500 font-semibold">
                                <tr>
                                    <th class="py-2">Producto</th>
                                    <th class="py-2 text-center">Cant.</th>
                                    <th class="py-2 text-right">P. Unit</th>
                                    <th class="py-2 text-right">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                <tr v-for="d in ventaSeleccionada?.detalles" :key="d.id_venta_detalle">
                                    <td class="py-2">
                                        <span class="font-semibold text-gray-900 dark:text-white">{{ d.producto?.nombre }}</span>
                                        <span class="text-[10px] text-gray-400 block font-mono">SKU: {{ d.producto?.sku }}</span>
                                    </td>
                                    <td class="py-2 text-center font-mono font-bold">{{ d.cantidad }}</td>
                                    <td class="py-2 text-right font-mono">${{ Number(d.precio_unitario).toFixed(2) }}</td>
                                    <td class="py-2 text-right font-mono font-bold">${{ Number(d.subtotal).toFixed(2) }}</td>
                                </tr>
                            </tbody>
                        </table>

                        <!-- Totales y QR -->
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-4 flex flex-col sm:flex-row justify-between items-center gap-4">
                            <!-- QR de Verificación -->
                            <div class="flex items-center gap-3">
                                <img
                                    v-if="qrDataUrl"
                                    :src="qrDataUrl"
                                    alt="Código QR Verificador"
                                    class="w-24 h-24 rounded-lg border border-gray-200 bg-white p-1"
                                />
                                <div class="text-[10px] text-gray-500 max-w-[180px]">
                                    <div class="font-bold text-gray-800 dark:text-gray-200">VERIFICADOR DIGITAL</div>
                                    Escanee este código QR para validar la autenticidad e integridad del comprobante.
                                </div>
                            </div>

                            <!-- Bloque de Montos -->
                            <div class="w-full sm:w-64 space-y-1.5 text-xs text-right font-mono">
                                <div class="flex justify-between text-gray-500">
                                    <span>Subtotal:</span>
                                    <span>${{ Number(ventaSeleccionada?.subtotal).toFixed(2) }}</span>
                                </div>
                                <div v-if="Number(ventaSeleccionada?.descuento_total) > 0" class="flex justify-between text-emerald-600 font-semibold">
                                    <span>Descuento:</span>
                                    <span>-${{ Number(ventaSeleccionada?.descuento_total).toFixed(2) }}</span>
                                </div>
                                <div class="flex justify-between text-base font-extrabold text-gray-900 dark:text-white border-t border-gray-200 dark:border-gray-700 pt-1">
                                    <span>TOTAL USD:</span>
                                    <span>${{ Number(ventaSeleccionada?.total).toFixed(2) }}</span>
                                </div>
                                <div class="flex justify-between text-xs text-gray-500">
                                    <span>Tasa BCV:</span>
                                    <span>Bs. {{ Number(ventaSeleccionada?.tasa_cambio).toFixed(2) }}</span>
                                </div>
                                <div class="flex justify-between font-bold text-gray-800 dark:text-gray-200">
                                    <span>TOTAL VES:</span>
                                    <span>Bs. {{ (Number(ventaSeleccionada?.total) * Number(ventaSeleccionada?.tasa_cambio)).toLocaleString('es-VE', { minimumFractionDigits: 2 }) }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Sección Cashea si aplica -->
                        <div
                            v-if="ventaSeleccionada?.cashea_transaccion"
                            class="p-4 bg-amber-50 dark:bg-amber-950/40 rounded-xl border border-amber-200 dark:border-amber-800/60 text-xs space-y-2"
                        >
                            <div class="flex justify-between items-center font-bold text-amber-900 dark:text-amber-200">
                                <span class="flex items-center gap-1.5">💛 FINANCIAMIENTO CASHEA (BNPL)</span>
                                <span class="font-mono">Ref: {{ ventaSeleccionada.cashea_transaccion.referencia_cashea }}</span>
                            </div>
                            <div class="grid grid-cols-2 gap-2 text-[11px] text-amber-800 dark:text-amber-300">
                                <div>Inicial Pagada: ${{ Number(ventaSeleccionada.cashea_transaccion.monto_inicial).toFixed(2) }} ({{ Number(ventaSeleccionada.cashea_transaccion.porcentaje_inicial) }}%)</div>
                                <div class="text-right">Financiado: ${{ Number(ventaSeleccionada.cashea_transaccion.monto_financiado).toFixed(2) }} (3 cuotas de ${{ Number(ventaSeleccionada.cashea_transaccion.monto_cuota).toFixed(2) }})</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- MODAL: Confirmar Anulación -->
            <div
                v-if="modalAnularAbierto"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs"
            >
                <div class="bg-white dark:bg-gray-800 w-full max-w-md rounded-2xl p-6 shadow-2xl border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-3 text-red-600 pb-3 border-b border-gray-100 dark:border-gray-700">
                        <span class="text-2xl">⚠️</span>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                            Anular Comprobante {{ ventaAnular?.numero_comprobante }}
                        </h3>
                    </div>

                    <div class="my-4 text-xs text-gray-600 dark:text-gray-300 space-y-2">
                        <p>
                            Al anular esta venta, el sistema ejecutará automáticamente las siguientes acciones:
                        </p>
                        <ul class="list-disc pl-4 space-y-1 text-gray-500">
                            <li>Reintegrará todas las unidades vendidas al stock de inventario.</li>
                            <li>Generará un movimiento inmutable de devolución en el kardex.</li>
                            <li>Revertirá los saldos de crédito cargados al cliente (si aplica).</li>
                        </ul>
                    </div>

                    <form @submit.prevent="ProcesarAnulacion" class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase mb-1">
                                Motivo de la Anulación *
                            </label>
                            <textarea
                                v-model="formAnular.motivo"
                                required
                                rows="3"
                                placeholder="Indique la justificación obligatoria..."
                                class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            />
                            <p v-if="formAnular.errors.motivo" class="text-xs text-red-500 mt-1">
                                {{ formAnular.errors.motivo }}
                            </p>
                        </div>

                        <div class="flex justify-end gap-2 pt-3 border-t border-gray-100 dark:border-gray-700">
                            <button
                                type="button"
                                @click="modalAnularAbierto = false"
                                class="px-4 py-2 text-xs text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg"
                            >
                                Cancelar
                            </button>
                            <button
                                type="submit"
                                :disabled="formAnular.processing || !formAnular.motivo"
                                class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-xs font-bold rounded-lg shadow-sm disabled:opacity-50"
                            >
                                Confirmar Anulación
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>
