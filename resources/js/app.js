import '../css/app.css';
import './bootstrap';

import {createInertiaApp} from '@inertiajs/vue3';
import {resolvePageComponent} from 'laravel-vite-plugin/inertia-helpers';
import {createApp, h} from 'vue';
import {ZiggyVue} from '../../vendor/tightenco/ziggy';
import VueApexCharts from "vue3-apexcharts";
import {Modal, ModalLink, putConfig, renderApp} from '@inertiaui/modal-vue'

// components
import {Input} from '@/Components/ui/input'
import {Button} from '@/Components/ui/button'
import {Label} from '@/Components/ui/label'
import { Badge } from "@/Components/ui/badge";
import {
  Card,
  CardTitle,
  CardHeader,
  CardDescription,
  CardContent,
  CardFooter
} from '@/Components/ui/card'
import FormField from './Components/Forms/FormField.vue';
import Loader from './Components/Forms/Loader.vue';

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
      .component('CardHeader', CardHeader)
      .component('CardDescription', CardDescription)
      .component('CardContent', CardContent)
      .component('CardFooter', CardFooter)
      .component('CardTitle', CardTitle)
      .component('Card', Card)
      .component('Input', Input)
      .component('Button', Button)
      .component('Label', Label)
      .component('FormField', FormField)
      .component('Loader', Loader)
      .component('Badge', Badge)
      .mount(el);
  },
  progress: {
    color: '#4B5563',
  },
});
