<script setup lang="ts">
import { ref, computed, onMounted, nextTick } from 'vue'
import { Head } from '@inertiajs/vue3'
import { gsap } from 'gsap'
import {
  BarChart3,
  TrendingUp,
  Users,
  Clock,
  Target,
  Award,
  Activity,
  Calendar,
  Download
} from 'lucide-vue-next'
import ManagerLayout from '@/layouts/ManagerLayout.vue'
import { Button } from '@/components/ui/button'
import CustomCard from '@/components/CustomCard.vue'
import StatsCard from '@/components/StatsCard.vue'
import UserAvatar from '@/components/UserAvatar.vue'
import { getInitials } from '@/lib/stringUtils'
import { useTheme } from '@/composables/useTheme'

interface Props {
  performanceData: {
    overall_score: number
    team_productivity: number
    efficiency_score: number
    avg_hours_per_member: number
    total_hours: number
    total_entries: number
    hours_change_percent: string
  }
  complianceData: {
    compliance_rate: number
    on_time_submissions: number
    overdue_count: number
    total_members: number
    active_members: number
  }
  trendingData: {
    top_performers: Array<{
      id: number
      name: string
      department: string
      hours: number
      score: number
      completion_rate: number
    }>
    improvement_areas: Array<{
      name: string
      score: number
    }>
  }
  chartData: {
    categories: Array<string>
    series: Array<{
      name: string
      data: Array<number>
    }>
  }
  period: string
}

const props = defineProps<Props>()

// Initialize theme
const { isDark } = useTheme()

// Calculate change percentages based on actual performance trends
const calculateOverallScoreChange = computed(() => {
  // If we have historical data from chartData, calculate real change
  const currentScore = props.performanceData?.overall_score || 0;
  if (props.chartData?.series && props.chartData.series.length > 0) {
    const productivitySeries = props.chartData.series.find(s => s.name === 'Productivity');
    if (productivitySeries && productivitySeries.data.length >= 2) {
      const recent = productivitySeries.data[productivitySeries.data.length - 1];
      const previous = productivitySeries.data[productivitySeries.data.length - 2];
      const change = ((recent - previous) / previous * 100).toFixed(1);
      return change >= 0 ? `+${change}%` : `${change}%`;
    }
  }
  return '+0%';
});

const calculateProductivityChange = computed(() => {
  return props.performanceData?.hours_change_percent || '+0%';
});

const calculateEfficiencyChange = computed(() => {
  // Calculate efficiency change from chart data if available
  if (props.chartData?.series && props.chartData.series.length > 0) {
    const efficiencySeries = props.chartData.series.find(s => s.name === 'Efficiency');
    if (efficiencySeries && efficiencySeries.data.length >= 2) {
      const recent = efficiencySeries.data[efficiencySeries.data.length - 1];
      const previous = efficiencySeries.data[efficiencySeries.data.length - 2];
      const change = ((recent - previous) / previous * 100).toFixed(1);
      return change >= 0 ? `+${change}%` : `${change}%`;
    }
  }
  return '+0%';
});

// Computed
const performanceMetrics = computed(() => [
  {
    icon: BarChart3,
    title: 'Overall Score',
    value: Math.round(props.performanceData?.overall_score || 0),
    suffix: '%',
    subtitle: 'Team average',
    change: calculateOverallScoreChange.value,
    color: 'from-blue-500 to-blue-600',
    bgColor: 'bg-blue-50 dark:bg-blue-900/20',
    iconColor: 'text-blue-600 dark:text-blue-400'
  },
  {
    icon: TrendingUp,
    title: 'Productivity',
    value: Math.round(props.performanceData?.team_productivity || 0),
    suffix: '%',
    subtitle: 'This month',
    change: calculateProductivityChange.value,
    color: 'from-green-500 to-green-600',
    bgColor: 'bg-green-50 dark:bg-green-900/20',
    iconColor: 'text-green-600 dark:text-green-400'
  },
  {
    icon: Activity,
    title: 'Efficiency',
    value: Math.round(props.performanceData?.efficiency_score || 0),
    suffix: '%',
    subtitle: 'Team efficiency',
    change: calculateEfficiencyChange.value,
    color: 'from-purple-500 to-purple-600',
    bgColor: 'bg-purple-50 dark:bg-purple-900/20',
    iconColor: 'text-purple-600 dark:text-purple-400'
  },
  {
    icon: Clock,
    title: 'Avg Hours',
    value: Math.round(props.performanceData?.avg_hours_per_member || 0),
    suffix: 'h',
    subtitle: 'Per member',
    change: props.performanceData?.hours_change_percent || '0%',
    color: 'from-orange-500 to-orange-600',
    bgColor: 'bg-orange-50 dark:bg-orange-900/20',
    iconColor: 'text-orange-600 dark:text-orange-400'
  }
])

// Lifecycle hooks
onMounted(() => {
  nextTick(() => {
    gsap.set('.performance-card', { opacity: 0, y: 20 })
    gsap.to('.performance-card', {
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
  <Head title="Team Performance" />

  <ManagerLayout>
    <!-- Header Section -->
    <div>
      <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-6 mb-6">
        <div>
          <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
            <BarChart3 class="w-8 h-8" />
            Team Performance
          </h1>
          <p class="text-gray-600 dark:text-gray-400 mt-1">
            Comprehensive analytics and performance metrics for your team
          </p>
        </div>

        <!-- Quick Actions -->
        <div class="flex flex-wrap gap-3">
          <Button class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium inline-flex items-center gap-2">
            <Download class="w-5 h-5" />
            Export Report
          </Button>
        </div>
      </div>
    </div>

    <!-- Performance Stats -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
      <StatsCard
        v-for="(metric, index) in performanceMetrics"
        :key="index"
        :icon="metric.icon"
        :title="metric.title"
        :value="metric.value"
        :suffix="metric.suffix"
        :subtitle="metric.subtitle"
        :change="metric.change"
        :color="metric.color"
        :bg-color="metric.bgColor"
        :icon-color="metric.iconColor"
        :clickable="false"
        class="performance-card"
      />
    </div>

    <!-- Main Content -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
      <!-- Performance Chart -->
      <CustomCard
        title="Performance Trends"
        description="Team performance over time"
        :icon="TrendingUp"
        class="performance-card"
      >
        <div class="h-64">
          <apexchart
            type="line"
            height="100%"
            :options="{
              chart: {
                id: 'performance-chart',
                toolbar: { show: false },
                background: 'transparent'
              },
              theme: {
                mode: isDark ? 'dark' : 'light'
              },
              colors: ['#3B82F6', '#10B981', '#F59E0B'],
              dataLabels: { enabled: false },
              stroke: {
                curve: 'smooth',
                width: 3
              },
              grid: {
                borderColor: isDark ? '#374151' : '#E5E7EB',
                strokeDashArray: 3
              },
              xaxis: {
                categories: props.chartData?.categories || ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
                axisBorder: { show: false },
                axisTicks: { show: false }
              },
              yaxis: {
                max: 100,
                labels: {
                  formatter: (val) => val + '%'
                }
              },
              legend: {
                position: 'bottom',
                horizontalAlign: 'center'
              },
              tooltip: {
                theme: isDark ? 'dark' : 'light'
              }
            }"
            :series="props.chartData?.series || [
              {
                name: 'Productivity',
                data: [75, 82, 88, 92]
              },
              {
                name: 'Efficiency',
                data: [70, 78, 85, 88]
              },
              {
                name: 'Quality',
                data: [80, 85, 87, 90]
              }
            ]"
          />
        </div>
      </CustomCard>

      <!-- Top Performers -->
      <CustomCard
        title="Top Performers"
        description="Highest performing team members"
        :icon="Award"
        class="performance-card"
      >
        <div class="space-y-4">
          <div
            v-for="(performer, index) in (trendingData?.top_performers || []).slice(0, 5)"
            :key="performer.id || index"
            class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-lg"
          >
            <div class="flex items-center gap-3">
              <div
                class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold"
                :class="{
                  'bg-yellow-100 text-yellow-800': index === 0,
                  'bg-gray-100 text-gray-800': index === 1,
                  'bg-orange-100 text-orange-800': index === 2,
                  'bg-blue-100 text-blue-800': index > 2
                }"
              >
                {{ index + 1 }}
              </div>
              <UserAvatar
                :fallback="getInitials(performer.name || `User ${index + 1}`)"
                class="w-8 h-8"
              />
              <div>
                <div class="font-medium text-gray-900 dark:text-white text-sm">
                  {{ performer.name || `Team Member ${index + 1}` }}
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400">
                  {{ performer.department || 'Development' }}
                </div>
              </div>
            </div>
            <div class="text-right">
              <div class="text-sm font-medium text-green-600 dark:text-green-400">
                {{ Math.round(performer.score || (95 - index * 5)) }}%
              </div>
              <div class="text-xs text-gray-500 dark:text-gray-400">
                {{ performer.hours || (40 + index) }}h this week
              </div>
            </div>
          </div>
        </div>

        <div v-if="!(trendingData?.top_performers || []).length" class="text-center py-8">
          <Award class="w-12 h-12 text-gray-400 mx-auto mb-3" />
          <p class="text-gray-600 dark:text-gray-400">No performance data available</p>
        </div>
      </CustomCard>
    </div>

    <!-- Compliance and Additional Metrics -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- Compliance Status -->
      <CustomCard
        title="Compliance"
        description="Team compliance metrics"
        :icon="Target"
        class="performance-card"
      >
        <div class="space-y-4">
          <div class="text-center">
            <div class="text-3xl font-bold text-gray-900 dark:text-white">
              {{ Math.round(complianceData?.compliance_rate || 94) }}%
            </div>
            <p class="text-sm text-gray-500 dark:text-gray-400">Compliance Rate</p>
          </div>

          <div class="space-y-3">
            <div class="flex justify-between text-sm">
              <span class="text-gray-600 dark:text-gray-400">On-time submissions</span>
              <span class="font-medium text-green-600 dark:text-green-400">
                {{ complianceData?.on_time_submissions || 28 }}
              </span>
            </div>
            <div class="flex justify-between text-sm">
              <span class="text-gray-600 dark:text-gray-400">Overdue items</span>
              <span class="font-medium text-red-600 dark:text-red-400">
                {{ complianceData?.overdue_count || 2 }}
              </span>
            </div>
          </div>
        </div>
      </CustomCard>

      <!-- Activity Summary -->
      <CustomCard
        title="Activity Summary"
        description="Recent team activity"
        :icon="Activity"
        class="performance-card"
      >
        <div class="space-y-3">
          <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
            <div class="text-lg font-bold text-blue-600 dark:text-blue-400">{{ props.performanceData?.total_entries || 0 }}</div>
            <div class="text-sm text-blue-700 dark:text-blue-300">Work Entries</div>
          </div>
          <div class="p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
            <div class="text-lg font-bold text-green-600 dark:text-green-400">{{ props.complianceData?.active_members || 0 }}</div>
            <div class="text-sm text-green-700 dark:text-green-300">Active Members</div>
          </div>
          <div class="p-3 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
            <div class="text-lg font-bold text-purple-600 dark:text-purple-400">{{ Math.round(props.performanceData?.team_productivity || 0) }}%</div>
            <div class="text-sm text-purple-700 dark:text-purple-300">Team Productivity</div>
          </div>
        </div>
      </CustomCard>

      <!-- Improvement Areas -->
      <CustomCard
        title="Improvement Areas"
        description="Focus areas for growth"
        :icon="TrendingUp"
        class="performance-card"
      >
        <div class="space-y-3">
          <div
            v-for="area in (trendingData?.improvement_areas || []).slice(0, 4)"
            :key="area.name"
            class="space-y-2"
          >
            <div class="flex justify-between items-center">
              <span class="text-sm text-gray-600 dark:text-gray-400">{{ area.name }}</span>
              <span class="text-sm font-medium text-gray-900 dark:text-white">{{ area.score }}%</span>
            </div>
            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
              <div
                class="h-2 rounded-full transition-all duration-300"
                :class="{
                  'bg-green-500': area.score >= 80,
                  'bg-yellow-500': area.score >= 60 && area.score < 80,
                  'bg-red-500': area.score < 60
                }"
                :style="{ width: `${area.score}%` }"
              />
            </div>
          </div>
        </div>

        <div v-if="!(trendingData?.improvement_areas || []).length" class="text-center py-8">
          <TrendingUp class="w-12 h-12 text-gray-400 mx-auto mb-3" />
          <p class="text-gray-600 dark:text-gray-400">No improvement areas data available</p>
        </div>
      </CustomCard>
    </div>

  </ManagerLayout>
</template>
