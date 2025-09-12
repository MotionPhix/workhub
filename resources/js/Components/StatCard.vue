<script setup lang="ts">
import {TrendingDownIcon, TrendingUpIcon} from "lucide-vue-next";

defineProps<{
  title: string
  value: string | number
  description?: string
  trend?: string
}>()
</script>

<template>
  <div class="bg-gray-100 dark:bg-gray-700 shadow rounded-lg p-4">
    <div class="flex items-center justify-between">
      <h2 class="text-lg font-thin dark:text-gray-300 text-gray-800">{{ title }}</h2>
      <div v-if="trend" class="flex items-center space-x-2">
        <!-- Show the appropriate icon based on the trend -->
        <span
          v-if="trend === 'Increasing'"
          class="text-green-500">
          <!-- Upward arrow for increasing -->
          <TrendingUpIcon />
        </span>

        <span
          v-if="trend === 'Declining'"
          class="text-red-500">
          <!-- Downward arrow for declining -->
          <TrendingDownIcon />
        </span>
      </div>
    </div>

    <p class="text-2xl font-medium flex gap-x-2 items-center">
      <svg v-if="title === 'Total Work Logs'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" class="stroke-current" fill="none">
        <path d="M2 14C2 11.1911 2 9.78661 2.67412 8.77772C2.96596 8.34096 3.34096 7.96596 3.77772 7.67412C4.78661 7 6.19108 7 9 7H15C17.8089 7 19.2134 7 20.2223 7.67412C20.659 7.96596 21.034 8.34096 21.3259 8.77772C22 9.78661 22 11.1911 22 14C22 16.8089 22 18.2134 21.3259 19.2223C21.034 19.659 20.659 20.034 20.2223 20.3259C19.2134 21 17.8089 21 15 21H9C6.19108 21 4.78661 21 3.77772 20.3259C3.34096 20.034 2.96596 19.659 2.67412 19.2223C2 18.2134 2 16.8089 2 14Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
        <path d="M16 7C16 5.11438 16 4.17157 15.4142 3.58579C14.8284 3 13.8856 3 12 3C10.1144 3 9.17157 3 8.58579 3.58579C8 4.17157 8 5.11438 8 7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
        <path d="M6 11L6.65197 11.202C10.0851 12.266 13.9149 12.266 17.348 11.202L18 11M12 12V14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
      </svg>

      <svg v-if="title === 'Total Hours Worked'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" class="stroke-current" fill="none">
        <path d="M12 8V12L13.5 13.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
        <path d="M19.5454 16.4534C21.1818 17.337 22 17.7789 22 18.5C22 19.2211 21.1818 19.663 19.5454 20.5466L18.4311 21.1484C17.1744 21.827 16.5461 22.1663 16.2439 21.9196C15.504 21.3154 16.6567 19.7561 16.9403 19.2037C17.2277 18.644 17.2225 18.3459 16.9403 17.7963C16.6567 17.2439 15.504 15.6846 16.2439 15.0804C16.5461 14.8337 17.1744 15.173 18.4311 15.8516L19.5454 16.4534Z" stroke="currentColor" stroke-width="1.5" />
        <path d="M13.0261 21.948C12.6888 21.9824 12.3464 22 12 22C6.47715 22 2 17.5228 2 12C2 6.47715 6.47715 2 12 2C17.5228 2 22 6.47715 22 12C22 12.6849 21.9311 13.3538 21.8 14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
      </svg>

      <svg v-if="trend" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" class="shrink-0   stroke-current" fill="none">
        <path d="M12 22L10 16H2L4 22H12ZM12 22H16" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
        <path d="M12 13V12.5C12 10.6144 12 9.67157 11.4142 9.08579C10.8284 8.5 9.88562 8.5 8 8.5C6.11438 8.5 5.17157 8.5 4.58579 9.08579C4 9.67157 4 10.6144 4 12.5V13" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
        <path d="M19 13C19 14.1046 18.1046 15 17 15C15.8954 15 15 14.1046 15 13C15 11.8954 15.8954 11 17 11C18.1046 11 19 11.8954 19 13Z" stroke="currentColor" stroke-width="1.5" />
        <path d="M10 4C10 5.10457 9.10457 6 8 6C6.89543 6 6 5.10457 6 4C6 2.89543 6.89543 2 8 2C9.10457 2 10 2.89543 10 4Z" stroke="currentColor" stroke-width="1.5" />
        <path d="M14 17.5H20C21.1046 17.5 22 18.3954 22 19.5V20C22 21.1046 21.1046 22 20 22H19" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
      </svg>

      {{ value }}
    </p>
    <p class="text-gray-500">{{ description }}</p>
  </div>
</template>


