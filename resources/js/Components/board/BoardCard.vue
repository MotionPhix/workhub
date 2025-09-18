<script setup lang="ts">
import { computed } from 'vue'
import { Card } from '@/components/ui/card'
import Badge from '@/components/ui/badge.vue'
import Avatar from '@/components/ui/avatar.vue'
import AvatarFallback from '@/components/ui/avatar-fallback.vue'
import AvatarImage from '@/components/ui/avatar-image.vue'
import { 
  Calendar, 
  Clock, 
  Flag,
  User,
  CheckCircle,
  AlertCircle,
  MoreVertical
} from 'lucide-vue-next'

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
  item: BoardItem
}

const props = defineProps<Props>()

const priorityConfig = computed(() => {
  const configs = {
    urgent: {
      color: 'bg-red-500',
      textColor: 'text-red-700 dark:text-red-300',
      bgColor: 'bg-red-50 dark:bg-red-900/20',
      icon: AlertCircle
    },
    high: {
      color: 'bg-orange-500',
      textColor: 'text-orange-700 dark:text-orange-300',
      bgColor: 'bg-orange-50 dark:bg-orange-900/20',
      icon: Flag
    },
    medium: {
      color: 'bg-yellow-500',
      textColor: 'text-yellow-700 dark:text-yellow-300',
      bgColor: 'bg-yellow-50 dark:bg-yellow-900/20',
      icon: Clock
    },
    low: {
      color: 'bg-green-500',
      textColor: 'text-green-700 dark:text-green-300',
      bgColor: 'bg-green-50 dark:bg-green-900/20',
      icon: CheckCircle
    }
  }
  
  return configs[props.item.priority] || configs.medium
})

const formattedDueDate = computed(() => {
  if (!props.item.dueDate) return null
  
  try {
    const date = new Date(props.item.dueDate)
    const today = new Date()
    const timeDiff = date.getTime() - today.getTime()
    const daysDiff = Math.ceil(timeDiff / (1000 * 3600 * 24))
    
    if (daysDiff < 0) return { text: 'Overdue', class: 'text-red-600 dark:text-red-400' }
    if (daysDiff === 0) return { text: 'Due today', class: 'text-orange-600 dark:text-orange-400' }
    if (daysDiff === 1) return { text: 'Due tomorrow', class: 'text-yellow-600 dark:text-yellow-400' }
    if (daysDiff <= 7) return { text: `Due in ${daysDiff} days`, class: 'text-blue-600 dark:text-blue-400' }
    
    return { 
      text: date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }), 
      class: 'text-gray-600 dark:text-gray-400' 
    }
  } catch {
    return null
  }
})

const getInitials = (name: string) => {
  return name
    .split(' ')
    .map(word => word[0])
    .join('')
    .toUpperCase()
    .slice(0, 2)
}

const emit = defineEmits<{
  click: []
}>()

function onDragStart(event: DragEvent) {
  if (event.dataTransfer) {
    event.dataTransfer.setData('text/plain', props.item.id.toString())
    event.dataTransfer.setData('application/column-id', props.item.status)
  }
}

function onClick() {
  emit('click')
}
</script>

<template>
  <Card 
    class="board-card group cursor-pointer hover:shadow-md transition-all duration-200 border-l-4 hover:border-l-blue-500"
    draggable="true"
    @dragstart="onDragStart"
    @click="onClick"
  >
    <div class="p-4">
      <!-- Card Header -->
      <div class="flex items-start justify-between mb-3">
        <h4 class="font-medium text-gray-900 dark:text-white text-sm line-clamp-2 flex-1 pr-2">
          {{ item.title }}
        </h4>
        <button class="opacity-0 group-hover:opacity-100 transition-opacity p-1 hover:bg-gray-100 dark:hover:bg-gray-700 rounded">
          <MoreVertical class="w-4 h-4 text-gray-400" />
        </button>
      </div>

      <!-- Description (if available) -->
      <p v-if="item.description" class="text-sm text-gray-600 dark:text-gray-400 mb-3 line-clamp-2">
        {{ item.description }}
      </p>

      <!-- Progress Bar -->
      <div v-if="item.progress !== undefined" class="mb-3">
        <div class="flex items-center justify-between text-xs text-gray-600 dark:text-gray-400 mb-1">
          <span>Progress</span>
          <span>{{ item.progress }}%</span>
        </div>
        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
          <div 
            class="bg-blue-500 h-2 rounded-full transition-all duration-300"
            :style="{ width: `${item.progress}%` }"
          ></div>
        </div>
      </div>

      <!-- Tags -->
      <div v-if="item.tags && item.tags.length" class="flex flex-wrap gap-1 mb-3">
        <Badge
          v-for="tag in item.tags.slice(0, 3)"
          :key="tag"
          variant="secondary"
          class="text-xs px-2 py-0.5"
        >
          {{ tag }}
        </Badge>
        <Badge
          v-if="item.tags.length > 3"
          variant="outline"
          class="text-xs px-2 py-0.5"
        >
          +{{ item.tags.length - 3 }}
        </Badge>
      </div>

      <!-- Card Footer -->
      <div class="flex items-center justify-between">
        <!-- Left side - Priority and Due Date -->
        <div class="flex items-center space-x-2">
          <!-- Priority Indicator -->
          <div 
            :class="[priorityConfig.bgColor, priorityConfig.textColor]" 
            class="flex items-center px-2 py-1 rounded-full text-xs font-medium"
          >
            <component :is="priorityConfig.icon" class="w-3 h-3 mr-1" />
            <span class="capitalize">{{ item.priority }}</span>
          </div>
          
          <!-- Due Date -->
          <div v-if="formattedDueDate" :class="formattedDueDate.class" class="flex items-center text-xs font-medium">
            <Calendar class="w-3 h-3 mr-1" />
            {{ formattedDueDate.text }}
          </div>
        </div>

        <!-- Right side - Assignee -->
        <div v-if="item.assignee" class="flex items-center">
          <Avatar class="w-6 h-6">
            <AvatarImage 
              v-if="item.assignee.avatar"
              :src="item.assignee.avatar" 
              :alt="item.assignee.name" 
            />
            <AvatarFallback class="text-xs bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300">
              {{ getInitials(item.assignee.name) }}
            </AvatarFallback>
          </Avatar>
        </div>
      </div>
    </div>
  </Card>
</template>

<style scoped>
.board-card {
  transition: transform 0.2s ease-in-out;
}

.board-card:hover {
  transform: scale(1.02);
}

.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.border-l-4 {
  border-left-width: 4px;
}
</style>
