<script setup>
import { reactive } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    ajustes: Object,
    filtros: Object,
    tipos_entrada: Array,
    tipos_salida: Array,
});

const filtroForm = reactive({
    buscar: props.filtros?.buscar || '',
    naturaleza: props.filtros?.naturaleza || '',
    fecha_desde: props.filtros?.fecha_desde || '',
    fecha_hasta: props.filtros?.fecha_hasta || '',
});

const Filtrar = () => {
    router.get(route('inventario.ajustes.index'), filtroForm, {
        preserveState: true,
        replace: true,
    });
};

const LimpiarFiltros = () => {
    filtroForm.buscar = '';
    filtroForm.naturaleza = '';
    filtroForm.fecha_desde = '';
    filtroForm.fecha_hasta = '';
    Filtrar();
};
</script>

<template>
    <Head title="Ajustes de Inventario" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-100 flex items-center gap-2">
                        <span class="p-2 bg-emerald-100 dark:bg-emerald-900/50 text-emerald-600 dark:text-emerald-400 rounded-lg">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </span>
                        Proceso de Ajustes de Inventario
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Control formal de documentos de entrada (compras, recepciones) y salida (mermas, consumo interno, bajas).
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <Link
                        :href="route('inventario.ajustes.create')"
                        class="inline-flex items-center px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-xl shadow-sm transition duration-150 gap-2"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Nuevo Ajuste
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

                <!-- Barra de Filtros -->
                <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700">
                    <form @submit.prevent="Filtrar" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 items-end">
                        <div class="lg:col-span-2">
                            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider mb-1">
                                Buscar
                            </label>
                            <input
                                type="text"
                                v-model="filtroForm.buscar"
                                placeholder="Código de ajuste, motivo o documento..."
                                class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-sm"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider mb-1">
                                Naturaleza
                            </label>
                            <select
                                v-model="filtroForm.naturaleza"
                                class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-sm"
                            >
                                <option value="">Todas</option>
                                <option value="ENTRADA">ENTRADA (Incremento)</option>
                                <option value="SALIDA">SALIDA (Deducción)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider mb-1">
                                Fecha Desde
                            </label>
                            <input
                                type="date"
                                v-model="filtroForm.fecha_desde"
                                class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-sm"
                            />
                        </div>

                        <div class="flex items-center gap-2">
                            <button
                                type="submit"
                                class="w-full py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-sm font-semibold transition"
                            >
                                Filtrar
                            </button>
                            <button
                                type="button"
                                @click="LimpiarFiltros"
                                class="px-3 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl text-sm font-semibold transition"
                            >
                                Limpiar
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Tabla de Documentos de Ajuste -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700/50">
                                <tr>
                                    <th class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                                        Documento
                                    </th>
                                    <th class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                                        Naturaleza / Tipo
                                    </th>
                                    <th class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                                        Motivo
                                    </th>
                                    <th class="px-6 py-3.5 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">
                                        Items
                                    </th>
                                    <th class="px-6 py-3.5 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">
                                        Costo Total USD
                                    </th>
                                    <th class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                                        Usuario / Fecha
                                    </th>
                                    <th class="px-6 py-3.5 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">
                                        Acción
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                <tr
                                    v-for="ajuste in ajustes.data"
                                    :key="ajuste.id_ajuste"
                                    class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition"
                                >
                                    <td class="px-6 py-4">
                                        <div class="font-mono font-bold text-gray-900 dark:text-gray-100 text-sm">
                                            {{ ajuste.codigo_ajuste }}
                                        </div>
                                        <div v-if="ajuste.documento_referencia" class="text-xs text-gray-400 font-mono mt-0.5">
                                            Ref: {{ ajuste.documento_referencia }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span
                                            :class="[
                                                'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold',
                                                ajuste.tipo_movimiento?.naturaleza === 'ENTRADA'
                                                    ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300'
                                                    : 'bg-rose-100 text-rose-800 dark:bg-rose-900/50 dark:text-rose-300'
                                            ]"
                                        >
                                            {{ ajuste.tipo_movimiento?.naturaleza }}
                                        </span>
                                        <div class="text-xs text-gray-600 dark:text-gray-300 font-medium mt-1">
                                            {{ ajuste.tipo_movimiento?.nombre }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-800 dark:text-gray-200 max-w-xs truncate" :title="ajuste.motivo">
                                            {{ ajuste.motivo }}
                                        </div>
                                        <span class="inline-block mt-1 text-[10px] font-semibold uppercase px-2 py-0.5 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 rounded">
                                            {{ ajuste.estado }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center font-mono text-sm font-bold text-gray-700 dark:text-gray-300">
                                        {{ ajuste.total_items }}
                                    </td>
                                    <td class="px-6 py-4 text-right font-mono text-sm font-bold text-gray-900 dark:text-gray-100">
                                        ${{ Number(ajuste.total_costo).toFixed(2) }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-800 dark:text-gray-200">
                                            {{ ajuste.usuario?.nombre || 'Usuario' }}
                                        </div>
                                        <div class="text-xs text-gray-400 font-mono">
                                            {{ new Date(ajuste.fecha_ajuste).toLocaleDateString() }} {{ new Date(ajuste.fecha_ajuste).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <Link
                                            :href="route('inventario.ajustes.show', ajuste.id_ajuste)"
                                            class="inline-flex items-center px-3 py-1.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 text-xs font-semibold rounded-lg transition gap-1"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                            Ver Acta
                                        </Link>
                                    </td>
                                </tr>
                                <tr v-if="ajustes.data.length === 0">
                                    <td colspan="7" class="px-6 py-12 text-center text-gray-400 text-sm">
                                        No hay ajustes de inventario registrados.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginación -->
                    <div v-if="ajustes.links && ajustes.links.length > 3" class="px-6 py-4 border-t border-gray-100 dark:border-gray-700 flex justify-between items-center">
                        <div class="text-xs text-gray-500">
                            Mostrando {{ ajustes.from || 0 }} - {{ ajustes.to || 0 }} de {{ ajustes.total }} documentos
                        </div>
                        <div class="flex gap-1">
                            <template v-for="(link, index) in ajustes.links" :key="index">
                                <button
                                    v-if="link.url"
                                    @click="router.get(link.url, {}, { preserveState: true })"
                                    v-html="link.label"
                                    :class="[
                                        'px-3 py-1 rounded-lg text-xs font-semibold transition',
                                        link.active
                                            ? 'bg-emerald-600 text-white'
                                            : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200'
                                    ]"
                                />
                            </template>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
