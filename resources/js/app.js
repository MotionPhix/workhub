import '../css/app.css';
import './bootstrap';

import {createInertiaApp} from '@inertiajs/vue3';
import {resolvePageComponent} from 'laravel-vite-plugin/inertia-helpers';
import {createApp, h} from 'vue';
import {ZiggyVue} from '../../vendor/tightenco/ziggy';
import VueApexCharts from "vue3-apexcharts";
import {Modal, ModalLink, putConfig, renderApp} from '@inertiaui/modal-vue'
import {createPinia} from 'pinia'

// components
import {Input} from '@/Components/ui/input'
import {Calendar} from '@/Components/ui/v-calendar'
import {Button} from '@/Components/ui/button'
import {Label} from '@/Components/ui/label'
import {Badge} from "@/Components/ui/badge";
import {Switch} from "@/Components/ui/switch";
import {Checkbox} from "@/Components/ui/checkbox";
import FormField from './Components/Forms/FormField.vue';
import Loader from './Components/Forms/Loader.vue';
import {OhVueIcon, addIcons} from "oh-vue-icons";
import {
  Card,
  CardTitle,
  CardHeader,
  CardDescription,
  CardContent,
  CardFooter
} from '@/Components/ui/card'
import {
  FaGoogle,
  FaFacebookSquare,
  FaRegularBell,
  HiUpload,
  HiCheckCircle
} from "oh-vue-icons/icons";

addIcons(
  FaGoogle,
  FaFacebookSquare,
  FaRegularBell,
  HiUpload,
  HiCheckCircle
);

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';
const pinia = createPinia()

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
    return createApp({render: renderApp(App, props)})
      .use(plugin)
      .use(ZiggyVue)
      .use(pinia)
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
      .component('Checkbox', Checkbox)
      .component('Button', Button)
      .component('Label', Label)
      .component('Switch', Switch)
      .component('Calendar', Calendar)
      .component('FormField', FormField)
      .component('Loader', Loader)
      .component('Badge', Badge)
      .mount(el);
  },
  progress: {
    color: '#4B5563',
  },
});
