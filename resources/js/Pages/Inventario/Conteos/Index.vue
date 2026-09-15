<script setup>
import { ref, reactive } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    conteos: Object,
    filtros: Object,
    catalogos: Object,
});

const filtroForm = reactive({
    buscar: props.filtros?.buscar || '',
    estado: props.filtros?.estado || '',
    fecha_desde: props.filtros?.fecha_desde || '',
    fecha_hasta: props.filtros?.fecha_hasta || '',
});

const Filtrar = () => {
    router.get(route('inventario.conteos.index'), filtroForm, {
        preserveState: true,
        replace: true,
    });
};

const LimpiarFiltros = () => {
    filtroForm.buscar = '';
    filtroForm.estado = '';
    filtroForm.fecha_desde = '';
    filtroForm.fecha_hasta = '';
    Filtrar();
};

// Modal Iniciar Conteo
const modalIniciarAbierto = ref(false);

const formIniciar = useForm({
    descripcion: '',
    id_categoria: '',
    id_marca: '',
    precargar_stock: false,
});

const IniciarConteo = () => {
    formIniciar.post(route('inventario.conteos.store'), {
        onSuccess: () => {
            modalIniciarAbierto.value = false;
            formIniciar.reset();
        },
    });
};
</script>

<template>
    <Head title="Tomas Físicas y Conteos de Inventario" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-100 flex items-center gap-2">
                        <span class="p-2 bg-indigo-100 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-400 rounded-lg">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                            </svg>
                        </span>
                        Tomas Físicas y Auditorías de Inventario
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Sesiones de conteo con congelamiento de stock teórico, lectura de código de barras y conciliación automática.
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <button
                        @click="modalIniciarAbierto = true"
                        class="inline-flex items-center px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl shadow-sm transition duration-150 gap-2"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Iniciar Toma Física
                    </button>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

                <!-- Filtros -->
                <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700">
                    <form @submit.prevent="Filtrar" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 items-end">
                        <div class="lg:col-span-2">
                            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider mb-1">
                                Buscar
                            </label>
                            <input
                                type="text"
                                v-model="filtroForm.buscar"
                                placeholder="Código de conteo o descripción..."
                                class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-sm"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider mb-1">
                                Estado
                            </label>
                            <select
                                v-model="filtroForm.estado"
                                class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-sm"
                            >
                                <option value="">Todos los Estados</option>
                                <option value="EN_PROCESO">EN PROCESO (Abierto)</option>
                                <option value="APLICADO">APLICADO (Conciliado)</option>
                                <option value="CANCELADO">CANCELADO</option>
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
                                class="w-full py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-semibold transition"
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

                <!-- Tabla de Tomas Físicas -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700/50">
                                <tr>
                                    <th class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                                        Código / Sesión
                                    </th>
                                    <th class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                                        Descripción
                                    </th>
                                    <th class="px-6 py-3.5 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">
                                        Estado
                                    </th>
                                    <th class="px-6 py-3.5 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">
                                        Items Contados
                                    </th>
                                    <th class="px-6 py-3.5 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">
                                        Diferencia Unidades
                                    </th>
                                    <th class="px-6 py-3.5 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">
                                        Diferencia Costo USD
                                    </th>
                                    <th class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                                        Usuario / Inicio
                                    </th>
                                    <th class="px-6 py-3.5 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">
                                        Acción
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                <tr
                                    v-for="conteo in conteos.data"
                                    :key="conteo.id_conteo"
                                    class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition"
                                >
                                    <td class="px-6 py-4">
                                        <div class="font-mono font-bold text-gray-900 dark:text-gray-100 text-sm">
                                            {{ conteo.codigo_conteo }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-800 dark:text-gray-200">
                                            {{ conteo.descripcion || 'Toma general de inventario' }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span
                                            :class="[
                                                'inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold',
                                                conteo.estado === 'EN_PROCESO'
                                                    ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300 animate-pulse'
                                                    : (conteo.estado === 'APLICADO'
                                                        ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300'
                                                        : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400')
                                            ]"
                                        >
                                            {{ conteo.estado === 'EN_PROCESO' ? 'EN PROCESO' : conteo.estado }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center font-mono text-sm font-bold text-gray-700 dark:text-gray-300">
                                        {{ conteo.total_items_contados }}
                                    </td>
                                    <td class="px-6 py-4 text-center font-mono font-bold">
                                        <span
                                            :class="[
                                                conteo.total_diferencia_unidades === 0
                                                    ? 'text-emerald-600'
                                                    : (conteo.total_diferencia_unidades > 0 ? 'text-blue-600' : 'text-rose-600')
                                            ]"
                                        >
                                            {{ conteo.total_diferencia_unidades > 0 ? '+' : '' }}{{ conteo.total_diferencia_unidades }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right font-mono font-bold text-sm">
                                        <span
                                            :class="[
                                                Number(conteo.total_diferencia_costo) === 0
                                                    ? 'text-gray-600'
                                                    : (Number(conteo.total_diferencia_costo) > 0 ? 'text-blue-600' : 'text-rose-600')
                                            ]"
                                        >
                                            ${{ Number(conteo.total_diferencia_costo).toFixed(2) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-800 dark:text-gray-200">
                                            {{ conteo.usuario?.nombre }}
                                        </div>
                                        <div class="text-xs text-gray-400 font-mono">
                                            {{ new Date(conteo.fecha_inicio).toLocaleDateString() }} {{ new Date(conteo.fecha_inicio).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <Link
                                            :href="route('inventario.conteos.worksheet', conteo.id_conteo)"
                                            :class="[
                                                'inline-flex items-center px-3 py-1.5 text-xs font-semibold rounded-lg transition gap-1',
                                                conteo.estado === 'EN_PROCESO'
                                                    ? 'bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm'
                                                    : 'bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300'
                                            ]"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                            {{ conteo.estado === 'EN_PROCESO' ? 'Abrir Toma' : 'Ver Detalle' }}
                                        </Link>
                                    </td>
                                </tr>
                                <tr v-if="conteos.data.length === 0">
                                    <td colspan="8" class="px-6 py-12 text-center text-gray-400 text-sm">
                                        No se han registrado sesiones de toma física de inventario.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginación -->
                    <div v-if="conteos.links && conteos.links.length > 3" class="px-6 py-4 border-t border-gray-100 dark:border-gray-700 flex justify-between items-center">
                        <div class="text-xs text-gray-500">
                            Mostrando {{ conteos.from || 0 }} - {{ conteos.to || 0 }} de {{ conteos.total }} sesiones
                        </div>
                        <div class="flex gap-1">
                            <template v-for="(link, index) in conteos.links" :key="index">
                                <button
                                    v-if="link.url"
                                    @click="router.get(link.url, {}, { preserveState: true })"
                                    v-html="link.label"
                                    :class="[
                                        'px-3 py-1 rounded-lg text-xs font-semibold transition',
                                        link.active
                                            ? 'bg-indigo-600 text-white'
                                            : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200'
                                    ]"
                                />
                            </template>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Modal Iniciar Nueva Toma Física -->
        <div v-if="modalIniciarAbierto" class="fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white dark:bg-gray-800 rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-gray-100 dark:border-gray-700">
                <div class="flex justify-between items-center pb-4 border-b border-gray-100 dark:border-gray-700">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">
                        Iniciar Nueva Sesión de Toma Física
                    </h3>
                    <button @click="modalIniciarAbierto = false" class="text-gray-400 hover:text-gray-500 text-xl font-bold">
                        ✕
                    </button>
                </div>

                <form @submit.prevent="IniciarConteo" class="mt-5 space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">
                            Descripción / Referencia del Conteo
                        </label>
                        <input
                            type="text"
                            v-model="formIniciar.descripcion"
                            placeholder="Ej: Auditoría física de fin de mes, Conteo estantes zona A"
                            class="w-full py-2.5 px-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-sm"
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">
                                Filtrar por Categoría
                            </label>
                            <select
                                v-model="formIniciar.id_categoria"
                                class="w-full py-2 px-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-sm"
                            >
                                <option value="">Todas las Categorías</option>
                                <option v-for="cat in catalogos.categorias" :key="cat.id_categoria" :value="cat.id_categoria">
                                    {{ cat.nombre }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">
                                Filtrar por Marca
                            </label>
                            <select
                                v-model="formIniciar.id_marca"
                                class="w-full py-2 px-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-sm"
                            >
                                <option value="">Todas las Marcas</option>
                                <option v-for="marca in catalogos.marcas" :key="marca.id_marca" :value="marca.id_marca">
                                    {{ marca.nombre }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="p-4 bg-indigo-50 dark:bg-indigo-950/30 rounded-2xl border border-indigo-100 dark:border-indigo-900/50">
                        <label class="flex items-start gap-3 cursor-pointer">
                            <input
                                type="checkbox"
                                v-model="formIniciar.precargar_stock"
                                class="rounded border-gray-300 text-indigo-600 mt-0.5 focus:ring-indigo-500"
                            />
                            <div>
                                <span class="text-xs font-bold text-indigo-900 dark:text-indigo-200 block">
                                    Precargar existencia teórica como conteo inicial
                                </span>
                                <span class="text-[11px] text-indigo-700 dark:text-indigo-300 block mt-0.5">
                                    Si se activa, el conteo físico iniciará con el valor actual del sistema en lugar de cero.
                                </span>
                            </div>
                        </label>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-700">
                        <button
                            type="button"
                            @click="modalIniciarAbierto = false"
                            class="px-4 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl text-sm font-semibold transition"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            :disabled="formIniciar.processing"
                            class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-semibold transition disabled:opacity-50"
                        >
                            {{ formIniciar.processing ? 'Iniciando...' : 'Comenzar Toma Física' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </AuthenticatedLayout>
</template>
