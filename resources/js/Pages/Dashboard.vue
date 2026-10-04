<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { FormatearFecha, FormatearFechaHora } from '@/Utils/FechaHora';

const props = defineProps({
    kpis: Object,
    productos_stock_bajo: Array,
    ventas_por_metodo: Array,
    top_productos: Array,
    ultimas_ventas: Array,
    ultimas_auditorias: Array,
    turno_activo: Object,
});

const tasaVes = computed(() =>
{
    return Number(props.kpis?.tasa_ves || 1.0);
});

const SaludoHorario = computed(() =>
{
    const hora = new Date().getHours();
    if (hora >= 5 && hora < 12)
    {
        return '¡Buenos días';
    }
    if (hora >= 12 && hora < 19)
    {
        return '¡Buenas tardes';
    }
    return '¡Buenas noches';
});

const FechaHoyFormateada = computed(() =>
{
    return FormatearFecha(new Date());
});

const BadgeAccion = (acc) =>
{
    switch (acc)
    {
        case 'CREAR':
            return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300 border-emerald-300 dark:border-emerald-700';
        case 'ACTUALIZAR':
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300 border-blue-300 dark:border-blue-700';
        case 'ELIMINAR':
            return 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300 border-red-300 dark:border-red-700';
        case 'ANULAR':
            return 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300 border-amber-300 dark:border-amber-700';
        case 'APLICAR':
            return 'bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300 border-purple-300 dark:border-purple-700';
        case 'LOGIN':
            return 'bg-teal-100 text-teal-800 dark:bg-teal-900/40 dark:text-teal-300 border-teal-300 dark:border-teal-700';
        case 'LOGOUT':
            return 'bg-slate-100 text-slate-800 dark:bg-slate-700 dark:text-slate-300 border-slate-300 dark:border-slate-600';
        case 'LOGIN_FALLIDO':
            return 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300 border-rose-300 dark:border-rose-700';
        default:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-600';
    }
};
</script>

<template>
    <Head title="Panel de Control y Analítica Ejecutiva" />

    <AuthenticatedLayout>
        <div class="py-6 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto space-y-6">

            <!-- Hero Banner Ejecutivo -->
            <div class="relative overflow-hidden rounded-3xl bg-linear-to-r from-slate-900 via-indigo-950 to-indigo-900 text-white p-6 sm:p-8 shadow-2xl border border-indigo-800/40">
                <!-- Efectos decorativos de fondo -->
                <div class="absolute -right-16 -top-16 w-64 h-64 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>
                <div class="absolute right-1/3 -bottom-16 w-64 h-64 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>

                <div class="relative z-10 flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                    <div class="space-y-2">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-indigo-500/20 text-indigo-300 border border-indigo-400/20 backdrop-blur-xs">
                                ⚡ Fácil Shop v2.0
                            </span>
                            <span class="text-xs text-indigo-200/70 capitalize">
                                {{ FechaHoyFormateada }}
                            </span>
                        </div>

                        <h1 class="text-2xl sm:text-3xl lg:text-4xl font-black tracking-tight">
                            {{ SaludoHorario }}, <span class="bg-linear-to-r from-indigo-200 to-white bg-clip-text text-transparent">{{ $page.props.auth.user.nombre || $page.props.auth.user.name }}</span>!
                        </h1>

                        <p class="text-xs sm:text-sm text-indigo-200/80 max-w-xl">
                            Panel integral de operaciones: ventas multimoneda, financiamiento Cashea, inventario en tiempo real y pistas de auditoría forense.
                        </p>
                    </div>

                    <!-- Accesos Rápidos del Hero -->
                    <div class="flex flex-wrap items-center gap-3">
                        <!-- Tasa Oficial -->
                        <Link
                            :href="route('monedas.index')"
                            class="bg-white/10 hover:bg-white/15 backdrop-blur-md px-4 py-2.5 rounded-2xl border border-white/15 transition text-right group"
                            title="Cotización oficial BCV USD/VES"
                        >
                            <span class="text-[10px] text-indigo-300 uppercase font-bold tracking-wider block">Tasa BCV Oficial</span>
                            <div class="flex items-center gap-1 justify-end font-mono">
                                <span class="text-lg font-black text-emerald-400">Bs. {{ tasaVes.toFixed(2) }}</span>
                                <span class="text-xs text-indigo-300 group-hover:translate-x-0.5 transition">→</span>
                            </div>
                        </Link>

                        <!-- Botón Principal: Abrir POS -->
                        <Link
                            :href="route('pos.index')"
                            class="px-6 py-3 bg-linear-to-r from-emerald-500 to-teal-500 hover:from-emerald-600 hover:to-teal-600 text-white font-black text-sm rounded-2xl shadow-lg shadow-emerald-500/30 flex items-center gap-2.5 transition transform hover:-translate-y-0.5 hover:scale-102"
                        >
                            <span class="text-lg">🛒</span>
                            <span>ABRIR POS (F2)</span>
                        </Link>
                    </div>
                </div>

                <!-- Barra de Accesos Rápidos Inferior -->
                <div class="relative z-10 mt-6 pt-5 border-t border-indigo-800/50 flex flex-wrap gap-2 text-xs">
                    <span class="text-indigo-300 font-semibold flex items-center gap-1 self-center mr-2">
                        Acciones rápidas:
                    </span>
                    <Link
                        :href="route('inventario.productos.index')"
                        class="px-3 py-1.5 rounded-xl bg-white/10 hover:bg-white/20 transition backdrop-blur-xs flex items-center gap-1.5 font-medium"
                    >
                        <span>📦</span> Catálogo Productos
                    </Link>
                    <Link
                        :href="route('inventario.ajustes.create')"
                        class="px-3 py-1.5 rounded-xl bg-white/10 hover:bg-white/20 transition backdrop-blur-xs flex items-center gap-1.5 font-medium"
                    >
                        <span>🔄</span> Registrar Ajuste
                    </Link>
                    <Link
                        :href="route('clientes.index')"
                        class="px-3 py-1.5 rounded-xl bg-white/10 hover:bg-white/20 transition backdrop-blur-xs flex items-center gap-1.5 font-medium"
                    >
                        <span>👥</span> Cobros & Clientes
                    </Link>
                    <Link
                        :href="route('auditorias.index')"
                        class="px-3 py-1.5 rounded-xl bg-white/10 hover:bg-white/20 transition backdrop-blur-xs flex items-center gap-1.5 font-medium"
                    >
                        <span>🛡️</span> Ver Auditoría
                    </Link>
                </div>
            </div>

            <!-- 4 Tarjetas KPI Principales con Micro-animación -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Ventas de Hoy -->
                <div class="bg-white dark:bg-gray-800 rounded-3xl p-5 border border-gray-200/80 dark:border-gray-700/80 shadow-xs hover:shadow-md transition-all duration-300 hover:-translate-y-0.5 group">
                    <div class="flex justify-between items-start">
                        <div>
                            <span class="text-[11px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Ventas de Hoy</span>
                            <div class="text-2xl sm:text-3xl font-black text-gray-900 dark:text-white mt-1 font-mono tracking-tight">
                                ${{ Number(kpis?.ventas_hoy_usd || 0).toFixed(2) }}
                            </div>
                            <div class="text-xs text-emerald-600 dark:text-emerald-400 mt-0.5 font-mono font-semibold">
                                ≈ Bs. {{ Number(kpis?.ventas_hoy_ves || 0).toLocaleString('es-VE', { minimumFractionDigits: 2 }) }}
                            </div>
                        </div>
                        <div class="p-3 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 rounded-2xl text-xl shadow-xs group-hover:scale-110 transition transform">
                            💰
                        </div>
                    </div>
                    <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-700/60 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                        <span>{{ kpis?.conteo_ventas_hoy || 0 }} tickets emitidos</span>
                        <Link :href="route('facturacion.index')" class="text-indigo-600 dark:text-indigo-400 font-bold hover:underline">Ver ventas →</Link>
                    </div>
                </div>

                <!-- Ventas del Mes -->
                <div class="bg-white dark:bg-gray-800 rounded-3xl p-5 border border-gray-200/80 dark:border-gray-700/80 shadow-xs hover:shadow-md transition-all duration-300 hover:-translate-y-0.5 group">
                    <div class="flex justify-between items-start">
                        <div>
                            <span class="text-[11px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Ventas del Mes</span>
                            <div class="text-2xl sm:text-3xl font-black text-gray-900 dark:text-white mt-1 font-mono tracking-tight">
                                ${{ Number(kpis?.ventas_mes_usd || 0).toFixed(2) }}
                            </div>
                            <div class="text-xs text-indigo-600 dark:text-indigo-400 mt-0.5 font-mono font-semibold">
                                ≈ Bs. {{ Number(kpis?.ventas_mes_ves || 0).toLocaleString('es-VE', { minimumFractionDigits: 2 }) }}
                            </div>
                        </div>
                        <div class="p-3 bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 rounded-2xl text-xl shadow-xs group-hover:scale-110 transition transform">
                            📈
                        </div>
                    </div>
                    <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-700/60 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                        <span>Ticket Prom: ${{ Number(kpis?.ticket_promedio_usd || 0).toFixed(2) }}</span>
                        <span class="font-bold text-gray-700 dark:text-gray-300">{{ kpis?.conteo_ventas_mes || 0 }} ventas</span>
                    </div>
                </div>

                <!-- Cartera de Crédito -->
                <div class="bg-white dark:bg-gray-800 rounded-3xl p-5 border border-gray-200/80 dark:border-gray-700/80 shadow-xs hover:shadow-md transition-all duration-300 hover:-translate-y-0.5 group">
                    <div class="flex justify-between items-start">
                        <div>
                            <span class="text-[11px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Cartera por Cobrar</span>
                            <div class="text-2xl sm:text-3xl font-black text-rose-600 dark:text-rose-400 mt-1 font-mono tracking-tight">
                                ${{ Number(kpis?.total_cartera_usd || 0).toFixed(2) }}
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 font-mono">
                                ≈ Bs. {{ Number(kpis?.total_cartera_ves || 0).toLocaleString('es-VE', { minimumFractionDigits: 2 }) }}
                            </div>
                        </div>
                        <div class="p-3 bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 rounded-2xl text-xl shadow-xs group-hover:scale-110 transition transform">
                            💳
                        </div>
                    </div>
                    <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-700/60 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                        <span>{{ kpis?.clientes_con_deuda || 0 }} clientes con deuda</span>
                        <Link :href="route('clientes.index')" class="text-rose-600 dark:text-rose-400 font-bold hover:underline">Cobrar abonos →</Link>
                    </div>
                </div>

                <!-- Stock Crítico -->
                <div class="bg-white dark:bg-gray-800 rounded-3xl p-5 border border-gray-200/80 dark:border-gray-700/80 shadow-xs hover:shadow-md transition-all duration-300 hover:-translate-y-0.5 group">
                    <div class="flex justify-between items-start">
                        <div>
                            <span class="text-[11px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Stock Crítico</span>
                            <div class="text-2xl sm:text-3xl font-black text-amber-600 dark:text-amber-400 mt-1 font-mono tracking-tight">
                                {{ kpis?.conteo_stock_bajo || 0 }}
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 font-medium">
                                Artículos bajo mínimo
                            </div>
                        </div>
                        <div class="p-3 bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 rounded-2xl text-xl shadow-xs group-hover:scale-110 transition transform">
                            ⚠️
                        </div>
                    </div>
                    <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-700/60 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                        <span>Alerta de reposición</span>
                        <Link :href="route('inventario.productos.index')" class="text-amber-600 dark:text-amber-400 font-bold hover:underline">Ver alertas →</Link>
                    </div>
                </div>
            </div>

            <!-- Grilla Principal: Métodos de Pago y Ranking de Productos -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- Columna Izquierda: Desglose por Método de Pago -->
                <div class="bg-white dark:bg-gray-800 rounded-3xl p-6 border border-gray-200/80 dark:border-gray-700 shadow-sm space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-700">
                        <div>
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                                <span>💳</span> Distribución de Pagos (Mes)
                            </h3>
                            <p class="text-[11px] text-gray-400 mt-0.5">Ingresos categorizados por método</p>
                        </div>
                        <span class="text-xs font-mono font-bold text-gray-500 dark:text-gray-400">USD ($)</span>
                    </div>

                    <div v-if="!ventas_por_metodo || ventas_por_metodo.length === 0" class="py-12 text-center text-xs text-gray-400">
                        No se registran transacciones de venta en este período.
                    </div>

                    <div v-else class="space-y-4">
                        <div
                            v-for="m in ventas_por_metodo"
                            :key="m.metodo_nombre"
                            class="space-y-1.5"
                        >
                            <div class="flex justify-between items-center text-xs font-semibold">
                                <span class="text-gray-800 dark:text-gray-200 flex items-center gap-2">
                                    <span v-if="m.metodo_tipo === 'FINANCIAMIENTO'">💛</span>
                                    <span v-else-if="m.metodo_tipo === 'EFECTIVO'">💵</span>
                                    <span v-else-if="m.metodo_tipo === 'CREDITO'">📝</span>
                                    <span v-else>📱</span>
                                    <span>{{ m.metodo_nombre }}</span>
                                </span>
                                <div class="text-right">
                                    <span class="font-mono text-gray-900 dark:text-white font-black">
                                        ${{ Number(m.total_usd).toFixed(2) }}
                                    </span>
                                    <span class="text-[10px] text-gray-400 ml-1.5 font-mono">({{ m.porcentaje }}%)</span>
                                </div>
                            </div>

                            <!-- Barra de progreso con gradientes -->
                            <div class="w-full bg-gray-100 dark:bg-gray-700 h-2.5 rounded-full overflow-hidden">
                                <div
                                    :class="[
                                        'h-full rounded-full transition-all duration-700',
                                        m.metodo_tipo === 'FINANCIAMIENTO' ? 'bg-linear-to-r from-amber-400 to-yellow-500' : '',
                                        m.metodo_tipo === 'EFECTIVO' ? 'bg-linear-to-r from-emerald-400 to-teal-500' : '',
                                        m.metodo_tipo === 'DIGITAL' ? 'bg-linear-to-r from-indigo-500 to-purple-600' : '',
                                        m.metodo_tipo === 'CREDITO' ? 'bg-linear-to-r from-rose-400 to-red-500' : '',
                                        !['FINANCIAMIENTO', 'EFECTIVO', 'DIGITAL', 'CREDITO'].includes(m.metodo_tipo) ? 'bg-linear-to-r from-blue-400 to-indigo-500' : ''
                                    ]"
                                    :style="{ width: `${m.porcentaje}%` }"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Columna Derecha: Top 5 Productos Más Vendidos -->
                <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-3xl p-6 border border-gray-200/80 dark:border-gray-700 shadow-sm space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-700">
                        <div>
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                                <span>🏆</span> Ranking Top Productos del Mes
                            </h3>
                            <p class="text-[11px] text-gray-400 mt-0.5">Artículos con mayor rotación e ingresos</p>
                        </div>
                        <Link :href="route('inventario.productos.index')" class="text-xs text-indigo-600 dark:text-indigo-400 font-bold hover:underline">
                            Ver catálogo completo →
                        </Link>
                    </div>

                    <div v-if="!top_productos || top_productos.length === 0" class="py-12 text-center text-xs text-gray-400">
                        No hay ventas registradas aún para calcular el ranking de productos.
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="text-gray-400 uppercase text-[10px] font-bold border-b border-gray-100 dark:border-gray-700">
                                <tr>
                                    <th class="py-2.5">Posición / Artículo</th>
                                    <th class="py-2.5 text-center">Unidades</th>
                                    <th class="py-2.5 text-right">Recaudado</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60">
                                <tr v-for="(p, idx) in top_productos" :key="p.id_producto" class="hover:bg-gray-50/70 dark:hover:bg-gray-750 transition">
                                    <td class="py-3 flex items-center gap-3">
                                        <span
                                            :class="[
                                                'w-7 h-7 rounded-xl flex items-center justify-center font-black text-xs shadow-2xs',
                                                idx === 0 ? 'bg-amber-100 text-amber-900 dark:bg-amber-900/50 dark:text-amber-200 border border-amber-300 dark:border-amber-700' : (
                                                    idx === 1 ? 'bg-slate-200 text-slate-800 dark:bg-slate-700 dark:text-slate-200 border border-slate-300' : (
                                                        idx === 2 ? 'bg-orange-100 text-orange-900 dark:bg-orange-950/50 dark:text-orange-300 border border-orange-300' : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400'
                                                    )
                                                )
                                            ]"
                                        >
                                            {{ idx === 0 ? '🥇' : (idx === 1 ? '🥈' : (idx === 2 ? '🥉' : idx + 1)) }}
                                        </span>
                                        <div>
                                            <div class="font-bold text-gray-900 dark:text-white">{{ p.nombre }}</div>
                                            <div class="text-[10px] text-gray-400 font-mono">SKU: {{ p.sku }}</div>
                                        </div>
                                    </td>
                                    <td class="py-3 text-center font-mono font-bold text-sm text-gray-800 dark:text-gray-200">
                                        {{ p.total_unidades }} <span class="text-[10px] font-normal text-gray-400">unds</span>
                                    </td>
                                    <td class="py-3 text-right font-mono font-black text-sm text-emerald-600 dark:text-emerald-400">
                                        ${{ Number(p.total_facturado).toFixed(2) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Grilla Inferior: Stock Bajo y Feed de Auditoría Forense -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                <!-- Alertas de Stock Crítico -->
                <div class="bg-white dark:bg-gray-800 rounded-3xl p-6 border border-gray-200/80 dark:border-gray-700 shadow-sm space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-700">
                        <div>
                            <h3 class="text-sm font-bold text-amber-600 dark:text-amber-400 uppercase tracking-wider flex items-center gap-2">
                                <span>⚠️</span> Alertas de Inventario Crítico
                            </h3>
                            <p class="text-[11px] text-gray-400 mt-0.5">Productos con stock por debajo del mínimo</p>
                        </div>
                        <Link :href="route('inventario.ajustes.create')" class="text-xs text-indigo-600 dark:text-indigo-400 font-bold hover:underline">
                            + Crear Ajuste
                        </Link>
                    </div>

                    <div v-if="!productos_stock_bajo || productos_stock_bajo.length === 0" class="p-8 text-center text-xs text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/20 rounded-2xl border border-emerald-200 dark:border-emerald-800/40">
                        ✓ Todos los productos se encuentran por encima de su nivel de stock mínimo.
                    </div>

                    <div v-else class="space-y-2.5">
                        <div
                            v-for="prod in productos_stock_bajo"
                            :key="prod.id_producto"
                            class="p-3 bg-gray-50 dark:bg-gray-900/40 rounded-2xl border border-gray-200/70 dark:border-gray-700 flex justify-between items-center text-xs"
                        >
                            <div>
                                <div class="font-bold text-gray-900 dark:text-white">{{ prod.nombre }}</div>
                                <div class="text-[10px] text-gray-500 font-mono">
                                    SKU: {{ prod.sku }} | Mínimo requerido: {{ prod.stock_minimo }}
                                </div>
                            </div>
                            <div class="text-right flex items-center gap-2">
                                <span class="px-2.5 py-1 bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300 font-mono font-black rounded-lg text-xs">
                                    {{ prod.stock_actual }} disp.
                                </span>
                                <Link
                                    :href="route('inventario.productos.index', { buscar: prod.sku })"
                                    class="text-indigo-600 dark:text-indigo-400 hover:underline text-[11px] font-bold"
                                >
                                    Ver →
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Feed en Vivo del Audit Trail -->
                <div class="bg-white dark:bg-gray-800 rounded-3xl p-6 border border-gray-200/80 dark:border-gray-700 shadow-sm space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-700">
                        <div class="flex items-center gap-2">
                            <span class="relative flex h-2.5 w-2.5">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-indigo-600"></span>
                            </span>
                            <div>
                                <h3 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider flex items-center gap-1.5">
                                    <span>🛡️</span> Feed del Audit Trail
                                </h3>
                                <p class="text-[11px] text-gray-400">Eventos de seguridad y modificaciones en vivo</p>
                            </div>
                        </div>
                        <Link :href="route('auditorias.index')" class="text-xs text-indigo-600 dark:text-indigo-400 font-bold hover:underline">
                            Ver auditorías →
                        </Link>
                    </div>

                    <div v-if="!ultimas_auditorias || ultimas_auditorias.length === 0" class="py-12 text-center text-xs text-gray-400">
                        No se registran eventos de auditoría recientes.
                    </div>

                    <div v-else class="space-y-2.5">
                        <div
                            v-for="a in ultimas_auditorias"
                            :key="a.id_auditoria"
                            class="p-3 bg-gray-50 dark:bg-gray-900/40 rounded-2xl border border-gray-200/70 dark:border-gray-700 flex items-center justify-between text-xs hover:border-indigo-300 dark:hover:border-indigo-600 transition"
                        >
                            <div class="flex items-center gap-2.5">
                                <span :class="['px-2 py-0.5 rounded-full text-[10px] font-bold font-mono border', BadgeAccion(a.accion)]">
                                    {{ a.accion }}
                                </span>
                                <div>
                                    <div class="font-bold text-gray-800 dark:text-gray-200 flex items-center gap-1">
                                        <span>{{ a.modulo }}</span>
                                        <span class="text-gray-400 font-mono text-[10px]">({{ a.tabla_afectada }})</span>
                                    </div>
                                    <div class="text-[10px] text-gray-400 font-mono">
                                        {{ a.usuario?.nombre || a.usuario?.name || 'Sistema' }} • {{ FormatearFechaHora(a.created_at) }}
                                    </div>
                                </div>
                            </div>

                            <Link
                                :href="route('auditorias.index', { buscar: a.id_auditoria })"
                                class="px-2.5 py-1 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-950/60 rounded-lg text-xs font-bold transition"
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
