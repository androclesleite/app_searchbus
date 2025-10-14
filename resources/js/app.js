import './bootstrap';
import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { router } from '@inertiajs/vue3';
import Toast from 'vue-toastification';
import 'vue-toastification/dist/index.css';

// Configura a barra de progresso do Inertia
router.on('start', () => {
    document.getElementById('inertia-progress-bar')?.classList.remove('hidden');
});

router.on('finish', () => {
    document.getElementById('inertia-progress-bar')?.classList.add('hidden');
});

// Opções do Toast
const toastOptions = {
    position: "top-right",
    timeout: 4000,
    closeOnClick: true,
    pauseOnFocusLoss: true,
    pauseOnHover: true,
    draggable: true,
    draggablePercent: 0.6,
    showCloseButtonOnHover: false,
    hideProgressBar: false,
    closeButton: "button",
    icon: true,
    rtl: false,
    transition: "Vue-Toastification__fade",
    maxToasts: 3,
    newestOnTop: true
};

createInertiaApp({
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(Toast, toastOptions)
            .mount(el);
    },
});