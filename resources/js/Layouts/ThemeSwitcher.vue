<script setup>
import { ref, onMounted } from 'vue'
import { SunIcon, MoonIcon } from 'lucide-vue-next'
import {Button} from '@/Components/ui/button'

const isDarkMode = ref(false)

const toggleTheme = () => {
  isDarkMode.value = !isDarkMode.value

  if (isDarkMode.value) {
    document.documentElement.classList.add('dark')
    localStorage.setItem('theme', 'dark')
  } else {
    document.documentElement.classList.remove('dark')
    localStorage.setItem('theme', 'light')
  }
}

onMounted(() => {
  // Initial theme setup
  const savedTheme = localStorage.getItem('theme')
  if (savedTheme === 'dark' ||
    (!savedTheme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
    document.documentElement.classList.add('dark')
    isDarkMode.value = true
  }
})
</script>

<template>
  <div class="fixed bottom-4 right-4 z-50">
    <Button
      @click="toggleTheme"
      variant="outline"
      size="icon">
      <SunIcon v-if="isDarkMode" class="h-5 w-5" />
      <MoonIcon v-else class="h-5 w-5" />
    </Button>
  </div>
</template>
