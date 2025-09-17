<script setup lang="ts">
import { ref, onMounted, computed, nextTick } from 'vue'
import { gsap } from 'gsap'
import {
  FolderIcon,
  Clock,
  CheckCircle,
  TrendingUp,
  Activity,
  Plus,
  ChartBarIcon,
  Target,
  Users,
  Calendar,
  AlertCircle,
  Building,
  ArrowRight,
  MoreHorizontal
} from 'lucide-vue-next'
import AppLayout from '@/layouts/AppLayout.vue'
import { Link } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import StatusBadge from '@/components/projects/StatusBadge.vue'
import PriorityBadge from '@/components/projects/PriorityBadge.vue'
import UserAvatar from '@/components/UserAvatar.vue'
import { getInitials } from '@/lib/stringUtils'
import { useTheme } from '@/composables/useTheme'

interface DashboardProps {
  projects?: {
    active: Array<any>
    recent: Array<any>
    overdue: Array<any>
    total_count: number
    active_count: number
    completed_count: number
    overdue_count: number
  }
  work_entries?: {
    recent: Array<any>
    today_count: number
    week_count: number
    completion_rate: number
  }
  team_activity?: {
    active_users: number
    total_hours_today: number
    top_contributors: Array<any>
  }
  quick_stats?: {
    projects_completion_rate: number
    average_project_progress: number
    tasks_completed_today: number
    hours_logged_today: number
  }
  departments?: Array<any>
  // Employee dashboard props
  base_metrics?: any
  user_targets?: any
  recent_activity?: any
  personal_metrics?: any
  performance_insights?: any
  total_work_entries?: number
  total_hours_worked?: number
  recent_entries?: Array<any>
  this_week_hours?: number
  this_week_entries?: number
  average_hours_per_entry?: number
}

const props = defineProps<DashboardProps>()

// Initialize theme
const { isDark } = useTheme()

// Reactive state
const selectedView = ref('overview')
const selectedDepartment = ref('all')

// Computed
const filteredProjects = computed(() => {
  if (!props.projects?.active) return []
  if (selectedDepartment.value === 'all') {
    return props.projects.active
  }
  return props.projects.active.filter(project =>
    project.department?.uuid === selectedDepartment.value
  )
})

// Methods
const getProgressColor = (percentage: number): string => {
  if (percentage >= 80) return 'bg-green-500'
  if (percentage >= 60) return 'bg-blue-500'
  if (percentage >= 40) return 'bg-yellow-500'
  if (percentage >= 20) return 'bg-orange-500'
  return 'bg-red-500'
}

const formatDate = (date: string): string => {
  return new Date(date).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric'
  })
}

const getDaysUntilDue = (dueDate: string): number => {
  const today = new Date()
  const due = new Date(dueDate)
  const diffTime = due - today
  return Math.ceil(diffTime / (1000 * 60 * 60 * 24))
}

const getTimeOfDay = (): string => {
  const hour = new Date().getHours()
  if (hour < 12) return 'morning'
  if (hour < 17) return 'afternoon'
  return 'evening'
}

// Lifecycle hooks
onMounted(() => {
  nextTick(() => {
    gsap.set('.metric-card', { opacity: 0, y: 20 })
    gsap.to('.metric-card', {
      opacity: 1,
      y: 0,
      duration: 0.6,
      stagger: 0.1,
      ease: 'power2.out',
      delay: 0.1
    })
  })
})
</script>

<template>
  <AppLayout>
    <!-- Monday.com Style Header -->
    <div class="mb-8">
      <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-6 mb-6">
        <div>
          <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
            Good {{ getTimeOfDay() }}, {{ $page.props.auth.user.name.split(' ')[0] }}! 👋
          </h1>
          <p class="text-gray-600 dark:text-gray-400 mt-1">
            Here's what's happening with your projects today
          </p>
        </div>

        <!-- Quick Actions -->
        <div class="flex flex-wrap gap-3">
          <Link :href="route('projects.create')">
            <Button class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium inline-flex items-center gap-2">
              <Plus class="w-5 h-5" />
              New Project
            </Button>
          </Link>
          <Link :href="route('work-entries.create')">
            <Button variant="outline" class="px-6 py-3 rounded-lg font-medium inline-flex items-center gap-2">
              <Plus class="w-5 h-5" />
              Log Task
            </Button>
          </Link>
          <Link :href="route('reports.index')">
            <Button variant="outline" class="px-6 py-3 rounded-lg font-medium inline-flex items-center gap-2">
              <ChartBarIcon class="w-5 h-5" />
              Reports
            </Button>
          </Link>
        </div>
      </div>

      <!-- Filter Bar -->
      <div class="flex flex-wrap gap-4 items-center">
        <!-- View Toggle -->
        <div class="flex bg-gray-100 dark:bg-gray-800 rounded-lg p-1">
          <button
            @click="selectedView = 'overview'"
            class="px-4 py-2 rounded text-sm transition-colors"
            :class="selectedView === 'overview' ? 'bg-white dark:bg-gray-700 text-blue-600 dark:text-blue-400 shadow' : 'text-gray-600 dark:text-gray-400'"
          >
            Overview
          </button>
          <button
            @click="selectedView = 'projects'"
            class="px-4 py-2 rounded text-sm transition-colors"
            :class="selectedView === 'projects' ? 'bg-white dark:bg-gray-700 text-blue-600 dark:text-blue-400 shadow' : 'text-gray-600 dark:text-gray-400'"
          >
            Projects
          </button>
          <button
            @click="selectedView = 'team'"
            class="px-4 py-2 rounded text-sm transition-colors"
            :class="selectedView === 'team' ? 'bg-white dark:bg-gray-700 text-blue-600 dark:text-blue-400 shadow' : 'text-gray-600 dark:text-gray-400'"
          >
            Team
          </button>
        </div>

        <!-- Department Filter -->
        <select
          v-model="selectedDepartment"
          v-if="departments && departments.length > 0"
          class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
        >
          <option value="all">All Departments</option>
          <option v-for="dept in departments" :key="dept.uuid" :value="dept.uuid">
            {{ dept.name }}
          </option>
        </select>
      </div>
    </div>

    <!-- Key Metrics Cards -->
    <div v-if="projects" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
      <div
        v-for="(metric, index) in [
          {
            icon: FolderIcon,
            title: 'Active Projects',
            value: projects?.active_count || 0,
            change: projects?.total_count ? `+${Math.round((projects.active_count / projects.total_count) * 100)}%` : '0%',
            color: 'from-blue-500 to-blue-600',
            bgColor: 'bg-blue-50 dark:bg-blue-900/20',
            iconColor: 'text-blue-600 dark:text-blue-400',
            route: 'projects.index'
          },
          {
            icon: CheckCircle,
            title: 'Completion Rate',
            value: quick_stats?.projects_completion_rate || 0,
            suffix: '%',
            change: '+5.2%',
            color: 'from-green-500 to-green-600',
            bgColor: 'bg-green-50 dark:bg-green-900/20',
            iconColor: 'text-green-600 dark:text-green-400'
          },
          {
            icon: Clock,
            title: 'Hours Today',
            value: quick_stats?.hours_logged_today || 0,
            suffix: 'h',
            change: '+12%',
            color: 'from-purple-500 to-purple-600',
            bgColor: 'bg-purple-50 dark:bg-purple-900/20',
            iconColor: 'text-purple-600 dark:text-purple-400',
            route: 'work-entries.index'
          },
          {
            icon: Users,
            title: 'Team Members',
            value: team_activity?.active_users || 0,
            change: 'Active now',
            color: 'from-orange-500 to-orange-600',
            bgColor: 'bg-orange-50 dark:bg-orange-900/20',
            iconColor: 'text-orange-600 dark:text-orange-400'
          }
        ]"
        :key="index"
        class="metric-card group relative overflow-hidden bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm hover:shadow-lg transition-all duration-300 border border-gray-200 dark:border-gray-700 cursor-pointer"
        @click="metric.route ? $inertia.visit(route(metric.route)) : null"
      >
        <!-- Gradient Background -->
        <div
          :class="['absolute inset-0 opacity-0 group-hover:opacity-5 transition-opacity duration-300 bg-gradient-to-br', metric.color]"
        ></div>

        <!-- Card Content -->
        <div class="relative z-10">
          <div class="flex items-start justify-between mb-4">
            <div :class="['p-3 rounded-lg', metric.bgColor]">
              <component
                :is="metric.icon"
                :class="['w-6 h-6', metric.iconColor]"
              />
            </div>
            <div class="text-xs font-medium text-green-600 dark:text-green-400">
              {{ metric.change }}
            </div>
          </div>

          <div>
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">
              {{ metric.title }}
            </h3>
            <p class="text-3xl font-bold text-gray-900 dark:text-white">
              {{ metric.value }}<span v-if="metric.suffix" class="text-lg font-medium text-gray-500 dark:text-gray-400">{{ metric.suffix }}</span>
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Content Sections -->
    <div class="space-y-8">
      <!-- Employee Dashboard (when projects data is not available) -->
      <div v-if="!projects && (base_metrics || recent_entries)" class="space-y-8">
        <!-- Employee Stats -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <Clock class="h-8 w-8 text-blue-600 dark:text-blue-400" />
              </div>
              <div class="ml-5 w-0 flex-1">
                <dl>
                  <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Total Hours</dt>
                  <dd class="text-lg font-medium text-gray-900 dark:text-white">{{ total_hours_worked || 0 }}h</dd>
                </dl>
              </div>
            </div>
          </div>

          <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <CheckCircle class="h-8 w-8 text-green-600 dark:text-green-400" />
              </div>
              <div class="ml-5 w-0 flex-1">
                <dl>
                  <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Work Entries</dt>
                  <dd class="text-lg font-medium text-gray-900 dark:text-white">{{ total_work_entries || 0 }}</dd>
                </dl>
              </div>
            </div>
          </div>

          <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <Calendar class="h-8 w-8 text-purple-600 dark:text-purple-400" />
              </div>
              <div class="ml-5 w-0 flex-1">
                <dl>
                  <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">This Week</dt>
                  <dd class="text-lg font-medium text-gray-900 dark:text-white">{{ this_week_hours || 0 }}h</dd>
                </dl>
              </div>
            </div>
          </div>

          <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <TrendingUp class="h-8 w-8 text-orange-600 dark:text-orange-400" />
              </div>
              <div class="ml-5 w-0 flex-1">
                <dl>
                  <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Avg/Entry</dt>
                  <dd class="text-lg font-medium text-gray-900 dark:text-white">{{ average_hours_per_entry || 0 }}h</dd>
                </dl>
              </div>
            </div>
          </div>
        </div>

        <!-- Recent Work Entries -->
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
          <div class="flex items-center justify-between mb-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Recent Work Entries</h2>
            <Link :href="route('work-entries.index')" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 text-sm font-medium">
              View All
            </Link>
          </div>

          <div class="space-y-4">
            <div
              v-for="entry in (recent_entries || []).slice(0, 5)"
              :key="entry.uuid"
              class="flex items-center justify-between p-4 border border-gray-200 dark:border-gray-700 rounded-lg"
            >
              <div class="flex-1 min-w-0">
                <h4 class="font-medium text-gray-900 dark:text-white truncate">{{ entry.work_title }}</h4>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ entry.hours_worked }}h worked</p>
              </div>
              <div class="text-sm text-gray-500 dark:text-gray-400">
                {{ formatDate(entry.start_date_time) }}
              </div>
            </div>
          </div>

          <div v-if="!(recent_entries || []).length" class="text-center py-8">
            <Activity class="w-12 h-12 text-gray-400 mx-auto mb-3" />
            <p class="text-gray-600 dark:text-gray-400 mb-3">No work entries yet</p>
            <Link :href="route('work-entries.create')" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 text-sm font-medium">
              Log your first entry
            </Link>
          </div>
        </div>
      </div>

      <!-- Admin/Manager Dashboard (when projects data is available) -->
      <!-- Overview Section -->
      <div v-else-if="selectedView === 'overview'" class="grid grid-cols-1 lg:grid-cols-2 gap-8">

        <!-- Active Projects -->
        <div v-if="projects" class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
          <div class="flex items-center justify-between mb-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Active Projects</h2>
            <Link
              :href="route('projects.index')"
              class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 text-sm font-medium flex items-center gap-1"
            >
              View All
              <ArrowRight class="w-4 h-4" />
            </Link>
          </div>

          <div class="space-y-4">
            <div
              v-for="project in filteredProjects?.slice(0, 5) || []"
              :key="project.uuid"
              class="flex items-center justify-between p-4 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors cursor-pointer"
              @click="$inertia.visit(route('projects.show', project.uuid))"
            >
              <div class="flex-1 min-w-0">
                <h4 class="font-medium text-gray-900 dark:text-white truncate">
                  {{ project.name }}
                </h4>
                <div class="flex items-center gap-3 mt-1">
                  <StatusBadge :status="project.status" />
                  <PriorityBadge :priority="project.priority" />
                  <span class="text-sm text-gray-500 dark:text-gray-400">
                    Due {{ formatDate(project.due_date) }}
                  </span>
                </div>
              </div>
              <div class="text-right ml-4">
                <div class="text-sm font-medium text-gray-900 dark:text-white">
                  {{ Math.round(project.completion_percentage || 0) }}%
                </div>
                <div class="w-20 bg-gray-200 dark:bg-gray-700 rounded-full h-2 mt-1">
                  <div
                    :class="['h-2 rounded-full transition-all duration-300', getProgressColor(project.completion_percentage)]"
                    :style="{ width: `${project.completion_percentage || 0}%` }"
                  />
                </div>
              </div>
            </div>
          </div>

          <div v-if="!filteredProjects?.length" class="text-center py-8">
            <FolderIcon class="w-12 h-12 text-gray-400 mx-auto mb-3" />
            <p class="text-gray-600 dark:text-gray-400">No active projects</p>
            <Link :href="route('projects.create')" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 text-sm font-medium">
              Create your first project
            </Link>
          </div>
        </div>

        <!-- Recent Tasks -->
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
          <div class="flex items-center justify-between mb-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Recent Tasks</h2>
            <Link
              :href="route('work-entries.index')"
              class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 text-sm font-medium flex items-center gap-1"
            >
              View All
              <ArrowRight class="w-4 h-4" />
            </Link>
          </div>

          <div class="space-y-4">
            <div
              v-for="entry in (work_entries?.recent || recent_entries || []).slice(0, 5)"
              :key="entry.id || entry.uuid"
              class="flex items-center justify-between p-4 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
            >
              <div class="flex-1 min-w-0">
                <h4 class="font-medium text-gray-900 dark:text-white truncate">
                  {{ entry.work_title }}
                </h4>
                <div class="flex items-center gap-3 mt-1 text-sm text-gray-600 dark:text-gray-400">
                  <div class="flex items-center gap-1">
                    <UserAvatar :fallback="getInitials(entry.user?.name || 'U')" class="w-4 h-4 text-xs" />
                    {{ entry.user?.name }}
                  </div>
                  <div class="flex items-center gap-1">
                    <Calendar class="w-4 h-4" />
                    {{ formatDate(entry.start_date_time) }}
                  </div>
                </div>
              </div>
              <div class="flex items-center gap-3 ml-4">
                <StatusBadge :status="entry.status" />
                <div class="text-sm font-medium text-gray-900 dark:text-white">
                  {{ Math.round(entry.hours_worked || 0) }}h
                </div>
              </div>
            </div>
          </div>

          <div v-if="!(work_entries?.recent || recent_entries || []).length" class="text-center py-8">
            <Activity class="w-12 h-12 text-gray-400 mx-auto mb-3" />
            <p class="text-gray-600 dark:text-gray-400">No recent tasks</p>
            <Link :href="route('work-entries.create')" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 text-sm font-medium">
              Log your first task
            </Link>
          </div>
        </div>
      </div>

      <!-- Projects Section -->
      <div v-else-if="selectedView === 'projects'" class="space-y-6">

        <!-- Projects Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div
            v-for="project in filteredProjects?.slice(0, 9) || []"
            :key="project.uuid"
            class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6 hover:shadow-lg transition-all duration-200 cursor-pointer"
            @click="$inertia.visit(route('projects.show', project.uuid))"
          >
            <div class="flex items-start justify-between mb-4">
              <div class="flex-1 min-w-0">
                <h3 class="font-semibold text-gray-900 dark:text-white truncate">
                  {{ project.name }}
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                  {{ project.department?.name }}
                </p>
              </div>
              <button class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <MoreHorizontal class="w-5 h-5" />
              </button>
            </div>

            <div class="mb-4">
              <div class="flex justify-between items-center mb-2">
                <span class="text-sm text-gray-600 dark:text-gray-400">Progress</span>
                <span class="text-sm font-medium text-gray-900 dark:text-white">
                  {{ Math.round(project.completion_percentage || 0) }}%
                </span>
              </div>
              <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                <div
                  :class="['h-2 rounded-full transition-all duration-300', getProgressColor(project.completion_percentage)]"
                  :style="{ width: `${project.completion_percentage || 0}%` }"
                />
              </div>
            </div>

            <div class="flex items-center justify-between">
              <div class="flex items-center gap-3">
                <StatusBadge :status="project.status" />
                <PriorityBadge :priority="project.priority" />
              </div>
              <div class="text-sm text-gray-500 dark:text-gray-400">
                {{ getDaysUntilDue(project.due_date) }}d left
              </div>
            </div>
          </div>
        </div>

        <!-- View All Projects Button -->
        <div class="text-center">
          <Link :href="route('projects.index')">
            <Button variant="outline" class="px-8 py-3">
              View All Projects
              <ArrowRight class="w-4 h-4 ml-2" />
            </Button>
          </Link>
        </div>
      </div>

      <!-- Team Section -->
      <div v-else-if="selectedView === 'team'" class="space-y-6">

        <!-- Team Activity -->
        <div v-if="team_activity" class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Team Activity</h2>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div class="text-center p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
              <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                {{ team_activity?.active_users || 0 }}
              </p>
              <p class="text-sm text-blue-700 dark:text-blue-300">Active Users</p>
            </div>
            <div class="text-center p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
              <p class="text-2xl font-bold text-green-600 dark:text-green-400">
                {{ Math.round(team_activity?.total_hours_today || 0) }}h
              </p>
              <p class="text-sm text-green-700 dark:text-green-300">Hours Today</p>
            </div>
            <div class="text-center p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
              <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">
                {{ work_entries?.completion_rate || 0 }}%
              </p>
              <p class="text-sm text-purple-700 dark:text-purple-300">Completion Rate</p>
            </div>
          </div>

          <!-- Top Contributors -->
          <div>
            <h3 class="font-medium text-gray-900 dark:text-white mb-4">Top Contributors</h3>
            <div class="space-y-3">
              <div
                v-for="contributor in (team_activity?.top_contributors || [])"
                :key="contributor.id"
                class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg"
              >
                <div class="flex items-center gap-3">
                  <UserAvatar :fallback="getInitials(contributor.name)" class="w-8 h-8" />
                  <div>
                    <div class="font-medium text-gray-900 dark:text-white">
                      {{ contributor.name }}
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                      {{ contributor.department?.name }}
                    </div>
                  </div>
                </div>
                <div class="text-right">
                  <div class="font-medium text-gray-900 dark:text-white">
                    {{ Math.round(contributor.hours_today || 0) }}h
                  </div>
                  <div class="text-sm text-gray-500 dark:text-gray-400">
                    {{ contributor.tasks_today || 0 }} tasks
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Urgent Items Alert -->
      <div v-if="projects?.overdue?.length > 0" class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
        <div class="flex items-start gap-3">
          <AlertCircle class="w-5 h-5 text-red-600 dark:text-red-400 mt-0.5 flex-shrink-0" />
          <div class="flex-1">
            <h3 class="font-medium text-red-800 dark:text-red-200">
              {{ projects.overdue.length }} Overdue Project{{ projects.overdue.length !== 1 ? 's' : '' }}
            </h3>
            <p class="text-sm text-red-700 dark:text-red-300 mt-1">
              Some projects need immediate attention to stay on track.
            </p>
            <Link
              :href="route('projects.index', { overdue: true })"
              class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 text-sm font-medium mt-2 inline-block"
            >
              Review Overdue Projects →
            </Link>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>



