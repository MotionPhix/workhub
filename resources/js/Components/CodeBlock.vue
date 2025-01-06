<script setup lang="ts">
defineProps<{
  code: string
}>()
</script>

<template>
  <div class="bg-gray-100 dark:bg-gray-800 p-3 rounded-lg font-mono">
    { code }
  </div>
</template>
