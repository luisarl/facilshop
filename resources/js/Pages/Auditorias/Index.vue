<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ModalDiffAuditoria from '@/Pages/Auditorias/Partials/ModalDiffAuditoria.vue';
import ModalHistorialEntidad from '@/Pages/Auditorias/Partials/ModalHistorialEntidad.vue';
import { FormatearFechaHora } from '@/Utils/FechaHora';
import { FormatearCantidad } from '@/Utils/FormatoNumero';
import axios from 'axios';

const props = defineProps({
    auditorias: Object,
    kpis: Object,
    usuarios: Array,
    tablas: Array,
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

const AplicarFiltros = () =>
{
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

const LimpiarFiltros = () =>
{
    buscar.value = '';
    modulo.value = '';
    accion.value = '';
    tablaAfectada.value = '';
    idUsuario.value = '';
    fechaDesde.value = '';
    fechaHasta.value = '';
    AplicarFiltros();
};

const FiltrarPorAccion = (NuevaAccion) =>
{
    accion.value = NuevaAccion;
    AplicarFiltros();
};

const FiltrarHoy = () =>
{
    const hoy = new Date().toISOString().split('T')[0];
    fechaDesde.value = hoy;
    fechaHasta.value = hoy;
    AplicarFiltros();
};

// Modal Diff
const modalDiffAbierto = ref(false);
const auditoriaSeleccionada = ref(null);
const diffSeleccionado = ref([]);
const cargandoDiff = ref(false);

const AbrirDiff = async (auditoria) =>
{
    auditoriaSeleccionada.value = auditoria;
    cargandoDiff.value = true;
    modalDiffAbierto.value = true;
    diffSeleccionado.value = [];

    try
    {
        const res = await axios.get(route('auditorias.show', auditoria.id_auditoria));
        diffSeleccionado.value = res.data.diff;
    }
    catch (e)
    {
        console.error('Error al obtener diff:', e);
    }
    finally
    {
        cargandoDiff.value = false;
    }
};

// Modal Historial de Entidad (Timeline)
const modalHistorialAbierto = ref(false);
const tablaHistorial = ref('');
const idRegistroHistorial = ref(null);
const historialData = ref(null);
const cargandoHistorial = ref(false);

const AbrirHistorialEntidad = async (tabla, idRegistro) =>
{
    if (!tabla || !idRegistro)
    {
        return;
    }

    tablaHistorial.value = tabla;
    idRegistroHistorial.value = idRegistro;
    cargandoHistorial.value = true;
    modalHistorialAbierto.value = true;
    historialData.value = null;

    try
    {
        const res = await axios.get(route('auditorias.entidad', {
            tabla: tabla,
            id_registro: idRegistro,
        }));
        historialData.value = res.data;
    }
    catch (e)
    {
        console.error('Error al cargar historial de entidad:', e);
    }
    finally
    {
        cargandoHistorial.value = false;
    }
};

const BadgeAccion = (acc) =>
{
    switch (acc)
    {
        case 'CREAR':
            return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-700';
        case 'ACTUALIZAR':
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300 border border-blue-300 dark:border-blue-700';
        case 'ELIMINAR':
            return 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300 border border-red-300 dark:border-red-700';
        case 'ANULAR':
            return 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300 border border-amber-300 dark:border-amber-700';
        case 'APLICAR':
            return 'bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300 border border-purple-300 dark:border-purple-700';
        case 'LOGIN':
            return 'bg-teal-100 text-teal-800 dark:bg-teal-900/40 dark:text-teal-300 border border-teal-300 dark:border-teal-700';
        case 'LOGOUT':
            return 'bg-slate-100 text-slate-800 dark:bg-slate-700 dark:text-slate-300 border border-slate-300 dark:border-slate-600';
        case 'LOGIN_FALLIDO':
            return 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300 border border-rose-300 dark:border-rose-700';
        default:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600';
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
                        class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-xl shadow-sm transition"
                    >
                        <span>📥</span> Exportar Log a CSV
                    </a>
                </div>
            </div>

            <!-- Tarjetas de Métricas de Auditoría (Interactivas) -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                <div
                    @click="LimpiarFiltros"
                    class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-100 dark:border-gray-700 shadow-sm hover:border-indigo-400 dark:hover:border-indigo-500 cursor-pointer transition"
                    title="Haga clic para ver todos los eventos"
                >
                    <span class="text-[10px] uppercase font-semibold text-gray-400">Total Eventos</span>
                    <div class="text-xl font-bold text-gray-900 dark:text-white mt-1 font-mono">
                        {{ FormatearCantidad(kpis.total_general) }}
                    </div>
                </div>

                <div
                    @click="FiltrarHoy"
                    class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-100 dark:border-gray-700 shadow-sm hover:border-indigo-400 dark:hover:border-indigo-500 cursor-pointer transition"
                    title="Haga clic para filtrar eventos de hoy"
                >
                    <span class="text-[10px] uppercase font-semibold text-gray-400">Registrados Hoy</span>
                    <div class="text-xl font-bold text-indigo-600 dark:text-indigo-400 mt-1 font-mono">
                        {{ FormatearCantidad(kpis.total_hoy) }}
                    </div>
                </div>

                <div
                    @click="FiltrarPorAccion('CREAR')"
                    class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-100 dark:border-gray-700 shadow-sm hover:border-emerald-400 cursor-pointer transition"
                    title="Haga clic para filtrar creaciones"
                >
                    <span class="text-[10px] uppercase font-semibold text-gray-400">Creaciones</span>
                    <div class="text-xl font-bold text-emerald-600 dark:text-emerald-400 mt-1 font-mono">
                        {{ FormatearCantidad(kpis.total_creaciones) }}
                    </div>
                </div>

                <div
                    @click="FiltrarPorAccion('ACTUALIZAR')"
                    class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-100 dark:border-gray-700 shadow-sm hover:border-blue-400 cursor-pointer transition"
                    title="Haga clic para filtrar actualizaciones"
                >
                    <span class="text-[10px] uppercase font-semibold text-gray-400">Actualizaciones</span>
                    <div class="text-xl font-bold text-blue-600 dark:text-blue-400 mt-1 font-mono">
                        {{ FormatearCantidad(kpis.total_actualizaciones) }}
                    </div>
                </div>

                <div
                    @click="FiltrarPorAccion('ELIMINAR')"
                    class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-100 dark:border-gray-700 shadow-sm hover:border-red-400 cursor-pointer transition"
                    title="Haga clic para filtrar eliminaciones"
                >
                    <span class="text-[10px] uppercase font-semibold text-gray-400">Eliminaciones</span>
                    <div class="text-xl font-bold text-red-600 dark:text-red-400 mt-1 font-mono">
                        {{ FormatearCantidad(kpis.total_eliminaciones) }}
                    </div>
                </div>

                <div
                    @click="FiltrarPorAccion('ANULAR')"
                    class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-100 dark:border-gray-700 shadow-sm hover:border-amber-400 cursor-pointer transition"
                    title="Haga clic para filtrar anulaciones"
                >
                    <span class="text-[10px] uppercase font-semibold text-gray-400">Anulaciones</span>
                    <div class="text-xl font-bold text-amber-600 dark:text-amber-400 mt-1 font-mono">
                        {{ FormatearCantidad(kpis.total_anulaciones) }}
                    </div>
                </div>
            </div>

            <!-- Filtros de Búsqueda Forense -->
            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700 shadow-sm space-y-3">
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-7 gap-3">
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
                        <SelectFiltro
                            v-model="modulo"
                            :options="modulos"
                            placeholder="Todos"
                            searchPlaceholder="Filtrar módulo..."
                            @change="AplicarFiltros"
                        />
                    </div>

                    <div>
                        <label class="block text-[10px] font-semibold text-gray-500 uppercase mb-1">Acción</label>
                        <SelectFiltro
                            v-model="accion"
                            :options="acciones"
                            placeholder="Todas"
                            searchPlaceholder="Filtrar acción..."
                            @change="AplicarFiltros"
                        />
                    </div>

                    <div>
                        <label class="block text-[10px] font-semibold text-gray-500 uppercase mb-1">Tabla Afectada</label>
                        <SelectFiltro
                            v-model="tablaAfectada"
                            :options="tablas"
                            placeholder="Todas las tablas"
                            searchPlaceholder="Filtrar tabla..."
                            @change="AplicarFiltros"
                        />
                    </div>

                    <div>
                        <label class="block text-[10px] font-semibold text-gray-500 uppercase mb-1">Usuario</label>
                        <SelectFiltro
                            v-model="idUsuario"
                            :options="usuarios"
                            valueKey="id_usuario"
                            labelKey="nombre"
                            placeholder="Todos los usuarios"
                            searchPlaceholder="Filtrar usuario..."
                            @change="AplicarFiltros"
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-1.5">
                        <div>
                            <label class="block text-[10px] font-semibold text-gray-500 uppercase mb-1">Desde</label>
                            <input
                                v-model="fechaDesde"
                                @change="AplicarFiltros"
                                type="date"
                                class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-1.5"
                            />
                        </div>
                        <div>
                            <label class="block text-[10px] font-semibold text-gray-500 uppercase mb-1">Hasta</label>
                            <input
                                v-model="fechaHasta"
                                @change="AplicarFiltros"
                                type="date"
                                class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-1.5"
                            />
                        </div>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row justify-between items-center gap-2 pt-2 border-t border-gray-100 dark:border-gray-700">
                    <span class="text-xs text-gray-400">
                        Mostrando registros ordenados cronológicamente
                    </span>

                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            @click="FiltrarHoy"
                            class="px-2.5 py-1 text-xs font-semibold bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg transition"
                        >
                            📅 Solo Hoy
                        </button>
                        <button
                            type="button"
                            @click="AplicarFiltros"
                            class="px-4 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg transition"
                        >
                            Filtrar
                        </button>
                        <button
                            v-if="buscar || modulo || accion || tablaAfectada || idUsuario || fechaDesde || fechaHasta"
                            type="button"
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
                                <th class="px-5 py-3 text-right">Acciones</th>
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
                                        {{ FormatearFechaHora(a.created_at, true) }}
                                    </div>
                                </td>

                                <td class="px-5 py-3">
                                    <div class="font-semibold text-gray-900 dark:text-white">
                                        {{ a.usuario?.nombre || a.usuario?.name || 'Sistema' }}
                                    </div>
                                    <div class="text-[10px] font-mono text-indigo-600 dark:text-indigo-400">
                                        {{ a.ip_direccion || '127.0.0.1' }}
                                    </div>
                                </td>

                                <td class="px-5 py-3">
                                    <div class="font-bold text-gray-800 dark:text-gray-200">
                                        {{ a.modulo }}
                                    </div>
                                    <div class="text-[10px] text-gray-400 font-mono flex items-center gap-1">
                                        <span>{{ a.tabla_afectada }}</span>
                                        <button
                                            v-if="a.id_registro_afectado"
                                            type="button"
                                            @click="AbrirHistorialEntidad(a.tabla_afectada, a.id_registro_afectado)"
                                            class="text-indigo-600 dark:text-indigo-400 hover:underline font-bold"
                                            :title="'Ver historial completo de ' + a.tabla_afectada + ' #' + a.id_registro_afectado"
                                        >
                                            (#{{ a.id_registro_afectado }}) 📜
                                        </button>
                                        <span v-else>(-</span>
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
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button
                                            v-if="a.id_registro_afectado"
                                            type="button"
                                            @click="AbrirHistorialEntidad(a.tabla_afectada, a.id_registro_afectado)"
                                            class="px-2 py-1 bg-gray-100 hover:bg-gray-200 text-gray-700 dark:bg-gray-700 dark:hover:bg-gray-600 dark:text-gray-200 rounded-lg text-xs font-semibold inline-flex items-center gap-1 transition"
                                            title="Historial de la entidad"
                                        >
                                            📜 Timeline
                                        </button>
                                        <button
                                            type="button"
                                            @click="AbrirDiff(a)"
                                            class="px-2.5 py-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:hover:bg-indigo-900/60 dark:text-indigo-300 rounded-lg text-xs font-semibold inline-flex items-center gap-1 transition"
                                        >
                                            🔍 Ver Diff
                                        </button>
                                    </div>
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

            <!-- MODAL HISTORIAL DE ENTIDAD (TIMELINE) -->
            <ModalHistorialEntidad
                v-if="modalHistorialAbierto"
                :tabla="tablaHistorial"
                :idRegistro="idRegistroHistorial"
                :historial="historialData"
                :cargando="cargandoHistorial"
                @cerrar="modalHistorialAbierto = false"
            />

        </div>
    </AuthenticatedLayout>
</template>
