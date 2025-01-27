<script setup lang="ts">
import {useMediaQuery} from "@vueuse/core"; // For responsiveness
import {cn} from "@/lib/utils"; // Utility for class management

// Props
defineProps<{
  workLogs: Array<{
    uuid: string
    work_title: string
    work_date: string
    hours_worked: number
    status: string
  }>,
}>();

// Check for smaller screens
const isSmallScreen = useMediaQuery("(max-width: 768px)");
</script>

<template>
  <div>
    <!-- Table for larger screens -->
    <div v-if="!isSmallScreen" class="overflow-x-auto rounded-lg shadow">
      <table class="min-w-full text-sm bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
        <thead class="bg-gray-100 dark:bg-gray-700">
          <tr>
            <th class="px-4 py-2 text-left text-gray-600 dark:text-gray-300">Date</th>
            <th class="px-4 py-2 text-left text-gray-600 dark:text-gray-300">Title</th>
            <th class="px-4 py-2 text-left text-gray-600 dark:text-gray-300">Hours Worked</th>
            <th class="px-4 py-2 text-left text-gray-600 dark:text-gray-300">Status</th>
          </tr>
        </thead>

        <tbody>
          <tr
            v-for="entry in workLogs"
            :key="entry.uuid"
            class="hover:bg-gray-50 dark:hover:bg-gray-900 border-b dark:border-gray-700">
            <td class="px-4 py-2">{{ entry.work_date }}</td>
            <td class="px-4 py-2">{{ entry.work_title }}</td>
            <td class="px-4 py-2">{{ entry.hours_worked }} hrs</td>
            <td class="px-4 py-2">
              <span
                :class="cn(
                  'inline-flex capitalize items-center px-3 py-1 rounded-full text-xs font-medium',
                  entry.status === 'completed'
                    ? 'bg-green-100 text-green-800'
                    : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-700 dark:text-yellow-100'
                )">
                {{ entry.status === 'in_progress' ? 'In Progress' : entry.status }}
              </span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Cards for smaller screens -->
    <div v-else class="space-y-4">
      <div
        v-for="entry in workLogs"
        :key="entry.uuid"
        class="p-4 border rounded-lg bg-white dark:bg-gray-800 dark:border-gray-700">
        <div class="flex items-center justify-between">
          <h3 class="text-sm font-medium text-gray-800 dark:text-gray-200">
            {{ entry.work_title }}
          </h3>
          <span
            :class="cn(
              'text-xs px-3 py-1 rounded-full font-medium',
              entry.status === 'Completed'
                ? 'bg-green-100 text-green-800'
                : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-700 dark:text-yellow-100'
            )">
            {{ entry.status }}
          </span>
        </div>

        <div class="mt-2 text-gray-600 dark:text-gray-400 text-sm">
          <p>Date: {{ entry.work_date }}</p>
          <p>Hours Worked: {{ entry.hours_worked }} hrs</p>
        </div>
      </div>
    </div>
  </div>
</template>
