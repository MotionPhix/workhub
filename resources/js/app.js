import '../css/app.css';
import './bootstrap';

import {createInertiaApp} from '@inertiajs/vue3';
import {resolvePageComponent} from 'laravel-vite-plugin/inertia-helpers';
import {createApp, h} from 'vue';
import {ZiggyVue} from '../../vendor/tightenco/ziggy';
import VueApexCharts from "vue3-apexcharts";
import {ModalLink, renderApp} from '@inertiaui/modal-vue'
import {createPinia} from 'pinia'

// components
import {Input} from '@/Components/ui/input'
import {Calendar} from '@/Components/ui/v-calendar'
import {Button} from '@/Components/ui/button'
import {Label} from '@/Components/ui/label'
import {Badge} from "@/Components/ui/badge";
import {Switch} from "@/Components/ui/switch";
import {Checkbox} from "@/Components/ui/checkbox";
import Divider from "@/Components/Divider.vue";
import FormField from '@/Components/Forms/FormField.vue';
import DataTable from '@/Components/DataTable.vue';
import GlobalModal from '@/Components/GlobalModal.vue';
import Loader from '@/Components/Forms/Loader.vue';
import ModalHeader from "@/Components/ModalHeader.vue";
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

const appName = import.meta.env.VITE_APP_NAME || 'WorkHub';
const pinia = createPinia()

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
      .component('GlobalModal', GlobalModal)
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
      .component('DataTable', DataTable)
      .component('ModalHeader', ModalHeader)
      .component('Divider', Divider)
      .component('Loader', Loader)
      .component('Badge', Badge)
      .mount(el);
  },
  progress: {
    color: '#4B5563',
  },
});
