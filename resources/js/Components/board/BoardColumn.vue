<script setup lang="ts">
import { ref, computed } from 'vue'
import BoardCard from './BoardCard.vue'
import { Button } from '@/components/ui/button'
import { Plus, MoreHorizontal } from 'lucide-vue-next'

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

interface Column {
  id: string
  title: string
  color: string
  items: BoardItem[]
}

interface Props {
  column: Column
  loading?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  loading: false
})

const emit = defineEmits<{
  'item-moved': [itemId: string | number, fromColumn: string, toColumn: string, newIndex: number]
  'item-clicked': [item: BoardItem]
  'add-item': []
}>()

const columnRef = ref<HTMLElement>()
const isDragOver = ref(false)

const columnColorClasses = computed(() => {
  const baseClasses = 'w-1 rounded-full'
  const colorMap = {
    blue: 'bg-blue-500',
    green: 'bg-green-500',
    yellow: 'bg-yellow-500',
    red: 'bg-red-500',
    purple: 'bg-purple-500',
    indigo: 'bg-indigo-500',
    pink: 'bg-pink-500',
    gray: 'bg-gray-500'
  }
  
  return `${baseClasses} ${colorMap[props.column.color as keyof typeof colorMap] || 'bg-gray-500'}`
})

function onItemClicked(item: BoardItem) {
  emit('item-clicked', item)
}

function onAddItem() {
  emit('add-item')
}

function onDragOver(event: DragEvent) {
  event.preventDefault()
  isDragOver.value = true
}

function onDragLeave(event: DragEvent) {
  event.preventDefault()
  if (!columnRef.value?.contains(event.relatedTarget as Node)) {
    isDragOver.value = false
  }
}

function onDrop(event: DragEvent) {
  event.preventDefault()
  isDragOver.value = false
  
  const itemId = event.dataTransfer?.getData('text/plain')
  const fromColumn = event.dataTransfer?.getData('application/column-id')
  
  if (itemId && fromColumn && fromColumn !== props.column.id) {
    emit('item-moved', itemId, fromColumn, props.column.id, props.column.items.length)
  }
}
</script>

<template>
  <div class="board-column flex-shrink-0 w-80">
    <!-- Column Header -->
    <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-800 rounded-t-lg border-b border-gray-200 dark:border-gray-700">
      <div class="flex items-center space-x-3">
        <div :class="columnColorClasses" style="height: 24px;"></div>
        <h3 class="font-semibold text-gray-900 dark:text-white">{{ column.title }}</h3>
        <span class="bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-400 text-xs px-2 py-1 rounded-full">
          {{ column.items.length }}
        </span>
      </div>
      <div class="flex items-center space-x-1">
        <Button variant="ghost" size="sm" @click="onAddItem" class="h-8 w-8 p-0">
          <Plus class="w-4 h-4" />
        </Button>
        <Button variant="ghost" size="sm" class="h-8 w-8 p-0">
          <MoreHorizontal class="w-4 h-4" />
        </Button>
      </div>
    </div>

    <!-- Column Content -->
    <div 
      ref="columnRef"
      :data-column-id="column.id"
      :class="[
        'bg-gray-50 dark:bg-gray-800 rounded-b-lg min-h-[500px] p-3 space-y-3 transition-colors',
        isDragOver ? 'bg-blue-50 dark:bg-blue-900/20 border-2 border-blue-300 border-dashed' : ''
      ]"
      @dragover="onDragOver"
      @dragleave="onDragLeave"
      @drop="onDrop"
    >
      <template v-if="loading">
        <!-- Loading skeleton -->
        <div v-for="n in 3" :key="n" class="animate-pulse">
          <div class="bg-white dark:bg-gray-700 p-4 rounded-lg shadow-sm">
            <div class="h-4 bg-gray-200 dark:bg-gray-600 rounded mb-2"></div>
            <div class="h-3 bg-gray-200 dark:bg-gray-600 rounded mb-2 w-2/3"></div>
            <div class="h-3 bg-gray-200 dark:bg-gray-600 rounded w-1/2"></div>
          </div>
        </div>
      </template>

      <template v-else>
        <BoardCard
          v-for="item in column.items"
          :key="item.id"
          :item="item"
          :data-item-id="item.id"
          @click="onItemClicked(item)"
        />
        
        <!-- Empty state -->
        <div v-if="column.items.length === 0" class="text-center py-8">
          <div class="text-gray-400 dark:text-gray-500 mb-2">
            <div class="w-12 h-12 mx-auto bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mb-3">
              <Plus class="w-6 h-6" />
            </div>
            <p class="text-sm">No items in this column</p>
          </div>
          <Button variant="ghost" size="sm" @click="onAddItem" class="mt-2">
            Add first item
          </Button>
        </div>
      </template>
    </div>
  </div>
</template>

<style scoped>
.sortable-ghost {
  opacity: 0.5;
}

.sortable-chosen {
  transform: scale(1.05);
}

.sortable-drag {
  transform: rotate(3deg);
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
}

.board-column {
  min-width: 320px;
}
</style>
