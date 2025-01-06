<script setup>
import { ref, computed, watch } from 'vue'
import {
  ArrowUpDownIcon,
  ColumnsIcon,
  DownloadIcon
} from 'lucide-vue-next'
import { exportToCSV, exportToExcel, exportToPDF } from '@/Services/ExportService'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow
} from "@/Components/ui/table";
import {Button} from "@/Components/ui/button";
import {
  DropdownMenu,
  DropdownMenuItem,
  DropdownMenuTrigger,
  DropdownMenuContent,
} from "@/Components/ui/dropdown-menu";

const props = defineProps({
  columns: {
    type: Array,
    required: true
  },
  data: {
    type: Array,
    required: true
  },
  showFilters: {
    type: Boolean,
    default: true
  },
  pageSize: {
    type: Number,
    default: 10
  }
})

// State
const searchQuery = ref('')
const currentPage = ref(1)
const sortColumn = ref(null)
const sortDirection = ref('asc')
const hiddenColumns = ref([])

// Computed Properties
const visibleColumns = computed(() =>
  props.columns.filter(column =>
    !hiddenColumns.value.includes(column.accessorKey)
  )
)

const processedData = computed(() => {
  let result = [...props.data]

  // Search filtering
  if (searchQuery.value) {
    result = result.filter(row =>
      visibleColumns.value.some(column =>
        String(row[column.accessorKey])
          .toLowerCase()
          .includes(searchQuery.value.toLowerCase())
      )
    )
  }

  // Sorting
  if (sortColumn.value) {
    result.sort((a, b) => {
      const modifier = sortDirection.value === 'asc' ? 1 : -1
      return a[sortColumn.value] > b[sortColumn.value]
        ? modifier
        : -modifier
    })
  }

  // Pagination
  const start = (currentPage.value - 1) * props.pageSize
  const end = start + props.pageSize
  return result.slice(start, end)
})

const totalItems = computed(() => props.data.length)

// Methods
const handleSort = (column) => {
  if (!column.sortable) return

  if (sortColumn.value === column.accessorKey) {
    sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortColumn.value = column.accessorKey
    sortDirection.value = 'asc'
  }
}

const toggleColumnVisibility = (column) => {
  const index = hiddenColumns.value.indexOf(column.accessorKey)
  if (index > -1) {
    hiddenColumns.value.splice(index, 1)
  } else {
    hiddenColumns.value.push(column.accessorKey)
  }
}

const isColumnVisible = (column) =>
  !hiddenColumns.value.includes(column.accessorKey)

const exportData = (format) => {
  const exportData = processedData.value.map(row =>
    visibleColumns.value.reduce((acc, column) => {
      acc[column.header] = row[column.accessorKey]
      return acc
    }, {})
  )

  switch (format) {
    case 'csv':
      exportToCSV(exportData, 'data-export')
      break
    case 'excel':
      exportToExcel(exportData, 'data-export')
      break
    case 'pdf':
      exportToPDF(exportData, 'data-export')
      break
  }
}

const nextPage = () => {
  if (currentPage.value * props.pageSize < totalItems.value) {
    currentPage.value++
  }
}

const prevPage = () => {
  if (currentPage .value > 1) {
    currentPage.value--
  }
}

// Watch for changes in data to reset pagination
watch(() => props.data, () => {
  currentPage.value = 1
})
</script>

<template>
  <div class="w-full">
    <!-- Table Filters (Optional) -->
    <div v-if="showFilters" class="mb-4 flex justify-between items-center">
      <Input
        type="text"
        placeholder="Search..."
        v-model="searchQuery"
        class="max-w-sm"
      />
      <div class="flex space-x-2">
        <!-- Column Visibility Toggles -->
        <DropdownMenu>
          <DropdownMenuTrigger as-child>
            <Button variant="outline">
              <ColumnsIcon class="mr-2 h-4 w-4" />
              Columns
            </Button>
          </DropdownMenuTrigger>
          <DropdownMenuContent>
            <DropdownMenuGroup>
              <DropdownMenuItem
                v-for="column in columns"
                :key="column.accessorKey"
                @click="toggleColumnVisibility(column)"
              >
                <Checkbox
                  :checked="isColumnVisible(column)"
                  class="mr-2"
                />
                {{ column.header }}
              </DropdownMenuItem>
            </DropdownMenuGroup>
          </DropdownMenuContent>
        </DropdownMenu>

        <!-- Export Options -->
        <DropdownMenu>
          <DropdownMenuTrigger as-child>
            <Button variant="outline">
              <DownloadIcon class="mr-2 h-4 w-4" />
              Export
            </Button>
          </DropdownMenuTrigger>
          <DropdownMenuContent>
            <DropdownMenuItem @click="exportData('csv')">
              Export as CSV
            </DropdownMenuItem>
            <DropdownMenuItem @click="exportData('excel')">
              Export as Excel
            </DropdownMenuItem>
            <DropdownMenuItem @click="exportData('pdf')">
              Export as PDF
            </DropdownMenuItem>
          </DropdownMenuContent>
        </DropdownMenu>
      </div>
    </div>

    <!-- Table -->
    <Table>
      <TableHeader>
        <TableRow>
          <TableHead
            v-for="column in visibleColumns"
            :key="column.accessorKey"
            @click="handleSort(column)"
            class="cursor-pointer hover:bg-gray-100"
          >
            {{ column.header }}
            <ArrowUpDownIcon v-if="column.sortable" class="ml-2 h-4 w-4 inline" />
          </TableHead>
        </TableRow>
      </TableHeader>
      <TableBody>
        <TableRow
          v-for="row in processedData"
          :key="row.id"
        >
          <TableCell
            v-for="column in visibleColumns"
            :key="column.accessorKey"
          >
            <slot
              :name="column.accessorKey"
              :row="row"
            >
              {{
                column.cell
                  ? column.cell({ row })
                  : row[column.accessorKey]
              }}
            </slot>
          </TableCell>

          <!-- Actions Column -->
          <TableCell v-if="$slots.actions">
            <slot name="actions" :row="row" />
          </TableCell>
        </TableRow>
      </TableBody>
    </Table>

    <!-- Pagination -->
    <div class="flex items-center justify-between mt-4">
      <div>
        Showing
        {{ (currentPage - 1) * pageSize + 1 }}-
        {{ Math.min(currentPage * pageSize, totalItems) }}
        of {{ totalItems }} entries
      </div>
      <div class="flex space-x-2">
        <Button
          variant="outline"
          @click="prevPage"
          :disabled="currentPage === 1"
        >
          Previous
        </Button>
        <Button
          variant="outline"
          @click="nextPage"
          :disabled="currentPage * pageSize >= totalItems"
        >
          Next
        </Button>
      </div>
    </div>
  </div>
</template>
