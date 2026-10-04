<script setup>
import { FormatearFechaHora } from '@/Utils/FechaHora';
import { FormatearNumero, FormatearCantidad } from '@/Utils/FormatoNumero';

const props = defineProps({
    venta: Object,
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
                <div class="font-bold text-sm">{{ venta.tipo_comprobante }} #{{ venta.numero_comprobante }}</div>
                <div class="text-[10px] text-gray-500">
                    {{ FormatearFechaHora(venta.created_at) }}
                </div>
                <div class="text-[10px] text-gray-500">
                    Cajero: {{ venta.caja_turno?.usuario?.nombre || 'Caja 1' }}
                </div>
                <div class="text-[10px] text-gray-700 font-bold">
                    Cliente: {{ venta.cliente?.nombre }} ({{ venta.cliente?.identificacion }})
                </div>
            </div>

            <div class="border-b border-dashed border-gray-300"></div>

            <!-- TABLA DE ARTÍCULOS -->
            <div class="space-y-1.5">
                <div
                    v-for="det in venta.detalles"
                    :key="det.id_venta_detalle"
                    class="flex justify-between items-start"
                >
                    <div class="max-w-[180px]">
                        <div class="font-bold truncate">{{ det.producto?.nombre }}</div>
                        <div class="text-[10px] text-gray-500">
                            {{ FormatearCantidad(det.cantidad) }} x ${{ FormatearNumero(det.precio_unitario) }}
                        </div>
                    </div>
                    <div class="font-bold text-right">
                        ${{ FormatearNumero(det.subtotal) }}
                    </div>
                </div>
            </div>

            <div class="border-b border-dashed border-gray-300"></div>

            <!-- TOTALES -->
            <div class="space-y-1 text-right">
                <div v-if="Number(venta.descuento_total) > 0" class="flex justify-between text-gray-500">
                    <span>Descuento:</span>
                    <span>-${{ FormatearNumero(venta.descuento_total) }}</span>
                </div>
                <div class="flex justify-between text-sm font-black pt-1">
                    <span>TOTAL USD:</span>
                    <span>${{ FormatearNumero(venta.total) }}</span>
                </div>
                <div class="flex justify-between text-xs font-bold text-gray-700">
                    <span>TOTAL BS (Tasa {{ FormatearNumero(venta.tasa_cambio) }}):</span>
                    <span>Bs. {{ FormatearNumero(Number(venta.total) * Number(venta.tasa_cambio)) }}</span>
                </div>
            </div>

            <div class="border-b border-dashed border-gray-300"></div>

            <!-- MÉTODOS DE PAGO -->
            <div class="space-y-1">
                <div class="text-[10px] uppercase font-bold text-gray-500">Forma(s) de Pago:</div>
                <div
                    v-for="pago in venta.pagos"
                    :key="pago.id_pago_venta"
                    class="flex justify-between text-[11px]"
                >
                    <span>{{ pago.metodo_pago?.nombre }}:</span>
                    <span class="font-bold">
                        {{ pago.moneda?.simbolo || '$' }} {{ FormatearNumero(pago.monto) }}
                        <span v-if="!pago.moneda?.es_principal" class="text-[9px] text-gray-500">
                            (≈${{ FormatearNumero(pago.monto_base) }})
                        </span>
                    </span>
                </div>
            </div>

            <!-- SECCIÓN CASHEA SI APLICA -->
            <div
                v-if="venta.cashea_transaccion"
                class="p-2.5 bg-amber-50 border border-amber-300 rounded-xl space-y-1 text-[10px]"
            >
                <div class="font-black text-amber-900 text-center uppercase">
                    Financiamiento Cashea BNPL
                </div>
                <div class="flex justify-between">
                    <span>Ref. Cashea:</span>
                    <span class="font-bold">{{ venta.cashea_transaccion.referencia_cashea }}</span>
                </div>
                <div class="flex justify-between">
                    <span>Inicial Cobrada ({{ FormatearNumero(venta.cashea_transaccion.porcentaje_inicial, 0) }}%):</span>
                    <span class="font-bold">${{ FormatearNumero(venta.cashea_transaccion.monto_inicial) }}</span>
                </div>
                <div class="flex justify-between">
                    <span>Monto Financiado:</span>
                    <span class="font-bold">${{ FormatearNumero(venta.cashea_transaccion.monto_financiado) }}</span>
                </div>
                <div class="flex justify-between text-indigo-700 font-bold border-t border-amber-200 pt-1">
                    <span>3 Cuotas C/14 días:</span>
                    <span>${{ FormatearNumero(venta.cashea_transaccion.monto_cuota) }} c/u</span>
                </div>
            </div>

            <!-- PIE DE TICKET -->
            <div class="text-center text-[10px] text-gray-500 space-y-0.5 pt-2">
                <div>¡Gracias por su compra en Fácil Shop!</div>
                <div>Comprobante sin validez fiscal</div>
                <div class="text-[8px] text-gray-400 mt-2">facilshop.com.ve</div>
            </div>

            <!-- BOTONES NO IMPRIMIBLES -->
            <div class="flex items-center justify-between gap-2 pt-2 border-t border-gray-200 no-print">
                <button
                    type="button"
                    @click="emit('cerrar')"
                    class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-xs font-semibold"
                >
                    Nueva Venta
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
        width: 80mm !important;
        margin: 0 !important;
        padding: 0 !important;
        border: none !important;
        box-shadow: none !important;
    }
}
</style>
