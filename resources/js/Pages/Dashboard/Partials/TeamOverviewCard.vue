<script setup lang="ts">
import { computed } from "vue";

// Props
defineProps({
  teamSize: {
    type: Number,
    required: true,
  },
  teamHoursWorked: {
    type: Number,
    required: true,
  },
  topPerformers: {
    type: Array,
    default: () => [],
  },
});
</script>

<template>
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
    <!-- Team Size Card -->
    <div class="p-4 border rounded-lg bg-white dark:bg-gray-800 dark:border-gray-700 shadow">
      <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400">Team Size</h3>
      <p class="mt-2 text-2xl font-bold text-gray-800 dark:text-gray-200">{{ teamSize }}</p>
    </div>

    <!-- Total Hours Worked Card -->
    <div class="p-4 border rounded-lg bg-white dark:bg-gray-800 dark:border-gray-700 shadow">
      <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400">
        Total Hours Worked
      </h3>
      <p class="mt-2 text-2xl font-bold text-gray-800 dark:text-gray-200">{{ teamHoursWorked }} hrs</p>
    </div>

    <!-- Top Performers -->
    <div class="p-4 border rounded-lg bg-white dark:bg-gray-800 dark:border-gray-700 shadow">
      <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400">Top Performers</h3>
      <ul class="mt-2 space-y-2">
        <li
          v-for="performer in topPerformers"
          :key="performer.user.id"
          class="flex justify-between items-center"
        >
          <div>
            <p class="text-sm font-medium text-gray-800 dark:text-gray-200">
              {{ performer.user.name }}
            </p>
            <p class="text-xs text-gray-600 dark:text-gray-400">
              {{ performer.total_hours }} hrs
            </p>
          </div>
        </li>
      </ul>
    </div>
  </div>
</template>
