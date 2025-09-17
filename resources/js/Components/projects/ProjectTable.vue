<template>
  <div class="overflow-x-auto">
    <table class="w-full">
      <thead class="bg-gray-50 dark:bg-gray-700">
        <tr>
          <th
            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600"
            @click="$emit('sort', 'name')"
          >
            <div class="flex items-center gap-2">
              Project Name
              <ArrowUpDownIcon class="w-4 h-4" />
            </div>
          </th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
            Status
          </th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
            Priority
          </th>
          <th
            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600"
            @click="$emit('sort', 'completion_percentage')"
          >
            <div class="flex items-center gap-2">
              Progress
              <ArrowUpDownIcon class="w-4 h-4" />
            </div>
          </th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
            Manager
          </th>
          <th
            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600"
            @click="$emit('sort', 'due_date')"
          >
            <div class="flex items-center gap-2">
              Due Date
              <ArrowUpDownIcon class="w-4 h-4" />
            </div>
          </th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
            Tasks
          </th>
          <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
            Actions
          </th>
        </tr>
      </thead>
      <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
        <tr
          v-for="project in projects"
          :key="project.uuid"
          class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
        >
          <!-- Project Name & Department -->
          <td class="px-6 py-4 whitespace-nowrap">
            <div class="flex flex-col">
              <Link
                :href="route('projects.show', project.uuid)"
                class="text-sm font-medium text-gray-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400 transition-colors"
              >
                {{ project.name }}
              </Link>
              <div class="text-sm text-gray-500 dark:text-gray-400">
                {{ project.department?.name }}
              </div>
            </div>
          </td>

          <!-- Status -->
          <td class="px-6 py-4 whitespace-nowrap">
            <StatusBadge :status="project.status" />
          </td>

          <!-- Priority -->
          <td class="px-6 py-4 whitespace-nowrap">
            <PriorityBadge :priority="project.priority" />
          </td>

          <!-- Progress -->
          <td class="px-6 py-4 whitespace-nowrap">
            <div class="flex items-center">
              <div class="flex-1 w-16">
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                  <div
                    class="h-2 rounded-full transition-all duration-300"
                    :class="getProgressColor(project.completion_percentage)"
                    :style="{ width: `${project.completion_percentage || 0}%` }"
                  />
                </div>
              </div>
              <span class="ml-2 text-sm font-medium text-gray-900 dark:text-white">
                {{ Math.round(project.completion_percentage || 0) }}%
              </span>
            </div>
          </td>

          <!-- Manager -->
          <td class="px-6 py-4 whitespace-nowrap">
            <div class="flex items-center gap-2">
              <UserAvatar
                :fallback="getInitials(project.manager?.name || 'U')"
                class="w-6 h-6 text-xs"
              />
              <span class="text-sm text-gray-900 dark:text-white">
                {{ project.manager?.name || 'Unassigned' }}
              </span>
            </div>
          </td>

          <!-- Due Date -->
          <td class="px-6 py-4 whitespace-nowrap">
            <div
              class="text-sm font-medium"
              :class="getDueDateColor(project.due_date)"
            >
              {{ formatDate(project.due_date) }}
            </div>
            <div v-if="isOverdue(project.due_date)" class="text-xs text-red-500 dark:text-red-400">
              Overdue
            </div>
          </td>

          <!-- Tasks Count -->
          <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
            <div class="flex items-center gap-1">
              <ClipboardListIcon class="w-4 h-4 text-gray-400" />
              {{ project.work_entries_count || 0 }}
            </div>
          </td>

          <!-- Actions -->
          <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
            <div class="flex items-center justify-end gap-2">
              <Link
                :href="route('projects.show', project.uuid)"
                class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
              >
                <EyeIcon class="w-4 h-4" />
              </Link>
              <Link
                :href="route('projects.edit', project.uuid)"
                class="text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-300"
              >
                <EditIcon class="w-4 h-4" />
              </Link>
              <button
                @click="handleArchive(project)"
                class="text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-300"
              >
                <ArchiveIcon class="w-4 h-4" />
              </button>
            </div>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
import { Link, router } from '@inertiajs/vue3'
import {
  ArrowUpDownIcon,
  EyeIcon,
  EditIcon,
  ArchiveIcon,
  ClipboardListIcon
} from 'lucide-vue-next'
import StatusBadge from '@/components/projects/StatusBadge.vue'
import PriorityBadge from '@/components/projects/PriorityBadge.vue'
import UserAvatar from '@/components/UserAvatar.vue'
import { getInitials } from '@/lib/stringUtils'

const props = defineProps({
  projects: Array,
  sortBy: String,
  sortDirection: String
})

const emit = defineEmits(['sort', 'updated'])

const handleArchive = (project) => {
  router.post(route('projects.archive', project.uuid), {}, {
    preserveScroll: true,
    onSuccess: () => {
      emit('updated')
    }
  })
}

const getProgressColor = (percentage) => {
  if (percentage >= 80) return 'bg-green-500'
  if (percentage >= 60) return 'bg-blue-500'
  if (percentage >= 40) return 'bg-yellow-500'
  if (percentage >= 20) return 'bg-orange-500'
  return 'bg-red-500'
}

const getDueDateColor = (dueDate) => {
  if (!dueDate) return 'text-gray-500 dark:text-gray-400'

  const today = new Date()
  const due = new Date(dueDate)
  const diffTime = due - today
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))

  if (diffDays < 0) return 'text-red-600 dark:text-red-400' // Overdue
  if (diffDays <= 3) return 'text-orange-600 dark:text-orange-400' // Due soon
  if (diffDays <= 7) return 'text-yellow-600 dark:text-yellow-400' // Due this week
  return 'text-gray-600 dark:text-gray-400' // Normal
}

const isOverdue = (dueDate) => {
  if (!dueDate) return false
  return new Date(dueDate) < new Date()
}

const formatDate = (date) => {
  if (!date) return 'No due date'
  return new Date(date).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric'
  })
}
</script>
