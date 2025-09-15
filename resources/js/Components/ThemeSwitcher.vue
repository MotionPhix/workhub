<script setup>
import {SunIcon, MoonIcon} from 'lucide-vue-next'
import {Button} from '@/components/ui/button'
import { useTheme } from '@/composables/useTheme'

// Use the global theme
const { isDark, toggleDark } = useTheme()
</script>

<template>
  <div>
    <Button
      @click="toggleDark()"
      variant="ghost"
      size="icon"
      class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100">
      <SunIcon v-if="isDark" class="w-5 h-5 text-yellow-500 dark:text-yellow-400"/>
      <MoonIcon v-else class="w-5 h-5 text-gray-700 dark:text-gray-300"/>
    </Button>
  </div>
</template>


