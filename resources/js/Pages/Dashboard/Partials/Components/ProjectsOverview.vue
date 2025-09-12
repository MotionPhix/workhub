<script setup lang="ts">
import {computed} from 'vue'
import {Card, CardContent, CardHeader, CardTitle} from '@/components/ui/card'
import {Progress} from '@/components/ui/progress'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import {
  Briefcase,
  Clock,
  CheckCircle2,
  AlertCircle,
  TrendingUp,
  TrendingDown,
  Minus,
} from 'lucide-vue-next'
import {router, usePage} from "@inertiajs/vue3";

// Updated interfaces to match backend data structure
interface ProjectTrend {
  direction: 'up' | 'down' | 'stable'
  value: number
  label: string
  comparison_period: string
}

interface ProjectMetrics {
  total_projects: number
  active_projects: number
  completed_projects: number
  total_hours: number
  completion_rate: number
  active_rate: number
  trends: {
    projects: ProjectTrend
    active: ProjectTrend
    completion: ProjectTrend
    hours: ProjectTrend
  }
}

interface DepartmentPerformance {
  name: string
  efficiency: number
  total_tasks: number
  completed_tasks: number
  total_hours: number
  member_count: number
}

interface DashboardData {
  base_metrics: {
    total_work_logs: number
    total_hours: number
    average_hours_per_day: number
    completed_tasks: number
  }
  organization_metrics: {
    company_wide_efficiency: number
    project_metrics: ProjectMetrics
    department_performance: DepartmentPerformance[]
  }
  time_filter: string
}

interface Props {
  dashboard: DashboardData
}

const props = usePage().props

// Computed property for safe access to project metrics with fallback
const projectMetrics = computed(() =>
  props.dashboard?.organization_metrics?.project_metrics ?? {
    total_projects: 0,
    active_projects: 0,
    completed_projects: 0,
    total_hours: 0,
    completion_rate: 0,
    active_rate: 0,
    trends: {
      projects: {direction: 'stable', value: 0, label: '', comparison_period: ''},
      active: {direction: 'stable', value: 0, label: '', comparison_period: ''},
      completion: {direction: 'stable', value: 0, label: '', comparison_period: ''},
      hours: {direction: 'stable', value: 0, label: '', comparison_period: ''}
    }
  })

// Time filter handling
const timeFilter = computed(() => props.dashboard?.time_filter ?? 'month')

const handleTimeFilterChange = (value: string) => {
  console.log(value)

  // Use Inertia's router to make a visit with the new time filter
  router.visit(route('dashboard'), {
    data: { time_filter: value },
    preserveState: true,
    preserveScroll: true,
    only: ['dashboard.organization_metrics', 'dashboard.system_metrics']
  })
}

const statusCards = computed(() => [
  {
    title: 'Total Projects',
    value: projectMetrics.value.total_projects,
    icon: Briefcase,
    trend: projectMetrics.value.trends.projects
  },
  {
    title: 'Active Projects',
    value: projectMetrics.value.active_projects,
    icon: Clock,
    progress: projectMetrics.value.active_rate
  },
  {
    title: 'Completion Rate',
    value: `${projectMetrics.value.completion_rate}%`,
    icon: CheckCircle2,
    trend: projectMetrics.value.trends.completion
  },
  {
    title: 'Total Hours',
    value: `${projectMetrics.value.total_hours}h`,
    icon: AlertCircle,
    trend: projectMetrics.value.trends.hours
  }
])

const getTrendIcon = (direction: 'up' | 'down' | 'stable') => {
  switch (direction) {
    case 'up':
      return TrendingUp
    case 'down':
      return TrendingDown
    default:
      return Minus
  }
}

const getTrendColor = (direction: 'up' | 'down' | 'stable') => {
  switch (direction) {
    case 'up':
      return 'text-green-500 dark:text-green-400'
    case 'down':
      return 'text-red-500 dark:text-red-400'
    default:
      return 'text-yellow-500 dark:text-yellow-400'
  }
}
</script>

<template>
  <div class="space-y-6">
    <Card>
      <CardHeader>
        <div class="flex items-center justify-between">
          <CardTitle>Projects Overview</CardTitle>
          <Select
            :model-value="props.time_filter"
            @update:modelValue="handleTimeFilterChange">
            <SelectTrigger class="w-[180px]">
              <SelectValue placeholder="Select period"/>
            </SelectTrigger>

            <SelectContent>
              <SelectItem value="week">This Week</SelectItem>
              <SelectItem value="month">This Month</SelectItem>
              <SelectItem value="quarter">This Quarter</SelectItem>
              <SelectItem value="year">This Year</SelectItem>
            </SelectContent>
          </Select>
        </div>
      </CardHeader>

      <CardContent>
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
          <Card v-for="card in statusCards" :key="card.title">
            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle class="text-sm font-medium">
                {{ card.title }}
              </CardTitle>
              <component
                :is="card.icon"
                class="h-4 w-4 text-muted-foreground"
              />
            </CardHeader>
            <CardContent>
              <div class="text-2xl font-bold">{{ card.value }}</div>
              <div v-if="card.progress" class="mt-2">
                <Progress :value="card.progress" class="h-2"/>
                <p class="mt-1 text-xs text-muted-foreground">
                  {{ card.progress }}% utilization
                </p>
              </div>
              <div
                v-if="card.trend"
                class="mt-2 flex items-center text-xs text-muted-foreground"
              >
                <component
                  :is="getTrendIcon(card.trend.direction)"
                  class="mr-1 h-3 w-3"
                  :class="getTrendColor(card.trend.direction)"
                />
                <span>{{ card.trend.value }}% {{ card.trend.label }}</span>
              </div>
            </CardContent>
          </Card>
        </div>
      </CardContent>
    </Card>
  </div>
</template>

<style scoped>
.card-grid {
  @apply grid gap-4 md:grid-cols-2 lg:grid-cols-4;
}

:deep(.progress-indicator) {
  @apply transition-all duration-300;
}
</style>


