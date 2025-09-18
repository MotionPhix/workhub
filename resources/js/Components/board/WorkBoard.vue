<script setup lang="ts">
import { ref, computed } from 'vue'
import BoardColumn from './BoardColumn.vue'
import BoardCard from './BoardCard.vue'
import { Card } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Plus, Filter, ArrowUpDown, Search } from 'lucide-vue-next'

interface BoardItem {
  id: string | number
  title: string
  status: string
  priority: 'low' | 'medium' | 'high' | 'urgent'
  assignee?: {
    name: string
    avatar?: string
    email?: string
  }
  dueDate?: string
  progress?: number
  tags?: string[]
  description?: string
  type?: string
}

interface Props {
  title: string
  columns: Array<{
    id: string
    title: string
    color: string
    items: BoardItem[]
  }>
  loading?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  loading: false
})

const emit = defineEmits<{
  'item-moved': [itemId: string | number, fromColumn: string, toColumn: string, newIndex: number]
  'item-clicked': [item: BoardItem]
  'add-item': [columnId: string]
  'filter-changed': [filters: Record<string, any>]
}>()

const searchQuery = ref('')
const selectedFilters = ref({
  priority: '',
  assignee: '',
  dueDate: ''
})

const filteredColumns = computed(() => {
  if (!searchQuery.value && !Object.values(selectedFilters.value).some(v => v)) {
    return props.columns
  }

  return props.columns.map(column => ({
    ...column,
    items: column.items.filter(item => {
      const matchesSearch = !searchQuery.value ||
        item.title.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
        item.description?.toLowerCase().includes(searchQuery.value.toLowerCase())

      const matchesPriority = !selectedFilters.value.priority ||
        item.priority === selectedFilters.value.priority

      const matchesAssignee = !selectedFilters.value.assignee ||
        item.assignee?.name.toLowerCase().includes(selectedFilters.value.assignee.toLowerCase())

      return matchesSearch && matchesPriority && matchesAssignee
    })
  }))
})

function onItemMoved(itemId: string | number, fromColumn: string, toColumn: string, newIndex: number) {
  emit('item-moved', itemId, fromColumn, toColumn, newIndex)
}

function onAddItem(columnId: string) {
  emit('add-item', columnId)
}

function onItemClicked(item: BoardItem) {
  emit('item-clicked', item)
}
</script>

<template>
  <div class="work-board">
    <!-- Board Header -->
    <div class="mb-6">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">{{ title }}</h2>
        <div class="flex items-center space-x-3">
          <Button variant="outline" size="sm" class="flex items-center">
            <Filter class="w-4 h-4 mr-2" />
            Filter
          </Button>
          <Button variant="outline" size="sm" class="flex items-center">
            <ArrowUpDown class="w-4 h-4 mr-2" />
            Sort
          </Button>
          <Button size="sm" class="flex items-center">
            <Plus class="w-4 h-4 mr-2" />
            New Item
          </Button>
        </div>
      </div>

      <!-- Search and Filters -->
      <div class="flex items-center space-x-4">
        <div class="relative flex-1 max-w-md">
          <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400" />
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Search items..."
            class="w-full pl-10 pr-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          />
        </div>
        <select
          v-model="selectedFilters.priority"
          class="px-3 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
        >
          <option value="">All Priorities</option>
          <option value="urgent">Urgent</option>
          <option value="high">High</option>
          <option value="medium">Medium</option>
          <option value="low">Low</option>
        </select>
      </div>
    </div>

    <!-- Board Columns -->
    <div class="flex gap-6 overflow-x-auto pb-6" style="min-height: 600px;">
      <BoardColumn
        v-for="column in filteredColumns"
        :key="column.id"
        :column="column"
        :loading="loading"
        @item-moved="onItemMoved"
        @add-item="() => onAddItem(column.id)"
        @item-clicked="onItemClicked"
      />
    </div>
  </div>
</template>

<style scoped>
.work-board {
  min-width: 100%;
}

/* Custom scrollbar */
.work-board ::-webkit-scrollbar {
  height: 8px;
}

.work-board ::-webkit-scrollbar-track {
  background: #f3f4f6;
  border-radius: 4px;
}

.dark .work-board ::-webkit-scrollbar-track {
  background: #374151;
}

.work-board ::-webkit-scrollbar-thumb {
  background: #d1d5db;
  border-radius: 4px;
}

.work-board ::-webkit-scrollbar-thumb:hover {
  background: #9ca3af;
}

.dark .work-board ::-webkit-scrollbar-thumb {
  background: #4b5563;
}

.dark .work-board ::-webkit-scrollbar-thumb:hover {
  background: #6b7280;
}
</style>
