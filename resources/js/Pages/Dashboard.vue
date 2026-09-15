<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    kpis: Object,
    productos_stock_bajo: Array,
    ventas_por_metodo: Array,
    top_productos: Array,
    ultimas_ventas: Array,
    ultimas_auditorias: Array,
    turno_activo: Object,
});

const tasaVes = computed(() => Number(props.kpis?.tasa_ves || 1.0));

const BadgeAccion = (acc) => {
    switch (acc) {
        case 'CREAR':
            return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300';
        case 'ACTUALIZAR':
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300';
        case 'ELIMINAR':
            return 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300';
        case 'ANULAR':
            return 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300';
        default:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
    }
};
</script>

<template>
    <Head title="Panel de Control y Analítica" />

    <AuthenticatedLayout>
        <div class="py-6 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto space-y-6">

            <!-- Cabecera de Bienvenida y Acceso Directo al POS -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-linear-to-r from-indigo-900 via-indigo-800 to-slate-900 rounded-2xl p-6 text-white shadow-xl">
                <div class="space-y-1">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-indigo-500/30 text-indigo-200 border border-indigo-400/20">
                        ⚡ Fácil Shop v2.0 - Plataforma Comercial Integral
                    </span>
                    <h1 class="text-2xl sm:text-3xl font-black tracking-tight">
                        Panel de Control Ejecutivo
                    </h1>
                    <p class="text-xs sm:text-sm text-indigo-200/90 max-w-xl">
                        Monitoreo en tiempo real de ventas multimoneda, financiamiento Cashea, inventario crítico y auditoría forense.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <div class="bg-white/10 backdrop-blur-xs px-3 py-2 rounded-xl border border-white/10 text-right">
                        <span class="text-[10px] text-indigo-200 uppercase font-semibold block">Tasa Oficial BCV</span>
                        <span class="text-base font-black font-mono">Bs. {{ tasaVes.toFixed(2) }}</span>
                    </div>

                    <Link
                        :href="route('pos.index')"
                        class="px-5 py-3 bg-emerald-500 hover:bg-emerald-600 text-white text-sm font-black rounded-xl shadow-lg shadow-emerald-500/20 flex items-center gap-2 transition transform hover:-translate-y-0.5"
                    >
                        <span>🛒</span> ABRIR POS (F2)
                    </Link>
                </div>
            </div>

            <!-- 4 Tarjetas KPI Principales -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Ventas de Hoy -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 border border-gray-200/80 dark:border-gray-700 shadow-sm relative overflow-hidden">
                    <div class="flex justify-between items-start">
                        <div>
                            <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Ventas de Hoy</span>
                            <div class="text-2xl font-black text-gray-900 dark:text-white mt-1">
                                ${{ Number(kpis?.ventas_hoy_usd || 0).toFixed(2) }}
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 font-mono">
                                ≈ Bs. {{ Number(kpis?.ventas_hoy_ves || 0).toLocaleString('es-VE', { minimumFractionDigits: 2 }) }}
                            </div>
                        </div>
                        <span class="p-3 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 rounded-xl text-lg">
                            💰
                        </span>
                    </div>
                    <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-700/60 flex items-center justify-between text-xs text-gray-500">
                        <span>{{ kpis?.conteo_ventas_hoy || 0 }} transacciones</span>
                        <Link :href="route('facturacion.index')" class="text-indigo-600 dark:text-indigo-400 font-semibold hover:underline">Ver ventas →</Link>
                    </div>
                </div>

                <!-- Ventas del Mes -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 border border-gray-200/80 dark:border-gray-700 shadow-sm relative overflow-hidden">
                    <div class="flex justify-between items-start">
                        <div>
                            <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Ventas del Mes</span>
                            <div class="text-2xl font-black text-gray-900 dark:text-white mt-1">
                                ${{ Number(kpis?.ventas_mes_usd || 0).toFixed(2) }}
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 font-mono">
                                ≈ Bs. {{ Number(kpis?.ventas_mes_ves || 0).toLocaleString('es-VE', { minimumFractionDigits: 2 }) }}
                            </div>
                        </div>
                        <span class="p-3 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 rounded-xl text-lg">
                            📈
                        </span>
                    </div>
                    <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-700/60 flex items-center justify-between text-xs text-gray-500">
                        <span>Ticket Prom: ${{ Number(kpis?.ticket_promedio_usd || 0).toFixed(2) }}</span>
                        <span class="font-bold text-gray-700 dark:text-gray-300">{{ kpis?.conteo_ventas_mes || 0 }} ventas</span>
                    </div>
                </div>

                <!-- Cuentas por Cobrar (Crédito) -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 border border-gray-200/80 dark:border-gray-700 shadow-sm relative overflow-hidden">
                    <div class="flex justify-between items-start">
                        <div>
                            <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Cartera por Cobrar</span>
                            <div class="text-2xl font-black text-red-600 dark:text-red-400 mt-1 font-mono">
                                ${{ Number(kpis?.total_cartera_usd || 0).toFixed(2) }}
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 font-mono">
                                ≈ Bs. {{ Number(kpis?.total_cartera_ves || 0).toLocaleString('es-VE', { minimumFractionDigits: 2 }) }}
                            </div>
                        </div>
                        <span class="p-3 bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400 rounded-xl text-lg">
                            💳
                        </span>
                    </div>
                    <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-700/60 flex items-center justify-between text-xs text-gray-500">
                        <span>{{ kpis?.clientes_con_deuda || 0 }} deudores activos</span>
                        <Link :href="route('clientes.index')" class="text-red-600 dark:text-red-400 font-semibold hover:underline">Cobrar →</Link>
                    </div>
                </div>

                <!-- Stock Crítico -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 border border-gray-200/80 dark:border-gray-700 shadow-sm relative overflow-hidden">
                    <div class="flex justify-between items-start">
                        <div>
                            <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Stock Bajo Mínimo</span>
                            <div class="text-2xl font-black text-amber-600 dark:text-amber-400 mt-1">
                                {{ kpis?.conteo_stock_bajo || 0 }}
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                Productos requieren reposición
                            </div>
                        </div>
                        <span class="p-3 bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 rounded-xl text-lg">
                            ⚠️
                        </span>
                    </div>
                    <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-700/60 flex items-center justify-between text-xs text-gray-500">
                        <span>Alerta automática</span>
                        <Link :href="route('inventario.productos.index')" class="text-amber-600 dark:text-amber-400 font-semibold hover:underline">Ver inventario →</Link>
                    </div>
                </div>
            </div>

            <!-- Grilla Principal: Métodos de Pago y Productos Estrella -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- Columna Izquierda: Desglose por Método de Pago -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-200/80 dark:border-gray-700 shadow-sm space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-700">
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                            <span>💳</span> Ingresos por Método de Pago (Mes)
                        </h3>
                        <span class="text-xs text-gray-400 font-mono">Total USD</span>
                    </div>

                    <div v-if="!ventas_por_metodo || ventas_por_metodo.length === 0" class="py-8 text-center text-xs text-gray-400">
                        No hay pagos registrados en este período.
                    </div>

                    <div v-else class="space-y-3">
                        <div
                            v-for="m in ventas_por_metodo"
                            :key="m.metodo_nombre"
                            class="space-y-1"
                        >
                            <div class="flex justify-between text-xs font-semibold">
                                <span class="text-gray-800 dark:text-gray-200 flex items-center gap-1.5">
                                    <span v-if="m.metodo_tipo === 'FINANCIAMIENTO'">💛</span>
                                    <span v-else-if="m.metodo_tipo === 'EFECTIVO'">💵</span>
                                    <span v-else-if="m.metodo_tipo === 'CREDITO'">📝</span>
                                    <span v-else>📱</span>
                                    {{ m.metodo_nombre }}
                                </span>
                                <span class="font-mono text-gray-900 dark:text-white font-bold">
                                    ${{ Number(m.total_usd).toFixed(2) }} ({{ m.porcentaje }}%)
                                </span>
                            </div>

                            <!-- Barra de progreso -->
                            <div class="w-full bg-gray-100 dark:bg-gray-700 h-2 rounded-full overflow-hidden">
                                <div
                                    :class="[
                                        'h-full rounded-full transition-all duration-500',
                                        m.metodo_tipo === 'FINANCIAMIENTO' ? 'bg-amber-500' : '',
                                        m.metodo_tipo === 'EFECTIVO' ? 'bg-emerald-500' : '',
                                        m.metodo_tipo === 'DIGITAL' ? 'bg-indigo-500' : '',
                                        m.metodo_tipo === 'CREDITO' ? 'bg-red-500' : '',
                                        !['FINANCIAMIENTO', 'EFECTIVO', 'DIGITAL', 'CREDITO'].includes(m.metodo_tipo) ? 'bg-blue-500' : ''
                                    ]"
                                    :style="{ width: `${m.porcentaje}%` }"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Columna Central / Derecha: Top 5 Productos Más Vendidos -->
                <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-200/80 dark:border-gray-700 shadow-sm space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-700">
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                            <span>🏆</span> Top 5 Productos Estrella del Mes
                        </h3>
                        <Link :href="route('inventario.productos.index')" class="text-xs text-indigo-600 dark:text-indigo-400 font-semibold hover:underline">
                            Ver catálogo completo →
                        </Link>
                    </div>

                    <div v-if="!top_productos || top_productos.length === 0" class="py-8 text-center text-xs text-gray-400">
                        No hay ventas acumuladas para listar el ranking de productos.
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="text-gray-400 uppercase text-[10px] font-bold border-b border-gray-100 dark:border-gray-700">
                                <tr>
                                    <th class="py-2.5">Posición / Producto</th>
                                    <th class="py-2.5 text-center">Unidades Vendidas</th>
                                    <th class="py-2.5 text-right">Monto Recaudado ($)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                <tr v-for="(p, idx) in top_productos" :key="p.id_producto" class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition">
                                    <td class="py-3 flex items-center gap-3">
                                        <span class="w-6 h-6 rounded-full bg-indigo-50 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-black text-xs">
                                            {{ idx + 1 }}
                                        </span>
                                        <div>
                                            <div class="font-bold text-gray-900 dark:text-white">{{ p.nombre }}</div>
                                            <div class="text-[10px] text-gray-400 font-mono">SKU: {{ p.sku }}</div>
                                        </div>
                                    </td>
                                    <td class="py-3 text-center font-mono font-bold text-sm text-gray-800 dark:text-gray-200">
                                        {{ p.total_unidades }}
                                    </td>
                                    <td class="py-3 text-right font-mono font-extrabold text-sm text-emerald-600 dark:text-emerald-400">
                                        ${{ Number(p.total_facturado).toFixed(2) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Grilla Inferior: Stock Bajo y Últimas Auditorías -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                <!-- Alertas de Stock Bajo -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-200/80 dark:border-gray-700 shadow-sm space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-700">
                        <h3 class="text-sm font-bold text-amber-600 dark:text-amber-400 uppercase tracking-wider flex items-center gap-2">
                            <span>⚠️</span> Alertas de Inventario Crítico
                        </h3>
                        <Link :href="route('inventario.ajustes.create')" class="text-xs text-indigo-600 dark:text-indigo-400 font-semibold hover:underline">
                            + Crear Ajuste Entrada
                        </Link>
                    </div>

                    <div v-if="!productos_stock_bajo || productos_stock_bajo.length === 0" class="p-6 text-center text-xs text-emerald-600 bg-emerald-50 dark:bg-emerald-950/20 rounded-xl">
                        ✓ Todos los productos se encuentran por encima de su nivel de stock mínimo.
                    </div>

                    <div v-else class="space-y-2">
                        <div
                            v-for="prod in productos_stock_bajo"
                            :key="prod.id_producto"
                            class="p-3 bg-gray-50 dark:bg-gray-900/40 rounded-xl border border-gray-200/80 dark:border-gray-700 flex justify-between items-center text-xs"
                        >
                            <div>
                                <div class="font-bold text-gray-900 dark:text-white">{{ prod.nombre }}</div>
                                <div class="text-[10px] text-gray-500 font-mono">SKU: {{ prod.sku }} | Mínimo: {{ prod.stock_minimo }}</div>
                            </div>
                            <div class="text-right">
                                <span class="px-2.5 py-1 bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300 font-mono font-bold rounded-lg text-xs">
                                    {{ prod.stock_actual }} {{ prod.unidad?.abreviatura || 'UND' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Últimas Pistas de Auditoría (Feed en Vivo) -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-200/80 dark:border-gray-700 shadow-sm space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-700">
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                            <span>🛡️</span> Actividad Reciente del Audit Trail
                        </h3>
                        <Link :href="route('auditorias.index')" class="text-xs text-indigo-600 dark:text-indigo-400 font-semibold hover:underline">
                            Ver todas las pistas →
                        </Link>
                    </div>

                    <div v-if="!ultimas_auditorias || ultimas_auditorias.length === 0" class="py-6 text-center text-xs text-gray-400">
                        No hay eventos de auditoría registrados recientemente.
                    </div>

                    <div v-else class="space-y-2">
                        <div
                            v-for="a in ultimas_auditorias"
                            :key="a.id_auditoria"
                            class="p-2.5 bg-gray-50 dark:bg-gray-900/40 rounded-xl border border-gray-200/70 dark:border-gray-700 flex items-center justify-between text-xs"
                        >
                            <div class="flex items-center gap-2">
                                <span :class="['px-2 py-0.5 rounded text-[10px] font-bold font-mono', BadgeAccion(a.accion)]">
                                    {{ a.accion }}
                                </span>
                                <div>
                                    <span class="font-semibold text-gray-800 dark:text-gray-200">{{ a.modulo }}</span>
                                    <span class="text-gray-400 font-mono text-[10px] ml-1">({{ a.tabla_afectada }})</span>
                                    <div class="text-[10px] text-gray-400">
                                        {{ a.usuario?.name || 'Sistema' }} - {{ new Date(a.created_at).toLocaleTimeString() }}
                                    </div>
                                </div>
                            </div>

                            <Link
                                :href="route('auditorias.index', { buscar: a.id_auditoria })"
                                class="text-indigo-600 dark:text-indigo-400 hover:underline text-[11px] font-semibold"
                            >
                                Detalle →
                            </Link>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </AuthenticatedLayout>
</template>
