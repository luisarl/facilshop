import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { createPinia } from 'pinia';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import SelectFiltro from './Components/SelectFiltro.vue';
import { FormatearFecha, FormatearHora, FormatearFechaHora } from './Utils/FechaHora';

const NombreApp = import.meta.env.VITE_APP_NAME || 'Laravel';
const pinia = createPinia();

createInertiaApp(
{
    title: (title) => `${title} - ${NombreApp}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin })
    {
        const AplicacionVue = createApp({ render: () => h(App, props) });

        AplicacionVue.use(plugin);
        AplicacionVue.use(pinia);
        AplicacionVue.use(ZiggyVue);

        AplicacionVue.component('SelectFiltro', SelectFiltro);
        AplicacionVue.component('Select2', SelectFiltro);

        AplicacionVue.config.globalProperties.$FormatearFecha = FormatearFecha;
        AplicacionVue.config.globalProperties.$FormatearHora = FormatearHora;
        AplicacionVue.config.globalProperties.$FormatearFechaHora = FormatearFechaHora;

        return AplicacionVue.mount(el);
    },
    progress:
    {
        color: '#4B5563',
    },
});
