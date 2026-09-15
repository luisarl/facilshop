<script setup>
import { usePosStore } from '@/Stores/usePosStore';

const emit = defineEmits(['cerrar']);
const posStore = usePosStore();

const Recuperar = (ticketId) => {
    posStore.recuperarTicket(ticketId);
    emit('cerrar');
};

const Descartar = (ticketId) => {
    if (confirm('¿Está seguro de descartar este ticket pausado?')) {
        posStore.eliminarTicketPausado(ticketId);
    }
};
</script>

<template>
    <div class="fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-800 rounded-3xl max-w-xl w-full p-6 shadow-2xl border border-gray-100 dark:border-gray-700 space-y-4">
            <div class="flex justify-between items-center pb-3 border-b border-gray-100 dark:border-gray-700">
                <div class="flex items-center gap-2">
                    <span class="p-2 bg-amber-100 dark:bg-amber-900/50 text-amber-600 dark:text-amber-400 rounded-xl">
                        ⏸️
                    </span>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">
                            Tickets Pausados en Cola (Parking)
                        </h3>
                        <p class="text-xs text-gray-500">
                            Ventas puestas en espera para reanudar la atención
                        </p>
                    </div>
                </div>

                <button @click="emit('cerrar')" class="text-gray-400 hover:text-gray-500 text-xl font-bold">
                    ✕
                </button>
            </div>

            <div class="max-h-96 overflow-y-auto space-y-3 pr-1">
                <div
                    v-for="ticket in posStore.ticketsPausados"
                    :key="ticket.id"
                    class="p-4 bg-gray-50 dark:bg-gray-750 rounded-2xl border border-gray-200 dark:border-gray-700 hover:border-indigo-400 transition space-y-3"
                >
                    <div class="flex justify-between items-start">
                        <div>
                            <div class="font-bold text-gray-900 dark:text-gray-100 text-sm">
                                {{ ticket.cliente?.nombre || 'Cliente Mostrador' }}
                            </div>
                            <div class="text-[11px] text-gray-400 font-mono mt-0.5">
                                Pausado: {{ new Date(ticket.fecha).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) }} | {{ ticket.items.length }} productos
                            </div>
                        </div>

                        <div class="text-right">
                            <div class="text-base font-black font-mono text-indigo-600 dark:text-indigo-400">
                                ${{ ticket.totalUsd.toFixed(2) }}
                            </div>
                            <div class="text-[10px] text-gray-400 font-mono">
                                Bs. {{ ticket.totalVes.toFixed(2) }}
                            </div>
                        </div>
                    </div>

                    <!-- Resumen de items -->
                    <div class="text-xs text-gray-500 dark:text-gray-400 bg-white/60 dark:bg-gray-800/60 p-2 rounded-xl border border-gray-100 dark:border-gray-700/60">
                        <span v-for="(it, idx) in ticket.items.slice(0, 3)" :key="it.id_producto">
                            {{ it.cantidad }}x {{ it.nombre }}<span v-if="idx < Math.min(2, ticket.items.length - 1)">, </span>
                        </span>
                        <span v-if="ticket.items.length > 3" class="font-semibold text-gray-400">
                            (+{{ ticket.items.length - 3 }} más...)
                        </span>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-1">
                        <button
                            type="button"
                            @click="Descartar(ticket.id)"
                            class="px-3 py-1.5 text-xs text-rose-500 hover:text-rose-700 font-semibold"
                        >
                            Descartar
                        </button>
                        <button
                            type="button"
                            @click="Recuperar(ticket.id)"
                            class="px-4 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl transition"
                        >
                            Recuperar al Carrito
                        </button>
                    </div>
                </div>

                <div v-if="posStore.ticketsPausados.length === 0" class="py-12 text-center text-gray-400 text-xs">
                    No hay tickets pausados en espera. Presione <kbd class="px-1.5 py-0.5 bg-gray-100 dark:bg-gray-700 rounded font-mono font-bold">F8</kbd> para pausar la venta actual.
                </div>
            </div>

            <div class="pt-3 border-t border-gray-100 dark:border-gray-700 text-right">
                <button
                    type="button"
                    @click="emit('cerrar')"
                    class="px-4 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl text-xs font-semibold"
                >
                    Cerrar (Esc)
                </button>
            </div>
        </div>
    </div>
</template>
