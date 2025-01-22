<script setup lang="ts">
import {ref, watchEffect} from "vue";
import {useDeviceDetection} from "@/composables/useDeviceDetection";

const props = defineProps({
  columns: {
    type: Array,
    required: true,
  },
  data: {
    type: Object, // Paginated object from Laravel
    required: true,
  },
});

const currentPageData = ref([]);
const {isMobile} = useDeviceDetection()

// Watch for changes in the paginated data
watchEffect(() => {
  if (props.data && Array.isArray(props.data.data)) {
    currentPageData.value = props.data.data; // Assign valid data
  } else {
    currentPageData.value = []; // Default to an empty array if invalid
  }
});

// Navigation handlers
const goToPage = (url) => {
  if (url) {
    window.location.href = url; // Redirect for server-side pagination
  }
};
</script>

<template>
  <div>
    <!-- Render as a table for larger screens -->
    <div v-if="!isMobile" class="overflow-x-auto">
      <!-- Table -->
      <table class="min-w-full border rounded-lg">
        <thead class="sticky top-0 z-10">
        <tr>
          <th
            v-for="column in columns"
            :key="column.accessorKey"
            class="text-left px-4 py-2 border-b text-sm font-medium text-gray-600">
            {{ column.header }}
          </th>
        </tr>

        </thead>

        <tbody>
          <tr
            v-for="(row, rowIndex) in currentPageData"
            :key="rowIndex"
            class="hover:bg-gray-50">
            <td
              v-for="column in columns"
              :key="column.accessorKey"
              class="px-4 py-2 border-b text-sm text-gray-800">
              <span v-if="column.cell">
                {{ column.cell({row}) }}
              </span>

              <span v-else>
              {{ row[column.accessorKey] }}
            </span>
            </td>
          </tr>

          <tr v-if="!currentPageData.length">
            <td
              :colspan="columns.length"
              class="px-4 py-4 text-center text-gray-500">
              No data available.
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Pagination Links -->
      <div class="flex justify-between items-center mt-4 text-sm">
        <button
          @click="goToPage(data.prev_page_url)"
          :disabled="!data.prev_page_url"
          class="px-3 py-1 rounded border border-gray-300 hover:bg-gray-50 disabled:opacity-50">
          Previous
        </button>

        <span>Page {{ data.current_page }} of {{ data.last_page }}</span>

        <button
          @click="goToPage(data.next_page_url)"
          :disabled="!data.next_page_url"
          class="px-3 py-1 rounded border border-gray-300 hover:bg-gray-50 disabled:opacity-50">
          Next
        </button>
      </div>

    </div>

    <!-- Render as cards for smaller screens -->
    <div v-else class="space-y-4">
      <div
        v-for="(row, rowIndex) in currentPageData"
        class="p-4 bg-white border border-gray-200 rounded-lg shadow-md"
        :key="rowIndex">
        <div
          v-for="column in columns"
          :key="column.accessorKey"
          class="mb-2">
          <p class="text-sm font-medium text-gray-600">
            {{ column.header }}
          </p>

          <p class="text-sm text-gray-800">
            <span v-if="column.cell">
              {{ column.cell({row}) }}
            </span>

            <span v-else>
              {{ row[column.accessorKey] }}
            </span>
          </p>
        </div>
      </div>

      <div v-if="!currentPageData.length" class="text-center text-gray-500">
        No data available.
      </div>
    </div>
  </div>
</template>
