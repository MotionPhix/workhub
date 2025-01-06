import '../css/app.css';
import './bootstrap';

import {createInertiaApp} from '@inertiajs/vue3';
import {resolvePageComponent} from 'laravel-vite-plugin/inertia-helpers';
import {createApp, h} from 'vue';
import {ZiggyVue} from '../../vendor/tightenco/ziggy';
import VueApexCharts from "vue3-apexcharts";
import { Modal, ModalLink, putConfig, renderApp } from '@inertiaui/modal-vue'

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

putConfig({
  type: 'modal',
  navigate: false,
  modal: {
    closeButton: true,
    closeExplicitly: false,
    maxWidth: 'md',
    paddingClasses: 'p-4 sm:p-6',
    panelClasses: 'bg-white rounded',
    position: 'center',
  },
  slideover: {
    closeButton: true,
    closeExplicitly: false,
    maxWidth: 'md',
    paddingClasses: 'p-4 sm:p-6',
    panelClasses: 'bg-white min-h-screen',
    position: 'right',
  },
})

createInertiaApp({
  title: (title) => `${title} - ${appName}`,
  resolve: (name) =>
    resolvePageComponent(
      `./Pages/${name}.vue`,
      import.meta.glob('./Pages/**/*.vue'),
    ),
  setup({el, App, props, plugin}) {
    return createApp({ render: renderApp(App, props) })
      .use(plugin)
      .use(ZiggyVue)
      .use(VueApexCharts)
      .component('GlobalModal', Modal)
      .component('ModalLink', ModalLink)
      .mount(el);
  },
  progress: {
    color: '#4B5563',
  },
});
