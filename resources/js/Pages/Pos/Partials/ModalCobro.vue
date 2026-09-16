<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { usePosStore } from '@/Stores/usePosStore';

const props = defineProps({
    metodosPago: Array,
    monedas: Array,
    tasaVes: Number,
    cliente: Object,
});

const emit = defineEmits(['cerrar', 'ventaCompletada']);
const posStore = usePosStore();

const pagos = ref([]);
const procesando = ref(false);
const errorMensaje = ref(null);

// Datos para Cashea si aplica
const datosCashea = ref({
    activo: false,
    cedula_cliente: props.cliente?.identificacion || '',
    telefono_cliente: props.cliente?.telefono || '',
    referencia_cashea: '',
    codigo_autorizacion: '',
    monto_total: posStore.totalUsd,
    porcentaje_inicial: 40.0,
    monto_inicial: 0.0,
    monto_financiado: 0.0,
    monto_cuota: 0.0,
    cuotas: [],
});

// Inicializar con un pago por defecto (Efectivo USD por el total)
onMounted(() =>
{
    const MetodoEfectivoUsd = props.metodosPago.find(m => m.codigo === 'EFECTIVO_USD') || props.metodosPago[0];
    if (MetodoEfectivoUsd)
    {
        const MonedaEncontrada = props.monedas?.find(m => m.id_moneda === MetodoEfectivoUsd.id_moneda) || MetodoEfectivoUsd.moneda;
        const Tasa = Number(MonedaEncontrada?.tasa_cambio) || (MonedaEncontrada?.codigo === 'VES' ? Number(props.tasaVes) : 1.0) || 1.0;
        const EsPrincipal = Boolean(MonedaEncontrada?.es_principal);

        const MontoInicial = EsPrincipal
            ? Number(posStore.totalUsd)
            : Number((posStore.totalUsd * Tasa).toFixed(2));
        const MontoBaseInicial = EsPrincipal
            ? Number(posStore.totalUsd)
            : (Tasa > 0 ? Number((MontoInicial / Tasa).toFixed(2)) : Number(posStore.totalUsd));

        pagos.value.push({
            id_metodo_pago: MetodoEfectivoUsd.id_metodo_pago,
            metodo: MetodoEfectivoUsd,
            id_moneda: MetodoEfectivoUsd.id_moneda,
            moneda: MonedaEncontrada || MetodoEfectivoUsd.moneda,
            monto: MontoInicial,
            tasa_cambio: Tasa,
            monto_base: MontoBaseInicial,
            referencia: '',
        });
    }
    RecalcularCashea();
});

const AgregarMetodoPago = () =>
{
    const restante = saldoRestanteUsd.value > 0 ? saldoRestanteUsd.value : 0;
    const MetodoDefault = props.metodosPago[0];
    if (!MetodoDefault)
    {
        return;
    }

    const MonedaEncontrada = props.monedas?.find(m => m.id_moneda === MetodoDefault.id_moneda) || MetodoDefault.moneda;
    const Tasa = Number(MonedaEncontrada?.tasa_cambio) || (MonedaEncontrada?.codigo === 'VES' ? Number(props.tasaVes) : 1.0) || 1.0;
    const EsPrincipal = Boolean(MonedaEncontrada?.es_principal);

    const MontoOriginal = EsPrincipal
        ? Number(restante.toFixed(2))
        : Number((restante * Tasa).toFixed(2));
    const MontoBase = EsPrincipal
        ? Number(restante.toFixed(2))
        : (Tasa > 0 ? Number((MontoOriginal / Tasa).toFixed(2)) : restante);

    pagos.value.push({
        id_metodo_pago: MetodoDefault.id_metodo_pago,
        metodo: MetodoDefault,
        id_moneda: MetodoDefault.id_moneda,
        moneda: MonedaEncontrada || MetodoDefault.moneda,
        monto: MontoOriginal,
        tasa_cambio: Tasa,
        monto_base: MontoBase,
        referencia: '',
    });
};

const QuitarPago = (index) =>
{
    pagos.value.splice(index, 1);
    VerificarSiUsaCashea();
};

const CambiarMetodo = (pago, IdMetodo) =>
{
    const NuevoMetodo = props.metodosPago.find(m => m.id_metodo_pago === IdMetodo);
    if (!NuevoMetodo)
    {
        return;
    }

    const IdMonedaAnterior = pago.id_moneda;
    const MonedaEncontrada = props.monedas?.find(m => m.id_moneda === NuevoMetodo.id_moneda) || NuevoMetodo.moneda;
    const NuevaTasa = Number(MonedaEncontrada?.tasa_cambio) || (MonedaEncontrada?.codigo === 'VES' ? Number(props.tasaVes) : 1.0) || 1.0;
    const MonedaCambio = IdMonedaAnterior !== NuevoMetodo.id_moneda;

    pago.metodo = NuevoMetodo;
    pago.id_metodo_pago = NuevoMetodo.id_metodo_pago;
    pago.id_moneda = NuevoMetodo.id_moneda;
    pago.moneda = MonedaEncontrada || NuevoMetodo.moneda;
    pago.tasa_cambio = NuevaTasa;

    if (MonedaCambio)
    {
        let MontoBase = Number(pago.monto_base);
        if (!MontoBase || MontoBase <= 0)
        {
            const SaldoPendiente = saldoRestanteUsd.value > 0 ? saldoRestanteUsd.value : 0;
            MontoBase = SaldoPendiente > 0 ? SaldoPendiente : Number(posStore.totalUsd);
        }

        if (pago.moneda?.es_principal)
        {
            pago.monto = Number(MontoBase.toFixed(2));
            pago.monto_base = Number(MontoBase.toFixed(2));
        }
        else
        {
            pago.monto = Number((MontoBase * NuevaTasa).toFixed(2));
            pago.monto_base = NuevaTasa > 0 ? Number((pago.monto / NuevaTasa).toFixed(2)) : MontoBase;
        }
    }

    VerificarSiUsaCashea();
};

const ActualizarMontoPago = (pago) =>
{
    const monto = Number(pago.monto) || 0;
    const tasa = Number(pago.tasa_cambio) || 1.0;

    if (pago.moneda?.es_principal)
    {
        pago.monto_base = Number(monto.toFixed(2));
    }
    else
    {
        // Moneda secundaria (ej: VES): monto / tasa = USD
        pago.monto_base = tasa > 0 ? Number((monto / tasa).toFixed(2)) : monto;
    }
};

const VerificarSiUsaCashea = () =>
{
    const TieneCashea = pagos.value.some(p => p.metodo?.tipo === 'FINANCIAMIENTO' || p.metodo?.codigo?.includes('CASHEA'));
    datosCashea.value.activo = TieneCashea;
    if (TieneCashea)
    {
        RecalcularCashea();
    }
};

const RecalcularCashea = () =>
{
    const total = posStore.totalUsd;
    const pct = Number(datosCashea.value.porcentaje_inicial) || 40.0;
    const inicial = Number(((total * pct) / 100).toFixed(2));
    const financiado = Number((total - inicial).toFixed(2));
    const cuota = Number((financiado / 3).toFixed(2));

    datosCashea.value.monto_total = total;
    datosCashea.value.monto_inicial = inicial;
    datosCashea.value.monto_financiado = financiado;
    datosCashea.value.monto_cuota = cuota;
};

// Cálculos de Totales y Vueltos
const totalPagadoUsd = computed(() => {
    return Number(pagos.value.reduce((total, p) => total + (Number(p.monto_base) || 0), 0).toFixed(2));
});

const saldoRestanteUsd = computed(() => {
    const restante = posStore.totalUsd - totalPagadoUsd.value;
    return Number(restante.toFixed(2));
});

const cambioVueltoUsd = computed(() => {
    const cambio = totalPagadoUsd.value - posStore.totalUsd;
    return cambio > 0 ? Number(cambio.toFixed(2)) : 0.00;
});

const cambioVueltoVes = computed(() => {
    return Number((cambioVueltoUsd.value * props.tasaVes).toFixed(2));
});

const esValidoParaCobrar = computed(() => {
    if (posStore.items.length === 0) return false;
    if (pagos.value.length === 0) return false;
    if (saldoRestanteUsd.value > 0.01) return false;

    // Si tiene Cashea, requiere cédula y referencia
    if (datosCashea.value.activo) {
        if (!datosCashea.value.cedula_cliente || !datosCashea.value.referencia_cashea) {
            return false;
        }
    }

    return true;
});

const ProcesarCobro = async () => {
    if (!esValidoParaCobrar.value) return;

    procesando.value = true;
    errorMensaje.value = null;

    const payload = {
        id_cliente: posStore.clienteSeleccionado?.id_cliente || 1,
        tipo_comprobante: posStore.tipoComprobante || 'TICKET',
        detalles: posStore.items.map(i => ({
            id_producto: i.id_producto,
            id_unidad: i.id_unidad,
            cantidad: i.cantidad,
            precio_unitario: i.precio_unitario,
            descuento: i.descuento,
        })),
        pagos: pagos.value.map(p => ({
            id_metodo_pago: p.id_metodo_pago,
            id_moneda: p.id_moneda,
            monto: Number(p.monto),
            tasa_cambio: Number(p.tasa_cambio),
            monto_base: Number(p.monto_base),
            referencia: p.referencia || null,
        })),
        cashea: datosCashea.value.activo ? {
            cedula_cliente: datosCashea.value.cedula_cliente,
            telefono_cliente: datosCashea.value.telefono_cliente,
            referencia_cashea: datosCashea.value.referencia_cashea,
            codigo_autorizacion: datosCashea.value.codigo_autorizacion,
            monto_total: datosCashea.value.monto_total,
            porcentaje_inicial: datosCashea.value.porcentaje_inicial,
            monto_inicial: datosCashea.value.monto_inicial,
            monto_financiado: datosCashea.value.monto_financiado,
            numero_cuotas: 3,
            monto_cuota: datosCashea.value.monto_cuota,
        } : null,
    };

    try {
        const respuesta = await axios.post(route('pos.checkout'), payload);
        posStore.limpiarCarrito();
        emit('ventaCompletada', respuesta.data.venta);
    } catch (error) {
        console.error('Error al procesar checkout:', error);
        errorMensaje.value = error.response?.data?.error || 'Error al procesar la venta. Verifique los datos.';
    } finally {
        procesando.value = false;
    }
};
</script>

<template>
    <div class="fixed inset-0 z-50 overflow-y-auto bg-black/70 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-800 rounded-3xl max-w-2xl w-full p-6 sm:p-8 shadow-2xl border border-gray-100 dark:border-gray-700 space-y-6">

            <!-- ENCABEZADO Y TOTALES -->
            <div class="flex justify-between items-start pb-4 border-b border-gray-100 dark:border-gray-700">
                <div>
                    <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Módulo de Cobro Multimoneda</span>
                    <h3 class="text-2xl font-black text-gray-900 dark:text-gray-100">
                        Total a Cobrar: <span class="text-indigo-600 dark:text-indigo-400 font-mono">${{ posStore.totalUsd.toFixed(2) }}</span>
                    </h3>
                    <div class="text-xs text-gray-500 font-mono mt-0.5">
                        Equivalente: <span class="font-bold text-gray-800 dark:text-gray-200">Bs. {{ posStore.totalVes.toFixed(2) }}</span> (Tasa BCV: {{ tasaVes.toFixed(2) }})
                    </div>
                </div>

                <button @click="emit('cerrar')" class="text-gray-400 hover:text-gray-500 text-2xl font-bold p-1">
                    ✕
                </button>
            </div>

            <!-- MENSAJE DE ERROR -->
            <div v-if="errorMensaje" class="p-3 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 rounded-xl text-xs text-rose-700 dark:text-rose-300 font-semibold flex items-center gap-2">
                <span>⚠️</span>
                <span>{{ errorMensaje }}</span>
            </div>

            <!-- DISTRIBUCIÓN DE MÉTODOS DE PAGO (MULTIPAGO) -->
            <div class="space-y-3">
                <div class="flex justify-between items-center">
                    <label class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                        Desglose de Formas de Pago
                    </label>
                    <button
                        type="button"
                        @click="AgregarMetodoPago"
                        class="text-xs text-indigo-600 dark:text-indigo-400 font-bold hover:underline flex items-center gap-1"
                    >
                        + Agregar otra forma de pago
                    </button>
                </div>

                <div class="space-y-2.5 max-h-56 overflow-y-auto pr-1">
                    <div
                        v-for="(pago, index) in pagos"
                        :key="index"
                        class="p-3 bg-gray-50 dark:bg-gray-750 rounded-2xl border border-gray-200 dark:border-gray-700 flex flex-col sm:flex-row items-center gap-3"
                    >
                        <!-- Selector de Método -->
                        <div class="w-full sm:w-1/3">
                            <select
                                :value="pago.id_metodo_pago"
                                @change="CambiarMetodo(pago, Number($event.target.value))"
                                class="w-full py-1.5 px-2.5 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-xs font-semibold"
                            >
                                <option v-for="m in metodosPago" :key="m.id_metodo_pago" :value="m.id_metodo_pago">
                                    {{ m.nombre }} ({{ m.moneda?.codigo }})
                                </option>
                            </select>
                        </div>

                        <!-- Monto en Moneda del Método -->
                        <div class="w-full sm:w-1/3 relative">
                            <input
                                type="number"
                                step="0.01"
                                min="0.01"
                                v-model="pago.monto"
                                @input="ActualizarMontoPago(pago)"
                                placeholder="Monto"
                                class="w-full py-1.5 pl-3 pr-8 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-xs font-mono font-bold"
                            />
                            <span class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-[10px] text-gray-400 font-bold">
                                {{ pago.moneda?.codigo }}
                            </span>
                        </div>

                        <!-- Equivalente USD y Referencia -->
                        <div class="w-full sm:w-1/3 flex items-center gap-2">
                            <div class="w-20 text-right font-mono text-xs font-bold text-gray-700 dark:text-gray-300 shrink-0">
                                ≈ ${{ Number(pago.monto_base).toFixed(2) }}
                            </div>
                            <input
                                v-if="pago.metodo?.requiere_referencia"
                                type="text"
                                v-model="pago.referencia"
                                placeholder="Ref / Comprobante"
                                class="w-full py-1.5 px-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-[11px]"
                            />
                            <button
                                v-if="pagos.length > 1"
                                type="button"
                                @click="QuitarPago(index)"
                                class="text-rose-500 hover:text-rose-700 font-bold text-sm p-1"
                            >
                                ✕
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SIMULADOR CASHEA BNPL (SE ACTIVA AUTOMÁTICAMENTE SI SE SELECCIONA CASHEA) -->
            <div
                v-if="datosCashea.activo"
                class="p-4 bg-amber-50/80 dark:bg-amber-950/30 rounded-2xl border border-amber-200 dark:border-amber-800 space-y-3"
            >
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="px-2 py-0.5 bg-amber-500 text-white rounded font-black text-xs">CASHEA</span>
                        <h4 class="text-xs font-bold text-amber-950 dark:text-amber-200">
                            Simulación de Compra a Cuotas (Buy Now Pay Later)
                        </h4>
                    </div>
                    <span class="text-xs font-mono font-bold text-amber-700 dark:text-amber-300">
                        3 Cuotas sin interés
                    </span>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                    <div class="bg-white/80 dark:bg-gray-800/80 p-2.5 rounded-xl border border-amber-100 dark:border-amber-900/40">
                        <span class="text-gray-400 block text-[10px] uppercase font-bold">Pago Inicial (40%)</span>
                        <span class="font-mono font-bold text-gray-900 dark:text-gray-100 text-sm mt-0.5 block">
                            ${{ datosCashea.monto_inicial.toFixed(2) }}
                        </span>
                    </div>

                    <div class="bg-white/80 dark:bg-gray-800/80 p-2.5 rounded-xl border border-amber-100 dark:border-amber-900/40">
                        <span class="text-gray-400 block text-[10px] uppercase font-bold">Monto Financiado</span>
                        <span class="font-mono font-bold text-gray-900 dark:text-gray-100 text-sm mt-0.5 block">
                            ${{ datosCashea.monto_financiado.toFixed(2) }}
                        </span>
                    </div>

                    <div class="bg-white/80 dark:bg-gray-800/80 p-2.5 rounded-xl border border-amber-100 dark:border-amber-900/40">
                        <span class="text-gray-400 block text-[10px] uppercase font-bold">3 Cuotas de:</span>
                        <span class="font-mono font-bold text-indigo-600 dark:text-indigo-400 text-sm mt-0.5 block">
                            ${{ datosCashea.monto_cuota.toFixed(2) }}
                        </span>
                    </div>

                    <div class="bg-white/80 dark:bg-gray-800/80 p-2.5 rounded-xl border border-amber-100 dark:border-amber-900/40">
                        <span class="text-gray-400 block text-[10px] uppercase font-bold">Frecuencia</span>
                        <span class="font-bold text-gray-800 dark:text-gray-200 text-xs mt-0.5 block">
                            Cada 14 días
                        </span>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                    <div>
                        <label class="block text-[10px] font-bold text-gray-700 dark:text-gray-300 uppercase mb-1">
                            Cédula del Cliente en Cashea *
                        </label>
                        <input
                            type="text"
                            v-model="datosCashea.cedula_cliente"
                            placeholder="V-12345678"
                            required
                            class="w-full py-1.5 px-3 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-xs font-mono"
                        />
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-700 dark:text-gray-300 uppercase mb-1">
                            Código / Referencia Cashea *
                        </label>
                        <input
                            type="text"
                            v-model="datosCashea.referencia_cashea"
                            placeholder="Ej: CSH-884920"
                            required
                            class="w-full py-1.5 px-3 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-xs font-mono"
                        />
                    </div>
                </div>
            </div>

            <!-- RESUMEN DE CUADRE Y VUELTO -->
            <div class="p-4 bg-gray-50 dark:bg-gray-750 rounded-2xl border border-gray-200 dark:border-gray-700 space-y-2 text-xs">
                <div class="flex justify-between text-gray-600 dark:text-gray-300 font-medium">
                    <span>Total Pagado:</span>
                    <span class="font-mono font-bold">${{ totalPagadoUsd.toFixed(2) }}</span>
                </div>

                <div v-if="saldoRestanteUsd > 0.00" class="flex justify-between text-rose-600 dark:text-rose-400 font-bold">
                    <span>Saldo Pendiente:</span>
                    <span class="font-mono">${{ saldoRestanteUsd.toFixed(2) }} (Bs. {{ (saldoRestanteUsd * tasaVes).toFixed(2) }})</span>
                </div>

                <div v-if="cambioVueltoUsd > 0.00" class="flex justify-between text-emerald-600 dark:text-emerald-400 font-bold border-t border-gray-200 dark:border-gray-600 pt-2 text-sm">
                    <span>Cambio / Vuelto al Cliente:</span>
                    <span class="font-mono">${{ cambioVueltoUsd.toFixed(2) }} / Bs. {{ cambioVueltoVes.toFixed(2) }}</span>
                </div>
            </div>

            <!-- BOTONES DE ACCIÓN -->
            <div class="flex items-center justify-end gap-3 pt-2">
                <button
                    type="button"
                    @click="emit('cerrar')"
                    class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl text-sm font-semibold transition"
                >
                    Cancelar (Esc)
                </button>
                <button
                    type="button"
                    @click="ProcesarCobro"
                    :disabled="!esValidoParaCobrar || procesando"
                    class="px-7 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-sm font-bold transition shadow-sm disabled:opacity-50 flex items-center gap-2"
                >
                    <span v-if="procesando">Procesando Venta...</span>
                    <span v-else>Confirmar y Facturar (F4)</span>
                </button>
            </div>

        </div>
    </div>
</template>
