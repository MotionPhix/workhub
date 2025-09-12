<script setup>
import {SunIcon, MoonIcon} from 'lucide-vue-next'
import {Button} from '@/components/ui/button'
import {UseDark} from "@vueuse/components";
</script>

<template>
  <div>
    <UseDark v-slot="{ isDark, toggleDark }">
      <Button
        @click="toggleDark()"
        variant="ghost"
        size="icon">
        <SunIcon v-if="isDark" class="w-5 h-5"/>
        <MoonIcon v-else class="w-5 h-5"/>
      </Button>
    </UseDark>
  </div>
</template>


