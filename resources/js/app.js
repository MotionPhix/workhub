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
import {Input} from '@/components/ui/input'
import {Calendar} from '@/components/ui/v-calendar'
import {Button} from '@/components/ui/button'
import {Label} from '@/components/ui/label'
import {Badge} from "@/components/ui/badge";
import {Switch} from "@/components/ui/switch";
import {Checkbox} from "@/components/ui/checkbox";
import Divider from "@/components/Divider.vue";
import FormField from '@/components/Forms/FormField.vue';
import DataTable from '@/components/DataTable.vue';
import GlobalModal from '@/components/GlobalModal.vue';
import Loader from '@/components/Forms/Loader.vue';
import ModalHeader from "@/components/ModalHeader.vue";
import {OhVueIcon, addIcons} from "oh-vue-icons";
import {
  Card,
  CardTitle,
  CardHeader,
  CardDescription,
  CardContent,
  CardFooter
} from '@/components/ui/card'
import {
  Select,
  SelectTrigger,
  SelectValue,
  SelectGroup,
  SelectItem,
  SelectContent,
  SelectSeparator,
} from '@/components/ui/select'
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
      `./pages/${name}.vue`,
      import.meta.glob('./pages/**/*.vue'),
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
      .component('Select', Select)
      .component('SelectTrigger', SelectTrigger)
      .component('SelectValue', SelectValue)
      .component('SelectGroup', SelectGroup)
      .component('SelectItem', SelectItem)
      .component('SelectContent', SelectContent)
      .component('SelectSeparator', SelectSeparator)
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
