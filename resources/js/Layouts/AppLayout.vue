<script setup>
import { ref, onMounted } from 'vue'
import MobileNavigation from '@/Components/Navigation/MobileNavigation.vue'
import DesktopNavigation from '@/Components/Navigation/DesktopNavigation.vue'
import ResponsiveFooter from '@/Components/Navigation/ResponsiveFooter.vue'
import { useDeviceDetection } from '@/composables/useDeviceDetection'
import {Toaster} from "vue-sonner";

const darkMode = ref(false)
const { isMobile, isTablet, isDesktop } = useDeviceDetection()

onMounted(() => {
  // Check system preference or stored preference
  darkMode.value = localStorage.getItem('theme') === 'dark' ||
    (window.matchMedia('(prefers-color-scheme: dark)').matches)
})
</script>

<template>
  <Toaster rich-colors />

  <div class="min-h-screen transition-colors duration-300">
    <nav class="sticky top-0 z-50">
      <MobileNavigation v-if="isMobile" />
      <DesktopNavigation v-if="isTablet || isDesktop" />
    </nav>

    <main class="px-4 py-6 mx-auto sm:px-6 lg:px-8 max-w-4xl">
      <slot />
    </main>

    <footer class="mx-auto max-w-4xl">
      <ResponsiveFooter />
    </footer>
  </div>
</template>
