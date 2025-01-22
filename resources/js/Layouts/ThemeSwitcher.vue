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
  <div>
    <Button
      @click="toggleTheme"
      variant="ghost"
      size="icon">
      <SunIcon v-if="isDarkMode" class="w-5 h-5" />
      <MoonIcon v-else class="w-5 h-5" />
    </Button>
  </div>
</template>
