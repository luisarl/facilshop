<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    auditoria: Object,
    diff: Array,
});

const emit = defineEmits(['cerrar']);

const busquedaCampo = ref('');
const soloModificados = ref(false);
const copiadoExitoso = ref(false);

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

const badgeAccion = computed(() =>
{
    switch (props.auditoria?.accion)
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
});

const DiffFiltrado = computed(() =>
{
    if (!props.diff)
    {
        return [];
    }

    return props.diff.filter(d =>
    {
        const CoincideBusqueda = !busquedaCampo.value || d.campo.toLowerCase().includes(busquedaCampo.value.toLowerCase());
        const CoincideModificado = !soloModificados.value || d.tipo_cambio !== 'SIN_CAMBIO';
        return CoincideBusqueda && CoincideModificado;
    });
});

const EsEventoAuth = computed(() =>
{
    return ['LOGIN', 'LOGOUT', 'LOGIN_FALLIDO'].includes(props.auditoria?.accion);
});

const CopiarSnapshot = async () =>
{
    const snapshot = {
        id_auditoria: props.auditoria?.id_auditoria,
        accion: props.auditoria?.accion,
        modulo: props.auditoria?.modulo,
        tabla: props.auditoria?.tabla_afectada,
        registro_id: props.auditoria?.id_registro_afectado,
        usuario: props.auditoria?.usuario?.nombre || props.auditoria?.usuario?.name || 'Sistema',
        fecha: props.auditoria?.created_at,
        valores_anteriores: props.auditoria?.valores_anteriores,
        valores_nuevos: props.auditoria?.valores_nuevos,
    };

    try
    {
        await navigator.clipboard.writeText(JSON.stringify(snapshot, null, 2));
        copiadoExitoso.value = true;
        setTimeout(() =>
        {
            copiadoExitoso.value = false;
        }, 2000);
    }
    catch (e)
    {
        console.error('Error al copiar al portapapeles', e);
    }
};
</script>

<template>
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs">
        <div class="bg-white dark:bg-gray-800 w-full max-w-4xl rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700 overflow-hidden flex flex-col max-h-[90vh]">
            
            <!-- Encabezado Modal -->
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                <div class="flex items-center gap-3">
                    <span class="text-xl">🔍</span>
                    <div>
                        <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            Pista de Auditoría #{{ auditoria.id_auditoria }}
                            <span :class="['px-2.5 py-0.5 rounded-full text-xs font-bold font-mono border', badgeAccion]">
                                {{ auditoria.accion }}
                            </span>
                        </h3>
                        <p class="text-xs text-gray-500 font-mono">
                            Tabla: <span class="font-bold text-gray-700 dark:text-gray-300">{{ auditoria.tabla_afectada }}</span> | Registro ID: #{{ auditoria.id_registro_afectado || 'N/A' }}
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

            <!-- Metadatos Forenses -->
            <div class="px-6 py-3 bg-gray-50/70 dark:bg-gray-900/30 border-b border-gray-200 dark:border-gray-700 grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                <div>
                    <span class="text-gray-400 uppercase text-[10px] font-bold tracking-wider">Usuario</span>
                    <div class="font-semibold text-gray-800 dark:text-gray-200">
                        {{ auditoria.usuario?.nombre || auditoria.usuario?.name || 'Sistema / Cron' }}
                    </div>
                    <div class="text-[10px] text-gray-400">{{ auditoria.usuario?.email || '' }}</div>
                </div>
                <div>
                    <span class="text-gray-400 uppercase text-[10px] font-bold tracking-wider">Fecha y Hora</span>
                    <div class="font-mono text-gray-800 dark:text-gray-200">
                        {{ new Date(auditoria.created_at).toLocaleString() }}
                    </div>
                </div>
                <div>
                    <span class="text-gray-400 uppercase text-[10px] font-bold tracking-wider">Dirección IP</span>
                    <div class="font-mono font-bold text-indigo-600 dark:text-indigo-400">
                        {{ auditoria.ip_direccion || '127.0.0.1' }}
                    </div>
                </div>
                <div>
                    <span class="text-gray-400 uppercase text-[10px] font-bold tracking-wider">Módulo</span>
                    <div class="font-semibold text-gray-800 dark:text-gray-200">
                        {{ auditoria.modulo }}
                    </div>
                </div>
            </div>

            <!-- URL y Dispositivo -->
            <div class="px-6 py-2 bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700 text-[11px] text-gray-500 font-mono flex flex-col sm:flex-row justify-between gap-1">
                <div class="truncate max-w-lg">
                    <span class="text-gray-400 uppercase text-[9px] font-bold mr-1">URL:</span>
                    {{ auditoria.url || 'No registrada' }}
                </div>
                <div class="truncate max-w-sm text-gray-400">
                    <span class="text-gray-400 uppercase text-[9px] font-bold mr-1">Agente:</span>
                    {{ auditoria.user_agent || 'Navegador desconocido' }}
                </div>
            </div>

            <!-- Vista especial para eventos de Autenticación -->
            <div v-if="EsEventoAuth" class="p-6 overflow-y-auto space-y-4 flex-1">
                <div class="bg-indigo-50/60 dark:bg-indigo-950/20 border border-indigo-200 dark:border-indigo-800/60 rounded-2xl p-5 space-y-3">
                    <div class="flex items-center gap-3">
                        <span class="text-2xl">{{ auditoria.accion === 'LOGIN' ? '🔓' : (auditoria.accion === 'LOGOUT' ? '🔒' : '⚠️') }}</span>
                        <div>
                            <h4 class="text-sm font-bold text-gray-900 dark:text-white">
                                Evento de Autenticación de Usuario
                            </h4>
                            <p class="text-xs text-gray-600 dark:text-gray-300">
                                {{ auditoria.resumen_cambios }}
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2 text-xs">
                        <div class="bg-white dark:bg-gray-800 p-3 rounded-xl border border-gray-200 dark:border-gray-700 space-y-1">
                            <span class="text-[10px] uppercase font-bold text-gray-400">Atributos del Evento</span>
                            <div v-for="(v, k) in auditoria.valores_nuevos" :key="k" class="font-mono text-gray-700 dark:text-gray-300">
                                <strong>{{ k }}:</strong> {{ FormatearValor(v) }}
                            </div>
                        </div>

                        <div class="bg-white dark:bg-gray-800 p-3 rounded-xl border border-gray-200 dark:border-gray-700 space-y-1">
                            <span class="text-[10px] uppercase font-bold text-gray-400">Origen de Conexión</span>
                            <div class="font-mono text-gray-700 dark:text-gray-300">
                                <strong>IP:</strong> {{ auditoria.ip_direccion || '127.0.0.1' }}
                            </div>
                            <div class="font-mono text-gray-700 dark:text-gray-300 break-all">
                                <strong>Dispositivo:</strong> {{ auditoria.user_agent || 'N/A' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Visor de Diferencias Estándar (Diff Viewer) -->
            <div v-else class="p-6 overflow-y-auto space-y-4 flex-1">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <h4 class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            Diferencial de Atributos (Snapshot Previo vs Snapshot Posterior)
                        </h4>
                        <div class="flex items-center gap-3 text-[11px] mt-1">
                            <span class="flex items-center gap-1 text-red-600 font-semibold">
                                <span class="w-2 h-2 rounded-full bg-red-500 inline-block"></span> Previo (Rojo)
                            </span>
                            <span class="flex items-center gap-1 text-emerald-600 font-semibold">
                                <span class="w-2 h-2 rounded-full bg-emerald-500 inline-block"></span> Nuevo (Verde)
                            </span>
                        </div>
                    </div>

                    <!-- Buscador rápido de campos y filtro de cambios -->
                    <div class="flex items-center gap-2">
                        <input
                            v-model="busquedaCampo"
                            type="text"
                            placeholder="Buscar campo..."
                            class="text-xs px-2.5 py-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        />
                        <label class="inline-flex items-center gap-1.5 text-xs text-gray-600 dark:text-gray-300 cursor-pointer select-none">
                            <input
                                v-model="soloModificados"
                                type="checkbox"
                                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 text-xs"
                            />
                            <span>Solo cambios</span>
                        </label>
                    </div>
                </div>

                <div v-if="!DiffFiltrado || DiffFiltrado.length === 0" class="p-8 text-center text-gray-400 text-xs bg-gray-50 dark:bg-gray-900/40 rounded-xl">
                    No se encontraron atributos coincidentes con los filtros seleccionados.
                </div>

                <div v-else class="border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-gray-50 dark:bg-gray-900/60 uppercase text-[10px] text-gray-500 font-bold border-b border-gray-200 dark:border-gray-700">
                            <tr>
                                <th class="px-4 py-2.5 w-1/4">Campo</th>
                                <th class="px-4 py-2.5 w-1/3">Valor Anterior</th>
                                <th class="px-4 py-2.5 w-1/3">Valor Nuevo</th>
                                <th class="px-4 py-2.5 text-center">Tipo</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700 font-mono">
                            <tr
                                v-for="d in DiffFiltrado"
                                :key="d.campo"
                                :class="[
                                    'transition',
                                    d.tipo_cambio === 'MODIFICADO' ? 'bg-amber-50/40 dark:bg-amber-950/20' : '',
                                    d.tipo_cambio === 'AGREGADO' ? 'bg-emerald-50/40 dark:bg-emerald-950/20' : '',
                                    d.tipo_cambio === 'ELIMINADO' ? 'bg-red-50/40 dark:bg-red-950/20' : '',
                                ]"
                            >
                                <td class="px-4 py-2.5 font-bold text-gray-900 dark:text-white">
                                    {{ d.campo }}
                                </td>

                                <!-- Valor Anterior (Rojo si cambió) -->
                                <td class="px-4 py-2.5">
                                    <div
                                        :class="[
                                            'p-1.5 rounded text-xs break-all',
                                            d.tipo_cambio === 'MODIFICADO' || d.tipo_cambio === 'ELIMINADO'
                                                ? 'bg-red-100/70 text-red-900 dark:bg-red-900/40 dark:text-red-300 font-semibold'
                                                : 'text-gray-500 dark:text-gray-400'
                                        ]"
                                    >
                                        {{ FormatearValor(d.valor_anterior) }}
                                    </div>
                                </td>

                                <!-- Valor Nuevo (Verde si cambió) -->
                                <td class="px-4 py-2.5">
                                    <div
                                        :class="[
                                            'p-1.5 rounded text-xs break-all',
                                            d.tipo_cambio === 'MODIFICADO' || d.tipo_cambio === 'AGREGADO'
                                                ? 'bg-emerald-100/70 text-emerald-900 dark:bg-emerald-900/40 dark:text-emerald-300 font-semibold'
                                                : 'text-gray-500 dark:text-gray-400'
                                        ]"
                                    >
                                        {{ FormatearValor(d.valor_nuevo) }}
                                    </div>
                                </td>

                                <!-- Badge de Tipo de Cambio -->
                                <td class="px-4 py-2.5 text-center whitespace-nowrap">
                                    <span
                                        v-if="d.tipo_cambio === 'MODIFICADO'"
                                        class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300"
                                    >
                                        MODIFICADO
                                    </span>
                                    <span
                                        v-else-if="d.tipo_cambio === 'AGREGADO'"
                                        class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300"
                                    >
                                        NUEVO
                                    </span>
                                    <span
                                        v-else-if="d.tipo_cambio === 'ELIMINADO'"
                                        class="px-2 py-0.5 rounded text-[10px] font-bold bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300"
                                    >
                                        ELIMINADO
                                    </span>
                                    <span
                                        v-else
                                        class="text-gray-400 text-[10px]"
                                    >
                                        =
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pie Modal -->
            <div class="px-6 py-3 bg-gray-50 dark:bg-gray-900/50 border-t border-gray-200 dark:border-gray-700 flex justify-between items-center">
                <button
                    type="button"
                    @click="CopiarSnapshot"
                    class="px-3 py-1.5 text-xs font-semibold text-gray-600 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400 inline-flex items-center gap-1.5 transition"
                >
                    <span>📋</span>
                    <span>{{ copiadoExitoso ? '¡Copiado al Portapapeles!' : 'Copiar Snapshot JSON' }}</span>
                </button>

                <button
                    @click="emit('cerrar')"
                    class="px-4 py-1.5 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 text-xs font-semibold rounded-lg transition"
                >
                    Cerrar Visor
                </button>
            </div>
        </div>
    </div>
</template>
