<script setup lang="ts">
defineProps<{
  heading: string
  description?: string
}>()
</script>

<template>
  <div
    class="flex items-start justify-between py-4 mb-4 border-b sm:mb-5 dark:border-gray-600 sticky top-0 bg-gray-100 z-30 dark:bg-gray-800">
    <div>

      <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
        {{ heading }}
      </h3>

      <p
        v-if="description"
        class="text-lg font-thin text-gray-400 dark:text-muted-foreground">
        {{ description }}
      </p>

    </div>

    <div
      v-if="$slots.action"
      class="flex gap-2">

      <slot name="action"/>

    </div>
  </div>
</template>

<style scoped>

</style>
