<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import VueApexCharts from 'vue3-apexcharts'
import { gsap } from 'gsap'
import {
  Users,
  TrendingUp,
  CheckCircle,
  Activity,
  Briefcase,
  AlertCircle
} from 'lucide-vue-next'

// Types for props
interface ManagerDashboardProps {
  base_metrics: {
    total_work_logs: number
    total_hours: number
    average_hours_per_day: number
    completed_tasks: number
  }
  team_performance: {
    total_team_members: number
    team_average_hours: {
      daily_average: number
      monthly_total: number
      per_member_average: any[]
      trend: {
        weekly_hours: any[]
        direction: string
        strength: number
      }
    }
    top_performers: Array<{
      name: string
      efficiency: number
      avatar?: string
    }>
    team_efficiency: {
      overall_efficiency: number
      completed_tasks: number
      total_tasks: number
      member_efficiency: any[]
    }
  }
  department_metrics: {
    department_efficiency: Array<{
      name: string
      efficiency: number
    }>
    ongoing_projects: Array<{
      title: string
      task_count: number
      assigned_members: Array<{
        id: number
        name: string
        avatar?: string
      }>
    }>
    completion_rate: {
      rate: number
      completed: number
      total: number
      by_member: any[]
    }
  }
  team_insights: {
    performance_summary: {
      improvement_areas: Array<{
        area: string
        description: string
        recommendation: string
      }>
    }
  }
}

const props = defineProps<ManagerDashboardProps>()

// Reactive state
const activeView = ref('overview')
const selectedTimeframe = ref('month')

// Computed properties for charts
const teamPerformanceChartOptions = computed(() => ({
  chart: {
    type: 'area',
    height: 350,
    toolbar: { show: false },
    animations: {
      enabled: true,
      easing: 'easeinout',
      speed: 800
    }
  },
  stroke: { curve: 'smooth', width: 2 },
  fill: {
    type: 'gradient',
    gradient: {
      shadeIntensity: 1,
      opacityFrom: 0.7,
      opacityTo: 0.3,
      stops: [0, 90, 100]
    }
  },
  dataLabels: { enabled: false },
  xaxis: {
    categories: props.team_performance.team_average_hours.trend.weekly_hours.map((_, i) => `Week ${i + 1}`),
    labels: { style: { colors: '#64748b' } }
  },
  yaxis: {
    labels: {
      formatter: (val: number) => `${val}hrs`,
      style: { colors: '#64748b' }
    }
  }
}))

const teamPerformanceSeries = computed(() => [{
  name: 'Team Hours',
  data: props.team_performance.team_average_hours.trend.weekly_hours
}])

const departmentEfficiencyOptions = computed(() => ({
  chart: {
    type: 'bar',
    height: 350,
    toolbar: { show: false }
  },
  plotOptions: {
    bar: {
      horizontal: true,
      borderRadius: 4,
      distributed: true
    }
  },
  colors: ['#3B82F6'],
  dataLabels: {
    enabled: true,
    formatter: (val: number) => `${val}%`
  },
  xaxis: {
    categories: props.department_metrics.department_efficiency.map(d => d.name)
  }
}))

const departmentEfficiencySeries = computed(() => [{
  data: props.department_metrics.department_efficiency.map(d => d.efficiency)
}])

// Methods
const getEfficiencyColor = (efficiency: number): string => {
  if (efficiency >= 75) return 'text-green-500'
  if (efficiency >= 50) return 'text-yellow-500'
  return 'text-red-500'
}

// Lifecycle hooks
onMounted(() => {
  gsap.from('.metric-card', {
    y: 20,
    opacity: 0,
    duration: 0.6,
    stagger: 0.1,
    ease: 'power2.out'
  })
})
</script>

<template>
  <div class="p-6 space-y-6">
    <!-- Header with navigation -->
    <div class="flex justify-between items-center">
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
        Team Performance Dashboard
      </h1>
      <div class="flex space-x-2">
        <button
          v-for="view in ['overview', 'team', 'projects']"
          :key="view"
          @click="activeView = view"
          :class="[
            'px-4 py-2 rounded-lg transition-colors',
            activeView === view
              ? 'bg-primary text-white'
              : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
          ]"
        >
          {{ view.charAt(0).toUpperCase() + view.slice(1) }}
        </button>
      </div>
    </div>

    <!-- Key metrics grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
      <div
        v-for="(metric, index) in [
          {
            icon: Users,
            title: 'Team Size',
            value: team_performance.total_team_members,
            suffix: 'members'
          },
          {
            icon: Activity,
            title: 'Team Efficiency',
            value: team_performance.team_efficiency.overall_efficiency,
            suffix: '%'
          },
          {
            icon: CheckCircle,
            title: 'Completed Tasks',
            value: team_performance.team_efficiency.completed_tasks,
            suffix: ''
          },
          {
            icon: Briefcase,
            title: 'Active Projects',
            value: department_metrics.ongoing_projects.length,
            suffix: ''
          }
        ]"
        :key="index"
        class="metric-card bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm"
      >
        <div class="flex items-center space-x-4">
          <component
            :is="metric.icon"
            class="w-8 h-8 text-primary"
          />
          <div>
            <h3 class="text-sm text-gray-500 dark:text-gray-400">
              {{ metric.title }}
            </h3>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">
              {{ metric.value }}{{ metric.suffix ? ` ${metric.suffix}` : '' }}
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Charts section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Team Performance Chart -->
      <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm">
        <div class="flex justify-between items-center mb-6">
          <h3 class="text-lg font-semibold">Team Performance Trend</h3>
          <div class="flex space-x-2">
            <button
              v-for="timeframe in ['week', 'month', 'quarter']"
              :key="timeframe"
              @click="selectedTimeframe = timeframe"
              :class="[
                'px-3 py-1 text-sm rounded-md',
                selectedTimeframe === timeframe
                  ? 'bg-primary text-white'
                  : 'bg-gray-100 dark:bg-gray-700'
              ]"
            >
              {{ timeframe.charAt(0).toUpperCase() + timeframe.slice(1) }}
            </button>
          </div>
        </div>
        <VueApexCharts
          type="area"
          height="300"
          :options="teamPerformanceChartOptions"
          :series="teamPerformanceSeries"
        />
      </div>

      <!-- Department Efficiency Chart -->
      <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm">
        <h3 class="text-lg font-semibold mb-6">Department Efficiency</h3>
        <VueApexCharts
          type="bar"
          height="300"
          :options="departmentEfficiencyOptions"
          :series="departmentEfficiencySeries"
        />
      </div>
    </div>

    <!-- Team Insights Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Top Performers -->
      <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm">
        <h3 class="text-lg font-semibold mb-4">Top Performers</h3>
        <div class="space-y-4">
          <div
            v-for="performer in team_performance.top_performers"
            :key="performer.name"
            class="flex items-center justify-between"
          >
            <div class="flex items-center space-x-3">
              <img
                :src="performer.avatar || '/default-avatar.png'"
                class="w-8 h-8 rounded-full"
                :alt="performer.name"
              />
              <span class="font-medium">{{ performer.name }}</span>
            </div>
            <span :class="getEfficiencyColor(performer.efficiency)">
              {{ performer.efficiency }}%
            </span>
          </div>
        </div>
      </div>

      <!-- Improvement Areas -->
      <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm col-span-2">
        <h3 class="text-lg font-semibold mb-4">Areas for Improvement</h3>
        <div class="space-y-4">
          <div
            v-for="(area, index) in team_insights.performance_summary.improvement_areas"
            :key="index"
            class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg"
          >
            <div class="flex items-start space-x-3">
              <AlertCircle class="w-5 h-5 text-primary mt-1" />
              <div>
                <h4 class="font-medium text-primary">{{ area.area }}</h4>
                <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">
                  {{ area.description }}
                </p>
                <p class="text-sm text-primary mt-2">
                  {{ area.recommendation }}
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.metric-card {
  @apply transition-all duration-300 hover:shadow-md;
}
</style>
