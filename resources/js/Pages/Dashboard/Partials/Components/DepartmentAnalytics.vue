<script setup lang="ts">
import { computed, ref } from 'vue'
import VueApexCharts from 'vue3-apexcharts'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Progress } from '@/components/ui/progress'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import {
  BarChart3,
  Users,
  Clock,
  TrendingUp,
  TrendingDown,
  Minus,
  Share2
} from 'lucide-vue-next'

interface DepartmentAnalytics {
  resource_utilization: {
    allocated_hours: number
    actual_hours: number
    utilization_rate: number
  }
  cross_department_collaboration: {
    collaborating_departments: string[]
    shared_projects: number
    collaboration_score: number
  }
  performance_trends: {
    weekly_efficiency: number[]
    monthly_completion_rates: number[]
    year_over_year_growth: number
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

interface Props {
  departmentAnalytics: DepartmentAnalytics
  departmentPerformance: DepartmentPerformance[]
}

const props = defineProps<Props>()
const selectedTimeframe = ref('month')

// Theme-aware chart colors
const chartColors = computed(() => ({
  primary: 'rgb(var(--primary))',
  muted: 'rgb(var(--muted))',
  background: 'rgb(var(--background))',
  foreground: 'rgb(var(--foreground))'
}))

// Weekly efficiency chart options
const weeklyEfficiencyOptions = computed(() => ({
  chart: {
    type: 'line',
    height: 350,
    toolbar: { show: false },
    animations: { enabled: true },
    background: 'transparent'
  },
  stroke: {
    curve: 'smooth',
    width: 3
  },
  colors: [chartColors.value.primary],
  xaxis: {
    categories: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
    labels: { style: { colors: chartColors.value.foreground } }
  },
  yaxis: {
    labels: {
      style: { colors: chartColors.value.foreground },
      formatter: (value: number) => `${value}%`
    }
  },
  grid: { borderColor: chartColors.value.muted },
  theme: { mode: 'light' },
  tooltip: {
    theme: 'dark',
    y: { formatter: (value: number) => `${value}%` }
  }
}))

const weeklyEfficiencySeries = computed(() => [{
  name: 'Efficiency',
  data: props.departmentAnalytics.performance_trends.weekly_efficiency
}])

// Resource utilization metrics
const utilizationMetrics = computed(() => ({
  allocated: props.departmentAnalytics.resource_utilization.allocated_hours,
  actual: props.departmentAnalytics.resource_utilization.actual_hours,
  rate: props.departmentAnalytics.resource_utilization.utilization_rate,
  variance: Math.abs(
    props.departmentAnalytics.resource_utilization.allocated_hours -
    props.departmentAnalytics.resource_utilization.actual_hours
  )
}))

// Collaboration metrics
const collaborationMetrics = computed(() => ({
  departments: props.departmentAnalytics.cross_department_collaboration.collaborating_departments,
  projects: props.departmentAnalytics.cross_department_collaboration.shared_projects,
  score: props.departmentAnalytics.cross_department_collaboration.collaboration_score
}))

const getUtilizationColor = (rate: number) => {
  if (rate > 90) return 'text-red-500 dark:text-red-400'
  if (rate > 70) return 'text-green-500 dark:text-green-400'
  return 'text-yellow-500 dark:text-yellow-400'
}

const getTrendIcon = (value: number) => {
  if (value > 0) return TrendingUp
  if (value < 0) return TrendingDown
  return Minus
}

const getTrendColor = (value: number) => {
  if (value > 0) return 'text-green-500 dark:text-green-400'
  if (value < 0) return 'text-red-500 dark:text-red-400'
  return 'text-muted-foreground'
}
</script>

<template>
  <div class="space-y-6">
    <!-- Time Period Selector -->
    <div class="flex justify-end">
      <Select v-model="selectedTimeframe">
        <SelectTrigger class="w-[180px]">
          <SelectValue :placeholder="selectedTimeframe" />
        </SelectTrigger>
        <SelectContent>
          <SelectItem value="week">This Week</SelectItem>
          <SelectItem value="month">This Month</SelectItem>
          <SelectItem value="quarter">This Quarter</SelectItem>
          <SelectItem value="year">This Year</SelectItem>
        </SelectContent>
      </Select>
    </div>

    <!-- Resource Utilization Card -->
    <Card>
      <CardHeader>
        <CardTitle class="flex items-center gap-2">
          <Clock class="h-5 w-5" />
          Resource Utilization
        </CardTitle>
      </CardHeader>
      <CardContent>
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
          <div class="space-y-2">
            <p class="text-sm text-muted-foreground">Allocated Hours</p>
            <p class="text-2xl font-bold">{{ utilizationMetrics.allocated }}h</p>
          </div>
          <div class="space-y-2">
            <p class="text-sm text-muted-foreground">Actual Hours</p>
            <p class="text-2xl font-bold">{{ utilizationMetrics.actual }}h</p>
          </div>
          <div class="space-y-2">
            <p class="text-sm text-muted-foreground">Utilization Rate</p>
            <p
              class="text-2xl font-bold"
              :class="getUtilizationColor(utilizationMetrics.rate)"
            >
              {{ utilizationMetrics.rate }}%
            </p>
          </div>
          <div class="space-y-2">
            <p class="text-sm text-muted-foreground">Hour Variance</p>
            <p class="text-2xl font-bold">{{ utilizationMetrics.variance }}h</p>
          </div>
        </div>
        <Progress
          :value="utilizationMetrics.rate"
          class="mt-6"
        />
      </CardContent>
    </Card>

    <!-- Weekly Efficiency Trend -->
    <Card>
      <CardHeader>
        <CardTitle class="flex items-center gap-2">
          <BarChart3 class="h-5 w-5" />
          Weekly Efficiency Trend
        </CardTitle>
      </CardHeader>
      <CardContent>
        <VueApexCharts
          type="line"
          height="350"
          :options="weeklyEfficiencyOptions"
          :series="weeklyEfficiencySeries"
          class="chart-container"
        />
      </CardContent>
    </Card>

    <!-- Cross-Department Collaboration -->
    <Card>
      <CardHeader>
        <CardTitle class="flex items-center gap-2">
          <Share2 class="h-5 w-5" />
          Cross-Department Collaboration
        </CardTitle>
      </CardHeader>
      <CardContent>
        <div class="grid gap-4 md:grid-cols-3">
          <div class="space-y-2">
            <p class="text-sm text-muted-foreground">Collaborating Departments</p>
            <div class="flex flex-wrap gap-2">
              <span
                v-for="dept in collaborationMetrics.departments"
                :key="dept"
                class="inline-flex items-center rounded-full bg-primary/10 px-2.5 py-0.5 text-xs font-medium text-primary"
              >
                {{ dept }}
              </span>
            </div>
          </div>
          <div class="space-y-2">
            <p class="text-sm text-muted-foreground">Shared Projects</p>
            <p class="text-2xl font-bold">{{ collaborationMetrics.projects }}</p>
          </div>
          <div class="space-y-2">
            <p class="text-sm text-muted-foreground">Collaboration Score</p>
            <p class="text-2xl font-bold">{{ collaborationMetrics.score }}%</p>
            <Progress :value="collaborationMetrics.score" class="h-2" />
          </div>
        </div>
      </CardContent>
    </Card>
  </div>
</template>

<style scoped>
:deep(.apexcharts-text) {
  @apply fill-foreground;
}

:deep(.apexcharts-grid-borders line),
:deep(.apexcharts-grid line) {
  @apply stroke-border;
}

:deep(.dark .apexcharts-theme-light) {
  @apply bg-background;
}

:deep(.apexcharts-tooltip) {
  @apply bg-popover text-popover-foreground border-border;
}
</style>
