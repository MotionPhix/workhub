<script setup lang="ts">
import { AlertCircle } from 'lucide-vue-next'
import { type Component } from 'vue'

interface Props {
  title?: string
  description?: string
  icon?: Component
}

const props = withDefaults(
  defineProps<Props>(),
  {
    title: 'No Data Available',
    description: 'There is no data to display at the moment.',
    icon: () => AlertCircle
  }
)
</script>

<template>
  <Card class="empty-state">
    <div class="flex min-h-[350px] flex-col items-center justify-center p-8 text-center">
      <component
        :is="icon"
        class="h-12 w-12 text-muted-foreground/50"
      />
      <h3 class="mt-4 text-lg font-semibold">{{ title }}</h3>
      <p class="mt-2 text-sm text-muted-foreground">{{ description }}</p>
    </div>
  </Card>
</template>

<style scoped>
.empty-state {
  @apply transition-all duration-300;
}
</style>
