<script setup lang="ts">
import { ref, onMounted, computed, nextTick } from 'vue'
import VueApexCharts from 'vue3-apexcharts'
import { gsap } from 'gsap'
import {
  Clock,
  CheckCircle,
  TrendingUp,
  Activity,
  Sun,
  Sunset,
  Moon,
  AlertCircle,
  Plus,
  ChartBarIcon,
  Target,
  Bell,
  Calendar
} from 'lucide-vue-next'
import AppLayout from '@/layouts/AppLayout.vue'
import { Link } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { useTheme } from '@/composables/useTheme'

interface TrendData {
  percentage: number
  direction: 'up' | 'down' | 'stable'
}

interface EmployeeDashboardProps {
  base_metrics: {
    total_work_logs: number
    total_hours: number
    average_hours_per_day: number
    completed_tasks: number
    // Trend data for each metric (calculated from backend)
    total_hours_trend: TrendData
    daily_average_trend: TrendData
    completed_tasks_trend: TrendData
    completion_rate_trend: TrendData
  }
  user_targets: {
    daily_hours_target: number
    daily_tasks_target: number
    quality_target: number
  }
  recent_activity: {
    recent_entries: Array<any>
    activity_summary: {
      total_entries_this_month: number
      total_hours_this_month: number
      completion_rate: number
      daily_average: number
    }
  }
  personal_metrics: {
    productivity_score: {
      score: number
      metrics: {
        hours_per_day: number
        task_completion_rate: number
        consistency_score: number
      }
      breakdown: {
        total_hours: number
        completed_tasks: number
        total_tasks: number
        work_days: number
      }
    }
    completion_rate: {
      current_month: {
        rate: number
        completed: number
        total: number
        average_completion_time: number
      }
      weekly_trends: Array<any>
      performance_indicator: string
    }
    work_pattern: {
      daily_patterns: Array<any>
      peak_productivity: {
        morning: { hours: number; efficiency: number }
        afternoon: { hours: number; efficiency: number }
        evening: { hours: number; efficiency: number }
      }
      average_hours: number
      completion_rate: number
    }
  }
  performance_insights: {
    trend: {
      weekly_metrics: Array<any>
      trend_direction: string
      trend_strength: number
      trend_summary: string
      comparison: {
        previous_period: number
        current_period: number
      }
    }
    recommendations: Array<string>
  }
}

const props = defineProps<EmployeeDashboardProps>()

// Initialize theme
const { isDark } = useTheme()

// Reactive state
const activeTab = ref('overview')
const selectedPeriod = ref('week')

// Computed properties for charts
const productivityChartOptions = computed(() => ({
  chart: {
    type: 'radialBar',
    height: 350,
    toolbar: { show: false }
  },
  plotOptions: {
    radialBar: {
      startAngle: -135,
      endAngle: 135,
      hollow: {
        margin: 0,
        size: '70%'
      },
      track: {
        background: 'var(--color-gray-200)',
        strokeWidth: '97%'
      },
      dataLabels: {
        name: {
          show: true,
          color: 'var(--color-gray-500)',
          fontSize: '15px'
        },
        value: {
          fontSize: '30px',
          show: true,
          color: 'var(--color-gray-900)',
          formatter: (val: number) => `${val}%`
        }
      }
    }
  },
  fill: {
    type: 'gradient',
    gradient: {
      shade: 'dark',
      type: 'horizontal',
      gradientToColors: ['#ABE5A1'],
      stops: [0, 100]
    }
  },
  stroke: {
    lineCap: 'round'
  }
}))

const productivitySeries = computed(() => [
  props.personal_metrics.productivity_score.score
])

const workPatternChartOptions = computed(() => ({
  chart: {
    type: 'bar',
    height: 350,
    toolbar: { show: false }
  },
  plotOptions: {
    bar: {
      borderRadius: 4,
      horizontal: true,
    }
  },
  colors: ['#3B82F6'],
  dataLabels: {
    enabled: true,
    formatter: (val: number) => `${val}%`
  },
  xaxis: {
    categories: ['Morning', 'Afternoon', 'Evening'],
    labels: {
      style: { colors: 'var(--color-gray-500)' }
    }
  },
  yaxis: {
    labels: {
      style: { colors: 'var(--color-gray-500)' }
    }
  },
  theme: {
    mode: 'light'
  }
}))

const workPatternSeries = computed(() => [{
  name: 'Efficiency',
  data: [
    props.personal_metrics.work_pattern.peak_productivity.morning.efficiency,
    props.personal_metrics.work_pattern.peak_productivity.afternoon.efficiency,
    props.personal_metrics.work_pattern.peak_productivity.evening.efficiency
  ]
}])

// Methods
const getMetricColor = (value: number): string => {
  if (value >= 75) return 'text-green-500'
  if (value >= 50) return 'text-yellow-500'
  return 'text-red-500'
}

// Lifecycle hooks
onMounted(() => {
  // Use nextTick to ensure DOM is fully rendered
  nextTick(() => {
    // Set initial state
    gsap.set('.metric-card', { opacity: 0, y: 20 })

    // Animate in with a slight delay to ensure all elements are ready
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
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
        Personal Dashboard
      </h2>

      <div class="text-sm text-gray-600 dark:text-gray-400">
        {{ new Date().toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) }}
      </div>
    </div>

    <div class="py-6 sm:py-8 dark:text-gray-200">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6 sm:space-y-8">
        <!-- Welcome Header -->
        <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4 lg:gap-6">
          <div class="space-y-1">
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white">
              Welcome back, {{ $page.props.auth.user.name.split(' ')[0] }}! 👋
            </h1>
            <p class="text-gray-600 dark:text-gray-400">
              Here's your productivity overview for today
            </p>
          </div>

          <div class="flex flex-col sm:flex-row items-start sm:items-center space-y-3 sm:space-y-0 sm:space-x-3">
            <!-- Quick Actions -->
            <div class="flex flex-wrap gap-2">
              <Link :href="route('work-entries.create')">
                <Button class="shadow-sm">
                  <Plus class="w-4 h-4 mr-2" />
                  Log Work
                </Button>
              </Link>
              <Link :href="route('reports.index')">
                <Button variant="outline" class="shadow-sm">
                  <ChartBarIcon class="w-4 h-4 mr-2" />
                  View Reports
                </Button>
              </Link>
            </div>

            <!-- Tab Navigation -->
            <div class="flex space-x-1 bg-gray-100 dark:bg-gray-800 p-1 rounded-lg shadow-inner">
              <button
                v-for="tab in ['overview', 'productivity', 'insights']"
                :key="tab"
                @click="activeTab = tab"
                :class="[
                  'px-3 py-1.5 text-sm font-medium rounded-md transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2',
                  activeTab === tab
                    ? 'bg-white dark:bg-gray-700 text-blue-600 dark:text-blue-400 shadow-sm'
                    : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200 hover:bg-white/50 dark:hover:bg-gray-700/50'
                ]">
                {{ tab.charAt(0).toUpperCase() + tab.slice(1) }}
              </button>
            </div>
          </div>
        </div>

        <!-- Enhanced Key Metrics -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          <div
            v-for="(metric, index) in [
              {
                icon: Clock,
                title: 'Total Hours',
                value: base_metrics.total_hours,
                suffix: 'hrs',
                color: 'from-blue-500 to-blue-600',
                bgColor: 'bg-blue-50 dark:bg-blue-900/20',
                iconColor: 'text-blue-600 dark:text-blue-400',
                trend: `${base_metrics.total_hours_trend.percentage >= 0 ? '+' : ''}${base_metrics.total_hours_trend.percentage}%`,
                trendDirection: base_metrics.total_hours_trend.direction
              },
              {
                icon: Activity,
                title: 'Daily Average',
                value: base_metrics.average_hours_per_day,
                suffix: 'hrs/day',
                color: 'from-green-500 to-green-600',
                bgColor: 'bg-green-50 dark:bg-green-900/20',
                iconColor: 'text-green-600 dark:text-green-400',
                trend: `${base_metrics.daily_average_trend.percentage >= 0 ? '+' : ''}${base_metrics.daily_average_trend.percentage}%`,
                trendDirection: base_metrics.daily_average_trend.direction
              },
              {
                icon: CheckCircle,
                title: 'Completed Tasks',
                value: base_metrics.completed_tasks,
                suffix: '',
                color: 'from-purple-500 to-purple-600',
                bgColor: 'bg-purple-50 dark:bg-purple-900/20',
                iconColor: 'text-purple-600 dark:text-purple-400',
                trend: `${base_metrics.completed_tasks_trend.percentage >= 0 ? '+' : ''}${base_metrics.completed_tasks_trend.percentage}%`,
                trendDirection: base_metrics.completed_tasks_trend.direction
              },
              {
                icon: TrendingUp,
                title: 'Completion Rate',
                value: Math.round(personal_metrics.completion_rate.current_month.rate),
                suffix: '%',
                color: 'from-orange-500 to-orange-600',
                bgColor: 'bg-orange-50 dark:bg-orange-900/20',
                iconColor: 'text-orange-600 dark:text-orange-400',
                trend: `${base_metrics.completion_rate_trend.percentage >= 0 ? '+' : ''}${base_metrics.completion_rate_trend.percentage}%`,
                trendDirection: base_metrics.completion_rate_trend.direction
              }
            ]"
            :key="index"
            class="metric-card group relative overflow-hidden bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm hover:shadow-lg transition-all duration-300 border border-gray-100 dark:border-gray-700"
          >
            <!-- Gradient Background -->
            <div
              :class="['absolute inset-0 opacity-0 group-hover:opacity-5 transition-opacity duration-300 bg-gradient-to-br', metric.color]"
            ></div>

            <!-- Card Content -->
            <div class="relative z-10">
              <div class="flex items-start justify-between">
                <div :class="['p-3 rounded-lg transition-colors duration-300', metric.bgColor]">
                  <component
                    :is="metric.icon"
                    :class="['w-6 h-6 transition-colors duration-300', metric.iconColor]"
                  />
                </div>

                <!-- Trend Indicator -->
                <div
                  :class="[
                    'flex items-center px-2 py-1 rounded-full text-xs font-medium',
                    metric.trendDirection === 'up'
                      ? 'bg-green-100 text-green-700 dark:bg-green-900/20 dark:text-green-400'
                      : 'bg-red-100 text-red-700 dark:bg-red-900/20 dark:text-red-400'
                  ]"
                >
                  <component
                    :is="metric.trendDirection === 'up' ? TrendingUp : TrendingUp"
                    :class="[
                      'w-3 h-3 mr-1',
                      metric.trendDirection === 'down' ? 'rotate-180' : ''
                    ]"
                  />
                  {{ metric.trend }}
                </div>
              </div>

              <div class="mt-4">
                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">
                  {{ metric.title }}
                </h3>
                <p class="text-3xl font-bold text-gray-900 dark:text-white">
                  {{ metric.value }}<span class="text-lg font-medium text-gray-500 dark:text-gray-400">{{ metric.suffix }}</span>
                </p>
              </div>

              <!-- Progress Bar for Completion Rate -->
              <div v-if="metric.title === 'Completion Rate'" class="mt-3">
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                  <div
                    :class="['h-2 rounded-full transition-all duration-500 bg-gradient-to-r', metric.color]"
                    :style="{ width: Math.min(metric.value, 100) + '%' }"
                  ></div>
                </div>
              </div>
            </div>

            <!-- Hover Effect -->
            <div class="absolute inset-0 rounded-xl ring-2 ring-transparent group-hover:ring-blue-500/20 transition-all duration-300"></div>
          </div>
        </div>

        <!-- Tab Content -->
        <div class="space-y-6">
          <!-- Overview Tab -->
          <div v-if="activeTab === 'overview'" class="space-y-6">
            <!-- Today's Goals -->
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-xs">
              <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold flex items-center">
                  <Target class="w-5 h-5 mr-2 text-blue-600" />
                  Today's Goals
                </h3>
                <span class="text-sm text-gray-500 dark:text-gray-400">
                  {{ new Date().toLocaleDateString('en-US', { weekday: 'long', month: 'short', day: 'numeric' }) }}
                </span>
              </div>
              <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg border-l-4 border-blue-500">
                  <div class="flex items-center justify-between">
                    <div>
                      <p class="text-sm text-blue-700 dark:text-blue-300 font-medium">Work Hours Target</p>
                      <p class="text-2xl font-bold text-blue-900 dark:text-blue-100">{{ user_targets.daily_hours_target }}h</p>
                    </div>
                    <div class="text-right">
                      <p class="text-sm text-blue-600 dark:text-blue-400">Progress</p>
                      <p class="text-lg font-semibold text-blue-900 dark:text-blue-100">
                        {{ Math.round((base_metrics.average_hours_per_day / user_targets.daily_hours_target) * 100) }}%
                      </p>
                    </div>
                  </div>
                  <div class="mt-3 w-full bg-blue-200 dark:bg-blue-800 rounded-full h-2">
                    <div
                      class="bg-blue-600 h-2 rounded-full transition-all duration-500"
                      :style="{ width: Math.min((base_metrics.average_hours_per_day / user_targets.daily_hours_target) * 100, 100) + '%' }"
                    ></div>
                  </div>
                </div>

                <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg border-l-4 border-green-500">
                  <div class="flex items-center justify-between">
                    <div>
                      <p class="text-sm text-green-700 dark:text-green-300 font-medium">Tasks Target</p>
                      <p class="text-2xl font-bold text-green-900 dark:text-green-100">{{ user_targets.daily_tasks_target }}</p>
                    </div>
                    <div class="text-right">
                      <p class="text-sm text-green-600 dark:text-green-400">Completed</p>
                      <p class="text-lg font-semibold text-green-900 dark:text-green-100">
                        {{ base_metrics.completed_tasks }}
                      </p>
                    </div>
                  </div>
                  <div class="mt-3 w-full bg-green-200 dark:bg-green-800 rounded-full h-2">
                    <div
                      class="bg-green-600 h-2 rounded-full transition-all duration-500"
                      :style="{ width: Math.min((base_metrics.completed_tasks / user_targets.daily_tasks_target) * 100, 100) + '%' }"
                    ></div>
                  </div>
                </div>

                <div class="bg-purple-50 dark:bg-purple-900/20 p-4 rounded-lg border-l-4 border-purple-500">
                  <div class="flex items-center justify-between">
                    <div>
                      <p class="text-sm text-purple-700 dark:text-purple-300 font-medium">Quality Target</p>
                      <p class="text-2xl font-bold text-purple-900 dark:text-purple-100">{{ user_targets.quality_target }}%</p>
                    </div>
                    <div class="text-right">
                      <p class="text-sm text-purple-600 dark:text-purple-400">Current</p>
                      <p class="text-lg font-semibold text-purple-900 dark:text-purple-100">
                        {{ Math.round(personal_metrics.completion_rate.current_month.rate) }}%
                      </p>
                    </div>
                  </div>
                  <div class="mt-3 w-full bg-purple-200 dark:bg-purple-800 rounded-full h-2">
                    <div
                      class="bg-purple-600 h-2 rounded-full transition-all duration-500"
                      :style="{ width: Math.min((personal_metrics.completion_rate.current_month.rate / user_targets.quality_target) * 100, 100) + '%' }"
                    ></div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Recent Activity -->
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-xs">
              <h3 class="text-lg font-semibold mb-4">Recent Activity</h3>
              <div class="space-y-4">
                <div
                  v-if="recent_activity.recent_entries.length === 0"
                  class="text-center py-8 text-gray-500 dark:text-gray-400"
                >
                  <Activity class="w-12 h-12 mx-auto mb-4 opacity-50" />
                  <p>No recent work entries</p>
                  <p class="text-sm mt-1">Start logging your work to see activity here</p>
                  <Link :href="route('work-entries.create')">
                    <Button class="mt-4">
                      <Plus class="w-4 h-4 mr-2" />
                      Log Your First Work Entry
                    </Button>
                  </Link>
                </div>
                <div
                  v-for="entry in recent_activity.recent_entries.slice(0, 5)"
                  :key="entry.id"
                  class="flex items-center justify-between p-4 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
                >
                  <div class="flex items-center space-x-3">
                    <div class="w-2 h-2 bg-blue-500 rounded-full animate-pulse"></div>
                    <div>
                      <p class="font-medium text-gray-900 dark:text-white">{{ entry.title }}</p>
                      <p class="text-sm text-gray-500 dark:text-gray-400 flex items-center">
                        <Calendar class="w-3 h-3 mr-1" />
                        {{ entry.date }}
                      </p>
                    </div>
                  </div>
                  <div class="text-right">
                    <p class="font-medium text-gray-900 dark:text-white">{{ entry.hours }}h</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400 capitalize">{{ entry.status }}</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Monthly Summary -->
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-xs">
              <h3 class="text-lg font-semibold mb-4">This Month's Summary</h3>
              <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="text-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                  <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                    {{ recent_activity.activity_summary.total_entries_this_month }}
                  </p>
                  <p class="text-sm text-gray-500 dark:text-gray-400">Total Entries</p>
                </div>
                <div class="text-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                  <p class="text-2xl font-bold text-green-600 dark:text-green-400">
                    {{ Math.round(recent_activity.activity_summary.total_hours_this_month) }}h
                  </p>
                  <p class="text-sm text-gray-500 dark:text-gray-400">Total Hours</p>
                </div>
                <div class="text-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                  <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">
                    {{ Math.round(recent_activity.activity_summary.completion_rate) }}%
                  </p>
                  <p class="text-sm text-gray-500 dark:text-gray-400">Completion Rate</p>
                </div>
                <div class="text-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                  <p class="text-2xl font-bold text-orange-600 dark:text-orange-400">
                    {{ Math.round(recent_activity.activity_summary.daily_average * 10) / 10 }}h
                  </p>
                  <p class="text-sm text-gray-500 dark:text-gray-400">Daily Average</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Productivity Tab -->
          <div v-else-if="activeTab === 'productivity'" class="space-y-6">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
              <!-- Productivity Score -->
              <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-xs">
                <h3 class="text-lg font-semibold mb-6">Productivity Score</h3>
                <VueApexCharts
                  type="radialBar"
                  height="300"
                  :options="productivityChartOptions"
                  :series="productivitySeries"
                />
                <div class="mt-4 grid grid-cols-3 gap-4 text-center">
                  <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Hours/Day</p>
                    <p class="font-semibold">{{ personal_metrics.productivity_score.metrics.hours_per_day }}h</p>
                  </div>
                  <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Task Rate</p>
                    <p class="font-semibold">{{ Math.round(personal_metrics.productivity_score.metrics.task_completion_rate) }}%</p>
                  </div>
                  <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Consistency</p>
                    <p class="font-semibold">{{ Math.round(personal_metrics.productivity_score.metrics.consistency_score) }}%</p>
                  </div>
                </div>
              </div>

              <!-- Work Pattern -->
              <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-xs">
                <h3 class="text-lg font-semibold mb-6">Peak Productivity Hours</h3>
                <VueApexCharts
                  type="bar"
                  height="300"
                  :options="workPatternChartOptions"
                  :series="workPatternSeries"
                />
              </div>
            </div>

            <!-- Productivity Breakdown -->
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-xs">
              <h3 class="text-lg font-semibold mb-4">Productivity Breakdown</h3>
              <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="text-center p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                  <p class="text-xl font-bold text-gray-900 dark:text-white">
                    {{ personal_metrics.productivity_score.breakdown.total_hours }}h
                  </p>
                  <p class="text-sm text-gray-500 dark:text-gray-400">Total Hours</p>
                </div>
                <div class="text-center p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                  <p class="text-xl font-bold text-gray-900 dark:text-white">
                    {{ personal_metrics.productivity_score.breakdown.completed_tasks }}
                  </p>
                  <p class="text-sm text-gray-500 dark:text-gray-400">Completed</p>
                </div>
                <div class="text-center p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                  <p class="text-xl font-bold text-gray-900 dark:text-white">
                    {{ personal_metrics.productivity_score.breakdown.total_tasks }}
                  </p>
                  <p class="text-sm text-gray-500 dark:text-gray-400">Total Tasks</p>
                </div>
                <div class="text-center p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                  <p class="text-xl font-bold text-gray-900 dark:text-white">
                    {{ personal_metrics.productivity_score.breakdown.work_days }}
                  </p>
                  <p class="text-sm text-gray-500 dark:text-gray-400">Work Days</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Insights Tab -->
          <div v-else-if="activeTab === 'insights'" class="space-y-6">
            <!-- Performance Trend -->
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-xs">
              <h3 class="text-lg font-semibold mb-4">Performance Trend</h3>
              <div class="flex items-center space-x-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                <TrendingUp
                  :class="[
                    'w-8 h-8',
                    performance_insights.trend.trend_direction === 'improving' ? 'text-green-500' :
                    performance_insights.trend.trend_direction === 'declining' ? 'text-red-500' :
                    'text-gray-500'
                  ]"
                />
                <div>
                  <p class="font-medium text-gray-900 dark:text-white">
                    {{ performance_insights.trend.trend_summary }}
                  </p>
                  <p class="text-sm text-gray-500 dark:text-gray-400">
                    Trend Strength: {{ performance_insights.trend.trend_strength }}%
                  </p>
                </div>
              </div>
            </div>

            <!-- Performance Comparison -->
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-xs">
              <h3 class="text-lg font-semibold mb-4">Performance Comparison</h3>
              <div class="grid grid-cols-2 gap-4">
                <div class="text-center p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                  <p class="text-xl font-bold text-gray-900 dark:text-white">
                    {{ performance_insights.trend.comparison.previous_period }}%
                  </p>
                  <p class="text-sm text-gray-500 dark:text-gray-400">Previous Period</p>
                </div>
                <div class="text-center p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                  <p class="text-xl font-bold text-gray-900 dark:text-white">
                    {{ performance_insights.trend.comparison.current_period }}%
                  </p>
                  <p class="text-sm text-gray-500 dark:text-gray-400">Current Period</p>
                </div>
              </div>
            </div>

            <!-- Recommendations -->
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-xs">
              <h3 class="text-lg font-semibold mb-4">Recommendations</h3>
              <div class="space-y-4">
                <div
                  v-for="(recommendation, index) in performance_insights.recommendations"
                  :key="index"
                  class="flex items-start space-x-3 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border-l-4 border-blue-500"
                >
                  <AlertCircle class="w-5 h-5 text-blue-500 mt-0.5 flex-shrink-0" />
                  <p class="text-gray-700 dark:text-gray-300">{{ recommendation }}</p>
                </div>
              </div>
            </div>

            <!-- Performance Indicator -->
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-xs">
              <h3 class="text-lg font-semibold mb-4">Overall Performance</h3>
              <div class="text-center p-6">
                <div
                  :class="[
                    'inline-flex items-center px-4 py-2 rounded-full text-lg font-semibold',
                    personal_metrics.completion_rate.performance_indicator === 'Excellent' ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400' :
                    personal_metrics.completion_rate.performance_indicator === 'Good' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400' :
                    personal_metrics.completion_rate.performance_indicator === 'Average' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400' :
                    'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400'
                  ]"
                >
                  {{ personal_metrics.completion_rate.performance_indicator }}
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                  Based on completion rate and task efficiency
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>



