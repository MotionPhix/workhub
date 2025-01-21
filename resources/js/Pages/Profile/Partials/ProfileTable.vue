<script setup>
import { computed } from "vue";

const props = defineProps({
  columns: {
    type: Array,
    required: true,
  },
  data: {
    type: Array,
    required: true,
  },
});

const paginatedData = computed(() => props.data); // Simple version without pagination
</script>

<template>
  <div class="overflow-x-auto">
    <table class="min-w-full bg-white border border-gray-200 rounded-lg">
      <thead class="bg-gray-100">
        <tr>
          <th
            v-for="column in columns"
            :key="column.accessorKey"
            class="text-left px-4 py-2 border-b border-gray-200 text-sm font-medium text-gray-600"
          >
            {{ column.header }}
          </th>
        </tr>
      </thead>
      <tbody>
        <tr
          v-for="(row, rowIndex) in paginatedData"
          :key="rowIndex"
          class="hover:bg-gray-50"
        >
          <td
            v-for="column in columns"
            :key="column.accessorKey"
            class="px-4 py-2 border-b border-gray-200 text-sm text-gray-800"
          >
            <span v-if="column.cell">
              {{ column.cell({ row }) }}
            </span>
            <span v-else>
              {{ row[column.accessorKey] }}
            </span>
          </td>
        </tr>
        <tr v-if="!paginatedData.length">
          <td
            :colspan="columns.length"
            class="px-4 py-4 text-center text-gray-500"
          >
            No data available.
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<style scoped>
/* Optional: Add hover and focus effects */
</style>
