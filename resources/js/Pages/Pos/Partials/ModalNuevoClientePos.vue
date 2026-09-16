<script setup>
import { ref } from 'vue';
import axios from 'axios';

const emit = defineEmits(['cerrar', 'clienteCreado']);

const FormularioCliente = ref({
    identificacion: '',
    nombre: '',
    telefono: '',
    email: '',
    limite_credito: 0.00,
});

const guardando = ref(false);
const errores = ref({});
const errorGeneral = ref(null);

const GuardarCliente = async () =>
{
    guardando.value = true;
    errores.value = {};
    errorGeneral.value = null;

    try
    {
        const respuesta = await axios.post(route('clientes.store'), {
            identificacion: FormularioCliente.value.identificacion.trim(),
            nombre: FormularioCliente.value.nombre.trim(),
            telefono: FormularioCliente.value.telefono ? FormularioCliente.value.telefono.trim() : null,
            email: FormularioCliente.value.email ? FormularioCliente.value.email.trim() : null,
            limite_credito: Number(FormularioCliente.value.limite_credito) || 0.00,
        });

        if (respuesta.data?.cliente)
        {
            emit('clienteCreado', respuesta.data.cliente);
        }
    }
    catch (error)
    {
        if (error.response?.status === 422 && error.response?.data?.errors)
        {
            errores.value = error.response.data.errors;
        }
        else
        {
            errorGeneral.value = error.response?.data?.error || error.response?.data?.message || 'Error al registrar el cliente.';
        }
    }
    finally
    {
        guardando.value = false;
    }
};
</script>

<template>
    <div class="fixed inset-0 z-50 overflow-y-auto bg-black/70 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-800 rounded-3xl max-w-md w-full p-6 sm:p-7 shadow-2xl border border-gray-100 dark:border-gray-700 space-y-5">

            <!-- ENCABEZADO -->
            <div class="flex justify-between items-start pb-3 border-b border-gray-100 dark:border-gray-700">
                <div>
                    <span class="text-[11px] font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider">
                        Punto de Venta
                    </span>
                    <h3 class="text-xl font-black text-gray-900 dark:text-gray-100">
                        Registrar Nuevo Cliente
                    </h3>
                </div>

                <button
                    type="button"
                    @click="emit('cerrar')"
                    class="text-gray-400 hover:text-gray-500 text-2xl font-bold p-1 leading-none"
                >
                    ✕
                </button>
            </div>

            <!-- ERROR GENERAL -->
            <div
                v-if="errorGeneral"
                class="p-3 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 rounded-xl text-xs text-rose-700 dark:text-rose-300 font-semibold"
            >
                ⚠️ {{ errorGeneral }}
            </div>

            <!-- FORMULARIO -->
            <form @submit.prevent="GuardarCliente" class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">
                        Cédula / RIF *
                    </label>
                    <input
                        type="text"
                        v-model="FormularioCliente.identificacion"
                        placeholder="Ej: V-12345678 o J-40912345-0"
                        required
                        autofocus
                        class="w-full py-2 px-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-sm font-mono focus:ring-2 focus:ring-indigo-500"
                    />
                    <p v-if="errores.identificacion" class="text-[11px] text-rose-600 font-semibold mt-1">
                        {{ errores.identificacion[0] }}
                    </p>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">
                        Nombre Completo o Razón Social *
                    </label>
                    <input
                        type="text"
                        v-model="FormularioCliente.nombre"
                        placeholder="Ej: Juan Pérez o Distribuidora El Éxito"
                        required
                        class="w-full py-2 px-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-sm font-medium focus:ring-2 focus:ring-indigo-500"
                    />
                    <p v-if="errores.nombre" class="text-[11px] text-rose-600 font-semibold mt-1">
                        {{ errores.nombre[0] }}
                    </p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">
                            Teléfono (Móvil)
                        </label>
                        <input
                            type="text"
                            v-model="FormularioCliente.telefono"
                            placeholder="Ej: 0414-1234567"
                            class="w-full py-2 px-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-xs font-mono focus:ring-2 focus:ring-indigo-500"
                        />
                        <p v-if="errores.telefono" class="text-[11px] text-rose-600 font-semibold mt-1">
                            {{ errores.telefono[0] }}
                        </p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">
                            Límite Crédito ($)
                        </label>
                        <input
                            type="number"
                            step="0.01"
                            min="0"
                            v-model="FormularioCliente.limite_credito"
                            placeholder="0.00"
                            class="w-full py-2 px-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-xs font-mono font-bold focus:ring-2 focus:ring-indigo-500"
                        />
                        <p v-if="errores.limite_credito" class="text-[11px] text-rose-600 font-semibold mt-1">
                            {{ errores.limite_credito[0] }}
                        </p>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">
                        Correo Electrónico
                    </label>
                    <input
                        type="email"
                        v-model="FormularioCliente.email"
                        placeholder="cliente@ejemplo.com"
                        class="w-full py-2 px-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500"
                    />
                    <p v-if="errores.email" class="text-[11px] text-rose-600 font-semibold mt-1">
                        {{ errores.email[0] }}
                    </p>
                </div>

                <!-- BOTONES DE ACCIÓN -->
                <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-100 dark:border-gray-700">
                    <button
                        type="button"
                        @click="emit('cerrar')"
                        class="px-4 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl text-xs font-semibold transition"
                    >
                        Cancelar
                    </button>
                    <button
                        type="submit"
                        :disabled="guardando"
                        class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition shadow-sm disabled:opacity-50 flex items-center gap-2"
                    >
                        <span v-if="guardando">Guardando...</span>
                        <span v-else>Guardar y Seleccionar</span>
                    </button>
                </div>
            </form>

        </div>
    </div>
</template>
