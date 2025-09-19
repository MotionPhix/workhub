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
  Download,
  AlertCircle,
  CheckCircle,
  ArrowUp,
  ArrowDown,
  Minus
} from 'lucide-vue-next'
import ManagerLayout from '@/layouts/ManagerLayout.vue'
import { Button } from '@/components/ui/button'
import CustomCard from '@/components/CustomCard.vue'
import StatsCard from '@/components/StatsCard.vue'
import { useTheme } from '@/composables/useTheme'

interface Props {
  insights: {
    performance_overview: {
      total_hours: number
      average_hours_per_member: number
      average_hours_per_day: number
      total_work_entries: number
      total_reports: number
      completion_rate: number
      quality_score: number
      efficiency_rating: number
    }
    productivity_trends: {
      weekly_breakdown: Record<string, {
        hours: number
        entries: number
        unique_contributors: number
      }>
      trend_direction: string
      peak_productivity_week: string
      productivity_variance: number
    }
    project_insights: {
      project_breakdown: Array<{
        project: { id: number | null, name: string }
        total_hours: number
        contributors: number
        entries: number
        average_hours_per_entry: number
        efficiency_score: number
      }>
      most_active_project: any
      project_distribution: any
      underutilized_projects: any[]
    }
    team_efficiency: {
      capacity_utilization: number
      time_tracking_compliance: number
      reporting_compliance: number
      average_entry_quality: number
      collaboration_index: number
      efficiency_bottlenecks: any
    }
    compliance_metrics: {
      time_entry_compliance: number
      daily_entry_rate: number
      report_submission_rate: number
      approval_pending_rate: number
      quality_control_score: number
      policy_adherence: number
    }
    recommendations: Array<{
      type: string
      priority: string
      title: string
      description: string
      affected_members?: string[]
    }>
  }
  period: string
}

const props = defineProps<Props>()

// Initialize theme
const { isDark } = useTheme()

// Computed insights
const overviewStats = computed(() => [
  {
    icon: Clock,
    title: 'Total Hours',
    value: props.insights.performance_overview.total_hours,
    suffix: 'h',
    subtitle: 'This period',
    change: '+5%',
    color: 'from-blue-500 to-blue-600',
    bgColor: 'bg-blue-50 dark:bg-blue-900/20',
    iconColor: 'text-blue-600 dark:text-blue-400'
  },
  {
    icon: Users,
    title: 'Work Entries',
    value: props.insights.performance_overview.total_work_entries,
    suffix: '',
    subtitle: 'Total entries',
    change: '+12%',
    color: 'from-green-500 to-green-600',
    bgColor: 'bg-green-50 dark:bg-green-900/20',
    iconColor: 'text-green-600 dark:text-green-400'
  },
  {
    icon: Target,
    title: 'Completion Rate',
    value: Math.round(props.insights.performance_overview.completion_rate),
    suffix: '%',
    subtitle: 'Quality metric',
    change: '+3%',
    color: 'from-purple-500 to-purple-600',
    bgColor: 'bg-purple-50 dark:bg-purple-900/20',
    iconColor: 'text-purple-600 dark:text-purple-400'
  },
  {
    icon: Award,
    title: 'Efficiency',
    value: Math.round(props.insights.performance_overview.efficiency_rating),
    suffix: '%',
    subtitle: 'Team efficiency',
    change: getTrendIcon(props.insights.productivity_trends.trend_direction),
    color: 'from-orange-500 to-orange-600',
    bgColor: 'bg-orange-50 dark:bg-orange-900/20',
    iconColor: 'text-orange-600 dark:text-orange-400'
  }
])

function getTrendIcon(direction: string): string {
  switch (direction) {
    case 'increasing': return '+8%'
    case 'decreasing': return '-2%'
    default: return '0%'
  }
}

const getTrendColor = (direction: string) => {
  switch (direction) {
    case 'increasing': return 'text-green-600'
    case 'decreasing': return 'text-red-600'
    default: return 'text-gray-600'
  }
}

const weeklyChartData = computed(() => {
  const breakdown = props.insights.productivity_trends.weekly_breakdown
  return {
    categories: Object.keys(breakdown),
    series: [{
      name: 'Hours',
      data: Object.values(breakdown).map(week => week.hours)
    }, {
      name: 'Entries',
      data: Object.values(breakdown).map(week => week.entries)
    }]
  }
})

// Lifecycle hooks
onMounted(() => {
  nextTick(() => {
    gsap.set('.insights-card', { opacity: 0, y: 20 })
    gsap.to('.insights-card', {
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
  <Head title="Team Insights" />

  <ManagerLayout>
    <!-- Header Section -->
    <div>
      <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-6 mb-6">
        <div>
          <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
            <BarChart3 class="w-8 h-8" />
            Team Insights
          </h1>
          <p class="text-gray-600 dark:text-gray-400 mt-1">
            Comprehensive analytics and insights for your team's performance
          </p>
        </div>

        <!-- Quick Actions -->
        <div class="flex flex-wrap gap-3">
          <Button class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium inline-flex items-center gap-2">
            <Download class="w-5 h-5" />
            Export Insights
          </Button>
        </div>
      </div>
    </div>

    <!-- Overview Stats -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
      <StatsCard
        v-for="(stat, index) in overviewStats"
        :key="index"
        :icon="stat.icon"
        :title="stat.title"
        :value="stat.value"
        :suffix="stat.suffix"
        :subtitle="stat.subtitle"
        :change="stat.change"
        :color="stat.color"
        :bg-color="stat.bgColor"
        :icon-color="stat.iconColor"
        :clickable="false"
        class="insights-card"
      />
    </div>

    <!-- Main Insights Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
      <!-- Productivity Trends -->
      <CustomCard
        title="Productivity Trends"
        description="Weekly productivity analysis"
        :icon="TrendingUp"
        class="insights-card"
      >
        <div class="space-y-4">
          <div class="flex items-center justify-between">
            <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Trend Direction</span>
            <span :class="['text-sm font-bold', getTrendColor(insights.productivity_trends.trend_direction)]">
              {{ insights.productivity_trends.trend_direction.charAt(0).toUpperCase() + insights.productivity_trends.trend_direction.slice(1) }}
            </span>
          </div>

          <div class="space-y-2">
            <div v-for="(week, date) in insights.productivity_trends.weekly_breakdown" :key="date"
                 class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
              <span class="text-sm text-gray-600 dark:text-gray-400">{{ date }}</span>
              <div class="text-right">
                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ week.hours }}h</div>
                <div class="text-xs text-gray-500 dark:text-gray-400">{{ week.entries }} entries</div>
              </div>
            </div>
          </div>
        </div>
      </CustomCard>

      <!-- Team Efficiency -->
      <CustomCard
        title="Team Efficiency"
        description="Efficiency and compliance metrics"
        :icon="Target"
        class="insights-card"
      >
        <div class="space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div class="text-center p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
              <div class="text-lg font-bold text-blue-600 dark:text-blue-400">
                {{ Math.round(insights.team_efficiency.capacity_utilization) }}%
              </div>
              <div class="text-xs text-blue-700 dark:text-blue-300">Capacity</div>
            </div>
            <div class="text-center p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
              <div class="text-lg font-bold text-green-600 dark:text-green-400">
                {{ Math.round(insights.team_efficiency.time_tracking_compliance) }}%
              </div>
              <div class="text-xs text-green-700 dark:text-green-300">Compliance</div>
            </div>
            <div class="text-center p-3 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
              <div class="text-lg font-bold text-purple-600 dark:text-purple-400">
                {{ Math.round(insights.team_efficiency.average_entry_quality) }}%
              </div>
              <div class="text-xs text-purple-700 dark:text-purple-300">Quality</div>
            </div>
            <div class="text-center p-3 bg-orange-50 dark:bg-orange-900/20 rounded-lg">
              <div class="text-lg font-bold text-orange-600 dark:text-orange-400">
                {{ insights.team_efficiency.collaboration_index }}
              </div>
              <div class="text-xs text-orange-700 dark:text-orange-300">Collaboration</div>
            </div>
          </div>
        </div>
      </CustomCard>
    </div>

    <!-- Secondary Insights Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
      <!-- Project Insights -->
      <CustomCard
        title="Project Breakdown"
        description="Hours distribution by project"
        :icon="Activity"
        class="insights-card"
      >
        <div class="space-y-3">
          <div v-for="project in insights.project_insights.project_breakdown.slice(0, 5)"
               :key="project.project.id || 'unassigned'"
               class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
            <div>
              <div class="font-medium text-sm text-gray-900 dark:text-white">
                {{ project.project.name }}
              </div>
              <div class="text-xs text-gray-500 dark:text-gray-400">
                {{ project.contributors }} contributors
              </div>
            </div>
            <div class="text-right">
              <div class="text-sm font-medium text-gray-900 dark:text-white">
                {{ project.total_hours }}h
              </div>
              <div class="text-xs text-gray-500 dark:text-gray-400">
                {{ project.entries }} entries
              </div>
            </div>
          </div>
        </div>
      </CustomCard>

      <!-- Compliance Metrics -->
      <CustomCard
        title="Compliance Status"
        description="Team compliance overview"
        :icon="CheckCircle"
        class="insights-card"
      >
        <div class="space-y-4">
          <div class="space-y-3">
            <div class="flex justify-between text-sm">
              <span class="text-gray-600 dark:text-gray-400">Time Entry</span>
              <span class="font-medium text-gray-900 dark:text-white">
                {{ Math.round(insights.compliance_metrics.time_entry_compliance) }}%
              </span>
            </div>
            <div class="flex justify-between text-sm">
              <span class="text-gray-600 dark:text-gray-400">Report Submission</span>
              <span class="font-medium text-gray-900 dark:text-white">
                {{ Math.round(insights.compliance_metrics.report_submission_rate) }}%
              </span>
            </div>
            <div class="flex justify-between text-sm">
              <span class="text-gray-600 dark:text-gray-400">Quality Control</span>
              <span class="font-medium text-gray-900 dark:text-white">
                {{ Math.round(insights.compliance_metrics.quality_control_score) }}%
              </span>
            </div>
            <div class="flex justify-between text-sm">
              <span class="text-gray-600 dark:text-gray-400">Policy Adherence</span>
              <span class="font-medium text-gray-900 dark:text-white">
                {{ Math.round(insights.compliance_metrics.policy_adherence) }}%
              </span>
            </div>
          </div>
        </div>
      </CustomCard>

      <!-- Recommendations -->
      <CustomCard
        title="Recommendations"
        description="Actionable insights"
        :icon="AlertCircle"
        class="insights-card"
      >
        <div class="space-y-3">
          <div v-for="(recommendation, index) in insights.recommendations.slice(0, 3)"
               :key="index"
               class="p-3 rounded-lg border-l-4"
               :class="{
                 'bg-red-50 dark:bg-red-900/20 border-red-500': recommendation.priority === 'high',
                 'bg-yellow-50 dark:bg-yellow-900/20 border-yellow-500': recommendation.priority === 'medium',
                 'bg-blue-50 dark:bg-blue-900/20 border-blue-500': recommendation.priority === 'low'
               }">
            <div class="text-sm font-medium text-gray-900 dark:text-white">
              {{ recommendation.title }}
            </div>
            <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">
              {{ recommendation.description }}
            </div>
          </div>

          <div v-if="!insights.recommendations.length" class="text-center py-4">
            <CheckCircle class="w-8 h-8 text-green-500 mx-auto mb-2" />
            <p class="text-sm text-gray-600 dark:text-gray-400">All metrics looking good!</p>
          </div>
        </div>
      </CustomCard>
    </div>
  </ManagerLayout>
</template>
