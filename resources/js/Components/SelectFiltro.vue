<script setup>
import { ref, computed, watch, nextTick, onMounted, onBeforeUnmount } from 'vue';

const props = defineProps({
    modelValue: {
        type: [String, Number, Boolean, Object, null],
        default: null
    },
    options: {
        type: Array,
        default: () => []
    },
    placeholder: {
        type: String,
        default: 'Seleccione una opción...'
    },
    searchPlaceholder: {
        type: String,
        default: 'Escriba para filtrar...'
    },
    valueKey: {
        type: String,
        default: ''
    },
    labelKey: {
        type: String,
        default: ''
    },
    disabled: {
        type: Boolean,
        default: false
    },
    permitirLimpiar: {
        type: Boolean,
        default: true
    },
    claseBoton: {
        type: String,
        default: ''
    },
    requerido: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['update:modelValue', 'change', 'abrir', 'cerrar']);

const abierto = ref(false);
const busqueda = ref('');
const indiceResaltado = ref(-1);
const contenedorRef = ref(null);
const inputBusquedaRef = ref(null);
const listaOpcionesRef = ref(null);

const NormalizarTexto = (texto) =>
{
    if (texto === null || texto === undefined)
    {
        return '';
    }
    return String(texto)
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '');
};

const ObtenerValorOpcion = (opcion) =>
{
    if (opcion === null || opcion === undefined)
    {
        return null;
    }
    if (typeof opcion !== 'object')
    {
        return opcion;
    }
    if (props.valueKey && opcion[props.valueKey] !== undefined)
    {
        return opcion[props.valueKey];
    }
    if (opcion.value !== undefined)
    {
        return opcion.value;
    }
    if (opcion.id !== undefined)
    {
        return opcion.id;
    }
    if (opcion.codigo !== undefined)
    {
        return opcion.codigo;
    }
    return opcion;
};

const ObtenerEtiquetaOpcion = (opcion) =>
{
    if (opcion === null || opcion === undefined)
    {
        return '';
    }
    if (typeof opcion !== 'object')
    {
        return String(opcion);
    }
    if (props.labelKey && opcion[props.labelKey] !== undefined)
    {
        return String(opcion[props.labelKey]);
    }
    if (opcion.label !== undefined)
    {
        return String(opcion.label);
    }
    if (opcion.nombre !== undefined)
    {
        return String(opcion.nombre);
    }
    if (opcion.name !== undefined)
    {
        return String(opcion.name);
    }
    if (opcion.descripcion !== undefined)
    {
        return String(opcion.descripcion);
    }
    if (opcion.titulo !== undefined)
    {
        return String(opcion.titulo);
    }
    return String(ObtenerValorOpcion(opcion));
};

const OpcionesNormalizadas = computed(() =>
{
    return props.options.map((item, index) =>
    {
        const valorItem = ObtenerValorOpcion(item);
        const etiquetaItem = ObtenerEtiquetaOpcion(item);
        return {
            indiceOriginal: index,
            itemOriginal: item,
            valor: valorItem,
            etiqueta: etiquetaItem
        };
    });
});

const OpcionesFiltradas = computed(() =>
{
    const termino = NormalizarTexto(busqueda.value);
    if (!termino)
    {
        return OpcionesNormalizadas.value;
    }
    return OpcionesNormalizadas.value.filter((op) =>
    {
        return NormalizarTexto(op.etiqueta).includes(termino) ||
               NormalizarTexto(op.valor).includes(termino);
    });
});

const OpcionSeleccionada = computed(() =>
{
    if (props.modelValue === null || props.modelValue === undefined || props.modelValue === '')
    {
        return null;
    }
    return OpcionesNormalizadas.value.find((op) =>
    {
        return String(op.valor) === String(props.modelValue);
    }) || null;
});

const EtiquetaActual = computed(() =>
{
    if (OpcionSeleccionada.value)
    {
        return OpcionSeleccionada.value.etiqueta;
    }
    return props.placeholder;
});

const AlternarDesplegable = () =>
{
    if (props.disabled)
    {
        return;
    }
    if (abierto.value)
    {
        CerrarDesplegable();
    }
    else
    {
        AbrirDesplegable();
    }
};

const AbrirDesplegable = () =>
{
    if (props.disabled)
    {
        return;
    }
    abierto.value = true;
    busqueda.value = '';
    indiceResaltado.value = -1;
    emit('abrir');

    nextTick(() =>
    {
        if (inputBusquedaRef.value)
        {
            inputBusquedaRef.value.focus();
        }
    });
};

const CerrarDesplegable = () =>
{
    if (!abierto.value)
    {
        return;
    }
    abierto.value = false;
    busqueda.value = '';
    indiceResaltado.value = -1;
    emit('cerrar');
};

const SeleccionarOpcion = (opcion) =>
{
    const nuevoValor = opcion ? opcion.valor : null;
    emit('update:modelValue', nuevoValor);
    emit('change', nuevoValor, opcion ? opcion.itemOriginal : null);
    CerrarDesplegable();
};

const LimpiarSeleccion = (evento) =>
{
    evento.stopPropagation();
    emit('update:modelValue', null);
    emit('change', null, null);
};

const ManejarTeclas = (evento) =>
{
    if (!abierto.value)
    {
        if (evento.key === 'ArrowDown' || evento.key === 'Enter')
        {
            evento.preventDefault();
            AbrirDesplegable();
        }
        return;
    }

    const cantidad = OpcionesFiltradas.value.length;
    if (cantidad === 0)
    {
        if (evento.key === 'Escape')
        {
            CerrarDesplegable();
        }
        return;
    }

    if (evento.key === 'ArrowDown')
    {
        evento.preventDefault();
        indiceResaltado.value = (indiceResaltado.value + 1) % cantidad;
        AsegurarVisibilidadOpcion();
    }
    else if (evento.key === 'ArrowUp')
    {
        evento.preventDefault();
        indiceResaltado.value = (indiceResaltado.value - 1 + cantidad) % cantidad;
        AsegurarVisibilidadOpcion();
    }
    else if (evento.key === 'Enter')
    {
        evento.preventDefault();
        if (indiceResaltado.value >= 0 && indiceResaltado.value < cantidad)
        {
            SeleccionarOpcion(OpcionesFiltradas.value[indiceResaltado.value]);
        }
    }
    else if (evento.key === 'Escape')
    {
        evento.preventDefault();
        CerrarDesplegable();
    }
};

const AsegurarVisibilidadOpcion = () =>
{
    nextTick(() =>
    {
        if (!listaOpcionesRef.value)
        {
            return;
        }
        const elementoActivo = listaOpcionesRef.value.children[indiceResaltado.value];
        if (elementoActivo)
        {
            elementoActivo.scrollIntoView({ block: 'nearest' });
        }
    });
};

const ManejarClickFuera = (evento) =>
{
    if (contenedorRef.value && !contenedorRef.value.contains(evento.target))
    {
        CerrarDesplegable();
    }
};

onMounted(() =>
{
    document.addEventListener('click', ManejarClickFuera);
});

onBeforeUnmount(() =>
{
    document.removeEventListener('click', ManejarClickFuera);
});

watch(busqueda, () =>
{
    indiceResaltado.value = 0;
});
</script>

<template>
    <div
        ref="contenedorRef"
        class="relative select-none text-left w-full"
        @keydown="ManejarTeclas"
    >
        <!-- Boton disparador del Select2 -->
        <button
            type="button"
            :disabled="disabled"
            @click="AlternarDesplegable"
            :class="[
                'w-full flex items-center justify-between text-left px-3 py-2 text-xs rounded-lg border transition-all shadow-xs outline-hidden focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500',
                disabled ? 'bg-gray-100 dark:bg-gray-800 text-gray-400 cursor-not-allowed border-gray-300 dark:border-gray-700' : 'bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-650 cursor-pointer border-gray-300 dark:border-gray-600',
                abierto ? 'ring-2 ring-indigo-500 border-indigo-500' : '',
                claseBoton
            ]"
        >
            <span
                :class="[
                    'truncate pr-2',
                    OpcionSeleccionada ? 'text-gray-900 dark:text-white font-medium' : 'text-gray-400 dark:text-gray-400'
                ]"
            >
                {{ EtiquetaActual }}
            </span>

            <div class="flex items-center gap-1.5 shrink-0 text-gray-400">
                <!-- Boton Limpiar -->
                <span
                    v-if="permitirLimpiar && OpcionSeleccionada && !disabled"
                    @click="LimpiarSeleccion"
                    class="hover:text-red-500 transition-colors p-0.5 rounded cursor-pointer"
                    title="Limpiar selección"
                >
                    ✕
                </span>

                <!-- Flecha desplegable -->
                <svg
                    class="w-4 h-4 transition-transform duration-200"
                    :class="{ 'rotate-180 text-indigo-500': abierto }"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </div>
        </button>

        <!-- Menu Desplegable Flotante tipo Select2 -->
        <div
            v-if="abierto"
            class="absolute left-0 mt-1 w-full bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 z-50 overflow-hidden text-xs"
        >
            <!-- Cuadro de Busqueda Integrado -->
            <div class="p-2 border-b border-gray-100 dark:border-gray-700 bg-gray-50/70 dark:bg-gray-900/50">
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-gray-400">
                        🔍
                    </span>
                    <input
                        ref="inputBusquedaRef"
                        v-model="busqueda"
                        type="text"
                        :placeholder="searchPlaceholder"
                        class="w-full pl-8 pr-3 py-1.5 text-xs bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-md border border-gray-300 dark:border-gray-600 focus:outline-hidden focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                    />
                </div>
            </div>

            <!-- Lista de Resultados Filtrables -->
            <ul
                ref="listaOpcionesRef"
                class="max-h-56 overflow-y-auto divide-y divide-gray-100/50 dark:divide-gray-700/50 py-1"
                role="listbox"
            >
                <li
                    v-for="(opcion, index) in OpcionesFiltradas"
                    :key="String(opcion.valor) + '_' + index"
                    @click="SeleccionarOpcion(opcion)"
                    @mouseenter="indiceResaltado = index"
                    :class="[
                        'px-3 py-2 cursor-pointer flex items-center justify-between transition-colors',
                        indiceResaltado === index ? 'bg-indigo-50 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300' : 'text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700/50',
                        String(opcion.valor) === String(modelValue) ? 'font-bold bg-indigo-50/50 dark:bg-indigo-950/30 text-indigo-600 dark:text-indigo-400' : ''
                    ]"
                    role="option"
                    :aria-selected="String(opcion.valor) === String(modelValue)"
                >
                    <span class="truncate">{{ opcion.etiqueta }}</span>
                    <span
                        v-if="String(opcion.valor) === String(modelValue)"
                        class="text-indigo-600 dark:text-indigo-400 font-bold ml-2 text-xs"
                    >
                        ✓
                    </span>
                </li>

                <!-- Mensaje cuando no hay resultados coincidentes -->
                <li
                    v-if="OpcionesFiltradas.length === 0"
                    class="px-4 py-4 text-center text-gray-400 dark:text-gray-500 italic"
                >
                    No se encontraron coincidencias para "{{ busqueda }}"
                </li>
            </ul>
        </div>
    </div>
</template>
