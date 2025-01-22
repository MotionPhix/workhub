<script setup>
import { ref, onMounted } from 'vue'
import { usePage } from '@inertiajs/vue3'
import MobileNavigation from '@/Components/Navigation/MobileNavigation.vue'
import DesktopNavigation from '@/Components/Navigation/DesktopNavigation.vue'
import ResponsiveFooter from '@/Components/Navigation/ResponsiveFooter.vue'
import { useDeviceDetection } from '@/composables/useDeviceDetection'

const darkMode = ref(false)
const { isMobile, isDesktop } = useDeviceDetection()

onMounted(() => {
  // Check system preference or stored preference
  darkMode.value = localStorage.getItem('theme') === 'dark' ||
    (window.matchMedia('(prefers-color-scheme: dark)').matches)
})
</script>

<template>
  <div class="min-h-screen transition-colors duration-300">
    <nav class="sticky top-0 z-50">
      <MobileNavigation v-if="isMobile" />
      <DesktopNavigation v-if="isDesktop" />
    </nav>

    <main class="px-4 py-6 mx-auto sm:px-6 lg:px-8">
      <slot />
    </main>

    <footer>
      <ResponsiveFooter />
    </footer>
  </div>
</template>
