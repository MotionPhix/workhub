<template>
  <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6 hover:shadow-lg transition-all duration-200 group">
    <!-- Project Header -->
    <div class="flex items-start justify-between mb-4">
      <div class="flex-1 min-w-0">
        <Link
          :href="route('projects.show', project.uuid)"
          class="text-lg font-semibold text-gray-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400 transition-colors line-clamp-2"
        >
          {{ project.name }}
        </Link>
        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
          {{ project.department?.name }}
        </p>
      </div>

      <!-- Actions Dropdown -->
      <div class="relative ml-4">
        <button
          @click="toggleDropdown"
          class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors opacity-0 group-hover:opacity-100"
        >
          <MoreVerticalIcon class="w-5 h-5" />
        </button>

        <div v-if="dropdownOpen" class="absolute right-0 top-10 w-48 bg-white dark:bg-gray-700 rounded-lg shadow-lg border border-gray-200 dark:border-gray-600 py-1 z-10">
          <Link
            :href="route('projects.show', project.uuid)"
            class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600"
            @click="closeDropdown"
          >
            <EyeIcon class="w-4 h-4 inline mr-2" />
            View Details
          </Link>
          <Link
            :href="route('projects.edit', project.uuid)"
            class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600"
            @click="closeDropdown"
          >
            <EditIcon class="w-4 h-4 inline mr-2" />
            Edit Project
          </Link>
          <button
            @click="handleArchive"
            class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600"
          >
            <ArchiveIcon class="w-4 h-4 inline mr-2" />
            {{ project.status === 'cancelled' ? 'Reactivate' : 'Archive' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Project Description -->
    <p class="text-gray-600 dark:text-gray-400 text-sm line-clamp-2 mb-4">
      {{ project.description || 'No description provided' }}
    </p>

    <!-- Project Stats -->
    <div class="grid grid-cols-2 gap-4 mb-4">
      <div class="text-center">
        <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
          {{ project.work_entries_count || 0 }}
        </div>
        <div class="text-xs text-gray-500 dark:text-gray-400">Tasks</div>
      </div>
      <div class="text-center">
        <div class="text-2xl font-bold text-green-600 dark:text-green-400">
          {{ Math.round(project.completion_percentage || 0) }}%
        </div>
        <div class="text-xs text-gray-500 dark:text-gray-400">Complete</div>
      </div>
    </div>

    <!-- Progress Bar -->
    <div class="mb-4">
      <div class="flex justify-between items-center mb-2">
        <span class="text-sm text-gray-600 dark:text-gray-400">Progress</span>
        <span class="text-sm font-medium text-gray-900 dark:text-white">
          {{ Math.round(project.completion_percentage || 0) }}%
        </span>
      </div>
      <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
        <div
          class="h-2 rounded-full transition-all duration-300"
          :class="getProgressColor(project.completion_percentage)"
          :style="{ width: `${project.completion_percentage || 0}%` }"
        />
      </div>
    </div>

    <!-- Status and Priority Badges -->
    <div class="flex items-center gap-2 mb-3">
      <StatusBadge :status="project.status" />
      <PriorityBadge :priority="project.priority" />
      <div v-if="project.is_shared" class="text-xs text-blue-600 dark:text-blue-400 font-medium bg-blue-50 dark:bg-blue-900/20 px-2 py-1 rounded">
        Shared
      </div>
    </div>

    <!-- Project Manager -->
    <div class="flex items-center gap-2 pt-3 border-t border-gray-200 dark:border-gray-700">
      <UserAvatar
        :fallback="getInitials(project.manager?.name || 'Unknown')"
        class="w-6 h-6 text-xs"
      />
      <span class="text-sm text-gray-600 dark:text-gray-400">
        {{ project.manager?.name || 'No Manager' }}
      </span>
    </div>

    <!-- Due Date at Bottom -->
    <div class="mt-3 pt-2 border-t border-gray-100 dark:border-gray-600">
      <div class="flex items-center justify-between">
        <span class="text-xs text-gray-500 dark:text-gray-400">Due Date</span>
        <span
          class="text-sm font-medium"
          :class="getDueDateColor(project.due_date)"
        >
          {{ formatDate(project.due_date) }}
        </span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import {
  MoreVerticalIcon,
  EyeIcon,
  EditIcon,
  ArchiveIcon
} from 'lucide-vue-next'
import StatusBadge from '@/components/projects/StatusBadge.vue'
import PriorityBadge from '@/components/projects/PriorityBadge.vue'
import UserAvatar from '@/components/UserAvatar.vue'
import { getInitials } from '@/lib/stringUtils'

const props = defineProps({
  project: Object
})

const emit = defineEmits(['updated'])

const dropdownOpen = ref(false)

const toggleDropdown = () => {
  dropdownOpen.value = !dropdownOpen.value
}

const closeDropdown = () => {
  dropdownOpen.value = false
}

const handleArchive = () => {
  router.post(route('projects.archive', props.project.uuid), {}, {
    preserveScroll: true,
    onSuccess: () => {
      emit('updated')
    }
  })
  closeDropdown()
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

const formatDate = (date) => {
  if (!date) return 'No due date'
  return new Date(date).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric'
  })
}
</script>
