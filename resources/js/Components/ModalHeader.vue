<script setup lang="ts">
defineProps<{
  heading: string
}>()
</script>

<template>
  <div class="py-4 mb-4 border-b sm:mb-5 dark:border-gray-600 sticky top-0 bg-gray-100 z-30 dark:bg-gray-800">
    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
      {{ heading }}
    </h3>

    <slot name="action" />
  </div>
</template>

<style scoped>

</style>
