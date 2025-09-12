<script setup lang="ts">
import {ref, watchEffect} from "vue";
import {useDeviceDetection} from "@/composables/useDeviceDetection";
import {Table, TableBody, TableCell, TableHead, TableHeader, TableRow} from "@/components/ui/table";
import Divider from "@/components/Divider.vue";

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
const {isMobile} = useDeviceDetection();

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
  <div class="space-y-6">
    <!-- Table view for larger screens -->
    <div
      v-if="!isMobile"
      class="overflow-x-auto @container">
      <Table class="min-w-full dark:border-gray-700">
        <TableHeader class="bg-gray-100 dark:bg-gray-800 sticky top-0 z-10">
          <TableRow>
            <TableHead
              v-for="column in columns"
              :key="column.accessorKey"
              class="text-left px-4 py-2 border-b text-sm font-medium text-gray-600 dark:text-gray-300">
              {{ column.header }}
            </TableHead>
          </TableRow>
        </TableHeader>

        <TableBody>
          <TableRow
            v-for="(row, rowIndex) in currentPageData"
            :key="rowIndex"
            class="hover:bg-gray-50 dark:hover:bg-gray-700">
            <TableCell
              v-for="column in columns"
              :key="column.accessorKey"
              class="px-4 py-2 border-b text-sm text-gray-800 dark:text-gray-300">
              <span v-if="column.accessorKey === 'description'">
                <span v-html="row[column.accessorKey]"/>
              </span>

              <span v-else>
                <span v-if="column.cell">
                  {{ column.cell({row}) }}
                </span>

                <span v-else>
                  {{ row[column.accessorKey] }}
                </span>
              </span>
            </TableCell>
          </TableRow>

          <TableRow v-if="!currentPageData.length">
            <TableCell
              :colspan="columns.length"
              class="px-4 py-4 text-center text-gray-500 dark:text-gray-400">
              No data available.
            </TableCell>
          </TableRow>
        </TableBody>
      </Table>

      <!-- Pagination Links -->
      <div class="flex justify-between items-center mt-4 text-sm dark:text-gray-300">
        <button
          @click="goToPage(data.prev_page_url)"
          :disabled="!data.prev_page_url"
          class="px-3 py-1 rounded border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 focus:ring focus:ring-blue-300 disabled:opacity-50">
          Previous
        </button>

        <span>
          Page {{ data.current_page }} of {{ data.last_page }}
        </span>

        <button
          @click="goToPage(data.next_page_url)"
          :disabled="!data.next_page_url"
          class="px-3 py-1 rounded border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 focus:ring focus:ring-blue-300 disabled:opacity-50">
          Next
        </button>
      </div>
    </div>

    <!-- Card view for smaller screens -->
    <div
      v-else
      class="grid gap-6 @container sm:grid-cols-1 md:grid-cols-2 lg:grid-cols-3">
      <div
        v-for="(row, rowIndex) in currentPageData"
        :key="rowIndex"
        class="p-4 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-md">
        <div
          v-for="(column, idx) in columns"
          :key="column.accessorKey"
          class="mb-2">
          <section>
            <p class="text-sm font-medium text-gray-600 dark:text-gray-300">
              {{ column.header }}
            </p>

            <p class="text-sm text-gray-800 dark:text-gray-300">
              <span v-if="column.accessorKey === 'description'">
                <span v-html="row[column.accessorKey]"/>
              </span>

              <span v-else>
                <span v-if="column.cell">
                  {{ column.cell({row}) }}
                </span>

                <span v-else>
                  {{ row[column.accessorKey] }}
                </span>
              </span>
            </p>
          </section>

          <Divider v-if="idx !== columns.length - 1" />
        </div>
      </div>

      <div v-if="!currentPageData.length" class="text-center text-gray-500 dark:text-gray-400">
        No data available.
      </div>
    </div>
  </div>
</template>


