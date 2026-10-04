<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    tabla: String,
    idRegistro: [Number, String],
    historial: Object,
    cargando: Boolean,
});

const emit = defineEmits(['cerrar']);

const eventoExpandido = ref(null);

const AlternarExpansion = (IdAuditoria) =>
{
    if (eventoExpandido.value === IdAuditoria)
    {
        eventoExpandido.value = null;
    }
    else
    {
        eventoExpandido.value = IdAuditoria;
    }
};

const FormatearValor = (val) =>
{
    if (val === null || val === undefined)
    {
        return '<vacio>';
    }
    if (typeof val === 'boolean')
    {
        return val ? 'true (VERDADERO)' : 'false (FALSO)';
    }
    if (typeof val === 'object')
    {
        return JSON.stringify(val, null, 2);
    }
    return String(val);
};

const BadgeAccion = (acc) =>
{
    switch (acc)
    {
        case 'CREAR':
            return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300 border-emerald-300 dark:border-emerald-700';
        case 'ACTUALIZAR':
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300 border-blue-300 dark:border-blue-700';
        case 'ELIMINAR':
            return 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300 border-red-300 dark:border-red-700';
        case 'ANULAR':
            return 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300 border-amber-300 dark:border-amber-700';
        case 'APLICAR':
            return 'bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300 border-purple-300 dark:border-purple-700';
        case 'LOGIN':
            return 'bg-teal-100 text-teal-800 dark:bg-teal-900/40 dark:text-teal-300 border-teal-300 dark:border-teal-700';
        case 'LOGOUT':
            return 'bg-slate-100 text-slate-800 dark:bg-slate-700 dark:text-slate-300 border-slate-300 dark:border-slate-600';
        case 'LOGIN_FALLIDO':
            return 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300 border-rose-300 dark:border-rose-700';
        default:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-600';
    }
};
</script>

<template>
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs">
        <div class="bg-white dark:bg-gray-800 w-full max-w-4xl rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700 overflow-hidden flex flex-col max-h-[90vh]">

            <!-- Encabezado Modal -->
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                <div class="flex items-center gap-3">
                    <span class="text-xl">📜</span>
                    <div>
                        <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            Línea de Tiempo y Trazabilidad de la Entidad
                        </h3>
                        <p class="text-xs text-gray-500 font-mono">
                            Tabla: <span class="font-bold text-indigo-600 dark:text-indigo-400 uppercase">{{ tabla }}</span> | Registro ID: #<span class="font-bold text-gray-800 dark:text-gray-200">{{ idRegistro }}</span>
                        </p>
                    </div>
                </div>
                <button
                    @click="emit('cerrar')"
                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 text-lg px-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition"
                >
                    ✕
                </button>
            </div>

            <!-- Contenido / Timeline -->
            <div class="p-6 overflow-y-auto flex-1 space-y-4">
                <div v-if="cargando" class="py-12 flex flex-col items-center justify-center text-gray-400 gap-3">
                    <svg class="animate-spin h-7 w-7 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <span class="text-xs font-semibold">Cargando pistas de auditoría del registro...</span>
                </div>

                <div v-else-if="!historial || !historial.timeline || historial.timeline.length === 0" class="py-12 text-center text-gray-400 text-xs">
                    No se encontraron pistas de auditoría registradas para esta entidad.
                </div>

                <div v-else class="relative border-l-2 border-indigo-200 dark:border-indigo-900/60 ml-4 space-y-6 pb-2">
                    <div
                        v-for="evento in historial.timeline"
                        :key="evento.id_auditoria"
                        class="relative pl-6"
                    >
                        <!-- Nodo indicador del timeline -->
                        <span class="absolute -left-[9px] top-1.5 w-4 h-4 rounded-full bg-white dark:bg-gray-800 border-2 border-indigo-600 dark:border-indigo-400"></span>

                        <div class="bg-gray-50/80 dark:bg-gray-900/40 rounded-xl border border-gray-200/80 dark:border-gray-700/80 p-4 space-y-2">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span :class="['px-2 py-0.5 rounded-full text-[10px] font-bold font-mono border', BadgeAccion(evento.accion)]">
                                        {{ evento.accion }}
                                    </span>
                                    <span class="text-xs font-bold text-gray-800 dark:text-gray-200 font-mono">
                                        Pista #{{ evento.id_auditoria }}
                                    </span>
                                    <span class="text-xs text-gray-500">
                                        por <strong class="text-gray-700 dark:text-gray-300">{{ evento.usuario }}</strong>
                                    </span>
                                </div>
                                <div class="text-[11px] font-mono text-gray-400">
                                    {{ evento.fecha_hora }}
                                </div>
                            </div>

                            <p class="text-xs text-gray-600 dark:text-gray-300">
                                {{ evento.resumen }}
                            </p>

                            <div class="flex items-center justify-between text-[11px] text-gray-400 pt-1 border-t border-gray-200/50 dark:border-gray-700/50">
                                <div class="font-mono">
                                    IP: <span class="text-indigo-600 dark:text-indigo-400">{{ evento.ip_direccion || '127.0.0.1' }}</span>
                                </div>
                                <button
                                    v-if="evento.diff && evento.diff.length > 0"
                                    type="button"
                                    @click="AlternarExpansion(evento.id_auditoria)"
                                    class="text-indigo-600 dark:text-indigo-400 hover:underline font-semibold flex items-center gap-1"
                                >
                                    <span>{{ eventoExpandido === evento.id_auditoria ? 'Ocultar Diferencial ▲' : 'Ver Diferencial ▼' }}</span>
                                </button>
                            </div>

                            <!-- Grilla de diferencias desplegable -->
                            <div
                                v-if="eventoExpandido === evento.id_auditoria && evento.diff && evento.diff.length > 0"
                                class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700 overflow-hidden"
                            >
                                <table class="w-full text-left text-xs font-mono">
                                    <thead class="bg-gray-100 dark:bg-gray-800 text-[10px] text-gray-500 uppercase font-bold">
                                        <tr>
                                            <th class="px-3 py-1.5">Campo</th>
                                            <th class="px-3 py-1.5">Valor Anterior</th>
                                            <th class="px-3 py-1.5">Valor Nuevo</th>
                                            <th class="px-3 py-1.5 text-center">Tipo</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200/60 dark:divide-gray-700/60">
                                        <tr
                                            v-for="d in evento.diff"
                                            :key="d.campo"
                                            :class="[
                                                d.tipo_cambio === 'MODIFICADO' ? 'bg-amber-50/50 dark:bg-amber-950/20' : '',
                                                d.tipo_cambio === 'AGREGADO' ? 'bg-emerald-50/50 dark:bg-emerald-950/20' : '',
                                                d.tipo_cambio === 'ELIMINADO' ? 'bg-red-50/50 dark:bg-red-950/20' : '',
                                            ]"
                                        >
                                            <td class="px-3 py-1.5 font-bold text-gray-900 dark:text-white">
                                                {{ d.campo }}
                                            </td>
                                            <td class="px-3 py-1.5 text-red-700 dark:text-red-400">
                                                {{ FormatearValor(d.valor_anterior) }}
                                            </td>
                                            <td class="px-3 py-1.5 text-emerald-700 dark:text-emerald-400 font-semibold">
                                                {{ FormatearValor(d.valor_nuevo) }}
                                            </td>
                                            <td class="px-3 py-1.5 text-center text-[10px] font-bold">
                                                {{ d.tipo_cambio }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pie Modal -->
            <div class="px-6 py-3 bg-gray-50 dark:bg-gray-900/50 border-t border-gray-200 dark:border-gray-700 flex justify-between items-center text-xs">
                <span class="text-gray-500">
                    Total eventos históricos: <strong>{{ historial?.total_eventos || 0 }}</strong>
                </span>
                <button
                    @click="emit('cerrar')"
                    class="px-4 py-1.5 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 text-xs font-semibold rounded-lg transition"
                >
                    Cerrar
                </button>
            </div>
        </div>
    </div>
</template>
