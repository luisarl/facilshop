<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { usePosStore } from '@/Stores/usePosStore';
import ModalCobro from './Partials/ModalCobro.vue';
import ModalTicketsPausados from './Partials/ModalTicketsPausados.vue';
import TicketImprimible from './Partials/TicketImprimible.vue';

const props = defineProps({
    turno_activo: Object,
    moneda_base: Object,
    tasa_ves: Number,
    monedas: Array,
    metodos_pago: Array,
    clientes: Array,
    categorias: Array,
    productos: Array,
    config_cashea: Object,
});

const posStore = usePosStore();

// Sincronizar tasa BCV en el store
onMounted(() => {
    posStore.setTasaVes(props.tasa_ves);
    EnfocarBuscador();
    window.addEventListener('keydown', ManejarAtajosTeclado);
});

onUnmounted(() => {
    window.removeEventListener('keydown', ManejarAtajosTeclado);
});

// Referencias y Estados Locales
const inputCodigoBarras = ref(null);
const codigoEscaneado = ref('');
const categoriaSeleccionada = ref('');
const busquedaTexto = ref('');
const mensajeAlerta = ref(null);

// Modales
const modalCobroAbierto = ref(false);
const modalTicketsPausadosAbierto = ref(false);
const ventaRecienteParaTicket = ref(null);

const EnfocarBuscador = () => {
    nextTick(() => {
        if (inputCodigoBarras.value) {
            inputCodigoBarras.value.focus();
        }
    });
};

// Atajos de Teclado Globales (F2, F4, F8, Shift+F8, Esc)
const ManejarAtajosTeclado = (e) => {
    // Si hay un modal abierto y presiona Escape, cerrar
    if (e.key === 'Escape') {
        modalCobroAbierto.value = false;
        modalTicketsPausadosAbierto.value = false;
        ventaRecienteParaTicket.value = null;
        EnfocarBuscador();
        return;
    }

    // F2: Enfocar escáner / buscador
    if (e.key === 'F2') {
        e.preventDefault();
        EnfocarBuscador();
        return;
    }

    // F4: Abrir Modal de Cobro
    if (e.key === 'F4') {
        e.preventDefault();
        if (posStore.items.length > 0) {
            modalCobroAbierto.value = true;
        }
        return;
    }

    // Shift + F8: Ver Tickets Pausados
    if (e.key === 'F8' && e.shiftKey) {
        e.preventDefault();
        modalTicketsPausadosAbierto.value = true;
        return;
    }

    // F8: Pausar Ticket Actual
    if (e.key === 'F8' && !e.shiftKey) {
        e.preventDefault();
        if (posStore.items.length > 0) {
            PausarVentaActual();
        }
        return;
    }
};

// Filtro de productos
const productosFiltrados = computed(() => {
    let prods = props.productos;

    if (categoriaSeleccionada.value) {
        prods = prods.filter(p => p.id_categoria === categoriaSeleccionada.value);
    }

    if (busquedaTexto.value) {
        const termino = busquedaTexto.value.toLowerCase();
        prods = prods.filter(p =>
            p.nombre.toLowerCase().includes(termino) ||
            p.sku.toLowerCase().includes(termino) ||
            (p.codigo_barras && p.codigo_barras.toLowerCase().includes(termino))
        );
    }

    return prods;
});

// Escaneo rápido
const ProcesarEscaneo = () => {
    const codigo = codigoEscaneado.value.trim();
    if (!codigo) return;

    const producto = props.productos.find(p =>
        p.codigo_barras === codigo || p.sku.toLowerCase() === codigo.toLowerCase()
    );

    if (producto) {
        if (producto.stock_actual <= 0) {
            MostrarAlerta(`El producto "${producto.nombre}" no tiene existencias disponibles.`, 'error');
        } else {
            posStore.agregarProducto(producto);
            MostrarAlerta(`+1 ${producto.nombre} agregado al carrito.`, 'exito');
        }
    } else {
        MostrarAlerta(`Producto con código "${codigo}" no encontrado en el catálogo.`, 'error');
    }

    codigoEscaneado.value = '';
    EnfocarBuscador();
};

const AgregarAlCarrito = (producto) => {
    if (producto.stock_actual <= 0) {
        MostrarAlerta(`El producto "${producto.nombre}" está agotado.`, 'error');
        return;
    }
    posStore.agregarProducto(producto);
    EnfocarBuscador();
};

const PausarVentaActual = () => {
    if (posStore.items.length === 0) return;
    const pausado = posStore.pausarTicket();
    if (pausado) {
        MostrarAlerta('Ticket pausado y colocado en cola de espera (F8).', 'exito');
    }
};

const AbrirCobro = () => {
    if (posStore.items.length === 0) return;
    modalCobroAbierto.value = true;
};

const OnVentaCompletada = (venta) => {
    modalCobroAbierto.value = false;
    ventaRecienteParaTicket.value = venta;
    MostrarAlerta(`Venta ${venta.numero_comprobante} completada exitosamente.`, 'exito');
};

const MostrarAlerta = (mensaje, tipo = 'info') => {
    mensajeAlerta.value = { mensaje, tipo };
    setTimeout(() => {
        mensajeAlerta.value = null;
    }, 3000);
};
</script>

<template>
    <Head title="Punto de Venta (POS) - Fácil Shop" />

    <div class="min-h-screen bg-gray-100 dark:bg-gray-950 flex flex-col font-sans text-gray-800 dark:text-gray-200 select-none">

        <!-- BARRA SUPERIOR DEL POS -->
        <header class="bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800 px-4 py-2.5 flex justify-between items-center shrink-0">
            <div class="flex items-center gap-4">
                <Link
                    :href="route('dashboard')"
                    class="p-2 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 rounded-xl transition text-gray-500"
                    title="Salir al Dashboard"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </Link>

                <div>
                    <h1 class="font-black text-lg tracking-tight text-gray-900 dark:text-gray-100 flex items-center gap-2">
                        FÁCIL SHOP POS
                        <span class="text-xs px-2 py-0.5 bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 font-mono font-bold rounded-md">
                            EN VIVO
                        </span>
                    </h1>
                </div>

                <!-- BADGE DE CAJA ABIERTA -->
                <div class="hidden sm:flex items-center gap-2 px-3 py-1 bg-gray-100 dark:bg-gray-800 rounded-xl text-xs">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    <span class="text-gray-500 font-medium">Cajero:</span>
                    <span class="font-bold text-gray-800 dark:text-gray-200">{{ turno_activo?.usuario?.nombre || 'Cajero' }}</span>
                </div>

                <!-- TASA BCV DINÁMICA -->
                <div class="hidden md:flex items-center gap-2 px-3 py-1 bg-indigo-50 dark:bg-indigo-950/40 border border-indigo-100 dark:border-indigo-900/50 rounded-xl text-xs font-mono">
                    <span class="text-indigo-600 dark:text-indigo-400 font-bold">BCV:</span>
                    <span class="font-bold text-indigo-900 dark:text-indigo-200">1 USD = {{ tasa_ves.toFixed(2) }} Bs.</span>
                </div>
            </div>

            <!-- ATAJOS DE TECLADO Y COLA DE TICKETS -->
            <div class="flex items-center gap-3">
                <div class="hidden lg:flex items-center gap-2 text-xs text-gray-400 font-mono">
                    <span class="px-2 py-0.5 bg-gray-100 dark:bg-gray-800 rounded border border-gray-200 dark:border-gray-700">F2: Buscar</span>
                    <span class="px-2 py-0.5 bg-gray-100 dark:bg-gray-800 rounded border border-gray-200 dark:border-gray-700">F4: Cobrar</span>
                    <span class="px-2 py-0.5 bg-gray-100 dark:bg-gray-800 rounded border border-gray-200 dark:border-gray-700">F8: Pausar</span>
                </div>

                <!-- BOTÓN COLA DE TICKETS (PARKING) -->
                <button
                    type="button"
                    @click="modalTicketsPausadosAbierto = true"
                    class="relative px-3.5 py-1.5 bg-amber-500/10 hover:bg-amber-500/20 text-amber-600 dark:text-amber-400 font-bold rounded-xl text-xs flex items-center gap-1.5 transition border border-amber-500/30"
                >
                    <span>⏸️ Pausados</span>
                    <span
                        v-if="posStore.conteoTicketsPausados > 0"
                        class="px-1.5 py-0.2 bg-amber-500 text-white rounded-full text-[10px] font-black"
                    >
                        {{ posStore.conteoTicketsPausados }}
                    </span>
                    <span class="text-[10px] text-gray-400 font-mono hidden sm:inline">(Shift+F8)</span>
                </button>
            </div>
        </header>

        <!-- ALERTA TOAST FLOTANTE -->
        <div
            v-if="mensajeAlerta"
            :class="[
                'fixed top-16 right-6 z-40 px-4 py-2.5 rounded-2xl shadow-xl border text-xs font-bold transition-all flex items-center gap-2',
                mensajeAlerta.tipo === 'exito'
                    ? 'bg-emerald-600 text-white border-emerald-500'
                    : 'bg-rose-600 text-white border-rose-500'
            ]"
        >
            <span>{{ mensajeAlerta.tipo === 'exito' ? '✓' : '⚠️' }}</span>
            <span>{{ mensajeAlerta.mensaje }}</span>
        </div>

        <!-- CONTENIDO PRINCIPAL: 2 COLUMNAS (CATÁLOGO / CARRITO) -->
        <div class="flex-1 flex flex-col md:flex-row overflow-hidden">

            <!-- COLUMNA IZQUIERDA: CATÁLOGO DE PRODUCTOS -->
            <div class="flex-1 flex flex-col overflow-hidden p-4 space-y-4">

                <!-- BARRA DE BÚSQUEDA Y ESCÁNER DE CÓDIGO DE BARRAS -->
                <div class="flex gap-2">
                    <form @submit.prevent="ProcesarEscaneo" class="flex-1 relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                            🔍
                        </span>
                        <input
                            ref="inputCodigoBarras"
                            type="text"
                            v-model="codigoEscaneado"
                            placeholder="Escanee código de barras (F2) o escriba para buscar..."
                            class="w-full pl-10 pr-4 py-3 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl text-sm font-medium focus:ring-2 focus:ring-indigo-500 focus:border-transparent shadow-sm"
                        />
                    </form>

                    <div class="w-48 hidden sm:block">
                        <input
                            type="text"
                            v-model="busquedaTexto"
                            placeholder="Filtro rápido..."
                            class="w-full py-3 px-4 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl text-xs focus:ring-2 focus:ring-indigo-500"
                        />
                    </div>
                </div>

                <!-- CATEGORÍAS TABS -->
                <div class="flex gap-2 overflow-x-auto pb-1 shrink-0 scrollbar-none">
                    <button
                        type="button"
                        @click="categoriaSeleccionada = ''"
                        :class="[
                            'px-4 py-2 rounded-xl text-xs font-bold transition whitespace-nowrap',
                            categoriaSeleccionada === ''
                                ? 'bg-indigo-600 text-white shadow-sm'
                                : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-50'
                        ]"
                    >
                        Todas las Categorías
                    </button>
                    <button
                        v-for="cat in categorias"
                        :key="cat.id_categoria"
                        type="button"
                        @click="categoriaSeleccionada = cat.id_categoria"
                        :class="[
                            'px-4 py-2 rounded-xl text-xs font-bold transition whitespace-nowrap',
                            categoriaSeleccionada === cat.id_categoria
                                ? 'bg-indigo-600 text-white shadow-sm'
                                : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-50'
                        ]"
                    >
                        {{ cat.nombre }}
                    </button>
                </div>

                <!-- GRID DE TARJETAS DE PRODUCTOS -->
                <div class="flex-1 overflow-y-auto pr-1">
                    <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-3">
                        <div
                            v-for="producto in productosFiltrados"
                            :key="producto.id_producto"
                            @click="AgregarAlCarrito(producto)"
                            :class="[
                                'p-3.5 bg-white dark:bg-gray-800 rounded-2xl border transition shadow-sm cursor-pointer flex flex-col justify-between hover:shadow-md hover:border-indigo-400 dark:hover:border-indigo-500 relative group',
                                producto.stock_actual <= 0 ? 'opacity-60 border-rose-200 dark:border-rose-900/50' : 'border-gray-100 dark:border-gray-700'
                            ]"
                        >
                            <div>
                                <div class="flex justify-between items-start">
                                    <span class="text-[10px] font-mono text-gray-400 uppercase">
                                        {{ producto.sku }}
                                    </span>
                                    <span
                                        :class="[
                                            'px-2 py-0.5 rounded-full text-[10px] font-bold font-mono',
                                            producto.stock_actual <= 0
                                                ? 'bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300'
                                                : (producto.es_stock_bajo ? 'bg-amber-100 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300' : 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300')
                                        ]"
                                    >
                                        {{ producto.stock_actual }} {{ producto.unidad?.abreviatura || 'UND' }}
                                    </span>
                                </div>

                                <div class="font-bold text-gray-900 dark:text-gray-100 text-sm mt-1 line-clamp-2 leading-tight">
                                    {{ producto.nombre }}
                                </div>
                            </div>

                            <div class="mt-3 pt-2 border-t border-gray-100 dark:border-gray-700 flex justify-between items-end">
                                <div>
                                    <div class="text-base font-black font-mono text-indigo-600 dark:text-indigo-400">
                                        ${{ Number(producto.precio_venta).toFixed(2) }}
                                    </div>
                                    <div class="text-[11px] font-mono text-gray-400">
                                        Bs. {{ Number(producto.precio_venta_ves).toFixed(2) }}
                                    </div>
                                </div>

                                <span class="w-7 h-7 rounded-xl bg-indigo-50 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold text-sm group-hover:bg-indigo-600 group-hover:text-white transition">
                                    +
                                </span>
                            </div>
                        </div>

                        <div v-if="productosFiltrados.length === 0" class="col-span-full py-16 text-center text-gray-400 text-xs">
                            No se encontraron productos coincidentes.
                        </div>
                    </div>
                </div>

            </div>

            <!-- COLUMNA DERECHA: TICKET / CARRITO DE COMPRA (POS CHECKOUT) -->
            <div class="w-full md:w-96 lg:w-[420px] bg-white dark:bg-gray-900 border-t md:border-t-0 md:border-l border-gray-200 dark:border-gray-800 flex flex-col justify-between shrink-0 shadow-lg">

                <!-- ENCABEZADO DEL TICKET (CLIENTE Y COMPROBANTE) -->
                <div class="p-4 border-b border-gray-200 dark:border-gray-800 space-y-3 shrink-0">
                    <div class="flex items-center justify-between">
                        <label class="text-xs font-bold text-gray-500 uppercase tracking-wider">
                            Cliente
                        </label>
                        <select
                            :value="posStore.clienteSeleccionado?.id_cliente"
                            @change="posStore.setCliente(clientes.find(c => c.id_cliente === Number($event.target.value)))"
                            class="py-1 px-2.5 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-semibold max-w-[200px]"
                        >
                            <option v-for="c in clientes" :key="c.id_cliente" :value="c.id_cliente">
                                {{ c.nombre }} ({{ c.identificacion }})
                            </option>
                        </select>
                    </div>

                    <div class="flex items-center justify-between">
                        <label class="text-xs font-bold text-gray-500 uppercase tracking-wider">
                            Comprobante
                        </label>
                        <div class="inline-flex p-0.5 bg-gray-100 dark:bg-gray-800 rounded-xl">
                            <button
                                type="button"
                                @click="posStore.setTipoComprobante('TICKET')"
                                :class="[
                                    'px-2.5 py-1 rounded-lg text-xs font-bold transition',
                                    posStore.tipoComprobante === 'TICKET' ? 'bg-indigo-600 text-white' : 'text-gray-500'
                                ]"
                            >
                                Ticket
                            </button>
                            <button
                                type="button"
                                @click="posStore.setTipoComprobante('FACTURA')"
                                :class="[
                                    'px-2.5 py-1 rounded-lg text-xs font-bold transition',
                                    posStore.tipoComprobante === 'FACTURA' ? 'bg-indigo-600 text-white' : 'text-gray-500'
                                ]"
                            >
                                Factura
                            </button>
                        </div>
                    </div>
                </div>

                <!-- LÍNEAS DEL CARRITO -->
                <div class="flex-1 overflow-y-auto p-4 space-y-2.5">
                    <div
                        v-for="(item, index) in posStore.items"
                        :key="item.id_producto + '-' + item.id_unidad"
                        class="p-3 bg-gray-50 dark:bg-gray-800/80 rounded-2xl border border-gray-150 dark:border-gray-700/80 space-y-2"
                    >
                        <div class="flex justify-between items-start gap-2">
                            <div class="min-w-0 flex-1">
                                <div class="font-bold text-gray-900 dark:text-gray-100 text-xs truncate">
                                    {{ item.nombre }}
                                </div>
                                <div class="text-[10px] text-gray-400 font-mono">
                                    ${{ Number(item.precio_unitario).toFixed(2) }} c/u
                                </div>
                            </div>

                            <button
                                type="button"
                                @click="posStore.eliminarItem(index)"
                                class="text-gray-400 hover:text-rose-500 p-0.5 text-xs font-bold"
                            >
                                ✕
                            </button>
                        </div>

                        <!-- CONTROLES DE CANTIDAD, UNIDAD Y SUBTOTAL -->
                        <div class="flex items-center justify-between gap-2 pt-1 border-t border-gray-200/50 dark:border-gray-700/50">
                            <!-- Selector de Unidad Multiescala si tiene secundaria -->
                            <select
                                v-if="item.unidad_secundaria"
                                :value="item.id_unidad"
                                @change="posStore.cambiarUnidad(index, Number($event.target.value))"
                                class="py-1 px-1.5 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-[10px] font-bold"
                            >
                                <option :value="item.id_unidad_principal">
                                    {{ item.unidad_principal?.nombre }} (1x)
                                </option>
                                <option :value="item.id_unidad_secundaria">
                                    {{ item.unidad_secundaria?.nombre }} ({{ item.equivalencia_unidad_secundaria }}x)
                                </option>
                            </select>
                            <span v-else class="text-[10px] font-bold text-gray-400">
                                {{ item.unidad_nombre }}
                            </span>

                            <!-- Contador Stepper -->
                            <div class="flex items-center gap-1 bg-white dark:bg-gray-700 rounded-xl border border-gray-200 dark:border-gray-600 p-0.5">
                                <button
                                    type="button"
                                    @click="posStore.modificarCantidad(index, item.cantidad - 1)"
                                    class="w-6 h-6 rounded-lg bg-gray-100 dark:bg-gray-600 text-gray-700 dark:text-gray-200 font-bold text-xs flex items-center justify-center"
                                >
                                    -
                                </button>
                                <input
                                    type="number"
                                    min="1"
                                    :value="item.cantidad"
                                    @input="posStore.modificarCantidad(index, Number($event.target.value))"
                                    class="w-10 py-0.5 text-center bg-transparent border-0 text-xs font-mono font-bold focus:ring-0 p-0"
                                />
                                <button
                                    type="button"
                                    @click="posStore.modificarCantidad(index, item.cantidad + 1)"
                                    class="w-6 h-6 rounded-lg bg-gray-100 dark:bg-gray-600 text-gray-700 dark:text-gray-200 font-bold text-xs flex items-center justify-center"
                                >
                                    +
                                </button>
                            </div>

                            <div class="font-mono font-bold text-sm text-gray-900 dark:text-gray-100 text-right">
                                ${{ item.subtotal.toFixed(2) }}
                            </div>
                        </div>
                    </div>

                    <div v-if="posStore.items.length === 0" class="py-16 text-center text-gray-400 text-xs space-y-2">
                        <div class="text-3xl">🛒</div>
                        <div>El carrito está vacío.</div>
                        <div class="text-[10px] text-gray-400">Escanee un producto o selecciónelo del catálogo.</div>
                    </div>
                </div>

                <!-- TOTALES Y ACCIONES DE COBRO -->
                <div class="p-4 bg-gray-50 dark:bg-gray-800/60 border-t border-gray-200 dark:border-gray-800 space-y-3 shrink-0">
                    <div class="space-y-1.5 text-xs">
                        <div class="flex justify-between text-gray-500">
                            <span>Subtotal ({{ posStore.totalItems }} items):</span>
                            <span class="font-mono font-bold">${{ posStore.subtotal.toFixed(2) }}</span>
                        </div>
                        <div v-if="posStore.descuentoTotal > 0" class="flex justify-between text-rose-500">
                            <span>Descuento:</span>
                            <span class="font-mono font-bold">-${{ posStore.descuentoTotal.toFixed(2) }}</span>
                        </div>
                        <div class="flex justify-between items-baseline pt-2 border-t border-gray-200 dark:border-gray-700">
                            <span class="text-sm font-black text-gray-900 dark:text-gray-100">TOTAL USD:</span>
                            <span class="text-2xl font-black font-mono text-indigo-600 dark:text-indigo-400">
                                ${{ posStore.totalUsd.toFixed(2) }}
                            </span>
                        </div>
                        <div class="flex justify-between text-xs font-mono font-bold text-gray-500">
                            <span>TOTAL BS:</span>
                            <span>Bs. {{ posStore.totalVes.toFixed(2) }}</span>
                        </div>
                    </div>

                    <!-- BOTONES DE ACCIÓN PRINCIPALES -->
                    <div class="grid grid-cols-4 gap-2 pt-1">
                        <button
                            type="button"
                            @click="posStore.limpiarCarrito"
                            :disabled="posStore.items.length === 0"
                            class="col-span-1 py-3 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-2xl text-xs font-bold transition disabled:opacity-40"
                            title="Limpiar carrito"
                        >
                            Limpiar
                        </button>

                        <button
                            type="button"
                            @click="PausarVentaActual"
                            :disabled="posStore.items.length === 0"
                            class="col-span-1 py-3 bg-amber-100 hover:bg-amber-200 dark:bg-amber-950/60 dark:hover:bg-amber-900/60 text-amber-700 dark:text-amber-300 rounded-2xl text-xs font-bold transition disabled:opacity-40"
                            title="Pausar ticket en cola (F8)"
                        >
                            F8 Pausar
                        </button>

                        <button
                            type="button"
                            @click="AbrirCobro"
                            :disabled="posStore.items.length === 0"
                            class="col-span-2 py-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-2xl text-sm font-black transition shadow-lg shadow-emerald-600/20 disabled:opacity-40 flex items-center justify-center gap-1.5"
                        >
                            <span>COBRAR (F4)</span>
                        </button>
                    </div>
                </div>

            </div>

        </div>

        <!-- MODAL MULTIPAGO Y CASHEA (F4) -->
        <ModalCobro
            v-if="modalCobroAbierto"
            :metodos-pago="metodos_pago"
            :monedas="monedas"
            :tasa-ves="tasa_ves"
            :cliente="posStore.clienteSeleccionado"
            @cerrar="modalCobroAbierto = false"
            @venta-completada="OnVentaCompletada"
        />

        <!-- MODAL TICKETS PAUSADOS (SHIFT + F8) -->
        <ModalTicketsPausados
            v-if="modalTicketsPausadosAbierto"
            @cerrar="modalTicketsPausadosAbierto = false"
        />

        <!-- MODAL TICKET IMPRIMIBLE TRAS VENTA EXITOSA -->
        <TicketImprimible
            v-if="ventaRecienteParaTicket"
            :venta="ventaRecienteParaTicket"
            :tasa-ves="tasa_ves"
            @cerrar="ventaRecienteParaTicket = null"
        />

    </div>
</template>
