<script setup>
defineProps({
  title: {
    type: String,
    required: true
  },
  value: {
    type: [String, Number],
    required: true
  },
  description: {
    type: String,
    default: ''
  }
})
</script>

<template>
  <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-4">
    <h2 class="text-lg font-semibold">{{ title }}</h2>
    <p class="text-2xl font-bold">{{ value }}</p>
    <p class="text-gray-500">{{ description }}</p>
  </div>
</template>
