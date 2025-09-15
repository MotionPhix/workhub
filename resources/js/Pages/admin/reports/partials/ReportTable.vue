<script setup lang="ts">
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import { ChevronsLeftIcon, ChevronsRightIcon, FileIcon } from 'lucide-vue-next'

interface Column {
  key: string
  label: string
  format?: (value: any) => string | { template: string; data: any }
}

interface PaginationLink {
  url?: string
  label: string
  active: boolean
}

interface Props {
  columns: Column[]
  data: {
    data: any[]
    current_page: number
    last_page: number
    total: number
    links: PaginationLink[]
  }
  emptyStateMessage?: string
}

const props = withDefaults(defineProps<Props>(), {
  emptyStateMessage: 'No data available'
})

const getNestedValue = (obj: any, path: string) => {
  return path.split('.').reduce((acc, part) => acc?.[part], obj) ?? '—'
}

const formattedData = computed(() => {
  return props.data.data.map(row => {
    const formattedRow = { ...row }

    props.columns.forEach(column => {
      const value = getNestedValue(row, column.key)

      if (column.format && value !== undefined) {
        try {
          const formatted = column.format(value, row)
          if (typeof formatted === 'object') {
            formattedRow[`_formatted_${column.key}`] = formatted
          } else {
            formattedRow[column.key] = formatted
          }
        } catch (error) {
          console.error(`Error formatting column ${column.key}:`, error)
          formattedRow[column.key] = '—'
        }
      }
    })

    return formattedRow
  })
})

const paginationLinks = computed(() => {
  return props.data.links.filter(link =>
    link.label !== '&laquo; Previous' &&
    link.label !== 'Next &raquo;'
  )
})

const showPagination = computed(() => {
  return props.data.last_page > 1
})

const renderCell = (row: any, column: Column) => {
  try {
    const value = getNestedValue(row, column.key)

    if (!row[column.key]) {
      return '-'
    }

    if (column.format) {
      const formatted = column.format(value)
      if (typeof formatted === 'object') {
        return { template: formatted.template, data: formatted.data }
      }
      return formatted
    }
    return value
  } catch (e) {
    console.error(`Error rendering cell for column ${column.key}:`, e)
    return 'Error'
  }
}
</script>

<template>
  <div class="space-y-4">
    <!-- Empty State -->
    <div v-if="!formattedData.length" class="flex flex-col items-center justify-center p-8 text-center">
      <div class="rounded-full bg-muted p-3 mb-4">
        <FileIcon class="h-6 w-6 text-muted-foreground" />
      </div>

      <h3 class="text-lg font-semibold">
        {{ emptyStateMessage }}
      </h3>

      <p class="text-sm text-muted-foreground">
        No entries found matching your criteria.
      </p>
    </div>

    <template v-else>
      <!-- Desktop Table View -->
      <div class="hidden md:block relative overflow-x-auto">
        <table class="w-full text-sm text-left">
          <thead class="text-xs uppercase bg-muted/50">
          <tr>
            <th
              v-for="column in columns"
              :key="column.key"
              class="px-6 py-3 font-medium">
              {{ column.label }}
            </th>
            <th class="px-6 py-3 font-medium" />
          </tr>
          </thead>
          <tbody>
          <tr
            v-for="row in formattedData"
            :key="row.id"
            class="border-b hover:bg-muted/50 transition-colors">
            <td
              v-for="column in columns"
              :key="column.key"
              class="px-6 py-4 whitespace-nowrap capitalize">
              <component
                v-if="column.format && typeof renderCell(row, column) === 'object'"
                :is="renderCell(row, column).template"
                v-bind="renderCell(row, column).data">
                {{ renderCell(row, column).data.value === 'in_progress' ? 'In Progress': renderCell(row, column).data.value }}
              </component>

              <template v-else>
                {{ renderCell(row, column) }}
              </template>
            </td>
            <td class="px-6 py-4">
              <slot name="actions" :row="row" />
            </td>
          </tr>
          </tbody>
        </table>
      </div>

      <!-- Mobile Card View -->
      <div class="md:hidden space-y-4">
        <Card
          v-for="row in formattedData"
          :key="row.id"
          class="hover:bg-muted/50 transition-colors">
          <CardContent class="p-4 space-y-3">
            <div
              v-for="column in columns"
              :key="column.key"
              class="flex justify-between items-start">
              <span class="text-sm font-medium">{{ column.label }}</span>
              <span class="text-sm text-right">
                <component
                  v-if="column.format && typeof renderCell(row, column) === 'object'"
                  :is="renderCell(row, column).template"
                  v-bind="renderCell(row, column).data">
                  {{ renderCell(row, column).data.value }}
                </component>
                <template v-else>
                  {{ renderCell(row, column) }}
                </template>
              </span>
            </div>
            <div class="flex justify-end pt-2 border-t">
              <slot name="actions" :row="row" />
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Pagination -->
      <div
        v-if="showPagination"
        class="flex flex-col sm:flex-row items-center justify-between px-2 gap-4">
        <div class="text-sm text-muted-foreground order-2 sm:order-1">
          Showing {{ (props.data.current_page - 1) * 15 + 1 }} to
          {{ Math.min(props.data.current_page * 15, props.data.total) }}
          of {{ props.data.total }} entries
        </div>
        <div class="flex items-center space-x-2 order-1 sm:order-2">
          <Link
            v-if="props.data.current_page > 1"
            :href="props.data.links[0].url"
            preserve-scroll
            preserve-state>
            <Button
              variant="outline"
              class="h-8 w-8 p-0" >
              <ChevronsLeftIcon class="h-4 w-4" />
            </Button>
          </Link>
          <Link
            v-for="link in paginationLinks"
            :key="link.label"
            :href="link.url"
            preserve-scroll
            preserve-state>
            <Button
              :variant="link.active ? 'default' : 'outline-solid'"
              class="h-8 min-w-8 px-3">
              {{ link.label }}
            </Button>
          </Link>
          <Link
            v-if="props.data.current_page < props.data.last_page"
            :href="props.data.links[props.data.links.length - 1].url"
            preserve-scroll
            preserve-state>
            <Button
              variant="outline"
              class="h-8 w-8 p-0">
              <ChevronsRightIcon class="h-4 w-4" />
            </Button>
          </Link>
        </div>
      </div>
    </template>
  </div>
</template>


