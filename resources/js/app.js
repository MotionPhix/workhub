import '../css/app.css';
import './bootstrap';

import {createInertiaApp} from '@inertiajs/vue3';
import {resolvePageComponent} from 'laravel-vite-plugin/inertia-helpers';
import {createApp, h} from 'vue';
import {ZiggyVue} from '../../vendor/tightenco/ziggy';
import VueApexCharts from "vue3-apexcharts";
import {ModalLink, renderApp} from '@inertiaui/modal-vue'
import {createPinia} from 'pinia'
import { initializeTheme } from '@/composables/useTheme'

const appName = import.meta.env.VITE_APP_NAME || 'WorkHub';
const pinia = createPinia()

// Initialize theme immediately
initializeTheme()

createInertiaApp({
  title: (title) => `${title} - ${appName}`,
  resolve: (name) =>
    resolvePageComponent(
      `./pages/${name}.vue`,
      import.meta.glob('./pages/**/*.vue'),
    ),
  setup({el, App, props, plugin}) {
    return createApp({render: renderApp(App, props)})
      .use(plugin)
      .use(ZiggyVue)
      .use(pinia)
      .use(VueApexCharts)
      .component('ModalLink', ModalLink)
      .mount(el);
  },
  progress: {
    color: '#4B5563',
  },
});
