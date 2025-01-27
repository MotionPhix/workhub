<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
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
  AlertCircle
} from 'lucide-vue-next'

interface EmployeeDashboardProps {
  base_metrics: {
    total_work_logs: number
    total_hours: number
    average_hours_per_day: number
    completed_tasks: number
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
        background: '#e7e7e7',
        strokeWidth: '97%'
      },
      dataLabels: {
        name: {
          show: true,
          color: '#888',
          fontSize: '15px'
        },
        value: {
          fontSize: '30px',
          show: true,
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
      style: { colors: '#64748b' }
    }
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
    <!-- Header -->
    <div class="flex justify-between items-center">
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
        Personal Dashboard
      </h1>
      <div class="flex space-x-2">
        <button
          v-for="tab in ['overview', 'productivity', 'insights']"
          :key="tab"
          @click="activeTab = tab"
          :class="[
            'px-4 py-2 rounded-lg transition-colors',
            activeTab === tab
              ? 'bg-primary text-white'
              : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
          ]"
        >
          {{ tab.charAt(0).toUpperCase() + tab.slice(1) }}
        </button>
      </div>
    </div>

    <!-- Key Metrics -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
      <div
        v-for="(metric, index) in [
          {
            icon: Clock,
            title: 'Total Hours',
            value: base_metrics.total_hours,
            suffix: 'hrs'
          },
          {
            icon: Activity,
            title: 'Daily Average',
            value: base_metrics.average_hours_per_day,
            suffix: 'hrs/day'
          },
          {
            icon: CheckCircle,
            title: 'Completed Tasks',
            value: base_metrics.completed_tasks,
            suffix: ''
          },
          {
            icon: TrendingUp,
            title: 'Completion Rate',
            value: personal_metrics.completion_rate.current_month.rate,
            suffix: '%'
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
              {{ metric.value }}{{ metric.suffix }}
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Productivity Score -->
      <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm">
        <h3 class="text-lg font-semibold mb-6">Productivity Score</h3>
        <VueApexCharts
          type="radialBar"
          height="300"
          :options="productivityChartOptions"
          :series="productivitySeries"
        />
      </div>

      <!-- Work Pattern -->
      <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm">
        <h3 class="text-lg font-semibold mb-6">Peak Productivity Hours</h3>
        <VueApexCharts
          type="bar"
          height="300"
          :options="workPatternChartOptions"
          :series="workPatternSeries"
        />
      </div>
    </div>

    <!-- Recommendations -->
    <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm">
      <h3 class="text-lg font-semibold mb-4">Recommendations</h3>
      <div class="space-y-4">
        <div
          v-for="(recommendation, index) in performance_insights.recommendations"
          :key="index"
          class="flex items-start space-x-3"
        >
          <AlertCircle class="w-5 h-5 text-primary mt-0.5" />
          <p class="text-gray-600 dark:text-gray-300">{{ recommendation }}</p>
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
