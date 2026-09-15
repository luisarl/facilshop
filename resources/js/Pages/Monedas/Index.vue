<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    monedas: {
        type: Array,
        required: true,
    },
    monedaPrincipal: {
        type: Object,
        default: null,
    },
    historicoInicial: {
        type: Object,
        default: null,
    },
});

// Modal de Actualización de Tasa
const modalAbierto = ref(false);
const monedaSeleccionada = ref(null);
const cargandoHistorico = ref(false);
const monedaHistoricoSeleccionada = ref(props.monedas.find(m => !m.es_principal) || props.monedas[0]);
const listaHistorico = ref(props.historicoInicial ? props.historicoInicial.data : []);

const form = useForm({
    id_moneda: '',
    tasa_cambio: '',
    observaciones: '',
});

const abrirModalActualizar = (moneda) => {
    monedaSeleccionada.value = moneda;
    form.id_moneda = moneda.id_moneda;
    form.tasa_cambio = moneda.tasa_cambio;
    form.observaciones = '';
    modalAbierto.value = true;
};

const cerrarModal = () => {
    modalAbierto.value = false;
    monedaSeleccionada.value = null;
    form.reset();
    form.clearErrors();
};

const guardarTasa = () => {
    form.post(route('monedas.tasas.actualizar'), {
        preserveScroll: true,
        onSuccess: () => {
            cerrarModal();
            if (monedaHistoricoSeleccionada.value?.id_moneda === form.id_moneda) {
                cargarHistorico(form.id_moneda);
            }
        },
    });
};

const cargarHistorico = async (idMoneda) => {
    cargandoHistorico.value = true;
    monedaHistoricoSeleccionada.value = props.monedas.find(m => m.id_moneda === idMoneda);
    try {
        const respuesta = await fetch(route('monedas.historico', { id_moneda: idMoneda }), {
            headers: { 'Accept': 'application/json' }
        });
        const datos = await respuesta.json();
        if (datos.success) {
            listaHistorico.value = datos.data.data;
        }
    } catch (e) {
        console.error('Error cargando historial de tasas:', e);
    } finally {
        cargandoHistorico.value = false;
    }
};

const calcularInversa = computed(() => {
    const valor = parseFloat(form.tasa_cambio);
    if (!valor || valor <= 0) return '0.0000';
    return (1 / valor).toFixed(6);
});
</script>

<template>
    <Head title="Monedas y Cotizaciones" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">
                        Monedas y Tasas de Cambio
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Administre las divisas operativas, cotizaciones del día y consulte la trazabilidad histórica de cotizaciones.
                    </p>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">
                <!-- Parrilla de Tarjetas de Monedas -->
                <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                    <div
                        v-for="moneda in monedas"
                        :key="moneda.id_moneda"
                        class="relative flex flex-col justify-between overflow-hidden rounded-2xl border bg-white p-6 shadow-sm transition-all duration-200 hover:shadow-md dark:bg-gray-800"
                        :class="moneda.es_principal 
                            ? 'border-blue-500/50 ring-1 ring-blue-500/30 dark:border-blue-400/40' 
                            : 'border-gray-200 dark:border-gray-700'"
                    >
                        <!-- Badge Moneda Principal -->
                        <div class="flex items-start justify-between">
                            <div class="flex items-center space-x-3">
                                <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-50 text-2xl font-black text-blue-700 dark:bg-blue-950/60 dark:text-blue-300">
                                    {{ moneda.simbolo }}
                                </span>
                                <div>
                                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                                        {{ moneda.nombre }}
                                    </h3>
                                    <span class="inline-block font-mono text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-400">
                                        Código: {{ moneda.codigo }}
                                    </span>
                                </div>
                            </div>
                            <span
                                v-if="moneda.es_principal"
                                class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900/60 dark:text-blue-200"
                            >
                                Moneda Base
                            </span>
                        </div>

                        <!-- Valor de Tasa -->
                        <div class="my-6">
                            <span class="text-xs uppercase tracking-wider text-gray-400">Cotización respecto a Base</span>
                            <div class="mt-1 flex items-baseline space-x-2">
                                <span class="text-3xl font-extrabold tracking-tight text-gray-900 dark:text-white">
                                    {{ Number(moneda.tasa_cambio).toLocaleString('es-VE', { minimumFractionDigits: 4, maximumFractionDigits: 4 }) }}
                                </span>
                                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                    {{ moneda.codigo }}
                                </span>
                            </div>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                <span v-if="moneda.es_principal">Equivalencia 1:1 fija en el sistema</span>
                                <span v-else>1 USD = {{ Number(moneda.tasa_cambio).toFixed(2) }} {{ moneda.simbolo }}</span>
                            </p>
                        </div>

                        <!-- Acciones -->
                        <div class="pt-4 border-t border-gray-100 dark:border-gray-700">
                            <button
                                v-if="!moneda.es_principal"
                                type="button"
                                @click="abrirModalActualizar(moneda)"
                                class="w-full inline-flex items-center justify-center rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:bg-blue-600 dark:hover:bg-blue-500"
                            >
                                <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                Actualizar Cotización
                            </button>
                            <div
                                v-else
                                class="flex items-center justify-center py-2.5 text-xs font-medium text-gray-400"
                            >
                                Moneda de referencia contable
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Historial Cronológico de Tasas -->
                <div class="rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="flex flex-col border-b border-gray-200 p-6 sm:flex-row sm:items-center sm:justify-between dark:border-gray-700">
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                                Trazabilidad e Histórico de Cotizaciones
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Registro inmutable de modificaciones de tasa para auditoría y arqueo multimoneda.
                            </p>
                        </div>
                        <!-- Selector de Moneda para Historial -->
                        <div class="mt-4 flex flex-wrap gap-2 sm:mt-0">
                            <button
                                v-for="m in monedas"
                                :key="m.id_moneda"
                                type="button"
                                @click="cargarHistorico(m.id_moneda)"
                                class="rounded-lg px-3 py-1.5 text-xs font-semibold transition-colors"
                                :class="monedaHistoricoSeleccionada?.id_moneda === m.id_moneda
                                    ? 'bg-blue-600 text-white shadow-sm'
                                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600'"
                            >
                                {{ m.codigo }} ({{ m.simbolo }})
                            </button>
                        </div>
                    </div>

                    <!-- Tabla de Histórico -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300">
                            <thead class="bg-gray-50 text-xs uppercase tracking-wider text-gray-500 dark:bg-gray-900/40 dark:text-gray-400">
                                <tr>
                                    <th scope="col" class="px-6 py-3.5 font-semibold">Fecha y Hora</th>
                                    <th scope="col" class="px-6 py-3.5 font-semibold">Moneda</th>
                                    <th scope="col" class="px-6 py-3.5 font-semibold">Tasa Anterior</th>
                                    <th scope="col" class="px-6 py-3.5 font-semibold">Tasa Nueva</th>
                                    <th scope="col" class="px-6 py-3.5 font-semibold">Variación</th>
                                    <th scope="col" class="px-6 py-3.5 font-semibold">Usuario Responsable</th>
                                    <th scope="col" class="px-6 py-3.5 font-semibold">Observaciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                <tr
                                    v-for="item in listaHistorico"
                                    :key="item.id_historico_tasa"
                                    class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30"
                                >
                                    <td class="whitespace-nowrap px-6 py-4 font-mono text-xs">
                                        {{ new Date(item.created_at).toLocaleString('es-VE') }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 font-semibold text-gray-900 dark:text-white">
                                        {{ item.moneda?.codigo || monedaHistoricoSeleccionada?.codigo }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 font-mono">
                                        {{ Number(item.tasa_anterior).toFixed(4) }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 font-mono font-bold text-gray-900 dark:text-white">
                                        {{ Number(item.tasa_nueva).toFixed(4) }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 font-mono text-xs">
                                        <span
                                            v-if="item.tasa_nueva > item.tasa_anterior"
                                            class="inline-flex items-center text-rose-600 dark:text-rose-400 font-semibold"
                                        >
                                            ▲ +{{ (((item.tasa_nueva - item.tasa_anterior) / (item.tasa_anterior || 1)) * 100).toFixed(2) }}%
                                        </span>
                                        <span
                                            v-else-if="item.tasa_nueva < item.tasa_anterior"
                                            class="inline-flex items-center text-emerald-600 dark:text-emerald-400 font-semibold"
                                        >
                                            ▼ {{ (((item.tasa_nueva - item.tasa_anterior) / (item.tasa_anterior || 1)) * 100).toFixed(2) }}%
                                        </span>
                                        <span v-else class="text-gray-400">
                                            = 0.00%
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-xs">
                                        {{ item.usuario?.nombre || 'Sistema' }}
                                    </td>
                                    <td class="px-6 py-4 text-xs text-gray-500 dark:text-gray-400">
                                        {{ item.observaciones || '—' }}
                                    </td>
                                </tr>
                                <tr v-if="listaHistorico.length === 0">
                                    <td colspan="7" class="py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                        No hay registros históricos para la moneda seleccionada.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Actualizar Cotización -->
        <div
            v-if="modalAbierto"
            class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-gray-900/60 p-4 backdrop-blur-sm"
        >
            <div
                class="w-full max-w-lg rounded-2xl border border-gray-100 bg-white p-6 shadow-2xl transition-all dark:border-gray-700 dark:bg-gray-800"
            >
                <div class="flex items-center justify-between border-b border-gray-100 pb-4 dark:border-gray-700">
                    <div class="flex items-center space-x-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100 font-bold text-blue-700 dark:bg-blue-900/60 dark:text-blue-200">
                            {{ monedaSeleccionada?.simbolo }}
                        </span>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                                Actualizar Cotización: {{ monedaSeleccionada?.nombre }}
                            </h3>
                            <span class="text-xs text-gray-400">Código ISO: {{ monedaSeleccionada?.codigo }}</span>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="cerrarModal"
                        class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-gray-700 dark:hover:text-gray-200"
                    >
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="guardarTasa" class="mt-6 space-y-4">
                    <!-- Tasa Actual vs Nueva Tasa -->
                    <div class="grid grid-cols-2 gap-4 rounded-xl bg-gray-50 p-4 dark:bg-gray-900/50">
                        <div>
                            <span class="text-xs text-gray-400">Tasa Actual</span>
                            <p class="font-mono text-lg font-bold text-gray-700 dark:text-gray-300">
                                {{ Number(monedaSeleccionada?.tasa_cambio).toFixed(4) }}
                            </p>
                        </div>
                        <div>
                            <span class="text-xs text-gray-400">Inversa Aproximada</span>
                            <p class="font-mono text-sm font-medium text-gray-600 dark:text-gray-400">
                                1 {{ monedaSeleccionada?.codigo }} ≈ ${{ calcularInversa }} USD
                            </p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">
                            Nueva Tasa de Cambio (respecto a 1 USD Base)
                        </label>
                        <div class="relative mt-1">
                            <input
                                v-model="form.tasa_cambio"
                                type="number"
                                step="0.0001"
                                min="0.0001"
                                required
                                class="block w-full rounded-xl border-gray-300 py-2.5 pl-4 pr-12 font-mono text-lg font-bold text-gray-900 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                placeholder="0.0000"
                            />
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-4 font-semibold text-gray-400">
                                {{ monedaSeleccionada?.codigo }}
                            </div>
                        </div>
                        <p v-if="form.errors.tasa_cambio" class="mt-1 text-xs text-rose-600">
                            {{ form.errors.tasa_cambio }}
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">
                            Motivo / Observaciones del Ajuste
                        </label>
                        <input
                            v-model="form.observaciones"
                            type="text"
                            maxlength="255"
                            class="mt-1 block w-full rounded-xl border-gray-300 py-2 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            placeholder="Ej: Publicación oficial tasa BCV de la tarde"
                        />
                    </div>

                    <div class="flex items-center justify-end space-x-3 pt-4 border-t border-gray-100 dark:border-gray-700">
                        <button
                            type="button"
                            @click="cerrarModal"
                            class="rounded-xl border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 focus:outline-none dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="inline-flex items-center rounded-xl bg-blue-600 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50"
                        >
                            <span v-if="form.processing">Guardando...</span>
                            <span v-else>Guardar Cotización</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
