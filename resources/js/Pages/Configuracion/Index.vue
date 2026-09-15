<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    usuarios: Array,
    categorias: Array,
    marcas: Array,
    monedas: Array,
    historico_tasas: Array,
    kpis: Object,
    tabActivo: String,
});

// Pestaña Activa
const tabActual = ref(props.tabActivo || 'usuarios');

const CambiarTab = (tab) => {
    tabActual.value = tab;
    router.get(route('configuracion.index'), { tab }, { preserveState: true, replace: true });
};

// ==============================
// 1. GESTIÓN DE USUARIOS
// ==============================
const buscarUsuario = ref('');
const modalUsuarioAbierto = ref(false);
const usuarioEditando = ref(null);

const formUsuario = useForm({
    nombre: '',
    email: '',
    rol: 'cajero',
    password: '',
    activo: true,
});

const usuariosFiltrados = computed(() => {
    if (!buscarUsuario.value) return props.usuarios;
    const term = buscarUsuario.value.toLowerCase();
    return props.usuarios.filter(u =>
        u.name?.toLowerCase().includes(term) ||
        u.email?.toLowerCase().includes(term) ||
        u.rol?.toLowerCase().includes(term)
    );
});

const AbrirModalNuevoUsuario = () => {
    usuarioEditando.value = null;
    formUsuario.reset();
    formUsuario.clearErrors();
    formUsuario.rol = 'cajero';
    formUsuario.activo = true;
    modalUsuarioAbierto.value = true;
};

const AbrirModalEditarUsuario = (usuario) => {
    usuarioEditando.value = usuario;
    formUsuario.nombre = usuario.name;
    formUsuario.email = usuario.email;
    formUsuario.rol = usuario.rol;
    formUsuario.password = '';
    formUsuario.activo = Boolean(usuario.activo);
    formUsuario.clearErrors();
    modalUsuarioAbierto.value = true;
};

const GuardarUsuario = () => {
    if (usuarioEditando.value) {
        formUsuario.put(route('configuracion.usuarios.update', usuarioEditando.value.id_usuario), {
            onSuccess: () => {
                modalUsuarioAbierto.value = false;
            },
        });
    } else {
        formUsuario.post(route('configuracion.usuarios.store'), {
            onSuccess: () => {
                modalUsuarioAbierto.value = false;
            },
        });
    }
};

const AlternarEstadoUsuario = (usuario) => {
    router.patch(route('configuracion.usuarios.estado', usuario.id_usuario), {}, {
        preserveScroll: true,
    });
};

const BadgeRol = (rol) => {
    switch (rol) {
        case 'superadmin':
            return 'bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300 border-purple-200';
        case 'admin':
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300 border-blue-200';
        default:
            return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300 border-emerald-200';
    }
};

// ==============================
// 2. GESTIÓN DE CATEGORÍAS
// ==============================
const buscarCategoria = ref('');
const modalCategoriaAbierto = ref(false);
const categoriaEditando = ref(null);

const formCategoria = useForm({
    nombre: '',
    descripcion: '',
    id_categoria_padre: '',
    activo: true,
});

const categoriasFiltradas = computed(() => {
    if (!buscarCategoria.value) return props.categorias;
    const term = buscarCategoria.value.toLowerCase();
    return props.categorias.filter(c =>
        c.nombre?.toLowerCase().includes(term) ||
        c.descripcion?.toLowerCase().includes(term)
    );
});

const AbrirModalNuevaCategoria = () => {
    categoriaEditando.value = null;
    formCategoria.reset();
    formCategoria.clearErrors();
    formCategoria.id_categoria_padre = '';
    formCategoria.activo = true;
    modalCategoriaAbierto.value = true;
};

const AbrirModalEditarCategoria = (cat) => {
    categoriaEditando.value = cat;
    formCategoria.nombre = cat.nombre;
    formCategoria.descripcion = cat.descripcion || '';
    formCategoria.id_categoria_padre = cat.id_categoria_padre || '';
    formCategoria.activo = Boolean(cat.activo);
    formCategoria.clearErrors();
    modalCategoriaAbierto.value = true;
};

const GuardarCategoria = () => {
    if (categoriaEditando.value) {
        formCategoria.put(route('configuracion.categorias.update', categoriaEditando.value.id_categoria), {
            onSuccess: () => {
                modalCategoriaAbierto.value = false;
            },
        });
    } else {
        formCategoria.post(route('configuracion.categorias.store'), {
            onSuccess: () => {
                modalCategoriaAbierto.value = false;
            },
        });
    }
};

const AlternarEstadoCategoria = (cat) => {
    router.patch(route('configuracion.categorias.estado', cat.id_categoria), {}, {
        preserveScroll: true,
    });
};

// ==============================
// 3. GESTIÓN DE MARCAS
// ==============================
const buscarMarca = ref('');
const modalMarcaAbierto = ref(false);
const marcaEditando = ref(null);

const formMarca = useForm({
    nombre: '',
    descripcion: '',
    activo: true,
});

const marcasFiltradas = computed(() => {
    if (!buscarMarca.value) return props.marcas;
    const term = buscarMarca.value.toLowerCase();
    return props.marcas.filter(m =>
        m.nombre?.toLowerCase().includes(term) ||
        m.descripcion?.toLowerCase().includes(term)
    );
});

const AbrirModalNuevaMarca = () => {
    marcaEditando.value = null;
    formMarca.reset();
    formMarca.clearErrors();
    formMarca.activo = true;
    modalMarcaAbierto.value = true;
};

const AbrirModalEditarMarca = (marca) => {
    marcaEditando.value = marca;
    formMarca.nombre = marca.nombre;
    formMarca.descripcion = marca.descripcion || '';
    formMarca.activo = Boolean(marca.activo);
    formMarca.clearErrors();
    modalMarcaAbierto.value = true;
};

const GuardarMarca = () => {
    if (marcaEditando.value) {
        formMarca.put(route('configuracion.marcas.update', marcaEditando.value.id_marca), {
            onSuccess: () => {
                modalMarcaAbierto.value = false;
            },
        });
    } else {
        formMarca.post(route('configuracion.marcas.store'), {
            onSuccess: () => {
                modalMarcaAbierto.value = false;
            },
        });
    }
};

const AlternarEstadoMarca = (marca) => {
    router.patch(route('configuracion.marcas.estado', marca.id_marca), {}, {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Centro de Configuración" />

    <AuthenticatedLayout>
        <div class="py-6 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto space-y-6">

            <!-- Cabecera Principal -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <span>⚙️</span> Centro de Configuración del Sistema
                    </h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Administración centralizada de usuarios, clasificación por categorías, marcas y divisas.
                    </p>
                </div>
            </div>

            <!-- Barra de Pestañas de Navegación -->
            <div class="border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 rounded-xl px-4 shadow-sm">
                <nav class="flex space-x-4 sm:space-x-8 overflow-x-auto py-2" aria-label="Tabs">
                    <button
                        @click="CambiarTab('usuarios')"
                        :class="[
                            'flex items-center gap-2 py-3 px-1 border-b-2 font-semibold text-xs sm:text-sm whitespace-nowrap transition',
                            tabActual === 'usuarios'
                                ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:hover:text-gray-300'
                        ]"
                    >
                        <span>👤</span> Usuarios y Roles
                        <span class="px-2 py-0.5 text-xs rounded-full bg-indigo-50 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400 font-mono">
                            {{ kpis.total_usuarios }}
                        </span>
                    </button>

                    <button
                        @click="CambiarTab('categorias')"
                        :class="[
                            'flex items-center gap-2 py-3 px-1 border-b-2 font-semibold text-xs sm:text-sm whitespace-nowrap transition',
                            tabActual === 'categorias'
                                ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:hover:text-gray-300'
                        ]"
                    >
                        <span>🏷️</span> Categorías
                        <span class="px-2 py-0.5 text-xs rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 font-mono">
                            {{ kpis.total_categorias }}
                        </span>
                    </button>

                    <button
                        @click="CambiarTab('marcas')"
                        :class="[
                            'flex items-center gap-2 py-3 px-1 border-b-2 font-semibold text-xs sm:text-sm whitespace-nowrap transition',
                            tabActual === 'marcas'
                                ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:hover:text-gray-300'
                        ]"
                    >
                        <span>🏢</span> Marcas
                        <span class="px-2 py-0.5 text-xs rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 font-mono">
                            {{ kpis.total_marcas }}
                        </span>
                    </button>

                    <button
                        @click="CambiarTab('monedas')"
                        :class="[
                            'flex items-center gap-2 py-3 px-1 border-b-2 font-semibold text-xs sm:text-sm whitespace-nowrap transition',
                            tabActual === 'monedas'
                                ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:hover:text-gray-300'
                        ]"
                    >
                        <span>💵</span> Monedas y Divisas
                        <span class="px-2 py-0.5 text-xs rounded-full bg-emerald-50 dark:bg-emerald-900/40 text-emerald-600 dark:text-emerald-400 font-mono font-bold">
                            {{ kpis.total_monedas }}
                        </span>
                    </button>
                </nav>
            </div>

            <!-- ============================================== -->
            <!-- 1. PESTAÑA: USUARIOS Y ROLES -->
            <!-- ============================================== -->
            <div v-if="tabActual === 'usuarios'" class="space-y-4">
                <div class="flex flex-col sm:flex-row justify-between items-center gap-3">
                    <div class="relative w-full sm:w-80">
                        <input
                            v-model="buscarUsuario"
                            type="text"
                            placeholder="Buscar por nombre, correo o rol..."
                            class="w-full pl-9 pr-4 py-2 text-xs rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        />
                        <span class="absolute left-3 top-2.5 text-gray-400 text-xs">🔍</span>
                    </div>

                    <button
                        @click="AbrirModalNuevoUsuario"
                        class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg shadow-sm flex items-center gap-1.5 transition"
                    >
                        + Nuevo Usuario
                    </button>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs text-gray-600 dark:text-gray-300">
                            <thead class="bg-gray-50 dark:bg-gray-900/50 uppercase text-[10px] text-gray-500 font-bold border-b border-gray-200 dark:border-gray-700">
                                <tr>
                                    <th class="px-5 py-3.5">Usuario / Nombre</th>
                                    <th class="px-5 py-3.5">Correo Electrónico</th>
                                    <th class="px-5 py-3.5 text-center">Rol Asignado</th>
                                    <th class="px-5 py-3.5 text-center">Turnos Caja</th>
                                    <th class="px-5 py-3.5 text-center">Movimientos Inv.</th>
                                    <th class="px-5 py-3.5 text-center">Estado</th>
                                    <th class="px-5 py-3.5 text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                <tr
                                    v-for="u in usuariosFiltrados"
                                    :key="u.id_usuario"
                                    class="hover:bg-gray-50/70 dark:hover:bg-gray-700/40 transition"
                                >
                                    <td class="px-5 py-3.5">
                                        <div class="font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                            <span class="w-7 h-7 rounded-full bg-indigo-100 dark:bg-indigo-900/60 text-indigo-700 dark:text-indigo-300 flex items-center justify-center font-bold text-xs uppercase">
                                                {{ u.name?.substring(0, 2) }}
                                            </span>
                                            {{ u.name }}
                                        </div>
                                    </td>
                                    <td class="px-5 py-3.5 font-mono text-gray-600 dark:text-gray-300">
                                        {{ u.email }}
                                    </td>
                                    <td class="px-5 py-3.5 text-center">
                                        <span :class="['px-2.5 py-0.5 rounded-full text-[10px] font-bold border uppercase tracking-wider', BadgeRol(u.rol)]">
                                            {{ u.rol }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-3.5 text-center font-mono font-bold text-gray-700 dark:text-gray-300">
                                        {{ u.caja_turnos_count || 0 }}
                                    </td>
                                    <td class="px-5 py-3.5 text-center font-mono font-bold text-gray-700 dark:text-gray-300">
                                        {{ u.movimientos_inventario_count || 0 }}
                                    </td>
                                    <td class="px-5 py-3.5 text-center">
                                        <button
                                            @click="AlternarEstadoUsuario(u)"
                                            :title="u.activo ? 'Clic para desactivar' : 'Clic para activar'"
                                            :class="[
                                                'inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold transition cursor-pointer',
                                                u.activo
                                                    ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300 hover:bg-emerald-200'
                                                    : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400 hover:bg-gray-200'
                                            ]"
                                        >
                                            {{ u.activo ? 'Activo' : 'Inactivo' }}
                                        </button>
                                    </td>
                                    <td class="px-5 py-3.5 text-right whitespace-nowrap">
                                        <button
                                            @click="AbrirModalEditarUsuario(u)"
                                            class="px-2.5 py-1 text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 rounded-lg text-xs font-semibold transition"
                                        >
                                            Editar
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ============================================== -->
            <!-- 2. PESTAÑA: CATEGORÍAS -->
            <!-- ============================================== -->
            <div v-if="tabActual === 'categorias'" class="space-y-4">
                <div class="flex flex-col sm:flex-row justify-between items-center gap-3">
                    <div class="relative w-full sm:w-80">
                        <input
                            v-model="buscarCategoria"
                            type="text"
                            placeholder="Buscar categoría..."
                            class="w-full pl-9 pr-4 py-2 text-xs rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        />
                        <span class="absolute left-3 top-2.5 text-gray-400 text-xs">🔍</span>
                    </div>

                    <button
                        @click="AbrirModalNuevaCategoria"
                        class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg shadow-sm flex items-center gap-1.5 transition"
                    >
                        + Nueva Categoría
                    </button>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs text-gray-600 dark:text-gray-300">
                            <thead class="bg-gray-50 dark:bg-gray-900/50 uppercase text-[10px] text-gray-500 font-bold border-b border-gray-200 dark:border-gray-700">
                                <tr>
                                    <th class="px-5 py-3.5">Categoría</th>
                                    <th class="px-5 py-3.5">Categoría Padre</th>
                                    <th class="px-5 py-3.5">Descripción</th>
                                    <th class="px-5 py-3.5 text-center">Productos</th>
                                    <th class="px-5 py-3.5 text-center">Estado</th>
                                    <th class="px-5 py-3.5 text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                <tr
                                    v-for="c in categoriasFiltradas"
                                    :key="c.id_categoria"
                                    class="hover:bg-gray-50/70 dark:hover:bg-gray-700/40 transition"
                                >
                                    <td class="px-5 py-3.5 font-bold text-gray-900 dark:text-white">
                                        {{ c.nombre }}
                                    </td>
                                    <td class="px-5 py-3.5">
                                        <span v-if="c.padre" class="px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-[11px]">
                                            📁 {{ c.padre.nombre }}
                                        </span>
                                        <span v-else class="text-gray-400 italic text-[11px]">Categoría Principal</span>
                                    </td>
                                    <td class="px-5 py-3.5 text-gray-500 max-w-xs truncate">
                                        {{ c.descripcion || 'Sin descripción' }}
                                    </td>
                                    <td class="px-5 py-3.5 text-center font-mono font-bold text-indigo-600 dark:text-indigo-400">
                                        {{ c.productos_count || 0 }}
                                    </td>
                                    <td class="px-5 py-3.5 text-center">
                                        <button
                                            @click="AlternarEstadoCategoria(c)"
                                            :class="[
                                                'inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold transition cursor-pointer',
                                                c.activo
                                                    ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300 hover:bg-emerald-200'
                                                    : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400 hover:bg-gray-200'
                                            ]"
                                        >
                                            {{ c.activo ? 'Activa' : 'Inactiva' }}
                                        </button>
                                    </td>
                                    <td class="px-5 py-3.5 text-right whitespace-nowrap">
                                        <button
                                            @click="AbrirModalEditarCategoria(c)"
                                            class="px-2.5 py-1 text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 rounded-lg text-xs font-semibold transition"
                                        >
                                            Editar
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ============================================== -->
            <!-- 3. PESTAÑA: MARCAS -->
            <!-- ============================================== -->
            <div v-if="tabActual === 'marcas'" class="space-y-4">
                <div class="flex flex-col sm:flex-row justify-between items-center gap-3">
                    <div class="relative w-full sm:w-80">
                        <input
                            v-model="buscarMarca"
                            type="text"
                            placeholder="Buscar marca..."
                            class="w-full pl-9 pr-4 py-2 text-xs rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        />
                        <span class="absolute left-3 top-2.5 text-gray-400 text-xs">🔍</span>
                    </div>

                    <button
                        @click="AbrirModalNuevaMarca"
                        class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg shadow-sm flex items-center gap-1.5 transition"
                    >
                        + Nueva Marca
                    </button>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs text-gray-600 dark:text-gray-300">
                            <thead class="bg-gray-50 dark:bg-gray-900/50 uppercase text-[10px] text-gray-500 font-bold border-b border-gray-200 dark:border-gray-700">
                                <tr>
                                    <th class="px-5 py-3.5">Marca Comercial</th>
                                    <th class="px-5 py-3.5">Descripción / Fabricante</th>
                                    <th class="px-5 py-3.5 text-center">Productos Asociados</th>
                                    <th class="px-5 py-3.5 text-center">Estado</th>
                                    <th class="px-5 py-3.5 text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                <tr
                                    v-for="m in marcasFiltradas"
                                    :key="m.id_marca"
                                    class="hover:bg-gray-50/70 dark:hover:bg-gray-700/40 transition"
                                >
                                    <td class="px-5 py-3.5 font-bold text-gray-900 dark:text-white">
                                        {{ m.nombre }}
                                    </td>
                                    <td class="px-5 py-3.5 text-gray-500 max-w-sm truncate">
                                        {{ m.descripcion || 'Sin descripción' }}
                                    </td>
                                    <td class="px-5 py-3.5 text-center font-mono font-bold text-indigo-600 dark:text-indigo-400">
                                        {{ m.productos_count || 0 }}
                                    </td>
                                    <td class="px-5 py-3.5 text-center">
                                        <button
                                            @click="AlternarEstadoMarca(m)"
                                            :class="[
                                                'inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold transition cursor-pointer',
                                                m.activo
                                                    ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300 hover:bg-emerald-200'
                                                    : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400 hover:bg-gray-200'
                                            ]"
                                        >
                                            {{ m.activo ? 'Activa' : 'Inactiva' }}
                                        </button>
                                    </td>
                                    <td class="px-5 py-3.5 text-right whitespace-nowrap">
                                        <button
                                            @click="AbrirModalEditarMarca(m)"
                                            class="px-2.5 py-1 text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 rounded-lg text-xs font-semibold transition"
                                        >
                                            Editar
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ============================================== -->
            <!-- 4. PESTAÑA: MONEDAS Y TASAS -->
            <!-- ============================================== -->
            <div v-if="tabActual === 'monedas'" class="space-y-6">
                <div class="flex justify-between items-center">
                    <div>
                        <h3 class="text-base font-bold text-gray-900 dark:text-white">Divisas y Cotizaciones</h3>
                        <p class="text-xs text-gray-500">Parámetros de conversión y cotizaciones diarias referenciales.</p>
                    </div>
                    <Link
                        :href="route('monedas.index')"
                        class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-lg shadow-sm flex items-center gap-1.5 transition"
                    >
                        <span>🔄</span> Gestionar Tasas en Vivo
                    </Link>
                </div>

                <!-- Tarjetas de Monedas -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div
                        v-for="m in monedas"
                        :key="m.id_moneda"
                        class="p-5 bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm space-y-2"
                    >
                        <div class="flex justify-between items-start">
                            <div>
                                <span class="text-xs font-bold text-gray-400 font-mono">{{ m.codigo }}</span>
                                <h4 class="text-lg font-black text-gray-900 dark:text-white">{{ m.nombre }}</h4>
                            </div>
                            <span :class="['px-2 py-0.5 text-[10px] font-bold rounded-full', m.es_principal ? 'bg-indigo-100 text-indigo-800' : 'bg-emerald-100 text-emerald-800']">
                                {{ m.es_principal ? 'Principal (Base)' : 'Secundaria' }}
                            </span>
                        </div>
                        <div class="pt-2 border-t border-gray-100 dark:border-gray-700/60 flex justify-between items-baseline">
                            <span class="text-xs text-gray-500">Cotización:</span>
                            <span class="text-lg font-black font-mono text-gray-900 dark:text-white">
                                {{ m.simbolo }} {{ Number(m.tasa_cambio).toFixed(4) }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Historial Reciente de Tasas -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 space-y-3">
                    <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider">Últimos Cambios en Cotización</h4>
                    <div class="divide-y divide-gray-100 dark:divide-gray-700">
                        <div
                            v-for="h in historico_tasas"
                            :key="h.id_historico_tasa"
                            class="py-2.5 flex justify-between items-center text-xs"
                        >
                            <div>
                                <span class="font-bold text-gray-900 dark:text-white">{{ h.moneda?.nombre }}</span>
                                <span class="text-gray-400 text-[11px] ml-1">por {{ h.usuario?.name || 'Sistema' }}</span>
                                <div class="text-[10px] text-gray-400">{{ new Date(h.created_at).toLocaleString() }}</div>
                            </div>
                            <div class="text-right font-mono">
                                <span class="text-gray-400">{{ Number(h.tasa_anterior).toFixed(4) }} ➔</span>
                                <span class="font-bold text-emerald-600 dark:text-emerald-400 ml-1">{{ Number(h.tasa_nueva).toFixed(4) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ============================================== -->
            <!-- MODALES DE CREACIÓN Y EDICIÓN -->
            <!-- ============================================== -->

            <!-- Modal Usuario -->
            <div v-if="modalUsuarioAbierto" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs">
                <div class="bg-white dark:bg-gray-800 w-full max-w-md rounded-2xl p-6 shadow-2xl border border-gray-200 dark:border-gray-700">
                    <div class="flex justify-between items-center pb-3 border-b border-gray-100 dark:border-gray-700">
                        <h3 class="text-base font-bold text-gray-900 dark:text-white">
                            {{ usuarioEditando ? 'Editar Usuario' : 'Registrar Nuevo Usuario' }}
                        </h3>
                        <button @click="modalUsuarioAbierto = false" class="text-gray-400 hover:text-gray-600">✕</button>
                    </div>

                    <form @submit.prevent="GuardarUsuario" class="mt-4 space-y-4 text-xs">
                        <div>
                            <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-1">Nombre Completo *</label>
                            <input v-model="formUsuario.nombre" type="text" required class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
                            <p v-if="formUsuario.errors.nombre" class="text-red-500 text-[10px] mt-0.5">{{ formUsuario.errors.nombre }}</p>
                        </div>

                        <div>
                            <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-1">Correo Electrónico *</label>
                            <input v-model="formUsuario.email" type="email" required class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
                            <p v-if="formUsuario.errors.email" class="text-red-500 text-[10px] mt-0.5">{{ formUsuario.errors.email }}</p>
                        </div>

                        <div>
                            <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-1">Rol Operativo *</label>
                            <select v-model="formUsuario.rol" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                <option value="cajero">Cajero (Ventas y Cobros POS)</option>
                                <option value="admin">Administrador (Inventario y Gestión)</option>
                                <option value="superadmin">Super Administrador (Acceso Total)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                {{ usuarioEditando ? 'Contraseña (dejar en blanco para mantener actual)' : 'Contraseña *' }}
                            </label>
                            <input v-model="formUsuario.password" type="password" :required="!usuarioEditando" minlength="6" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
                            <p v-if="formUsuario.errors.password" class="text-red-500 text-[10px] mt-0.5">{{ formUsuario.errors.password }}</p>
                        </div>

                        <div class="flex justify-end gap-2 pt-3 border-t border-gray-100 dark:border-gray-700">
                            <button type="button" @click="modalUsuarioAbierto = false" class="px-4 py-2 text-gray-500 hover:bg-gray-100 rounded-lg">Cancelar</button>
                            <button type="submit" :disabled="formUsuario.processing" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-lg disabled:opacity-50">
                                {{ usuarioEditando ? 'Guardar Cambios' : 'Registrar Usuario' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Modal Categoría -->
            <div v-if="modalCategoriaAbierto" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs">
                <div class="bg-white dark:bg-gray-800 w-full max-w-md rounded-2xl p-6 shadow-2xl border border-gray-200 dark:border-gray-700">
                    <div class="flex justify-between items-center pb-3 border-b border-gray-100 dark:border-gray-700">
                        <h3 class="text-base font-bold text-gray-900 dark:text-white">
                            {{ categoriaEditando ? 'Editar Categoría' : 'Nueva Categoría' }}
                        </h3>
                        <button @click="modalCategoriaAbierto = false" class="text-gray-400 hover:text-gray-600">✕</button>
                    </div>

                    <form @submit.prevent="GuardarCategoria" class="mt-4 space-y-4 text-xs">
                        <div>
                            <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-1">Nombre de la Categoría *</label>
                            <input v-model="formCategoria.nombre" type="text" required class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
                            <p v-if="formCategoria.errors.nombre" class="text-red-500 text-[10px] mt-0.5">{{ formCategoria.errors.nombre }}</p>
                        </div>

                        <div>
                            <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-1">Categoría Padre (Opcional)</label>
                            <select v-model="formCategoria.id_categoria_padre" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                <option value="">Ninguna (Categoría Principal)</option>
                                <option
                                    v-for="cat in categorias.filter(c => !categoriaEditando || c.id_categoria !== categoriaEditando.id_categoria)"
                                    :key="cat.id_categoria"
                                    :value="cat.id_categoria"
                                >
                                    {{ cat.nombre }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-1">Descripción</label>
                            <textarea v-model="formCategoria.descripcion" rows="2" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"></textarea>
                        </div>

                        <div class="flex justify-end gap-2 pt-3 border-t border-gray-100 dark:border-gray-700">
                            <button type="button" @click="modalCategoriaAbierto = false" class="px-4 py-2 text-gray-500 hover:bg-gray-100 rounded-lg">Cancelar</button>
                            <button type="submit" :disabled="formCategoria.processing" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-lg disabled:opacity-50">
                                {{ categoriaEditando ? 'Guardar Cambios' : 'Crear Categoría' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Modal Marca -->
            <div v-if="modalMarcaAbierto" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs">
                <div class="bg-white dark:bg-gray-800 w-full max-w-md rounded-2xl p-6 shadow-2xl border border-gray-200 dark:border-gray-700">
                    <div class="flex justify-between items-center pb-3 border-b border-gray-100 dark:border-gray-700">
                        <h3 class="text-base font-bold text-gray-900 dark:text-white">
                            {{ marcaEditando ? 'Editar Marca' : 'Nueva Marca Comercial' }}
                        </h3>
                        <button @click="modalMarcaAbierto = false" class="text-gray-400 hover:text-gray-600">✕</button>
                    </div>

                    <form @submit.prevent="GuardarMarca" class="mt-4 space-y-4 text-xs">
                        <div>
                            <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-1">Nombre de la Marca *</label>
                            <input v-model="formMarca.nombre" type="text" required class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
                            <p v-if="formMarca.errors.nombre" class="text-red-500 text-[10px] mt-0.5">{{ formMarca.errors.nombre }}</p>
                        </div>

                        <div>
                            <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-1">Descripción / Fabricante</label>
                            <textarea v-model="formMarca.descripcion" rows="2" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"></textarea>
                        </div>

                        <div class="flex justify-end gap-2 pt-3 border-t border-gray-100 dark:border-gray-700">
                            <button type="button" @click="modalMarcaAbierto = false" class="px-4 py-2 text-gray-500 hover:bg-gray-100 rounded-lg">Cancelar</button>
                            <button type="submit" :disabled="formMarca.processing" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-lg disabled:opacity-50">
                                {{ marcaEditando ? 'Guardar Cambios' : 'Crear Marca' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>
