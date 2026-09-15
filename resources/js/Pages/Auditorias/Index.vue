<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ModalDiffAuditoria from '@/Pages/Auditorias/Partials/ModalDiffAuditoria.vue';
import axios from 'axios';

const props = defineProps({
    auditorias: Object,
    kpis: Object,
    usuarios: Array,
    modulos: Array,
    acciones: Array,
    filtros: Object,
});

// Filtros Reactivos
const buscar = ref(props.filtros?.buscar || '');
const modulo = ref(props.filtros?.modulo || '');
const accion = ref(props.filtros?.accion || '');
const tablaAfectada = ref(props.filtros?.tabla_afectada || '');
const idUsuario = ref(props.filtros?.id_usuario || '');
const fechaDesde = ref(props.filtros?.fecha_desde || '');
const fechaHasta = ref(props.filtros?.fecha_hasta || '');

const AplicarFiltros = () => {
    router.get(route('auditorias.index'), {
        buscar: buscar.value || undefined,
        modulo: modulo.value || undefined,
        accion: accion.value || undefined,
        tabla_afectada: tablaAfectada.value || undefined,
        id_usuario: idUsuario.value || undefined,
        fecha_desde: fechaDesde.value || undefined,
        fecha_hasta: fechaHasta.value || undefined,
    }, {
        preserveState: true,
        replace: true,
    });
};

const LimpiarFiltros = () => {
    buscar.value = '';
    modulo.value = '';
    accion.value = '';
    tablaAfectada.value = '';
    idUsuario.value = '';
    fechaDesde.value = '';
    fechaHasta.value = '';
    AplicarFiltros();
};

// Modal Diff
const modalDiffAbierto = ref(false);
const auditoriaSeleccionada = ref(null);
const diffSeleccionado = ref([]);
const cargandoDiff = ref(false);

const AbrirDiff = async (auditoria) => {
    auditoriaSeleccionada.value = auditoria;
    cargandoDiff.value = true;
    modalDiffAbierto.value = true;
    diffSeleccionado.value = [];

    try {
        const res = await axios.get(route('auditorias.show', auditoria.id_auditoria));
        diffSeleccionado.value = res.data.diff;
    } catch (e) {
        console.error('Error al obtener diff:', e);
    } finally {
        cargandoDiff.value = false;
    }
};

const BadgeAccion = (acc) => {
    switch (acc) {
        case 'CREAR':
            return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300';
        case 'ACTUALIZAR':
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300';
        case 'ELIMINAR':
            return 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300';
        case 'ANULAR':
            return 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300';
        case 'APLICAR':
            return 'bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300';
        default:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
    }
};
</script>

<template>
    <Head title="Pistas de Auditoría Integral (Audit Trail)" />

    <AuthenticatedLayout>
        <div class="py-6 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto space-y-6">

            <!-- Cabecera -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <span>🛡️</span> Pistas de Auditoría y Trazabilidad Forense
                    </h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Registro inmutable de acciones en el sistema: quién, qué, cuándo, IP y visor diferencial de cambios (Diff).
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <a
                        :href="route('auditorias.exportar', filtros)"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg shadow-sm transition"
                    >
                        <span>📥</span> Exportar Log a CSV
                    </a>
                </div>
            </div>

            <!-- Tarjetas de Métricas de Auditoría -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-100 dark:border-gray-700 shadow-sm">
                    <span class="text-[10px] uppercase font-semibold text-gray-400">Total Eventos</span>
                    <div class="text-xl font-bold text-gray-900 dark:text-white mt-1 font-mono">
                        {{ kpis.total_general }}
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-100 dark:border-gray-700 shadow-sm">
                    <span class="text-[10px] uppercase font-semibold text-gray-400">Registrados Hoy</span>
                    <div class="text-xl font-bold text-indigo-600 dark:text-indigo-400 mt-1 font-mono">
                        {{ kpis.total_hoy }}
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-100 dark:border-gray-700 shadow-sm">
                    <span class="text-[10px] uppercase font-semibold text-gray-400">Creaciones</span>
                    <div class="text-xl font-bold text-emerald-600 dark:text-emerald-400 mt-1 font-mono">
                        {{ kpis.total_creaciones }}
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-100 dark:border-gray-700 shadow-sm">
                    <span class="text-[10px] uppercase font-semibold text-gray-400">Actualizaciones</span>
                    <div class="text-xl font-bold text-blue-600 dark:text-blue-400 mt-1 font-mono">
                        {{ kpis.total_actualizaciones }}
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-100 dark:border-gray-700 shadow-sm">
                    <span class="text-[10px] uppercase font-semibold text-gray-400">Eliminaciones</span>
                    <div class="text-xl font-bold text-red-600 dark:text-red-400 mt-1 font-mono">
                        {{ kpis.total_eliminaciones }}
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-100 dark:border-gray-700 shadow-sm">
                    <span class="text-[10px] uppercase font-semibold text-gray-400">Anulaciones</span>
                    <div class="text-xl font-bold text-amber-600 dark:text-amber-400 mt-1 font-mono">
                        {{ kpis.total_anulaciones }}
                    </div>
                </div>
            </div>

            <!-- Filtros de Búsqueda Forense -->
            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700 shadow-sm space-y-3">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
                    <div class="lg:col-span-2">
                        <label class="block text-[10px] font-semibold text-gray-500 uppercase mb-1">Buscar</label>
                        <input
                            v-model="buscar"
                            @keyup.enter="AplicarFiltros"
                            type="text"
                            placeholder="Usuario, tabla, IP, ID de registro..."
                            class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        />
                    </div>

                    <div>
                        <label class="block text-[10px] font-semibold text-gray-500 uppercase mb-1">Módulo</label>
                        <select
                            v-model="modulo"
                            @change="AplicarFiltros"
                            class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        >
                            <option value="">Todos los módulos</option>
                            <option v-for="m in modulos" :key="m" :value="m">{{ m }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[10px] font-semibold text-gray-500 uppercase mb-1">Acción</label>
                        <select
                            v-model="accion"
                            @change="AplicarFiltros"
                            class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        >
                            <option value="">Todas las acciones</option>
                            <option v-for="a in acciones" :key="a" :value="a">{{ a }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[10px] font-semibold text-gray-500 uppercase mb-1">Usuario</label>
                        <select
                            v-model="idUsuario"
                            @change="AplicarFiltros"
                            class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        >
                            <option value="">Todos los usuarios</option>
                            <option v-for="u in usuarios" :key="u.id_usuario" :value="u.id_usuario">
                                {{ u.name }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[10px] font-semibold text-gray-500 uppercase mb-1">Desde</label>
                        <input
                            v-model="fechaDesde"
                            @change="AplicarFiltros"
                            type="date"
                            class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        />
                    </div>
                </div>

                <div class="flex justify-between items-center pt-2 border-t border-gray-100 dark:border-gray-700">
                    <span class="text-xs text-gray-400">
                        Mostrando registros ordenados cronológicamente
                    </span>

                    <div class="flex gap-2">
                        <button
                            @click="AplicarFiltros"
                            class="px-4 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg transition"
                        >
                            Filtrar
                        </button>
                        <button
                            v-if="buscar || modulo || accion || tablaAfectada || idUsuario || fechaDesde || fechaHasta"
                            @click="LimpiarFiltros"
                            class="px-3 py-1.5 text-xs text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition"
                        >
                            Limpiar
                        </button>
                    </div>
                </div>
            </div>

            <!-- Tabla de Pistas de Auditoría -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-gray-600 dark:text-gray-300">
                        <thead class="bg-gray-50 dark:bg-gray-900/50 text-[10px] uppercase font-semibold text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
                            <tr>
                                <th class="px-5 py-3">ID / Fecha</th>
                                <th class="px-5 py-3">Usuario / IP</th>
                                <th class="px-5 py-3">Módulo / Tabla</th>
                                <th class="px-5 py-3 text-center">Acción</th>
                                <th class="px-5 py-3">Detalle / Resumen</th>
                                <th class="px-5 py-3 text-right">Diferencial</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr
                                v-for="a in auditorias.data"
                                :key="a.id_auditoria"
                                class="hover:bg-gray-50/70 dark:hover:bg-gray-700/40 transition"
                            >
                                <td class="px-5 py-3 whitespace-nowrap">
                                    <div class="font-mono font-bold text-gray-900 dark:text-white">
                                        #{{ a.id_auditoria }}
                                    </div>
                                    <div class="text-[10px] text-gray-400 font-mono">
                                        {{ new Date(a.created_at).toLocaleString() }}
                                    </div>
                                </td>

                                <td class="px-5 py-3">
                                    <div class="font-semibold text-gray-900 dark:text-white">
                                        {{ a.usuario?.name || 'Sistema' }}
                                    </div>
                                    <div class="text-[10px] font-mono text-indigo-600 dark:text-indigo-400">
                                        {{ a.ip_direccion || '127.0.0.1' }}
                                    </div>
                                </td>

                                <td class="px-5 py-3">
                                    <div class="font-bold text-gray-800 dark:text-gray-200">
                                        {{ a.modulo }}
                                    </div>
                                    <div class="text-[10px] text-gray-400 font-mono">
                                        {{ a.tabla_afectada }} (ID: {{ a.id_registro_afectado || '-' }})
                                    </div>
                                </td>

                                <td class="px-5 py-3 text-center whitespace-nowrap">
                                    <span :class="['inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold font-mono', BadgeAccion(a.accion)]">
                                        {{ a.accion }}
                                    </span>
                                </td>

                                <td class="px-5 py-3 max-w-xs">
                                    <div class="truncate text-gray-600 dark:text-gray-400 text-[11px]" :title="a.resumen_cambios">
                                        {{ a.resumen_cambios }}
                                    </div>
                                </td>

                                <td class="px-5 py-3 text-right whitespace-nowrap">
                                    <button
                                        @click="AbrirDiff(a)"
                                        class="px-2.5 py-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:hover:bg-indigo-900/60 dark:text-indigo-300 rounded-lg text-xs font-semibold inline-flex items-center gap-1 transition"
                                    >
                                        🔍 Ver Diff
                                    </button>
                                </td>
                            </tr>

                            <tr v-if="auditorias.data.length === 0">
                                <td colspan="6" class="px-6 py-12 text-center text-gray-400">
                                    No se encontraron eventos de auditoría con los criterios seleccionados.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                <div v-if="auditorias.links && auditorias.links.length > 3" class="px-6 py-3 bg-gray-50 dark:bg-gray-900/50 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <span class="text-xs text-gray-500">
                        Mostrando {{ auditorias.from }} - {{ auditorias.to }} de {{ auditorias.total }} pistas registradas
                    </span>
                    <div class="flex gap-1">
                        <template v-for="(link, idx) in auditorias.links" :key="idx">
                            <button
                                v-if="link.url"
                                @click="router.get(link.url, {}, { preserveState: true })"
                                v-html="link.label"
                                :class="[
                                    'px-2.5 py-1 text-xs rounded-md transition',
                                    link.active
                                        ? 'bg-indigo-600 text-white font-bold'
                                        : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-100'
                                ]"
                            />
                        </template>
                    </div>
                </div>
            </div>

            <!-- MODAL DIFF VIEWER -->
            <ModalDiffAuditoria
                v-if="modalDiffAbierto && auditoriaSeleccionada"
                :auditoria="auditoriaSeleccionada"
                :diff="diffSeleccionado"
                @cerrar="modalDiffAbierto = false"
            />

        </div>
    </AuthenticatedLayout>
</template>
