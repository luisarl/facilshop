<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import axios from 'axios';

const props = defineProps({
    clientes: Array,
    clienteSeleccionado: Object,
    metodosPago: Array,
    monedas: Array,
    tasaVes: Number,
});

const emit = defineEmits(['cerrar', 'abonoCompletado']);

// Cliente a cobrar (prioriza el seleccionado en POS si tiene saldo, o el primer deudor)
const idClienteActivo = ref(null);
const clienteAbonar = computed(() =>
{
    return props.clientes?.find(c => c.id_cliente === idClienteActivo.value) || null;
});

// Clientes con saldo pendiente para el selector rápido
const clientesDeudores = computed(() =>
{
    return (props.clientes || []).filter(c => Number(c.saldo_pendiente) > 0);
});

// Formulario de Pago
const idMetodoSeleccionado = ref(null);
const metodoSeleccionado = ref(null);
const montoIngresado = ref(0);
const montoBaseUsd = ref(0);
const tasaCambio = ref(1.0);
const referencia = ref('');
const observaciones = ref('');

const procesando = ref(false);
const errorMensaje = ref(null);

onMounted(() =>
{
    // Seleccionar cliente inicial
    if (props.clienteSeleccionado && Number(props.clienteSeleccionado.saldo_pendiente) > 0)
    {
        idClienteActivo.value = props.clienteSeleccionado.id_cliente;
    }
    else if (clientesDeudores.value.length > 0)
    {
        idClienteActivo.value = clientesDeudores.value[0].id_cliente;
    }
    else if (props.clienteSeleccionado)
    {
        idClienteActivo.value = props.clienteSeleccionado.id_cliente;
    }

    // Seleccionar método de pago por defecto (Efectivo USD o el primero)
    const MetodoInicial = props.metodosPago?.find(m => m.codigo === 'EFECTIVO_USD') || props.metodosPago?.[0];
    if (MetodoInicial)
    {
        idMetodoSeleccionado.value = MetodoInicial.id_metodo_pago;
        metodoSeleccionado.value = MetodoInicial;
        tasaCambio.value = Number(MetodoInicial.moneda?.tasa_cambio) || 1.0;
    }

    // Inicializar monto con la totalidad de la deuda
    AplicarMontoTotal();
});

const AplicarMontoTotal = () =>
{
    if (!clienteAbonar.value)
    {
        return;
    }

    const SaldoTotalUsd = Number(clienteAbonar.value.saldo_pendiente) || 0;
    montoBaseUsd.value = SaldoTotalUsd;

    const MonedaMetodo = metodoSeleccionado.value?.moneda;
    const Tasa = Number(tasaCambio.value) || 1.0;

    if (MonedaMetodo?.es_principal)
    {
        montoIngresado.value = Number(SaldoTotalUsd.toFixed(2));
    }
    else
    {
        montoIngresado.value = Number((SaldoTotalUsd * Tasa).toFixed(2));
    }
};

const AplicarPorcentaje = (porcentaje) =>
{
    if (!clienteAbonar.value)
    {
        return;
    }

    const SaldoTotalUsd = Number(clienteAbonar.value.saldo_pendiente) || 0;
    const MontoParcialUsd = Number(((SaldoTotalUsd * porcentaje) / 100).toFixed(2));
    montoBaseUsd.value = MontoParcialUsd;

    const MonedaMetodo = metodoSeleccionado.value?.moneda;
    const Tasa = Number(tasaCambio.value) || 1.0;

    if (MonedaMetodo?.es_principal)
    {
        montoIngresado.value = Number(MontoParcialUsd.toFixed(2));
    }
    else
    {
        montoIngresado.value = Number((MontoParcialUsd * Tasa).toFixed(2));
    }
};

const CambiarCliente = (IdNuevoCliente) =>
{
    idClienteActivo.value = IdNuevoCliente;
    AplicarMontoTotal();
};

const CambiarMetodoPago = (IdNuevoMetodo) =>
{
    const NuevoMetodo = props.metodosPago?.find(m => m.id_metodo_pago === Number(IdNuevoMetodo));
    if (!NuevoMetodo)
    {
        return;
    }

    const IdMonedaAnterior = metodoSeleccionado.value?.id_moneda;
    const MonedaEncontrada = props.monedas?.find(m => m.id_moneda === NuevoMetodo.id_moneda) || NuevoMetodo.moneda;
    const NuevaTasa = Number(MonedaEncontrada?.tasa_cambio) || (MonedaEncontrada?.codigo === 'VES' ? Number(props.tasaVes) : 1.0) || 1.0;
    const MonedaCambio = IdMonedaAnterior !== NuevoMetodo.id_moneda;

    metodoSeleccionado.value = NuevoMetodo;
    idMetodoSeleccionado.value = NuevoMetodo.id_metodo_pago;
    tasaCambio.value = NuevaTasa;

    if (MonedaCambio)
    {
        const Base = Number(montoBaseUsd.value) || 0;
        if (MonedaEncontrada?.es_principal)
        {
            montoIngresado.value = Number(Base.toFixed(2));
            montoBaseUsd.value = Number(Base.toFixed(2));
        }
        else
        {
            montoIngresado.value = Number((Base * NuevaTasa).toFixed(2));
            montoBaseUsd.value = NuevaTasa > 0 ? Number((montoIngresado.value / NuevaTasa).toFixed(2)) : Base;
        }
    }
};

const ActualizarCalculoMonto = () =>
{
    const monto = Number(montoIngresado.value) || 0;
    const Tasa = Number(tasaCambio.value) || 1.0;
    const EsPrincipal = Boolean(metodoSeleccionado.value?.moneda?.es_principal);

    if (EsPrincipal)
    {
        montoBaseUsd.value = Number(monto.toFixed(2));
    }
    else
    {
        montoBaseUsd.value = Tasa > 0 ? Number((monto / Tasa).toFixed(2)) : monto;
    }
};

// Saldo restante proyectado
const nuevoSaldoProyectado = computed(() =>
{
    if (!clienteAbonar.value)
    {
        return 0;
    }
    const DeudaActual = Number(clienteAbonar.value.saldo_pendiente) || 0;
    const Restante = DeudaActual - Number(montoBaseUsd.value);
    return Restante > 0 ? Number(Restante.toFixed(2)) : 0.00;
});

const esValidoParaAbonar = computed(() =>
{
    if (!clienteAbonar.value)
    {
        return false;
    }
    const Deuda = Number(clienteAbonar.value.saldo_pendiente) || 0;
    if (Deuda <= 0)
    {
        return false;
    }
    if (Number(montoBaseUsd.value) <= 0)
    {
        return false;
    }
    if (Number(montoBaseUsd.value) > Deuda + 0.01)
    {
        return false;
    }
    if (metodoSeleccionado.value?.requiere_referencia && !referencia.value.trim())
    {
        return false;
    }
    return true;
});

const ProcesarAbono = async () =>
{
    if (!esValidoParaAbonar.value)
    {
        return;
    }

    procesando.value = true;
    errorMensaje.value = null;

    const payload = {
        monto: Number(montoIngresado.value),
        monto_base: Number(montoBaseUsd.value),
        id_metodo_pago: metodoSeleccionado.value.id_metodo_pago,
        id_moneda: metodoSeleccionado.value.id_moneda,
        tasa_cambio: Number(tasaCambio.value),
        referencia: referencia.value ? referencia.value.trim() : null,
        observaciones: observaciones.value ? observaciones.value.trim() : 'Abono recibido en caja POS',
    };

    try
    {
        const respuesta = await axios.post(route('clientes.abonar', clienteAbonar.value.id_cliente), payload);

        if (respuesta.data?.abono)
        {
            emit('abonoCompletado', {
                ...respuesta.data.abono,
                metodo_pago: metodoSeleccionado.value,
                moneda: metodoSeleccionado.value.moneda,
                monto_pagado_moneda: Number(montoIngresado.value),
                tasa_cambio: Number(tasaCambio.value),
            });
        }
    }
    catch (error)
    {
        console.error('Error al registrar abono:', error);
        errorMensaje.value = error.response?.data?.error || error.response?.data?.message || 'Error al procesar el abono.';
    }
    finally
    {
        procesando.value = false;
    }
};
</script>

<template>
    <div class="fixed inset-0 z-50 overflow-y-auto bg-black/70 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-800 rounded-3xl max-w-lg w-full p-6 sm:p-7 shadow-2xl border border-gray-100 dark:border-gray-700 space-y-5">

            <!-- ENCABEZADO -->
            <div class="flex justify-between items-start pb-3 border-b border-gray-100 dark:border-gray-700">
                <div>
                    <span class="text-[11px] font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider">
                        Cobro de Cuentas por Cobrar
                    </span>
                    <h3 class="text-xl font-black text-gray-900 dark:text-gray-100">
                        Cobrar Saldo / Abono de Cliente
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
                v-if="errorMensaje"
                class="p-3 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 rounded-xl text-xs text-rose-700 dark:text-rose-300 font-semibold flex items-center gap-2"
            >
                <span>⚠️</span>
                <span>{{ errorMensaje }}</span>
            </div>

            <!-- SELECTOR DE CLIENTE -->
            <div class="space-y-1">
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                    Seleccionar Cliente
                </label>
                <select
                    :value="idClienteActivo"
                    @change="CambiarCliente(Number($event.target.value))"
                    class="w-full py-2 px-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-xs font-semibold focus:ring-2 focus:ring-emerald-500"
                >
                    <option v-for="c in clientes" :key="c.id_cliente" :value="c.id_cliente">
                        {{ c.nombre }} ({{ c.identificacion }}) — Deuda: ${{ Number(c.saldo_pendiente).toFixed(2) }}
                    </option>
                </select>
            </div>

            <!-- TARJETA DE ESTADO DE CUENTA ACTUAL -->
            <div
                v-if="clienteAbonar"
                class="p-4 bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-750 dark:to-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 space-y-2.5"
            >
                <div class="flex justify-between items-center text-xs">
                    <span class="text-gray-500 dark:text-gray-400 font-medium">Deuda Pendiente Actual:</span>
                    <div class="text-right font-mono">
                        <span class="text-base font-black text-rose-600 dark:text-rose-400">
                            ${{ Number(clienteAbonar.saldo_pendiente).toFixed(2) }}
                        </span>
                        <span class="block text-[11px] text-gray-500 dark:text-gray-400">
                            ≈ Bs. {{ (Number(clienteAbonar.saldo_pendiente) * tasaVes).toFixed(2) }}
                        </span>
                    </div>
                </div>

                <div v-if="Number(clienteAbonar.saldo_pendiente) <= 0" class="p-2 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 rounded-xl text-xs font-bold text-center">
                    ✓ Este cliente está al día y no posee saldo pendiente.
                </div>
            </div>

            <!-- DESGLOSE DEL ABONO -->
            <div v-if="clienteAbonar && Number(clienteAbonar.saldo_pendiente) > 0" class="space-y-4">

                <!-- BOTONES DE ACCESO RÁPIDO -->
                <div class="flex items-center gap-2">
                    <span class="text-[11px] font-bold text-gray-500 uppercase">Sugerir:</span>
                    <button
                        type="button"
                        @click="AplicarMontoTotal"
                        class="px-2.5 py-1 bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-300 text-xs font-bold rounded-lg transition"
                    >
                        Total ($ {{ Number(clienteAbonar.saldo_pendiente).toFixed(2) }})
                    </button>
                    <button
                        type="button"
                        @click="AplicarPorcentaje(50)"
                        class="px-2.5 py-1 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-xs font-semibold rounded-lg transition"
                    >
                        50% ($ {{ (Number(clienteAbonar.saldo_pendiente) / 2).toFixed(2) }})
                    </button>
                </div>

                <!-- FORMA DE PAGO Y MONTO -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">
                            Forma de Pago
                        </label>
                        <select
                            :value="idMetodoSeleccionado"
                            @change="CambiarMetodoPago($event.target.value)"
                            class="w-full py-2 px-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-xs font-semibold focus:ring-2 focus:ring-emerald-500"
                        >
                            <option v-for="m in metodosPago" :key="m.id_metodo_pago" :value="m.id_metodo_pago">
                                {{ m.nombre }} ({{ m.moneda?.codigo }})
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">
                            Monto a Cobrar
                        </label>
                        <div class="relative">
                            <input
                                type="number"
                                step="0.01"
                                min="0.01"
                                :max="metodoSeleccionado?.moneda?.es_principal ? Number(clienteAbonar.saldo_pendiente) : Number((Number(clienteAbonar.saldo_pendiente) * tasaCambio).toFixed(2))"
                                v-model="montoIngresado"
                                @input="ActualizarCalculoMonto"
                                placeholder="0.00"
                                class="w-full py-2 pl-3 pr-12 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-xs font-mono font-black focus:ring-2 focus:ring-emerald-500"
                            />
                            <span class="absolute inset-y-0 right-0 pr-3 flex items-center text-[10px] font-bold text-gray-400">
                                {{ metodoSeleccionado?.moneda?.codigo }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- EQUIVALENTE Y REFERENCIA -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 items-center">
                    <div class="p-2.5 bg-gray-50 dark:bg-gray-700/50 rounded-xl border border-gray-150 dark:border-gray-600/50 flex justify-between items-center text-xs">
                        <span class="text-gray-500 dark:text-gray-400 font-medium">Equivalente USD:</span>
                        <span class="font-mono font-black text-gray-800 dark:text-gray-200 text-sm">
                            ${{ Number(montoBaseUsd).toFixed(2) }}
                        </span>
                    </div>

                    <div v-if="metodoSeleccionado?.requiere_referencia">
                        <input
                            type="text"
                            v-model="referencia"
                            placeholder="Referencia bancaria *"
                            required
                            class="w-full py-2 px-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-xs font-mono focus:ring-2 focus:ring-emerald-500"
                        />
                    </div>
                </div>

                <!-- OBSERVACIONES -->
                <div>
                    <input
                        type="text"
                        v-model="observaciones"
                        placeholder="Nota u observaciones del abono (opcional)..."
                        class="w-full py-1.5 px-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-xs focus:ring-2 focus:ring-emerald-500"
                    />
                </div>

                <!-- RESUMEN DE NUEVO SALDO -->
                <div class="p-3 bg-indigo-50/70 dark:bg-indigo-950/30 rounded-2xl border border-indigo-100 dark:border-indigo-900/50 flex justify-between items-center text-xs font-semibold">
                    <span class="text-indigo-950 dark:text-indigo-200">Nuevo Saldo Deudor Restante:</span>
                    <span class="font-mono font-black text-indigo-700 dark:text-indigo-300 text-sm">
                        ${{ nuevoSaldoProyectado.toFixed(2) }}
                    </span>
                </div>
            </div>

            <!-- BOTONES DE ACCIÓN -->
            <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-100 dark:border-gray-700">
                <button
                    type="button"
                    @click="emit('cerrar')"
                    class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl text-xs font-semibold transition"
                >
                    Cancelar (Esc)
                </button>
                <button
                    type="button"
                    @click="ProcesarAbono"
                    :disabled="!esValidoParaAbonar || procesando"
                    class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition shadow-sm disabled:opacity-50 flex items-center gap-2"
                >
                    <span v-if="procesando">Procesando Abono...</span>
                    <span v-else>Confirmar e Imprimir Recibo</span>
                </button>
            </div>

        </div>
    </div>
</template>
