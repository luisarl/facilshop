<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    ajuste: Object,
});

const Imprimir = () => {
    window.print();
};
</script>

<template>
    <Head :title="`Ajuste ${ajuste.codigo_ajuste}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 no-print">
                <div class="flex items-center gap-2">
                    <Link
                        :href="route('inventario.ajustes.index')"
                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 text-sm font-semibold flex items-center gap-1"
                    >
                        ← Ajustes
                    </Link>
                    <span class="text-gray-300">/</span>
                    <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-100">
                        Acta de Ajuste {{ ajuste.codigo_ajuste }}
                    </h2>
                </div>

                <div class="flex items-center gap-3">
                    <button
                        @click="Imprimir"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl shadow-sm transition gap-2"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                        </svg>
                        Imprimir Documento
                    </button>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

                <!-- CONTENEDOR IMPRIMIBLE (ACTA OFICIAL) -->
                <div class="bg-white dark:bg-gray-800 p-8 sm:p-12 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 printable-card text-gray-800 dark:text-gray-200">

                    <!-- ENCABEZADO -->
                    <div class="flex flex-col sm:flex-row justify-between items-start border-b border-gray-200 dark:border-gray-700 pb-6 gap-4">
                        <div>
                            <div class="text-2xl font-black tracking-tight text-gray-900 dark:text-gray-100">
                                FÁCIL SHOP C.A.
                            </div>
                            <div class="text-xs text-gray-500 mt-1">
                                Sistema de Punto de Venta y Control de Inventario
                            </div>
                            <div class="text-xs text-gray-400 mt-0.5">
                                RIF: J-40912345-0 | San Cristóbal, Táchira
                            </div>
                        </div>

                        <div class="text-left sm:text-right">
                            <div class="text-xs font-bold text-gray-400 uppercase tracking-wider">
                                Acta de Ajuste Oficial
                            </div>
                            <div class="text-2xl font-black font-mono text-indigo-600 dark:text-indigo-400 mt-0.5">
                                {{ ajuste.codigo_ajuste }}
                            </div>
                            <div class="text-xs text-gray-500 font-mono mt-1">
                                Fecha: {{ new Date(ajuste.fecha_ajuste).toLocaleDateString() }} {{ new Date(ajuste.fecha_ajuste).toLocaleTimeString() }}
                            </div>
                        </div>
                    </div>

                    <!-- METADATOS DEL AJUSTE -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 my-6 p-5 bg-gray-50 dark:bg-gray-750 rounded-2xl border border-gray-100 dark:border-gray-700 text-xs">
                        <div>
                            <span class="text-gray-400 block uppercase font-bold text-[10px]">Naturaleza</span>
                            <span
                                :class="[
                                    'inline-block mt-1 px-2.5 py-1 rounded-full font-bold text-xs',
                                    ajuste.tipo_movimiento?.naturaleza === 'ENTRADA'
                                        ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300'
                                        : 'bg-rose-100 text-rose-800 dark:bg-rose-900/50 dark:text-rose-300'
                                ]"
                            >
                                {{ ajuste.tipo_movimiento?.naturaleza }} ({{ ajuste.tipo_movimiento?.nombre }})
                            </span>
                        </div>

                        <div>
                            <span class="text-gray-400 block uppercase font-bold text-[10px]">Responsable / Usuario</span>
                            <span class="font-bold text-gray-800 dark:text-gray-200 mt-1 block">
                                {{ ajuste.usuario?.nombre }}
                            </span>
                            <span class="text-gray-400 text-[10px]">{{ ajuste.usuario?.email }}</span>
                        </div>

                        <div>
                            <span class="text-gray-400 block uppercase font-bold text-[10px]">Documento Referencia</span>
                            <span class="font-mono font-bold text-gray-800 dark:text-gray-200 mt-1 block">
                                {{ ajuste.documento_referencia || 'SIN DOCUMENTO ASOCIADO' }}
                            </span>
                        </div>

                        <div class="sm:col-span-3 pt-2 border-t border-gray-200/60 dark:border-gray-700/60">
                            <span class="text-gray-400 block uppercase font-bold text-[10px]">Motivo / Justificación</span>
                            <span class="text-gray-800 dark:text-gray-200 font-medium text-xs mt-0.5 block">
                                {{ ajuste.motivo }}
                            </span>
                        </div>
                    </div>

                    <!-- TABLA DE DETALLES -->
                    <div class="overflow-x-auto mt-6">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-xs">
                            <thead>
                                <tr class="text-gray-500 uppercase font-bold text-[10px] bg-gray-50 dark:bg-gray-700/30">
                                    <th class="py-2.5 px-3 text-left">#</th>
                                    <th class="py-2.5 px-3 text-left">Producto / SKU</th>
                                    <th class="py-2.5 px-3 text-left">Unidad</th>
                                    <th class="py-2.5 px-3 text-right">Cant. Declarada</th>
                                    <th class="py-2.5 px-3 text-center">Unidades Base</th>
                                    <th class="py-2.5 px-3 text-center">Stock Ant.</th>
                                    <th class="py-2.5 px-3 text-center">Nuevo Stock</th>
                                    <th class="py-2.5 px-3 text-right">Costo Unit. USD</th>
                                    <th class="py-2.5 px-3 text-right">Total USD</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700 font-mono">
                                <tr v-for="(det, idx) in ajuste.detalles" :key="det.id_ajuste_detalle">
                                    <td class="py-2.5 px-3 text-gray-400">{{ idx + 1 }}</td>
                                    <td class="py-2.5 px-3 font-sans">
                                        <div class="font-bold text-gray-900 dark:text-gray-100">{{ det.producto?.nombre }}</div>
                                        <div class="text-[10px] text-gray-400 font-mono">SKU: {{ det.producto?.sku }}</div>
                                    </td>
                                    <td class="py-2.5 px-3 text-gray-600 dark:text-gray-300 font-sans">
                                        {{ det.unidad?.nombre }}
                                    </td>
                                    <td class="py-2.5 px-3 text-right font-bold">
                                        {{ det.cantidad }}
                                    </td>
                                    <td class="py-2.5 px-3 text-center font-bold text-indigo-600 dark:text-indigo-400">
                                        {{ det.cantidad_base }}
                                    </td>
                                    <td class="py-2.5 px-3 text-center text-gray-400">
                                        {{ det.stock_anterior }}
                                    </td>
                                    <td class="py-2.5 px-3 text-center font-bold text-gray-900 dark:text-gray-100">
                                        {{ det.nuevo_stock }}
                                    </td>
                                    <td class="py-2.5 px-3 text-right text-gray-600 dark:text-gray-300">
                                        ${{ Number(det.costo_unitario).toFixed(2) }}
                                    </td>
                                    <td class="py-2.5 px-3 text-right font-bold text-gray-900 dark:text-gray-100">
                                        ${{ Number(det.costo_total).toFixed(2) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- TOTALES -->
                    <div class="mt-6 flex justify-end border-t border-gray-200 dark:border-gray-700 pt-4">
                        <div class="w-72 space-y-2 text-xs">
                            <div class="flex justify-between text-gray-500">
                                <span>Total Unidades Base:</span>
                                <span class="font-mono font-bold text-gray-800 dark:text-gray-200">{{ ajuste.total_items }}</span>
                            </div>
                            <div class="flex justify-between text-base font-bold text-gray-900 dark:text-gray-100 border-t border-gray-200 dark:border-gray-700 pt-2">
                                <span>Total Costo USD:</span>
                                <span class="font-mono text-emerald-600 dark:text-emerald-400">${{ Number(ajuste.total_costo).toFixed(2) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- FIRMAS DE AUTORIZACIÓN (SOLO IMPRESIÓN Y AUDITORÍA) -->
                    <div class="mt-16 grid grid-cols-2 gap-12 pt-8 border-t border-dashed border-gray-300 dark:border-gray-600 text-center text-xs text-gray-500">
                        <div>
                            <div class="h-12"></div>
                            <div class="border-t border-gray-400 pt-2 font-semibold text-gray-800 dark:text-gray-200">
                                {{ ajuste.usuario?.nombre }}
                            </div>
                            <div>Responsable de Almacén / Inventario</div>
                        </div>
                        <div>
                            <div class="h-12"></div>
                            <div class="border-t border-gray-400 pt-2 font-semibold text-gray-800 dark:text-gray-200">
                                Gerencia General / Auditoría
                            </div>
                            <div>Aprobado y Autorizado</div>
                        </div>
                    </div>

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
    body {
        background-color: white !important;
    }
    .printable-card {
        border: none !important;
        box-shadow: none !important;
        padding: 0 !important;
    }
}
</style>
