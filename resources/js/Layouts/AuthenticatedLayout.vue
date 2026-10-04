<script setup>
import { ref, onMounted, computed } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { FormatearNumero } from '@/Utils/FormatoNumero';

const page = usePage();

const showingNavigationDropdown = ref(false);
const isDark = ref(false);
const menuVentasAbierto = ref(false);
const menuInventarioAbierto = ref(false);
const menuConfiguracionAbierto = ref(false);
const flashVisible = ref(true);

const usuario = computed(() => page.props.auth?.user || {});
const infoGlobal = computed(() => page.props.global_info || {});
const flash = computed(() => page.props.flash || {});

const TasaBcvFormateada = computed(() =>
{
    const tasa = Number(infoGlobal.value?.tasa_bcv || 1.0);
    return FormatearNumero(tasa, 2);
});

const InicializarTema = () =>
{
    if (typeof window !== 'undefined')
    {
        isDark.value = document.documentElement.classList.contains('dark');
    }
};

const AlternarTema = () =>
{
    isDark.value = !isDark.value;
    if (isDark.value)
    {
        document.documentElement.classList.add('dark');
        localStorage.setItem('theme', 'dark');
    }
    else
    {
        document.documentElement.classList.remove('dark');
        localStorage.setItem('theme', 'light');
    }
};

const ObtenerIniciales = (nombreCompleto) =>
{
    if (!nombreCompleto)
    {
        return 'FS';
    }
    const partes = nombreCompleto.trim().split(' ');
    if (partes.length >= 2)
    {
        return (partes[0][0] + partes[1][0]).toUpperCase();
    }
    return nombreCompleto.substring(0, 2).toUpperCase();
};

onMounted(() =>
{
    InicializarTema();
});
</script>

<template>
    <div>
        <div class="min-h-screen bg-slate-50 dark:bg-gray-900 transition-colors duration-200">
            <!-- Barra de Navegación Principal Superior -->
            <nav class="sticky top-0 z-40 bg-white/90 dark:bg-gray-800/90 backdrop-blur-md border-b border-gray-200/80 dark:border-gray-700/80 shadow-xs transition-colors duration-200">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="flex h-16 justify-between items-center gap-4">

                        <!-- Zona Izquierda: Logo y Menú Agrupado -->
                        <div class="flex items-center gap-6">
                            <!-- Logo Brand -->
                            <Link :href="route('dashboard')" class="flex items-center gap-2.5 group">
                                <div class="w-9 h-9 rounded-xl bg-linear-to-tr from-indigo-600 via-indigo-500 to-emerald-400 flex items-center justify-center text-white shadow-md shadow-indigo-500/20 group-hover:scale-105 transition transform">
                                    <span class="font-black text-lg">⚡</span>
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-base font-black tracking-tight text-gray-900 dark:text-white leading-none">
                                        Fácil<span class="text-indigo-600 dark:text-indigo-400">Shop</span>
                                    </span>
                                    <span class="text-[9px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mt-0.5">
                                        POS & Retail
                                    </span>
                                </div>
                            </Link>

                            <!-- Enlaces de Navegación Agrupados (Desktop) -->
                            <div class="hidden lg:flex items-center gap-1">
                                <!-- Dashboard Directo -->
                                <Link
                                    :href="route('dashboard')"
                                    :class="[
                                        'px-3 py-1.5 rounded-lg text-xs font-semibold transition flex items-center gap-1.5',
                                        route().current('dashboard')
                                            ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300 font-bold'
                                            : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700/60'
                                    ]"
                                >
                                    <span>📊</span> Dashboard
                                </Link>

                                <!-- Botón Destacado POS -->
                                <Link
                                    :href="route('pos.index')"
                                    :class="[
                                        'px-3.5 py-1.5 rounded-lg text-xs font-black transition flex items-center gap-1.5 shadow-xs',
                                        route().current('pos.*')
                                            ? 'bg-emerald-600 text-white shadow-emerald-500/25'
                                            : 'bg-emerald-500 hover:bg-emerald-600 text-white hover:shadow-emerald-500/20'
                                    ]"
                                >
                                    <span>🛒</span> POS (F2)
                                </Link>

                                <!-- Dropdown: Ventas & Caja -->
                                <div class="relative">
                                    <Dropdown align="left" width="56">
                                        <template #trigger>
                                            <button
                                                type="button"
                                                :class="[
                                                    'px-3 py-1.5 rounded-lg text-xs font-semibold transition flex items-center gap-1',
                                                    route().current('caja.*') || route().current('facturacion.*') || route().current('clientes.*')
                                                        ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300 font-bold'
                                                        : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700/60'
                                                ]"
                                            >
                                                <span>💼</span> Ventas & Caja
                                                <svg class="w-3.5 h-3.5 ml-0.5 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                </svg>
                                            </button>
                                        </template>
                                        <template #content>
                                            <DropdownLink :href="route('caja.index')" :class="route().current('caja.*') ? 'font-bold text-indigo-600' : ''">
                                                💵 Control de Caja y Turnos
                                            </DropdownLink>
                                            <DropdownLink :href="route('facturacion.index')" :class="route().current('facturacion.*') ? 'font-bold text-indigo-600' : ''">
                                                🧾 Facturación y Comprobantes
                                            </DropdownLink>
                                            <DropdownLink :href="route('clientes.index')" :class="route().current('clientes.*') ? 'font-bold text-indigo-600' : ''">
                                                👥 Directorio de Clientes y Créditos
                                            </DropdownLink>
                                        </template>
                                    </Dropdown>
                                </div>

                                <!-- Dropdown: Inventario -->
                                <div class="relative">
                                    <Dropdown align="left" width="56">
                                        <template #trigger>
                                            <button
                                                type="button"
                                                :class="[
                                                    'px-3 py-1.5 rounded-lg text-xs font-semibold transition flex items-center gap-1',
                                                    route().current('inventario.*')
                                                        ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300 font-bold'
                                                        : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700/60'
                                                ]"
                                            >
                                                <span>📦</span> Inventario
                                                <svg class="w-3.5 h-3.5 ml-0.5 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                </svg>
                                            </button>
                                        </template>
                                        <template #content>
                                            <DropdownLink :href="route('inventario.productos.index')" :class="route().current('inventario.productos.*') ? 'font-bold text-indigo-600' : ''">
                                                🏷️ Catálogo de Productos
                                            </DropdownLink>
                                            <DropdownLink :href="route('inventario.ajustes.index')" :class="route().current('inventario.ajustes.*') ? 'font-bold text-indigo-600' : ''">
                                                🔄 Ajustes de Stock (E/S)
                                            </DropdownLink>
                                            <DropdownLink :href="route('inventario.conteos.index')" :class="route().current('inventario.conteos.*') ? 'font-bold text-indigo-600' : ''">
                                                📋 Tomas Físicas (Auditorías)
                                            </DropdownLink>
                                        </template>
                                    </Dropdown>
                                </div>

                                <!-- Dropdown: Configuración & Finanzas -->
                                <div class="relative">
                                    <Dropdown align="left" width="60">
                                        <template #trigger>
                                            <button
                                                type="button"
                                                :class="[
                                                    'px-3 py-1.5 rounded-lg text-xs font-semibold transition flex items-center gap-1',
                                                    route().current('monedas.*') || route().current('metodos-pago.*') || route().current('auditorias.*') || route().current('configuracion.*')
                                                        ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300 font-bold'
                                                        : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700/60'
                                                ]"
                                            >
                                                <span>⚙️</span> Sistema
                                                <svg class="w-3.5 h-3.5 ml-0.5 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                </svg>
                                            </button>
                                        </template>
                                        <template #content>
                                            <DropdownLink :href="route('monedas.index')" :class="route().current('monedas.*') ? 'font-bold text-indigo-600' : ''">
                                                💱 Monedas y Tasas BCV
                                            </DropdownLink>
                                            <DropdownLink :href="route('metodos-pago.index')" :class="route().current('metodos-pago.*') ? 'font-bold text-indigo-600' : ''">
                                                💳 Métodos de Pago
                                            </DropdownLink>
                                            <DropdownLink :href="route('auditorias.index')" :class="route().current('auditorias.*') ? 'font-bold text-indigo-600' : ''">
                                                🛡️ Auditoría Forense (Audit Trail)
                                            </DropdownLink>
                                            <DropdownLink :href="route('configuracion.index')" :class="route().current('configuracion.*') ? 'font-bold text-indigo-600' : ''">
                                                ⚙️ Configuración (Usuarios, Marcas)
                                            </DropdownLink>
                                        </template>
                                    </Dropdown>
                                </div>
                            </div>
                        </div>

                        <!-- Zona Derecha: Tasa BCV, Estado Caja, Dark Mode Toggle y Perfil -->
                        <div class="hidden sm:flex items-center gap-3">
                            <!-- Badge Tasa BCV -->
                            <Link
                                :href="route('monedas.index')"
                                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-gray-100 hover:bg-gray-200 dark:bg-gray-700/80 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-600 text-xs font-mono text-gray-700 dark:text-gray-200 transition"
                                title="Cotización oficial BCV USD/VES. Haga clic para actualizar tasas."
                            >
                                <span class="text-[10px] text-gray-400 font-bold uppercase">BCV:</span>
                                <span class="font-extrabold text-emerald-600 dark:text-emerald-400">Bs. {{ TasaBcvFormateada }}</span>
                            </Link>

                            <!-- Badge Estado de Turno de Caja -->
                            <Link
                                :href="route('caja.index')"
                                :class="[
                                    'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold border transition',
                                    infoGlobal.caja_activa
                                        ? 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800'
                                        : 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-800'
                                ]"
                                :title="infoGlobal.caja_activa ? 'Turno de caja abierto y operativo' : 'Sin turno de caja abierto'"
                            >
                                <span class="relative flex h-2 w-2">
                                    <span v-if="infoGlobal.caja_activa" class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                    <span :class="['relative inline-flex rounded-full h-2 w-2', infoGlobal.caja_activa ? 'bg-emerald-500' : 'bg-amber-500']"></span>
                                </span>
                                <span>{{ infoGlobal.caja_activa ? 'Caja Abierta' : 'Caja Cerrada' }}</span>
                            </Link>

                            <!-- Botón Modo Oscuro / Claro -->
                            <button
                                type="button"
                                @click="AlternarTema"
                                class="p-2 rounded-xl text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700/60 transition"
                                :title="isDark ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro'"
                            >
                                <span v-if="isDark" class="text-base">☀️</span>
                                <span v-else class="text-base">🌙</span>
                            </button>

                            <!-- Perfil de Usuario Dropdown -->
                            <div class="relative">
                                <Dropdown align="right" width="48">
                                    <template #trigger>
                                        <button
                                            type="button"
                                            class="inline-flex items-center gap-2 px-2.5 py-1.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white hover:bg-gray-50 dark:bg-gray-800 dark:hover:bg-gray-750 text-xs font-semibold text-gray-700 dark:text-gray-200 transition shadow-2xs"
                                        >
                                            <span class="w-6 h-6 rounded-full bg-linear-to-tr from-indigo-500 to-purple-600 text-white flex items-center justify-center text-[10px] font-black">
                                                {{ ObtenerIniciales(usuario.nombre || usuario.name) }}
                                            </span>
                                            <span class="max-w-[100px] truncate text-left">
                                                {{ usuario.nombre || usuario.name }}
                                            </span>
                                            <svg class="h-3.5 w-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </button>
                                    </template>

                                    <template #content>
                                        <div class="px-4 py-2 border-b border-gray-100 dark:border-gray-700">
                                            <div class="font-bold text-gray-900 dark:text-white truncate text-xs">
                                                {{ usuario.nombre || usuario.name }}
                                            </div>
                                            <div class="text-[10px] text-gray-400 truncate">
                                                {{ usuario.email }}
                                            </div>
                                            <span class="inline-block mt-1 px-1.5 py-0.5 rounded text-[9px] font-bold uppercase bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300">
                                                {{ usuario.rol || 'Operador' }}
                                            </span>
                                        </div>
                                        <DropdownLink :href="route('profile.edit')">
                                            👤 Mi Perfil
                                        </DropdownLink>
                                        <DropdownLink :href="route('configuracion.index')">
                                            ⚙️ Configuración
                                        </DropdownLink>
                                        <DropdownLink :href="route('logout')" method="post" as="button" class="text-red-600 dark:text-red-400">
                                            🚪 Cerrar Sesión
                                        </DropdownLink>
                                    </template>
                                </Dropdown>
                            </div>
                        </div>

                        <!-- Botón Hamburguesa para Pantallas Pequeñas -->
                        <div class="flex items-center gap-2 sm:hidden">
                            <!-- Toggle tema rápido móvil -->
                            <button
                                type="button"
                                @click="AlternarTema"
                                class="p-1.5 rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700"
                            >
                                <span>{{ isDark ? '☀️' : '🌙' }}</span>
                            </button>

                            <button
                                @click="showingNavigationDropdown = !showingNavigationDropdown"
                                class="inline-flex items-center justify-center p-2 rounded-lg text-gray-400 hover:text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none transition"
                            >
                                <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                    <path
                                        :class="{'hidden': showingNavigationDropdown, 'inline-flex': !showingNavigationDropdown }"
                                        stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"
                                    />
                                    <path
                                        :class="{'hidden': !showingNavigationDropdown, 'inline-flex': showingNavigationDropdown }"
                                        stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Menú Desplegable Móvil -->
                <div v-show="showingNavigationDropdown" class="sm:hidden border-t border-gray-200 dark:border-gray-700 px-4 py-3 space-y-2 bg-white dark:bg-gray-800">
                    <div class="flex items-center justify-between pb-2 border-b border-gray-100 dark:border-gray-700 text-xs">
                        <span class="font-mono text-gray-500">BCV: Bs. {{ TasaBcvFormateada }}</span>
                        <span :class="['px-2 py-0.5 rounded text-[10px] font-bold', infoGlobal.caja_activa ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800']">
                            {{ infoGlobal.caja_activa ? 'Caja Abierta' : 'Caja Cerrada' }}
                        </span>
                    </div>

                    <Link :href="route('pos.index')" class="block w-full py-2 px-3 text-center bg-emerald-600 text-white font-bold rounded-xl text-xs shadow-sm">
                        🛒 ABRIR PUNTO DE VENTA (POS)
                    </Link>

                    <div class="grid grid-cols-2 gap-1.5 pt-2 text-xs font-semibold">
                        <Link :href="route('dashboard')" class="p-2 rounded-lg bg-gray-50 dark:bg-gray-700/50 text-gray-800 dark:text-gray-200">
                            📊 Dashboard
                        </Link>
                        <Link :href="route('caja.index')" class="p-2 rounded-lg bg-gray-50 dark:bg-gray-700/50 text-gray-800 dark:text-gray-200">
                            💵 Caja
                        </Link>
                        <Link :href="route('facturacion.index')" class="p-2 rounded-lg bg-gray-50 dark:bg-gray-700/50 text-gray-800 dark:text-gray-200">
                            🧾 Facturación
                        </Link>
                        <Link :href="route('clientes.index')" class="p-2 rounded-lg bg-gray-50 dark:bg-gray-700/50 text-gray-800 dark:text-gray-200">
                            👥 Clientes
                        </Link>
                        <Link :href="route('inventario.productos.index')" class="p-2 rounded-lg bg-gray-50 dark:bg-gray-700/50 text-gray-800 dark:text-gray-200">
                            📦 Productos
                        </Link>
                        <Link :href="route('inventario.ajustes.index')" class="p-2 rounded-lg bg-gray-50 dark:bg-gray-700/50 text-gray-800 dark:text-gray-200">
                            🔄 Ajustes
                        </Link>
                        <Link :href="route('inventario.conteos.index')" class="p-2 rounded-lg bg-gray-50 dark:bg-gray-700/50 text-gray-800 dark:text-gray-200">
                            📋 Conteos
                        </Link>
                        <Link :href="route('auditorias.index')" class="p-2 rounded-lg bg-gray-50 dark:bg-gray-700/50 text-gray-800 dark:text-gray-200">
                            🛡️ Auditoría
                        </Link>
                    </div>

                    <div class="pt-3 border-t border-gray-100 dark:border-gray-700 flex justify-between items-center text-xs">
                        <span class="text-gray-500 font-medium">{{ usuario.nombre || usuario.name }}</span>
                        <Link :href="route('logout')" method="post" as="button" class="text-rose-600 font-bold">
                            Cerrar Sesión
                        </Link>
                    </div>
                </div>
            </nav>

            <!-- Encabezado de Página Opcional -->
            <header v-if="$slots.header" class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                <div class="mx-auto max-w-7xl px-4 py-4 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <!-- Notificaciones Flotantes Flash Toast -->
            <transition
                enter-active-class="transform ease-out duration-300 transition"
                enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
                enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
                leave-active-class="transition ease-in duration-100"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="flashVisible && (flash.success || flash.error)"
                    class="fixed bottom-5 right-5 z-50 max-w-sm w-full bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700 p-4 overflow-hidden"
                >
                    <div class="flex items-start gap-3">
                        <span class="text-xl">
                            {{ flash.success ? '✅' : '❌' }}
                        </span>
                        <div class="flex-1">
                            <h4 class="text-xs font-bold uppercase tracking-wider text-gray-900 dark:text-white">
                                {{ flash.success ? 'Operación Exitosa' : 'Aviso del Sistema' }}
                            </h4>
                            <p class="text-xs text-gray-600 dark:text-gray-300 mt-0.5">
                                {{ flash.success || flash.error }}
                            </p>
                        </div>
                        <button
                            type="button"
                            @click="flashVisible = false"
                            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 text-xs px-1 rounded-md"
                        >
                            ✕
                        </button>
                    </div>
                </div>
            </transition>

            <!-- Contenido Principal -->
            <main>
                <slot />
            </main>
        </div>
    </div>
</template>
