# 🧠 AI Integration Guidelines for Laravel + Inertia.js + Vue 3 + TypeScript

These guidelines define how AI agents (LLMs) should interact with and generate code for this project. They ensure consistency, maintainability, and alignment with the project's architecture and tooling.

---

## 📁 Folder Structure Rules

- All **folder names** under the `resources` directory must be **lowercase**.
  - ✅ `resources/js/pages`, `resources/js/components`
  - ❌ `resources/js/Pages`, `resources/js/Components`
- **File names** inside these folders follow PascalCase as needed.

---

## 🧩 Modal Usage (InertiaUI)

Use [InertiaUI Modal](https://github.com/inertiajs/inertia-modal) for all modal implementations.

### ✅ ModalLink Example

```vue
<script setup lang="ts">
import { ModalLink } from '@inertiaui/modal-vue'
</script>

<template>
  <ModalLink href="/users/create">Create User</ModalLink>
</template>

✅ Modal Component Example

```vue
<script setup lang="ts">
import { Modal } from '@inertiaui/modal-vue'
</script>

<template>
  <Modal>
    <h2 class="font-semibold text-lg">Create User</h2>
    
    <form>
      <!-- Form fields -->
    </form>
  </Modal>
</template>

✅ Programmatic Modal Example

```vue
<script setup lang="ts">
import { visitModal } from '@inertiaui/modal-vue'

function openModal() {
  visitModal('/users/create')
}
</script>

<template>
  <button @click="openModal">Create User</button>
</template>

🔔 Toast Alerts (vue-sonner)

Render the toaster in the root of your app.

```vue
<!-- AppLayout/AdminLayout/ManagerLayout -->
<script lang="ts" setup>
import { Toaster, toast } from 'vue-sonner'
import 'vue-sonner/style.css'
</script>

<template>
  <!-- ... -->
  <Toaster theme expand position rich-colors close-button />
  <button @click="() => toast('My first toast')">
    Give me a toast
  </button>
</template>

## Styling

You can style your toasts globally with the toastOptions prop in the Toaster component.

// for all toasts
<Toaster
  :toastOptions="{
    style: { background: '#fda4af' },
    class: 'my-toast',
    descriptionClass: 'my-toast-description'
  }"
/>

// for specific toast
toast('Event has been created', {
  style: {
    background: '#6ee7b7'
  },
  class: 'my-toast',
  descriptionClass: 'my-toast-description'
})

## Headless Toast
You can create your own custom toast component by using the `toast.custom` method. This allows you to fully control the appearance and behavior of your toasts.

```ts
import { markRaw } from 'vue'

import HeadlessToast from './HeadlessToast.vue'

toast.custom(markRaw(HeadlessToast));

## Headless Toast with props
You can also pass props to your custom toast component.

```ts
import { markRaw } from 'vue'

import HeadlessToast from './HeadlessToast.vue'

toast.custom(markRaw(HeadlessToast), {
  componentProps: {
    message: 'User created successfully! <br/> This is a multiline headless toast.'
  }
});

📊 Charting (Vue ApexCharts)
Use Vue3-ApexCharts for all chart visualizations.

✅ Global Setup (in app.ts/app.js)

```ts
import { createApp } from 'vue'
import App from './App.vue'
import VueApexCharts from 'vue3-apexcharts'

const app = createApp(App)
app.use(VueApexCharts)
// <apexchart> is now globally available

✅ Chart Component Example
<script setup lang="ts">
const chartOptions = {
  chart: { id: 'basic-bar' },
  xaxis: { categories: [1991, 1992, 1993, 1994] }
}

const chartSeries = [
  { name: 'Sales', data: [30, 40, 45, 50] }
]
</script>

<template>
  <apexchart
    type="bar"
    width="100%"
    :options="chartOptions"
    :series="chartSeries"
  />
</template>

🧼 Code Style & Conventions

- Use TypeScript with Vue 3 Composition API:

  - Always use <script setup lang="ts"> at the top.

  - Template comes after the script block.

- Use PascalCase for Vue component filenames.

- Use camelCase for variables and functions.

- Use kebab-case for route names and URLs.

- Keep components small and focused; extract logic into composables when needed.
