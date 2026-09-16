<script setup>
const props = defineProps({
    abono: Object,
    tasaVes: Number,
});

const emit = defineEmits(['cerrar']);

const Imprimir = () =>
{
    window.print();
};
</script>

<template>
    <div class="fixed inset-0 z-50 overflow-y-auto bg-black/70 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl max-w-sm w-full p-6 shadow-2xl border border-gray-150 text-gray-900 font-mono text-xs space-y-4 printable-receipt">

            <!-- ENCABEZADO TICKET TÉRMICO -->
            <div class="text-center space-y-1">
                <div class="text-base font-black tracking-wider">FÁCIL SHOP C.A.</div>
                <div class="text-[11px] text-gray-600">RIF: J-40912345-0</div>
                <div class="text-[10px] text-gray-500">San Cristóbal, Edo. Táchira</div>
                <div class="text-[10px] text-gray-500">Tel: (0276) 555-1234</div>
                <div class="border-b border-dashed border-gray-300 my-2"></div>
                <div class="font-black text-sm uppercase text-indigo-900">
                    RECIBO DE ABONO A CRÉDITO
                </div>
                <div class="font-bold text-xs">{{ abono.comprobante_abono }}</div>
                <div class="text-[10px] text-gray-500">
                    {{ new Date(abono.fecha || Date.now()).toLocaleDateString() }} {{ new Date(abono.fecha || Date.now()).toLocaleTimeString() }}
                </div>
                <div class="text-[10px] text-gray-700 font-bold pt-1">
                    Cliente: {{ abono.cliente?.nombre }}
                </div>
                <div class="text-[10px] text-gray-500">
                    Doc: {{ abono.cliente?.identificacion }}
                </div>
            </div>

            <div class="border-b border-dashed border-gray-300"></div>

            <!-- DETALLE DEL PAGO RECIBIDO -->
            <div class="space-y-2">
                <div class="flex justify-between items-center text-xs">
                    <span class="text-gray-600">Forma de Pago:</span>
                    <span class="font-bold uppercase">{{ abono.metodo_pago?.nombre || 'Efectivo' }}</span>
                </div>

                <div v-if="abono.monto_pagado_moneda && !abono.moneda?.es_principal" class="flex justify-between items-center text-xs">
                    <span class="text-gray-600">Monto Percibido:</span>
                    <span class="font-black">
                        {{ abono.moneda?.codigo }} {{ Number(abono.monto_pagado_moneda).toFixed(2) }}
                    </span>
                </div>

                <div class="flex justify-between items-center text-xs font-black">
                    <span>Monto Abonado (USD):</span>
                    <span class="text-sm font-mono text-emerald-700">
                        ${{ Number(abono.monto_abonado).toFixed(2) }}
                    </span>
                </div>

                <div v-if="tasaVes && abono.moneda?.es_principal" class="flex justify-between items-center text-[10px] text-gray-500">
                    <span>Equivalente BCV:</span>
                    <span>Bs. {{ (Number(abono.monto_abonado) * tasaVes).toFixed(2) }}</span>
                </div>

                <div v-if="abono.referencia" class="flex justify-between items-center text-[10px] text-gray-500">
                    <span>Referencia:</span>
                    <span class="font-mono">{{ abono.referencia }}</span>
                </div>
            </div>

            <div class="border-b border-dashed border-gray-300"></div>

            <!-- ESTADO DEL CRÉDITO -->
            <div class="space-y-1 text-right text-xs">
                <div class="flex justify-between text-gray-600">
                    <span>Saldo Anterior:</span>
                    <span>${{ Number(abono.saldo_anterior).toFixed(2) }}</span>
                </div>
                <div class="flex justify-between text-emerald-700 font-bold">
                    <span>Abono Aplicado:</span>
                    <span>-${{ Number(abono.monto_abonado).toFixed(2) }}</span>
                </div>
                <div class="flex justify-between text-sm font-black pt-1 border-t border-gray-200">
                    <span>NUEVO SALDO:</span>
                    <span :class="Number(abono.nuevo_saldo) > 0 ? 'text-rose-600' : 'text-emerald-600'">
                        ${{ Number(abono.nuevo_saldo).toFixed(2) }}
                    </span>
                </div>
                <div v-if="Number(abono.nuevo_saldo) === 0" class="text-center text-[10px] font-bold text-emerald-700 pt-1">
                    ¡Cuenta saldada en su totalidad!
                </div>
            </div>

            <div class="border-b border-dashed border-gray-300"></div>

            <!-- PIE DE TICKET -->
            <div class="text-center text-[10px] text-gray-500 space-y-0.5 pt-1">
                <div>Comprobante de abono recibido</div>
                <div>Sin validez como factura fiscal</div>
                <div class="text-[8px] text-gray-400 mt-2">facilshop.com.ve</div>
            </div>

            <!-- BOTONES NO IMPRIMIBLES -->
            <div class="flex items-center justify-between gap-2 pt-2 border-t border-gray-200 no-print">
                <button
                    type="button"
                    @click="emit('cerrar')"
                    class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-xs font-semibold"
                >
                    Finalizar
                </button>
                <button
                    type="button"
                    @click="Imprimir"
                    class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold flex items-center gap-1.5"
                >
                    🖨️ Imprimir
                </button>
            </div>

        </div>
    </div>
</template>

<style scoped>
@media print {
    .no-print {
        display: none !important;
    }
    body {
        background: white !important;
    }
    .printable-receipt {
        max-width: 80mm !important;
        box-shadow: none !important;
        border: none !important;
        padding: 0 !important;
    }
}
</style>
