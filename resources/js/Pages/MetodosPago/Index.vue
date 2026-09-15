<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    metodosPago: {
        type: Array,
        required: true,
    },
    monedas: {
        type: Array,
        required: true,
    },
});

const modalAbierto = ref(false);
const modoEdicion = ref(false);
const idEdicion = ref(null);

const form = useForm({
    nombre: '',
    codigo: '',
    id_moneda: props.monedas[0]?.id_moneda || '',
    tipo: 'EFECTIVO',
    requiere_referencia: false,
    activo: true,
});

const tiposMetodo = [
    { valor: 'EFECTIVO', label: 'Efectivo en Caja', color: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/60 dark:text-emerald-300' },
    { valor: 'DIGITAL', label: 'Digital / Pago Móvil', color: 'bg-cyan-100 text-cyan-800 dark:bg-cyan-900/60 dark:text-cyan-300' },
    { valor: 'TRANSFERENCIA', label: 'Transferencia Bancaria / Zelle', color: 'bg-blue-100 text-blue-800 dark:bg-blue-900/60 dark:text-blue-300' },
    { valor: 'TARJETA', label: 'Punto de Venta / Tarjeta', color: 'bg-purple-100 text-purple-800 dark:bg-purple-900/60 dark:text-purple-300' },
    { valor: 'CREDITO', label: 'Crédito Interno', color: 'bg-amber-100 text-amber-800 dark:bg-amber-900/60 dark:text-amber-300' },
    { valor: 'FINANCIAMIENTO', label: 'Financiamiento BNPL (Cashea)', color: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/60 dark:text-yellow-300' },
];

const obtenerBadgeTipo = (tipo) => {
    const item = tiposMetodo.find(t => t.valor === tipo);
    return item ? item.color : 'bg-gray-100 text-gray-800';
};

const abrirModalCrear = () => {
    modoEdicion.value = false;
    idEdicion.value = null;
    form.reset();
    form.id_moneda = props.monedas[0]?.id_moneda || '';
    form.tipo = 'EFECTIVO';
    form.requiere_referencia = false;
    form.activo = true;
    form.clearErrors();
    modalAbierto.value = true;
};

const abrirModalEditar = (metodo) => {
    modoEdicion.value = true;
    idEdicion.value = metodo.id_metodo_pago;
    form.nombre = metodo.nombre;
    form.codigo = metodo.codigo;
    form.id_moneda = metodo.id_moneda;
    form.tipo = metodo.tipo;
    form.requiere_referencia = Boolean(metodo.requiere_referencia);
    form.activo = Boolean(metodo.activo);
    form.clearErrors();
    modalAbierto.value = true;
};

const cerrarModal = () => {
    modalAbierto.value = false;
    form.reset();
    form.clearErrors();
};

const guardarMetodo = () => {
    if (modoEdicion.value) {
        form.put(route('metodos-pago.update', { id_metodo_pago: idEdicion.value }), {
            preserveScroll: true,
            onSuccess: () => cerrarModal(),
        });
    } else {
        form.post(route('metodos-pago.store'), {
            preserveScroll: true,
            onSuccess: () => cerrarModal(),
        });
    }
};

const alternarEstado = (metodo) => {
    router.patch(route('metodos-pago.estado', { id_metodo_pago: metodo.id_metodo_pago }), {}, {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Métodos de Pago" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">
                        Métodos de Pago
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Configure las opciones de cobro disponibles en el POS vinculadas a sus respectivas monedas.
                    </p>
                </div>
                <button
                    type="button"
                    @click="abrirModalCrear"
                    class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:bg-blue-600 dark:hover:bg-blue-500"
                >
                    <svg class="mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Nuevo Método de Pago
                </button>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300">
                            <thead class="bg-gray-50 text-xs uppercase tracking-wider text-gray-500 dark:bg-gray-900/40 dark:text-gray-400">
                                <tr>
                                    <th scope="col" class="px-6 py-4 font-semibold">Método de Pago</th>
                                    <th scope="col" class="px-6 py-4 font-semibold">Código Sistema</th>
                                    <th scope="col" class="px-6 py-4 font-semibold">Moneda Asignada</th>
                                    <th scope="col" class="px-6 py-4 font-semibold">Tipo</th>
                                    <th scope="col" class="px-6 py-4 font-semibold">Referencia</th>
                                    <th scope="col" class="px-6 py-4 font-semibold text-center">Estado</th>
                                    <th scope="col" class="px-6 py-4 font-semibold text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                <tr
                                    v-for="metodo in metodosPago"
                                    :key="metodo.id_metodo_pago"
                                    class="transition-colors hover:bg-gray-50/50 dark:hover:bg-gray-700/30"
                                >
                                    <td class="whitespace-nowrap px-6 py-4 font-bold text-gray-900 dark:text-white">
                                        {{ metodo.nombre }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 font-mono text-xs text-gray-500 dark:text-gray-400">
                                        {{ metodo.codigo }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4">
                                        <span class="inline-flex items-center space-x-1.5 rounded-lg bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-700 dark:bg-gray-700 dark:text-gray-200">
                                            <span>{{ metodo.moneda?.simbolo }}</span>
                                            <span>{{ metodo.moneda?.codigo }}</span>
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4">
                                        <span
                                            class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold"
                                            :class="obtenerBadgeTipo(metodo.tipo)"
                                        >
                                            {{ metodo.tipo }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-xs">
                                        <span v-if="metodo.requiere_referencia" class="font-medium text-amber-600 dark:text-amber-400">
                                            Obligatoria
                                        </span>
                                        <span v-else class="text-gray-400">
                                            Opcional
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-center">
                                        <button
                                            type="button"
                                            @click="alternarEstado(metodo)"
                                            class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                                            :class="metodo.activo ? 'bg-emerald-600' : 'bg-gray-300 dark:bg-gray-600'"
                                        >
                                            <span
                                                class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                                                :class="metodo.activo ? 'translate-x-5' : 'translate-x-0'"
                                            />
                                        </button>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-right">
                                        <button
                                            type="button"
                                            @click="abrirModalEditar(metodo)"
                                            class="rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 hover:text-blue-600 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-blue-400"
                                            title="Editar método de pago"
                                        >
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Crear/Editar Método -->
        <div
            v-if="modalAbierto"
            class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-gray-900/60 p-4 backdrop-blur-sm"
        >
            <div
                class="w-full max-w-lg rounded-2xl border border-gray-100 bg-white p-6 shadow-2xl transition-all dark:border-gray-700 dark:bg-gray-800"
            >
                <div class="flex items-center justify-between border-b border-gray-100 pb-4 dark:border-gray-700">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                        {{ modoEdicion ? 'Editar Método de Pago' : 'Nuevo Método de Pago' }}
                    </h3>
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

                <form @submit.prevent="guardarMetodo" class="mt-6 space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">
                            Nombre del Método
                        </label>
                        <input
                            v-model="form.nombre"
                            type="text"
                            required
                            class="mt-1 block w-full rounded-xl border-gray-300 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            placeholder="Ej: Pago Móvil Banesco"
                        />
                        <p v-if="form.errors.nombre" class="mt-1 text-xs text-rose-600">{{ form.errors.nombre }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">
                            Código Interno (Único)
                        </label>
                        <input
                            v-model="form.codigo"
                            type="text"
                            required
                            class="mt-1 block w-full rounded-xl border-gray-300 py-2.5 font-mono text-sm uppercase shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            placeholder="EJ: PAGO_MOVIL_BANESCO"
                        />
                        <p v-if="form.errors.codigo" class="mt-1 text-xs text-rose-600">{{ form.errors.codigo }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">
                                Moneda Asignada
                            </label>
                            <select
                                v-model="form.id_moneda"
                                required
                                class="mt-1 block w-full rounded-xl border-gray-300 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            >
                                <option v-for="m in monedas" :key="m.id_moneda" :value="m.id_moneda">
                                    {{ m.codigo }} - {{ m.nombre }} ({{ m.simbolo }})
                                </option>
                            </select>
                            <p v-if="form.errors.id_moneda" class="mt-1 text-xs text-rose-600">{{ form.errors.id_moneda }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">
                                Tipo de Método
                            </label>
                            <select
                                v-model="form.tipo"
                                required
                                class="mt-1 block w-full rounded-xl border-gray-300 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            >
                                <option v-for="t in tiposMetodo" :key="t.valor" :value="t.valor">
                                    {{ t.label }}
                                </option>
                            </select>
                            <p v-if="form.errors.tipo" class="mt-1 text-xs text-rose-600">{{ form.errors.tipo }}</p>
                        </div>
                    </div>

                    <div class="flex items-center space-x-3 pt-2">
                        <input
                            v-model="form.requiere_referencia"
                            id="chk_referencia"
                            type="checkbox"
                            class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700"
                        />
                        <label for="chk_referencia" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            Exigir número de referencia bancaria al cobrar en POS
                        </label>
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
                            <span v-else>{{ modoEdicion ? 'Actualizar Método' : 'Crear Método' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
