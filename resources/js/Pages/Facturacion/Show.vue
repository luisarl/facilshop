<script setup>
import { ref, onMounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import QRCode from 'qrcode';

const props = defineProps({
    empresa: Object,
    venta: Object,
    tasa_ves: Number,
    total_ves: Number,
    qr_contenido: String,
    qr_datos: Object,
});

const qrDataUrl = ref('');

onMounted(async () => {
    try {
        qrDataUrl.value = await QRCode.toDataURL(props.qr_contenido, {
            width: 180,
            margin: 1,
            color: {
                dark: '#0f172a',
                light: '#ffffff',
            },
        });
    } catch (e) {
        console.error('Error generando QR:', e);
    }
});

const Imprimir = () => {
    window.print();
};
</script>

<template>
    <Head :title="`Comprobante ${venta.numero_comprobante}`" />

    <div class="min-h-screen bg-gray-100 dark:bg-gray-900 py-8 px-4 sm:px-6 print:p-0 print:bg-white">
        <div class="max-w-3xl mx-auto space-y-4">

            <!-- Botones de Acción (ocultos al imprimir) -->
            <div class="flex items-center justify-between print:hidden">
                <Link
                    :href="route('facturacion.index')"
                    class="inline-flex items-center gap-1.5 text-xs text-gray-500 hover:text-indigo-600 transition"
                >
                    ← Volver a Facturación
                </Link>
                <div class="flex gap-2">
                    <a
                        :href="route('facturacion.escpos', venta.id_venta)"
                        class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-lg shadow-sm transition"
                    >
                        Descargar ESC/POS
                    </a>
                    <button
                        @click="Imprimir"
                        class="px-4 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg shadow-sm transition flex items-center gap-1.5"
                    >
                        🖨️ Imprimir Factura
                    </button>
                </div>
            </div>

            <!-- Contenedor Principal de la Factura -->
            <div class="bg-white rounded-2xl shadow-xl border border-gray-200 p-8 sm:p-10 space-y-6 text-gray-800 print:shadow-none print:border-none print:p-0">

                <!-- Encabezado -->
                <div class="flex flex-col sm:flex-row justify-between items-start border-b border-gray-200 pb-6 gap-4">
                    <div>
                        <h1 class="text-2xl font-black text-indigo-600 tracking-tight">{{ empresa.nombre }}</h1>
                        <p class="text-xs text-gray-500 font-mono mt-0.5">RIF: {{ empresa.rif }}</p>
                        <p class="text-xs text-gray-500 max-w-sm mt-1">{{ empresa.direccion }}</p>
                        <p class="text-xs text-gray-500">Tel: {{ empresa.telefono }} | {{ empresa.email }}</p>
                    </div>
                    <div class="text-left sm:text-right">
                        <div class="inline-block px-4 py-1.5 bg-indigo-50 border border-indigo-200 text-indigo-800 font-mono font-bold text-sm rounded-lg">
                            {{ venta.tipo_comprobante }} N° {{ venta.numero_comprobante }}
                        </div>
                        <div class="text-xs text-gray-500 mt-2 space-y-0.5">
                            <div><strong>Fecha:</strong> {{ new Date(venta.created_at).toLocaleString() }}</div>
                            <div><strong>Caja / Turno:</strong> N° {{ venta.id_caja_turno }}</div>
                            <div><strong>Cajero:</strong> {{ venta.caja_turno?.usuario?.name || 'Administración' }}</div>
                            <div><strong>Estado:</strong> <span class="font-bold text-emerald-600">{{ venta.estado }}</span></div>
                        </div>
                    </div>
                </div>

                <!-- Datos del Receptor / Cliente -->
                <div class="p-4 bg-gray-50 rounded-xl border border-gray-200 grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                    <div>
                        <span class="text-gray-400 text-[10px] uppercase font-bold tracking-wider">DATOS DEL CLIENTE:</span>
                        <div class="text-sm font-bold text-gray-900 mt-0.5">{{ venta.cliente?.nombre || 'Cliente Mostrador' }}</div>
                        <div class="text-gray-600 font-mono">Cédula / RIF: {{ venta.cliente?.identificacion || 'V-00000000' }}</div>
                    </div>
                    <div class="sm:text-right">
                        <span class="text-gray-400 text-[10px] uppercase font-bold tracking-wider">INFORMACIÓN DE CONTACTO:</span>
                        <div class="text-gray-700 mt-0.5">{{ venta.cliente?.telefono || 'Sin teléfono' }}</div>
                        <div class="text-gray-600">{{ venta.cliente?.email || 'Sin correo' }}</div>
                    </div>
                </div>

                <!-- Tabla de Productos -->
                <div class="overflow-x-auto">
                    <table class="w-full text-xs text-left">
                        <thead class="border-b-2 border-gray-200 uppercase text-[10px] text-gray-500 font-bold tracking-wider">
                            <tr>
                                <th class="py-2.5">Código / Descripción</th>
                                <th class="py-2.5 text-center">Unidad</th>
                                <th class="py-2.5 text-center">Cantidad</th>
                                <th class="py-2.5 text-right">Precio Unitario</th>
                                <th class="py-2.5 text-right">Descuento</th>
                                <th class="py-2.5 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="d in venta.detalles" :key="d.id_venta_detalle">
                                <td class="py-2.5">
                                    <div class="font-semibold text-gray-900">{{ d.producto?.nombre }}</div>
                                    <div class="text-[10px] text-gray-400 font-mono">SKU: {{ d.producto?.sku }}</div>
                                </td>
                                <td class="py-2.5 text-center text-gray-500 uppercase">{{ d.producto?.unidad?.abreviatura || 'UND' }}</td>
                                <td class="py-2.5 text-center font-mono font-bold">{{ d.cantidad }}</td>
                                <td class="py-2.5 text-right font-mono">${{ Number(d.precio_unitario).toFixed(2) }}</td>
                                <td class="py-2.5 text-right font-mono text-emerald-600">
                                    {{ Number(d.descuento) > 0 ? `-$${Number(d.descuento).toFixed(2)}` : '-' }}
                                </td>
                                <td class="py-2.5 text-right font-mono font-bold">${{ Number(d.subtotal).toFixed(2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Totales y QR Verificador -->
                <div class="border-t-2 border-gray-200 pt-4 flex flex-col sm:flex-row justify-between items-center gap-6">
                    <div class="flex items-center gap-4">
                        <img
                            v-if="qrDataUrl"
                            :src="qrDataUrl"
                            alt="QR de Verificación"
                            class="w-28 h-28 p-1.5 border border-gray-300 rounded-xl bg-white shadow-xs"
                        />
                        <div class="text-[10px] text-gray-500 max-w-[200px] space-y-1">
                            <div class="font-bold text-gray-800 uppercase tracking-wider">Verificación Digital</div>
                            <p>Escanee para comprobar la validez de este comprobante en la base de datos de Fácil Shop.</p>
                            <p class="font-mono text-[9px] text-gray-400">HASH: {{ qr_datos.hash_seguridad.substring(0, 16) }}...</p>
                        </div>
                    </div>

                    <div class="w-full sm:w-72 space-y-1.5 text-xs text-right font-mono">
                        <div class="flex justify-between text-gray-600">
                            <span>Subtotal:</span>
                            <span>${{ Number(venta.subtotal).toFixed(2) }}</span>
                        </div>
                        <div v-if="Number(venta.descuento_total) > 0" class="flex justify-between text-emerald-600 font-semibold">
                            <span>Descuento Total:</span>
                            <span>-${{ Number(venta.descuento_total).toFixed(2) }}</span>
                        </div>
                        <div class="flex justify-between text-lg font-black text-gray-900 border-t-2 border-gray-200 pt-1.5">
                            <span>TOTAL USD:</span>
                            <span>${{ Number(venta.total).toFixed(2) }}</span>
                        </div>
                        <div class="flex justify-between text-xs text-gray-500">
                            <span>Tasa Referencial BCV:</span>
                            <span>Bs. {{ Number(tasa_ves).toFixed(2) }}</span>
                        </div>
                        <div class="flex justify-between text-sm font-bold text-indigo-700">
                            <span>TOTAL VES:</span>
                            <span>Bs. {{ Number(total_ves).toLocaleString('es-VE', { minimumFractionDigits: 2 }) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Formas de Pago Aplicadas -->
                <div class="border-t border-gray-200 pt-4">
                    <span class="text-[10px] uppercase font-bold text-gray-400 tracking-wider">FORMAS DE PAGO REGISTRADAS:</span>
                    <div class="mt-2 grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                        <div
                            v-for="p in venta.pagos"
                            :key="p.id_pago_venta"
                            class="p-2.5 bg-gray-50 rounded-lg border border-gray-100 flex justify-between items-center"
                        >
                            <div>
                                <span class="font-semibold text-gray-800">{{ p.metodo_pago?.nombre }}</span>
                                <span v-if="p.referencia" class="text-[10px] text-gray-500 font-mono block">Ref: {{ p.referencia }}</span>
                            </div>
                            <div class="text-right font-mono">
                                <span class="font-bold">${{ Number(p.monto_base).toFixed(2) }}</span>
                                <span v-if="p.moneda?.codigo !== 'USD'" class="text-[10px] text-gray-500 block">
                                    {{ p.moneda?.simbolo }}{{ Number(p.monto).toFixed(2) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Desglose de Financiamiento Cashea BNPL si aplica -->
                <div
                    v-if="venta.cashea_transaccion"
                    class="p-4 bg-amber-50 rounded-xl border border-amber-200 text-xs space-y-3"
                >
                    <div class="flex justify-between items-center font-bold text-amber-900">
                        <span class="flex items-center gap-1.5">💛 FINANCIAMIENTO CASHEA (COMPRA AHORA, PAGA DESPUÉS)</span>
                        <span class="font-mono text-xs">Referencia: {{ venta.cashea_transaccion.referencia_cashea }}</span>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-center">
                        <div class="p-2 bg-white rounded-lg border border-amber-100">
                            <span class="text-[9px] uppercase font-semibold text-amber-600 block">Inicial Tienda</span>
                            <span class="font-bold text-amber-900 font-mono">${{ Number(venta.cashea_transaccion.monto_inicial).toFixed(2) }}</span>
                        </div>
                        <div class="p-2 bg-white rounded-lg border border-amber-100">
                            <span class="text-[9px] uppercase font-semibold text-amber-600 block">Saldo Financiado</span>
                            <span class="font-bold text-amber-900 font-mono">${{ Number(venta.cashea_transaccion.monto_financiado).toFixed(2) }}</span>
                        </div>
                        <div class="p-2 bg-white rounded-lg border border-amber-100">
                            <span class="text-[9px] uppercase font-semibold text-amber-600 block">Cuotas en App</span>
                            <span class="font-bold text-amber-900 font-mono">{{ venta.cashea_transaccion.numero_cuotas }} quincenales</span>
                        </div>
                        <div class="p-2 bg-white rounded-lg border border-amber-100">
                            <span class="text-[9px] uppercase font-semibold text-amber-600 block">Monto por Cuota</span>
                            <span class="font-bold text-amber-900 font-mono">${{ Number(venta.cashea_transaccion.monto_cuota).toFixed(2) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Pie de Página -->
                <div class="border-t border-gray-100 pt-4 text-center text-[10px] text-gray-400 space-y-0.5">
                    <p>Documento de Control y Facturación Interna - Fácil Shop POS</p>
                    <p>Conserve este comprobante para reclamos o devoluciones.</p>
                </div>

            </div>

        </div>
    </div>
</template>

<style>
@media print {
    body {
        background-color: white !important;
    }
}
</style>
