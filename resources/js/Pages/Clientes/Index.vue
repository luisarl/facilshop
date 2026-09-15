<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import axios from 'axios';

const props = defineProps({
    clientes: Object,
    kpis: Object,
    filtros: Object,
    metodosPago: Array,
    monedas: Array,
    tasaVes: Number,
});

// Filtros y Búsqueda
const buscar = ref(props.filtros?.buscar || '');
const estadoCredito = ref(props.filtros?.estado_credito || '');

const AplicarFiltros = () => {
    router.get(route('clientes.index'), {
        buscar: buscar.value || undefined,
        estado_credito: estadoCredito.value || undefined,
    }, {
        preserveState: true,
        replace: true,
    });
};

const LimpiarFiltros = () => {
    buscar.value = '';
    estadoCredito.value = '';
    AplicarFiltros();
};

// Modal Crear / Editar Cliente
const modalClienteAbierto = ref(false);
const clienteEditando = ref(null);

const formCliente = useForm({
    identificacion: '',
    nombre: '',
    telefono: '',
    email: '',
    limite_credito: 0.00,
});

const AbrirModalNuevoCliente = () => {
    clienteEditando.value = null;
    formCliente.reset();
    formCliente.clearErrors();
    modalClienteAbierto.value = true;
};

const AbrirModalEditarCliente = (cliente) => {
    clienteEditando.value = cliente;
    formCliente.identificacion = cliente.identificacion;
    formCliente.nombre = cliente.nombre;
    formCliente.telefono = cliente.telefono || '';
    formCliente.email = cliente.email || '';
    formCliente.limite_credito = cliente.limite_credito;
    formCliente.clearErrors();
    modalClienteAbierto.value = true;
};

const GuardarCliente = () => {
    if (clienteEditando.value) {
        formCliente.put(route('clientes.update', clienteEditando.value.id_cliente), {
            onSuccess: () => {
                modalClienteAbierto.value = false;
            },
        });
    } else {
        formCliente.post(route('clientes.store'), {
            onSuccess: () => {
                modalClienteAbierto.value = false;
            },
        });
    }
};

// Modal Abonar Deuda
const modalAbonoAbierto = ref(false);
const clienteAbonar = ref(null);
const metodoSeleccionado = ref(null);

const formAbono = useForm({
    monto: '',
    monto_base: 0.00,
    id_metodo_pago: '',
    id_moneda: '',
    tasa_cambio: 1.0000,
    referencia: '',
    observaciones: '',
});

const AbrirModalAbonar = (cliente) => {
    clienteAbonar.value = cliente;
    const metodoInicial = props.metodosPago.find(m => m.codigo === 'EFECTIVO_USD') || props.metodosPago[0];

    formAbono.reset();
    formAbono.clearErrors();
    formAbono.id_metodo_pago = metodoInicial?.id_metodo_pago || '';
    formAbono.id_moneda = metodoInicial?.id_moneda || 1;
    formAbono.tasa_cambio = Number(metodoInicial?.moneda?.tasa_cambio) || 1.0;
    metodoSeleccionado.value = metodoInicial;

    // Por defecto sugerir saldo total pendiente
    formAbono.monto = cliente.saldo_pendiente;
    ActualizarCalculoAbono();

    modalAbonoAbierto.value = true;
};

const CambiarMetodoPagoAbono = (idMetodo) => {
    const metodo = props.metodosPago.find(m => m.id_metodo_pago === Number(idMetodo));
    if (!metodo) return;

    metodoSeleccionado.value = metodo;
    formAbono.id_metodo_pago = metodo.id_metodo_pago;
    formAbono.id_moneda = metodo.id_moneda;
    formAbono.tasa_cambio = Number(metodo.moneda?.tasa_cambio) || 1.0;

    ActualizarCalculoAbono();
};

const ActualizarCalculoAbono = () => {
    const monto = Number(formAbono.monto) || 0;
    const tasa = Number(formAbono.tasa_cambio) || 1.0;

    if (metodoSeleccionado.value?.moneda?.codigo === 'VES') {
        formAbono.monto_base = tasa > 0 ? Number((monto / tasa).toFixed(2)) : 0;
    } else {
        formAbono.monto_base = Number(monto.toFixed(2));
    }
};

const nuevoSaldoEstimado = computed(() => {
    if (!clienteAbonar.value) return 0;
    const restante = Number(clienteAbonar.value.saldo_pendiente) - Number(formAbono.monto_base);
    return restante > 0 ? Number(restante.toFixed(2)) : 0.00;
});

const ProcesarAbono = () => {
    if (!clienteAbonar.value) return;
    formAbono.post(route('clientes.abonar', clienteAbonar.value.id_cliente), {
        onSuccess: () => {
            modalAbonoAbierto.value = false;
        },
    });
};

// Modal Estado de Cuenta
const modalEstadoCuentaAbierto = ref(false);
const estadoCuenta = ref(null);
const cargandoEstadoCuenta = ref(false);

const VerEstadoCuenta = async (cliente) => {
    cargandoEstadoCuenta.value = true;
    modalEstadoCuentaAbierto.value = true;
    estadoCuenta.value = null;

    try {
        const res = await axios.get(route('clientes.estado-cuenta', cliente.id_cliente));
        estadoCuenta.value = res.data;
    } catch (e) {
        console.error('Error al cargar estado de cuenta', e);
    } finally {
        cargandoEstadoCuenta.value = false;
    }
};
</script>

<template>
    <Head title="Directorio de Clientes y Créditos" />

    <AuthenticatedLayout>
        <div class="py-6 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto space-y-6">

            <!-- Cabecera y Botón Nuevo -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <span>👥</span> Clientes y Gestión de Créditos
                    </h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Administración de cuentas por cobrar, límites comerciales y registro de abonos.
                    </p>
                </div>
                <button
                    @click="AbrirModalNuevoCliente"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-lg shadow-sm transition"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Nuevo Cliente
                </button>
            </div>

            <!-- Tarjetas KPIs -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-100 dark:border-gray-700 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Total Cartera por Cobrar
                        </span>
                        <span class="p-2 bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400 rounded-lg text-sm">
                            💳
                        </span>
                    </div>
                    <div class="mt-2 flex items-baseline gap-2">
                        <span class="text-2xl font-bold text-red-600 dark:text-red-400">
                            ${{ Number(kpis.total_cartera_usd).toFixed(2) }}
                        </span>
                        <span class="text-xs text-gray-500">USD</span>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        ≈ Bs. {{ Number(kpis.total_cartera_ves).toLocaleString('es-VE', { minimumFractionDigits: 2 }) }}
                    </p>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-100 dark:border-gray-700 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Límite Global Otorgado
                        </span>
                        <span class="p-2 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 rounded-lg text-sm">
                            📈
                        </span>
                    </div>
                    <div class="mt-2 flex items-baseline gap-2">
                        <span class="text-2xl font-bold text-gray-900 dark:text-white">
                            ${{ Number(kpis.total_limite_otorgado).toFixed(2) }}
                        </span>
                        <span class="text-xs text-gray-500">USD</span>
                    </div>
                    <p class="text-xs text-emerald-600 dark:text-emerald-400 mt-1">
                        Disponible: ${{ Math.max(0, kpis.total_limite_otorgado - kpis.total_cartera_usd).toFixed(2) }}
                    </p>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-100 dark:border-gray-700 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Clientes con Saldo Deudor
                        </span>
                        <span class="p-2 bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 rounded-lg text-sm">
                            ⚠️
                        </span>
                    </div>
                    <div class="mt-2 flex items-baseline gap-2">
                        <span class="text-2xl font-bold text-amber-600 dark:text-amber-400">
                            {{ kpis.clientes_con_deuda }}
                        </span>
                        <span class="text-xs text-gray-500">de {{ kpis.total_clientes }} clientes</span>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        {{ kpis.total_clientes > 0 ? ((kpis.clientes_con_deuda / kpis.total_clientes) * 100).toFixed(1) : 0 }}% de la cartera
                    </p>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-100 dark:border-gray-700 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Tasa Referencial BCV
                        </span>
                        <span class="p-2 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 rounded-lg text-sm">
                            💵
                        </span>
                    </div>
                    <div class="mt-2 flex items-baseline gap-2">
                        <span class="text-2xl font-bold text-gray-900 dark:text-white">
                            Bs. {{ Number(kpis.tasa_ves).toFixed(2) }}
                        </span>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Conversión automática para abonos en Bolívares
                    </p>
                </div>
            </div>

            <!-- Filtros de Búsqueda -->
            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700 shadow-sm flex flex-col sm:flex-row items-center gap-4">
                <div class="relative flex-1 w-full">
                    <input
                        v-model="buscar"
                        @keyup.enter="AplicarFiltros"
                        type="text"
                        placeholder="Buscar por cédula, RIF, nombre, teléfono..."
                        class="w-full pl-10 pr-4 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                    />
                    <svg class="w-4 h-4 text-gray-400 absolute left-3.5 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>

                <div class="w-full sm:w-56">
                    <select
                        v-model="estadoCredito"
                        @change="AplicarFiltros"
                        class="w-full text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white py-2 px-3 focus:ring-2 focus:ring-indigo-500"
                    >
                        <option value="">Todos los Estados</option>
                        <option value="CON_DEUDA">Con Deuda Pendiente</option>
                        <option value="AL_DIA">Al Día (Sin Saldo)</option>
                        <option value="LIMITE_ALCANZADO">Límite Alcanzado</option>
                    </select>
                </div>

                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <button
                        @click="AplicarFiltros"
                        class="flex-1 sm:flex-none px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition"
                    >
                        Filtrar
                    </button>
                    <button
                        v-if="buscar || estadoCredito"
                        @click="LimpiarFiltros"
                        class="px-3 py-2 text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition"
                    >
                        Limpiar
                    </button>
                </div>
            </div>

            <!-- Tabla de Clientes -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300">
                        <thead class="bg-gray-50 dark:bg-gray-900/50 text-xs uppercase font-semibold text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
                            <tr>
                                <th class="px-6 py-3.5">Cliente / Identificación</th>
                                <th class="px-6 py-3.5">Contacto</th>
                                <th class="px-6 py-3.5 text-right">Límite Crédito</th>
                                <th class="px-6 py-3.5 text-right">Saldo Deudor</th>
                                <th class="px-6 py-3.5 text-right">Crédito Libre</th>
                                <th class="px-6 py-3.5 text-center">Estado</th>
                                <th class="px-6 py-3.5 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr
                                v-for="c in clientes.data"
                                :key="c.id_cliente"
                                class="hover:bg-gray-50/70 dark:hover:bg-gray-700/40 transition"
                            >
                                <td class="px-6 py-4">
                                    <div class="font-semibold text-gray-900 dark:text-white">
                                        {{ c.nombre }}
                                    </div>
                                    <div class="text-xs text-gray-500 font-mono mt-0.5">
                                        {{ c.identificacion }}
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-xs text-gray-700 dark:text-gray-300">
                                        {{ c.telefono || 'Sin teléfono' }}
                                    </div>
                                    <div class="text-xs text-gray-400">
                                        {{ c.email || 'Sin correo' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-right font-mono font-medium text-gray-900 dark:text-white">
                                    ${{ Number(c.limite_credito).toFixed(2) }}
                                </td>
                                <td class="px-6 py-4 text-right font-mono font-bold">
                                    <span :class="Number(c.saldo_pendiente) > 0 ? 'text-red-600 dark:text-red-400' : 'text-emerald-600 dark:text-emerald-400'">
                                        ${{ Number(c.saldo_pendiente).toFixed(2) }}
                                    </span>
                                    <div v-if="Number(c.saldo_pendiente) > 0" class="text-[10px] text-gray-500 font-normal">
                                        ≈ Bs. {{ (Number(c.saldo_pendiente) * tasaVes).toLocaleString('es-VE', { minimumFractionDigits: 2 }) }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-right font-mono text-emerald-600 dark:text-emerald-400 font-semibold">
                                    ${{ Math.max(0, Number(c.limite_credito) - Number(c.saldo_pendiente)).toFixed(2) }}
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span
                                        v-if="Number(c.saldo_pendiente) <= 0"
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300"
                                    >
                                        Al Día
                                    </span>
                                    <span
                                        v-else-if="Number(c.saldo_pendiente) >= Number(c.limite_credito) && Number(c.limite_credito) > 0"
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300"
                                    >
                                        Límite Agotado
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300"
                                    >
                                        Con Saldo
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-2">
                                        <button
                                            v-if="Number(c.saldo_pendiente) > 0"
                                            @click="AbrirModalAbonar(c)"
                                            title="Registrar Abono a Deuda"
                                            class="p-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:hover:bg-emerald-900/60 dark:text-emerald-300 rounded-lg text-xs font-semibold flex items-center gap-1 transition"
                                        >
                                            💰 Abonar
                                        </button>
                                        <button
                                            @click="VerEstadoCuenta(c)"
                                            title="Ver Estado de Cuenta / Historial"
                                            class="p-1.5 text-gray-500 hover:text-indigo-600 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                            </svg>
                                        </button>
                                        <button
                                            @click="AbrirModalEditarCliente(c)"
                                            title="Editar Datos"
                                            class="p-1.5 text-gray-500 hover:text-indigo-600 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="clientes.data.length === 0">
                                <td colspan="7" class="px-6 py-12 text-center text-gray-400">
                                    No se encontraron clientes con los filtros seleccionados.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                <div v-if="clientes.links && clientes.links.length > 3" class="px-6 py-4 bg-gray-50 dark:bg-gray-900/50 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <span class="text-xs text-gray-500">
                        Mostrando {{ clientes.from }} - {{ clientes.to }} de {{ clientes.total }} clientes
                    </span>
                    <div class="flex gap-1">
                        <template v-for="(link, idx) in clientes.links" :key="idx">
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

            <!-- MODAL: Crear / Editar Cliente -->
            <div
                v-if="modalClienteAbierto"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs"
            >
                <div class="bg-white dark:bg-gray-800 w-full max-w-md rounded-2xl p-6 shadow-2xl border border-gray-200 dark:border-gray-700">
                    <div class="flex justify-between items-center pb-4 border-b border-gray-100 dark:border-gray-700">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                            {{ clienteEditando ? 'Editar Cliente' : 'Registrar Nuevo Cliente' }}
                        </h3>
                        <button @click="modalClienteAbierto = false" class="text-gray-400 hover:text-gray-600">✕</button>
                    </div>

                    <form @submit.prevent="GuardarCliente" class="mt-4 space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase mb-1">
                                Cédula / RIF *
                            </label>
                            <input
                                v-model="formCliente.identificacion"
                                type="text"
                                required
                                placeholder="V-12345678 ó J-00000000"
                                class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            />
                            <p v-if="formCliente.errors.identificacion" class="text-xs text-red-500 mt-1">
                                {{ formCliente.errors.identificacion }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase mb-1">
                                Nombre o Razón Social *
                            </label>
                            <input
                                v-model="formCliente.nombre"
                                type="text"
                                required
                                placeholder="Juan Pérez"
                                class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            />
                            <p v-if="formCliente.errors.nombre" class="text-xs text-red-500 mt-1">
                                {{ formCliente.errors.nombre }}
                            </p>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase mb-1">
                                    Teléfono
                                </label>
                                <input
                                    v-model="formCliente.telefono"
                                    type="text"
                                    placeholder="0414-1234567"
                                    class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase mb-1">
                                    Límite Crédito ($)
                                </label>
                                <input
                                    v-model.number="formCliente.limite_credito"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white font-mono"
                                />
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase mb-1">
                                Correo Electrónico
                            </label>
                            <input
                                v-model="formCliente.email"
                                type="email"
                                placeholder="cliente@correo.com"
                                class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            />
                        </div>

                        <div class="flex justify-end gap-2 pt-4 border-t border-gray-100 dark:border-gray-700">
                            <button
                                type="button"
                                @click="modalClienteAbierto = false"
                                class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg"
                            >
                                Cancelar
                            </button>
                            <button
                                type="submit"
                                :disabled="formCliente.processing"
                                class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-lg shadow-sm disabled:opacity-50"
                            >
                                {{ clienteEditando ? 'Guardar Cambios' : 'Registrar Cliente' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- MODAL: Abonar a Deuda -->
            <div
                v-if="modalAbonoAbierto"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs"
            >
                <div class="bg-white dark:bg-gray-800 w-full max-w-lg rounded-2xl p-6 shadow-2xl border border-gray-200 dark:border-gray-700">
                    <div class="flex justify-between items-center pb-4 border-b border-gray-100 dark:border-gray-700">
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                <span>💵</span> Registrar Abono a Deuda
                            </h3>
                            <p class="text-xs text-gray-500 mt-0.5">
                                Cliente: <span class="font-semibold text-gray-900 dark:text-white">{{ clienteAbonar?.nombre }}</span> ({{ clienteAbonar?.identificacion }})
                            </p>
                        </div>
                        <button @click="modalAbonoAbierto = false" class="text-gray-400 hover:text-gray-600">✕</button>
                    </div>

                    <!-- Resumen del Saldo -->
                    <div class="my-4 grid grid-cols-2 gap-3 p-3 bg-gray-50 dark:bg-gray-900/60 rounded-xl border border-gray-200 dark:border-gray-700">
                        <div>
                            <span class="text-[11px] uppercase font-semibold text-gray-500">Deuda Pendiente</span>
                            <div class="text-lg font-bold text-red-600 dark:text-red-400 font-mono">
                                ${{ Number(clienteAbonar?.saldo_pendiente).toFixed(2) }}
                            </div>
                            <div class="text-[10px] text-gray-400">
                                ≈ Bs. {{ (Number(clienteAbonar?.saldo_pendiente) * tasaVes).toFixed(2) }}
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="text-[11px] uppercase font-semibold text-gray-500">Nuevo Saldo Tras Abono</span>
                            <div class="text-lg font-bold text-emerald-600 dark:text-emerald-400 font-mono">
                                ${{ nuevoSaldoEstimado.toFixed(2) }}
                            </div>
                            <div class="text-[10px] text-gray-400">
                                Abono: ${{ Number(formAbono.monto_base).toFixed(2) }} USD
                            </div>
                        </div>
                    </div>

                    <form @submit.prevent="ProcesarAbono" class="space-y-4">
                        <!-- Método de Pago -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase mb-1">
                                Método de Cobro
                            </label>
                            <select
                                :value="formAbono.id_metodo_pago"
                                @change="CambiarMetodoPagoAbono($event.target.value)"
                                class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            >
                                <option
                                    v-for="m in metodosPago.filter(mp => mp.tipo !== 'CREDITO' && mp.tipo !== 'FINANCIAMIENTO')"
                                    :key="m.id_metodo_pago"
                                    :value="m.id_metodo_pago"
                                >
                                    {{ m.nombre }} ({{ m.moneda?.codigo }})
                                </option>
                            </select>
                        </div>

                        <!-- Monto en Moneda del Método -->
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase mb-1">
                                    Monto a Recibir ({{ metodoSeleccionado?.moneda?.simbolo || '$' }})
                                </label>
                                <input
                                    v-model="formAbono.monto"
                                    @input="ActualizarCalculoAbono"
                                    type="number"
                                    step="0.01"
                                    min="0.01"
                                    required
                                    placeholder="0.00"
                                    class="w-full text-sm font-mono font-bold rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase mb-1">
                                    Equivalente en USD ($)
                                </label>
                                <input
                                    :value="formAbono.monto_base"
                                    readonly
                                    class="w-full text-sm font-mono font-bold rounded-lg border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-900/50 text-gray-900 dark:text-white"
                                />
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase mb-1">
                                Número de Referencia / Comprobante de Pago
                            </label>
                            <input
                                v-model="formAbono.referencia"
                                type="text"
                                placeholder="Ej: Ref 987654 / Depósito / Recibo"
                                class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase mb-1">
                                Observaciones
                            </label>
                            <textarea
                                v-model="formAbono.observaciones"
                                rows="2"
                                placeholder="Nota interna del abono..."
                                class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            />
                        </div>

                        <div class="flex justify-end gap-2 pt-4 border-t border-gray-100 dark:border-gray-700">
                            <button
                                type="button"
                                @click="modalAbonoAbierto = false"
                                class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg"
                            >
                                Cancelar
                            </button>
                            <button
                                type="submit"
                                :disabled="formAbono.processing || formAbono.monto_base <= 0 || formAbono.monto_base > Number(clienteAbonar?.saldo_pendiente)"
                                class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg shadow-sm disabled:opacity-50"
                            >
                                Confirmar Abono de ${{ Number(formAbono.monto_base).toFixed(2) }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- MODAL: Estado de Cuenta -->
            <div
                v-if="modalEstadoCuentaAbierto"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs"
            >
                <div class="bg-white dark:bg-gray-800 w-full max-w-2xl rounded-2xl p-6 shadow-2xl border border-gray-200 dark:border-gray-700 max-h-[90vh] flex flex-col">
                    <div class="flex justify-between items-center pb-4 border-b border-gray-100 dark:border-gray-700">
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                                Estado de Cuenta de Crédito
                            </h3>
                            <p class="text-xs text-gray-500">
                                {{ estadoCuenta?.cliente?.nombre }} ({{ estadoCuenta?.cliente?.identificacion }})
                            </p>
                        </div>
                        <button @click="modalEstadoCuentaAbierto = false" class="text-gray-400 hover:text-gray-600">✕</button>
                    </div>

                    <div v-if="cargandoEstadoCuenta" class="py-12 text-center text-gray-400">
                        Cargando estado de cuenta...
                    </div>

                    <div v-else-if="estadoCuenta" class="overflow-y-auto py-4 space-y-4 flex-1">
                        <!-- Resumen Superior -->
                        <div class="grid grid-cols-3 gap-3 p-3 bg-gray-50 dark:bg-gray-900/60 rounded-xl text-center">
                            <div>
                                <span class="text-[10px] uppercase font-semibold text-gray-500">Límite Aprobado</span>
                                <div class="text-sm font-bold text-gray-900 dark:text-white font-mono">
                                    ${{ Number(estadoCuenta.limite_credito).toFixed(2) }}
                                </div>
                            </div>
                            <div>
                                <span class="text-[10px] uppercase font-semibold text-gray-500">Saldo Pendiente</span>
                                <div class="text-sm font-bold text-red-600 dark:text-red-400 font-mono">
                                    ${{ Number(estadoCuenta.saldo_pendiente_usd).toFixed(2) }}
                                </div>
                            </div>
                            <div>
                                <span class="text-[10px] uppercase font-semibold text-gray-500">Crédito Disponible</span>
                                <div class="text-sm font-bold text-emerald-600 dark:text-emerald-400 font-mono">
                                    ${{ Number(estadoCuenta.credito_disponible).toFixed(2) }}
                                </div>
                            </div>
                        </div>

                        <!-- Lista de Compras a Crédito -->
                        <div>
                            <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">
                                Compras Registradas a Crédito
                            </h4>

                            <div v-if="estadoCuenta.ventas_credito.length === 0" class="p-6 text-center text-xs text-gray-400 bg-gray-50 dark:bg-gray-900/40 rounded-xl">
                                Este cliente no posee ventas registradas a crédito.
                            </div>

                            <div v-else class="space-y-2">
                                <div
                                    v-for="v in estadoCuenta.ventas_credito"
                                    :key="v.id_venta"
                                    class="p-3 bg-white dark:bg-gray-700/50 rounded-xl border border-gray-200 dark:border-gray-700 flex justify-between items-center text-xs"
                                >
                                    <div>
                                        <div class="font-bold text-gray-900 dark:text-white font-mono">
                                            {{ v.numero_comprobante }}
                                        </div>
                                        <div class="text-gray-500 text-[11px]">
                                            {{ new Date(v.created_at).toLocaleDateString() }} - {{ v.tipo_comprobante }}
                                        </div>
                                        <div class="text-gray-400 text-[10px] mt-0.5">
                                            {{ v.detalles?.length || 0 }} productos adquiridos
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="font-bold text-gray-900 dark:text-white font-mono text-sm">
                                            ${{ Number(v.total).toFixed(2) }}
                                        </div>
                                        <span
                                            :class="[
                                                'inline-block px-2 py-0.5 rounded-full text-[10px] font-semibold mt-1',
                                                v.estado === 'COMPLETADA' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' : 'bg-red-100 text-red-700'
                                            ]"
                                        >
                                            {{ v.estado }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-gray-100 dark:border-gray-700 flex justify-end">
                        <button
                            @click="modalEstadoCuentaAbierto = false"
                            class="px-4 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 text-xs font-semibold rounded-lg transition"
                        >
                            Cerrar
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>
