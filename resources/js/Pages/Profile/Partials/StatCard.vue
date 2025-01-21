<script setup>
const props = defineProps({
  title: {
    type: String,
    required: true,
  },
  value: {
    type: [String, Number],
    required: true,
  },
  icon: {
    type: String,
    default: null, // Optional icon path or name
  },
});
</script>

<template>
  <div class="p-4 bg-white border border-gray-200 rounded-lg shadow-sm hover:shadow-md transition-shadow">
    <div class="flex items-center space-x-4">
      <!-- Optional Icon -->
      <div v-if="icon" class="w-10 h-10 bg-gray-100 rounded-full flex items-center justify-center">
        <img :src="icon" alt="" class="w-6 h-6" />
      </div>
      <div>
        <h4 class="text-sm font-medium text-gray-600">{{ title }}</h4>
        <p class="text-2xl font-semibold text-gray-800">{{ value }}</p>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Optional: Add hover and focus effects */
</style>
