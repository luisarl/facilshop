<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    turnoActivo: {
        type: Object,
        default: null,
    },
    resumenTurno: {
        type: Object,
        default: null,
    },
    historialTurnos: {
        type: Object,
        default: null,
    },
});

// Modal de Cierre Ciego
const modalCierreAbierto = ref(false);

const formApertura = useForm({
    monto_inicial: 0.00,
    observaciones: '',
});

const formCierre = useForm({
    monto_final_declarado: '',
    observaciones: '',
});

const abrirTurno = () => {
    formApertura.post(route('caja.abrir'), {
        preserveScroll: true,
        onSuccess: () => {
            formApertura.reset();
        },
    });
};

const abrirModalCierre = () => {
    formCierre.reset();
    formCierre.clearErrors();
    modalCierreAbierto.value = true;
};

const cerrarModalCierre = () => {
    modalCierreAbierto.value = false;
    formCierre.reset();
};

const confirmarCierre = () => {
    if (!props.turnoActivo) return;

    formCierre.post(route('caja.cerrar', { id_caja_turno: props.turnoActivo.id_caja_turno }), {
        preserveScroll: true,
        onSuccess: () => {
            cerrarModalCierre();
        },
    });
};

const calculoDiferencia = computed(() => {
    if (!props.resumenTurno) return 0;
    const declarado = parseFloat(formCierre.monto_final_declarado);
    if (isNaN(declarado)) return null;
    const teorico = props.resumenTurno.monto_teorico_efectivo;
    return (declarado - teorico).toFixed(2);
});
</script>

<template>
    <Head title="Control de Caja y Turnos" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">
                        Control de Turnos de Caja
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Apertura de gaveta, arqueo en tiempo real y cierre ciego de caja con cálculo auditado de diferencias.
                    </p>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">
                
                <!-- CASO 1: NO HAY TURNO ABIERTO (FORMULARIO DE APERTURA) -->
                <div
                    v-if="!turnoActivo"
                    class="mx-auto max-w-xl overflow-hidden rounded-3xl border border-gray-200 bg-white p-8 shadow-sm dark:border-gray-700 dark:bg-gray-800"
                >
                    <div class="text-center">
                        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-blue-50 text-blue-600 dark:bg-blue-950/60 dark:text-blue-300">
                            <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                        <h3 class="mt-4 text-xl font-bold text-gray-900 dark:text-white">
                            Aperturar Nuevo Turno de Caja
                        </h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            No tienes una sesión de caja activa. Ingrese el fondo inicial para habilitar la facturación.
                        </p>
                    </div>

                    <form @submit.prevent="abrirTurno" class="mt-8 space-y-6">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">
                                Fondo Inicial de Apertura (Moneda Base USD)
                            </label>
                            <div class="relative mt-2">
                                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 font-bold text-gray-400">
                                    $
                                </span>
                                <input
                                    v-model="formApertura.monto_inicial"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    required
                                    class="block w-full rounded-2xl border-gray-300 py-3.5 pl-10 pr-4 font-mono text-xl font-extrabold text-gray-900 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    placeholder="0.00"
                                />
                            </div>
                            <p v-if="formApertura.errors.monto_inicial" class="mt-1 text-xs text-rose-600">
                                {{ formApertura.errors.monto_inicial }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">
                                Observaciones / Nota de Turno
                            </label>
                            <textarea
                                v-model="formApertura.observaciones"
                                rows="2"
                                class="mt-2 block w-full rounded-2xl border-gray-300 py-2.5 px-4 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                placeholder="Ej: Fondo inicial entregado en 2 billetes de $20 y sencillo"
                            ></textarea>
                        </div>

                        <button
                            type="submit"
                            :disabled="formApertura.processing"
                            class="w-full inline-flex items-center justify-center rounded-2xl bg-blue-600 py-4 text-base font-bold text-white shadow-md transition-all hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50"
                        >
                            <span v-if="formApertura.processing">Aperturando caja...</span>
                            <span v-else>Abrir Turno de Caja</span>
                        </button>
                    </form>
                </div>

                <!-- CASO 2: TURNO ACTIVO ABIERTO -->
                <div v-else class="space-y-6">
                    <!-- Banner de Estado de Turno -->
                    <div class="flex flex-col gap-4 rounded-3xl border border-emerald-200 bg-gradient-to-r from-emerald-50 to-teal-50 p-6 shadow-sm sm:flex-row sm:items-center sm:justify-between dark:border-emerald-800/60 dark:from-emerald-950/40 dark:to-teal-950/40">
                        <div class="flex items-center space-x-4">
                            <span class="relative flex h-5 w-5">
                                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                                <span class="relative inline-flex h-5 w-5 rounded-full bg-emerald-500"></span>
                            </span>
                            <div>
                                <h3 class="text-xl font-black text-gray-900 dark:text-white">
                                    Turno #{{ turnoActivo.id_caja_turno }} — Caja Abierta
                                </h3>
                                <p class="text-xs text-gray-600 dark:text-gray-300">
                                    Iniciado el {{ new Date(turnoActivo.fecha_apertura).toLocaleString('es-VE') }} por {{ $page.props.auth.user.name }}
                                </p>
                            </div>
                        </div>

                        <button
                            type="button"
                            @click="abrirModalCierre"
                            class="inline-flex items-center justify-center rounded-2xl bg-rose-600 px-6 py-3 text-sm font-bold text-white shadow-sm transition-colors hover:bg-rose-700 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2 dark:bg-rose-600 dark:hover:bg-rose-500"
                        >
                            <svg class="mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                            Cerrar Turno (Corte Z)
                        </button>
                    </div>

                    <!-- Métricas de Arqueo en Vivo -->
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                            <span class="text-xs font-semibold uppercase tracking-wider text-gray-400">Fondo Inicial</span>
                            <div class="mt-2 text-2xl font-black text-gray-900 dark:text-white">
                                ${{ Number(resumenTurno?.monto_inicial || 0).toFixed(2) }}
                            </div>
                            <span class="text-xs text-gray-500">Monto de apertura registrado</span>
                        </div>

                        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                            <span class="text-xs font-semibold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Ventas en Efectivo</span>
                            <div class="mt-2 text-2xl font-black text-emerald-600 dark:text-emerald-400">
                                ${{ Number(resumenTurno?.total_efectivo_base || 0).toFixed(2) }}
                            </div>
                            <span class="text-xs text-gray-500">Ingresos directos en gaveta</span>
                        </div>

                        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                            <span class="text-xs font-semibold uppercase tracking-wider text-blue-600 dark:text-blue-400">Otros Métodos</span>
                            <div class="mt-2 text-2xl font-black text-blue-600 dark:text-blue-400">
                                ${{ Number(resumenTurno?.total_otros_base || 0).toFixed(2) }}
                            </div>
                            <span class="text-xs text-gray-500">Tarjetas, Pagos Móviles, Cashea</span>
                        </div>

                        <div class="rounded-2xl border border-gray-200 bg-gradient-to-br from-blue-600 to-indigo-700 p-6 text-white shadow-sm">
                            <span class="text-xs font-semibold uppercase tracking-wider text-blue-100">Teórico en Efectivo</span>
                            <div class="mt-2 text-2xl font-black">
                                ${{ Number(resumenTurno?.monto_teorico_efectivo || 0).toFixed(2) }}
                            </div>
                            <span class="text-xs text-blue-100">Fondo + Efectivo recaudado</span>
                        </div>
                    </div>
                </div>

                <!-- Historial de Turnos Pasados -->
                <div class="rounded-3xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="border-b border-gray-200 p-6 dark:border-gray-700">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                            Historial de Turnos de Caja (Cortes de Caja)
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Auditoría completa de aperturas, montos declarados y discrepancias detectadas al cierre.
                        </p>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300">
                            <thead class="bg-gray-50 text-xs uppercase tracking-wider text-gray-500 dark:bg-gray-900/40 dark:text-gray-400">
                                <tr>
                                    <th class="px-6 py-4 font-semibold">Turno</th>
                                    <th class="px-6 py-4 font-semibold">Cajero</th>
                                    <th class="px-6 py-4 font-semibold">Apertura</th>
                                    <th class="px-6 py-4 font-semibold">Cierre</th>
                                    <th class="px-6 py-4 font-semibold">Fondo Inicial</th>
                                    <th class="px-6 py-4 font-semibold">Teórico</th>
                                    <th class="px-6 py-4 font-semibold">Declarado</th>
                                    <th class="px-6 py-4 font-semibold">Diferencia</th>
                                    <th class="px-6 py-4 font-semibold text-center">Estado</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                <tr
                                    v-for="item in historialTurnos?.data || []"
                                    :key="item.id_caja_turno"
                                    class="transition-colors hover:bg-gray-50/50 dark:hover:bg-gray-700/30"
                                >
                                    <td class="whitespace-nowrap px-6 py-4 font-bold text-gray-900 dark:text-white">
                                        #{{ item.id_caja_turno }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-xs font-medium">
                                        {{ item.usuario?.nombre || '—' }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-xs font-mono">
                                        {{ new Date(item.fecha_apertura).toLocaleString('es-VE') }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-xs font-mono">
                                        {{ item.fecha_cierre ? new Date(item.fecha_cierre).toLocaleString('es-VE') : 'En curso' }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 font-mono text-xs">
                                        ${{ Number(item.monto_inicial).toFixed(2) }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 font-mono text-xs">
                                        {{ item.monto_final_teorico !== null ? '$' + Number(item.monto_final_teorico).toFixed(2) : '—' }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 font-mono text-xs font-bold text-gray-900 dark:text-white">
                                        {{ item.monto_final_declarado !== null ? '$' + Number(item.monto_final_declarado).toFixed(2) : '—' }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 font-mono text-xs font-bold">
                                        <span
                                            v-if="item.diferencia !== null"
                                            :class="Number(item.diferencia) === 0 
                                                ? 'text-emerald-600 dark:text-emerald-400' 
                                                : Number(item.diferencia) > 0 
                                                    ? 'text-blue-600 dark:text-blue-400' 
                                                    : 'text-rose-600 dark:text-rose-400'"
                                        >
                                            {{ Number(item.diferencia) > 0 ? '+' : '' }}${{ Number(item.diferencia).toFixed(2) }}
                                        </span>
                                        <span v-else class="text-gray-400">—</span>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-center">
                                        <span
                                            class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold"
                                            :class="item.estado === 'ABIERTA' 
                                                ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/60 dark:text-emerald-300' 
                                                : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300'"
                                        >
                                            {{ item.estado }}
                                        </span>
                                    </td>
                                </tr>
                                <tr v-if="(historialTurnos?.data || []).length === 0">
                                    <td colspan="9" class="py-8 text-center text-sm text-gray-500">
                                        No hay turnos registrados en el historial.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Cierre Ciego de Turno (Corte Z) -->
        <div
            v-if="modalCierreAbierto"
            class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-gray-900/60 p-4 backdrop-blur-sm"
        >
            <div
                class="w-full max-w-lg rounded-3xl border border-gray-100 bg-white p-8 shadow-2xl transition-all dark:border-gray-700 dark:bg-gray-800"
            >
                <div class="flex items-center justify-between border-b border-gray-100 pb-4 dark:border-gray-700">
                    <div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white">
                            Cierre Ciego de Turno (Corte Z)
                        </h3>
                        <p class="text-xs text-gray-500">Turno #{{ turnoActivo?.id_caja_turno }}</p>
                    </div>
                    <button
                        type="button"
                        @click="cerrarModalCierre"
                        class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-gray-700 dark:hover:text-gray-200"
                    >
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="confirmarCierre" class="mt-6 space-y-5">
                    <div class="rounded-2xl bg-amber-50 p-4 text-xs text-amber-800 dark:bg-amber-950/40 dark:text-amber-300 border border-amber-200/60 dark:border-amber-800/40">
                        <p class="font-bold">Instrucción para el cajero:</p>
                        <p class="mt-1">
                            Realice el arqueo físico contando todo el efectivo presente en gaveta e ingrese el total exacto. El sistema auditará la diferencia contra las ventas registradas.
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">
                            Monto Total en Efectivo Contado (USD)
                        </label>
                        <div class="relative mt-2">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 font-bold text-gray-400">
                                $
                            </span>
                            <input
                                v-model="formCierre.monto_final_declarado"
                                type="number"
                                step="0.01"
                                min="0"
                                required
                                autofocus
                                class="block w-full rounded-2xl border-gray-300 py-3.5 pl-10 pr-4 font-mono text-2xl font-black text-gray-900 shadow-sm focus:border-rose-500 focus:ring-rose-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                placeholder="0.00"
                            />
                        </div>
                        <p v-if="formCierre.errors.monto_final_declarado" class="mt-1 text-xs text-rose-600">
                            {{ formCierre.errors.monto_final_declarado }}
                        </p>
                    </div>

                    <!-- Comparativa en vivo de discrepancia -->
                    <div
                        v-if="calculoDiferencia !== null"
                        class="rounded-2xl p-4 transition-all"
                        :class="Number(calculoDiferencia) === 0 
                            ? 'bg-emerald-50 border border-emerald-200 text-emerald-800 dark:bg-emerald-950/40 dark:border-emerald-800 dark:text-emerald-300'
                            : Number(calculoDiferencia) > 0 
                                ? 'bg-blue-50 border border-blue-200 text-blue-800 dark:bg-blue-950/40 dark:border-blue-800 dark:text-blue-300'
                                : 'bg-rose-50 border border-rose-200 text-rose-800 dark:bg-rose-950/40 dark:border-rose-800 dark:text-rose-300'"
                    >
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold">Balance Teórico Esperado:</span>
                            <span class="font-mono font-bold">${{ Number(resumenTurno?.monto_teorico_efectivo || 0).toFixed(2) }}</span>
                        </div>
                        <div class="mt-2 flex items-center justify-between border-t pt-2 border-black/10 dark:border-white/10">
                            <span class="text-sm font-bold">
                                {{ Number(calculoDiferencia) === 0 ? 'Cuadre Perfecto:' : Number(calculoDiferencia) > 0 ? 'Sobrante detectado:' : 'Faltante en gaveta:' }}
                            </span>
                            <span class="font-mono text-lg font-black">
                                {{ Number(calculoDiferencia) > 0 ? '+' : '' }}${{ calculoDiferencia }}
                            </span>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">
                            Observaciones de Cierre (Opcional)
                        </label>
                        <textarea
                            v-model="formCierre.observaciones"
                            rows="2"
                            class="mt-2 block w-full rounded-2xl border-gray-300 py-2.5 px-4 text-sm shadow-sm focus:border-rose-500 focus:ring-rose-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            placeholder="Ej: Billetes deteriorados o justificación de diferencia"
                        ></textarea>
                    </div>

                    <div class="flex items-center justify-end space-x-3 pt-4 border-t border-gray-100 dark:border-gray-700">
                        <button
                            type="button"
                            @click="cerrarModalCierre"
                            class="rounded-xl border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 focus:outline-none dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            :disabled="formCierre.processing"
                            class="inline-flex items-center rounded-2xl bg-rose-600 px-6 py-2.5 text-sm font-bold text-white shadow-md hover:bg-rose-700 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2 disabled:opacity-50"
                        >
                            <span v-if="formCierre.processing">Procesando Cierre...</span>
                            <span v-else>Confirmar Cierre de Turno</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
