<template>
  <AppLayout>
    <Head :title="project.name" />

    <!-- Monday.com style project header -->
    <div class="mb-8">
      <!-- Breadcrumb -->
      <nav class="flex items-center text-sm text-gray-500 dark:text-gray-400 mb-4">
        <Link :href="route('projects.index')" class="hover:text-gray-700 dark:hover:text-gray-300">
          Projects
        </Link>
        <ChevronRightIcon class="w-4 h-4 mx-2" />
        <span class="text-gray-900 dark:text-white">{{ project.name }}</span>
      </nav>

      <!-- Project Title & Actions -->
      <div class="flex items-start justify-between mb-6">
        <div class="flex-1 min-w-0">
          <h1 class="text-3xl font-bold text-gray-900 dark:text-white">{{ project.name }}</h1>
          <div class="flex items-center gap-4 mt-2">
            <StatusBadge :status="project.status" />
            <PriorityBadge :priority="project.priority" />
            <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
              <BuildingIcon class="w-4 h-4" />
              {{ project.department?.name }}
            </div>
          </div>
        </div>

        <div class="flex items-center gap-3">
          <Link
            :href="route('work-entries.create', { project_uuid: project.uuid })"
            class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium inline-flex items-center gap-2 transition-colors"
          >
            <PlusIcon class="w-4 h-4" />
            Add Task
          </Link>
          <Link
            :href="route('projects.edit', project.uuid)"
            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium inline-flex items-center gap-2 transition-colors"
          >
            <EditIcon class="w-4 h-4" />
            Edit Project
          </Link>
        </div>
      </div>
    </div>

    <!-- Monday.com style project dashboard -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

      <!-- Main Content -->
      <div class="lg:col-span-2 space-y-8">

        <!-- Project Overview -->
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Project Overview</h2>

          <!-- Progress Section -->
          <div class="mb-6">
            <div class="flex items-center justify-between mb-2">
              <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Overall Progress</span>
              <span class="text-sm font-bold text-gray-900 dark:text-white">
                {{ Math.round(project.completion_percentage || 0) }}%
              </span>
            </div>
            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
              <div
                class="h-3 rounded-full transition-all duration-500"
                :class="getProgressColor(project.completion_percentage)"
                :style="{ width: `${project.completion_percentage || 0}%` }"
              />
            </div>
          </div>

          <!-- Project Description -->
          <div>
            <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-2">Description</h3>
            <p class="text-gray-600 dark:text-gray-400 leading-relaxed">
              {{ project.description || 'No description provided for this project.' }}
            </p>
          </div>
        </div>

        <!-- Recent Tasks -->
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
          <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Recent Tasks</h2>
            <Link
              :href="route('work-entries.index', { project_uuid: project.uuid })"
              class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 text-sm font-medium"
            >
              View All →
            </Link>
          </div>

          <div v-if="project.work_entries && project.work_entries.length > 0" class="space-y-4">
            <div
              v-for="entry in project.work_entries"
              :key="entry.id"
              class="flex items-center justify-between p-4 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
            >
              <div class="flex-1 min-w-0">
                <h4 class="font-medium text-gray-900 dark:text-white">
                  {{ entry.work_title }}
                </h4>
                <div class="flex items-center gap-4 mt-1 text-sm text-gray-600 dark:text-gray-400">
                  <div class="flex items-center gap-1">
                    <UserIcon class="w-4 h-4" />
                    {{ entry.user?.name }}
                  </div>
                  <div class="flex items-center gap-1">
                    <CalendarIcon class="w-4 h-4" />
                    {{ formatDate(entry.start_date_time) }}
                  </div>
                </div>
              </div>
              <div class="flex items-center gap-3">
                <StatusBadge :status="entry.status" />
                <PriorityBadge :priority="entry.priority" />
              </div>
            </div>
          </div>

          <div v-else class="text-center py-8">
            <ClipboardListIcon class="w-12 h-12 text-gray-400 mx-auto mb-3" />
            <p class="text-gray-600 dark:text-gray-400">No tasks added to this project yet</p>
            <Link
              :href="route('work-entries.create', { project_uuid: project.uuid })"
              class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 text-sm font-medium"
            >
              Create your first task
            </Link>
          </div>
        </div>
      </div>

      <!-- Sidebar -->
      <div class="space-y-6">

        <!-- Project Stats -->
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Project Stats</h3>

          <div class="space-y-4">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <ClipboardListIcon class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                <span class="text-gray-700 dark:text-gray-300">Total Tasks</span>
              </div>
              <span class="font-semibold text-gray-900 dark:text-white">
                {{ stats.total_work_entries }}
              </span>
            </div>

            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <CheckCircleIcon class="w-5 h-5 text-green-600 dark:text-green-400" />
                <span class="text-gray-700 dark:text-gray-300">Completed</span>
              </div>
              <span class="font-semibold text-gray-900 dark:text-white">
                {{ stats.completed_entries }}
              </span>
            </div>

            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <ClockIcon class="w-5 h-5 text-orange-600 dark:text-orange-400" />
                <span class="text-gray-700 dark:text-gray-300">Total Hours</span>
              </div>
              <span class="font-semibold text-gray-900 dark:text-white">
                {{ Math.round(stats.total_hours || 0) }}h
              </span>
            </div>

            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <UsersIcon class="w-5 h-5 text-purple-600 dark:text-purple-400" />
                <span class="text-gray-700 dark:text-gray-300">Team Members</span>
              </div>
              <span class="font-semibold text-gray-900 dark:text-white">
                {{ stats.team_members }}
              </span>
            </div>
          </div>
        </div>

        <!-- Project Details -->
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Project Details</h3>

          <div class="space-y-4">
            <div>
              <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Manager</label>
              <div class="flex items-center gap-2 mt-1">
                <UserAvatar
                  :fallback="getInitials(project.manager?.name || 'U')"
                  class="w-8 h-8"
                />
                <div>
                  <div class="font-medium text-gray-900 dark:text-white">
                    {{ project.manager?.name || 'No manager assigned' }}
                  </div>
                  <div v-if="project.manager?.email" class="text-sm text-gray-600 dark:text-gray-400">
                    {{ project.manager.email }}
                  </div>
                </div>
              </div>
            </div>

            <div>
              <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Start Date</label>
              <div class="flex items-center gap-2 mt-1">
                <CalendarIcon class="w-4 h-4 text-gray-400" />
                <span class="text-gray-900 dark:text-white">
                  {{ formatDate(project.start_date) }}
                </span>
              </div>
            </div>

            <div>
              <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Due Date</label>
              <div class="flex items-center gap-2 mt-1">
                <CalendarIcon class="w-4 h-4 text-gray-400" />
                <span
                  class="font-medium"
                  :class="getDueDateColor(project.due_date)"
                >
                  {{ formatDate(project.due_date) }}
                </span>
              </div>
              <div v-if="isOverdue(project.due_date)" class="text-sm text-red-600 dark:text-red-400 mt-1">
                {{ getDaysOverdue(project.due_date) }} days overdue
              </div>
            </div>

            <div v-if="project.estimated_hours">
              <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Estimated Hours</label>
              <div class="flex items-center gap-2 mt-1">
                <ClockIcon class="w-4 h-4 text-gray-400" />
                <span class="text-gray-900 dark:text-white">
                  {{ project.estimated_hours }}h
                </span>
              </div>
            </div>

            <div v-if="project.is_shared" class="flex items-center gap-2 p-3 bg-blue-50 dark:bg-blue-900/30 rounded-lg">
              <ShareIcon class="w-4 h-4 text-blue-600 dark:text-blue-400" />
              <span class="text-sm font-medium text-blue-800 dark:text-blue-300">
                Shared Project
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import StatusBadge from '@/components/projects/StatusBadge.vue'
import PriorityBadge from '@/components/projects/PriorityBadge.vue'
import UserAvatar from '@/components/UserAvatar.vue'
import { getInitials } from '@/lib/stringUtils'
import {
  ChevronRightIcon,
  BuildingIcon,
  PlusIcon,
  EditIcon,
  ClipboardListIcon,
  UserIcon,
  CalendarIcon,
  CheckCircleIcon,
  ClockIcon,
  UsersIcon,
  ShareIcon
} from 'lucide-vue-next'

const props = defineProps({
  project: Object,
  stats: Object
})

const getProgressColor = (percentage) => {
  if (percentage >= 80) return 'bg-green-500'
  if (percentage >= 60) return 'bg-blue-500'
  if (percentage >= 40) return 'bg-yellow-500'
  if (percentage >= 20) return 'bg-orange-500'
  return 'bg-red-500'
}

const getDueDateColor = (dueDate) => {
  if (!dueDate) return 'text-gray-600 dark:text-gray-400'

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

const getDaysOverdue = (dueDate) => {
  if (!dueDate) return 0
  const today = new Date()
  const due = new Date(dueDate)
  const diffTime = today - due
  return Math.ceil(diffTime / (1000 * 60 * 60 * 24))
}

const formatDate = (date) => {
  if (!date) return 'Not set'
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}
</script>
