<script setup lang="ts">
import {ref, computed, onMounted, nextTick} from 'vue'
import {Head, Link, router, usePoll} from '@inertiajs/vue3'
import {gsap} from 'gsap'
import {
  Users,
  Clock,
  CheckCircle,
  TrendingUp,
  Activity,
  AlertCircle,
  Calendar,
  Target,
  BarChart3,
  Plus,
  ArrowRight,
  FileText,
  UserCheck,
  Settings,
  Download,
  RefreshCw
} from 'lucide-vue-next'
import ManagerLayout from '@/layouts/ManagerLayout.vue'
import {Button} from '@/components/ui/button'
import {Badge} from '@/components/ui/badge'
import UserAvatar from '@/components/UserAvatar.vue'
import StatsCard from '@/components/StatsCard.vue'
import CustomCard from '@/components/CustomCard.vue'
import {getInitials} from '@/lib/stringUtils'
import {useTheme} from '@/composables/useTheme'

interface DashboardData {
  team_summary?: {
    total_members: number
    active_members: number
    on_leave: number
    pending_reports: number
  }
  performance_analytics?: {
    team_productivity: number
    average_hours_per_member: number
    completion_rate: number
    efficiency_score: number
    recent_activity: Array<any>
  }
  compliance_status?: {
    total_reports_due: number
    submitted_reports: number
    overdue_reports: number
    compliance_percentage: number
  }
  trending_insights?: {
    top_performers: Array<any>
    improvement_areas: Array<any>
    weekly_trends: Array<any>
  }
  recent_team_activity?: Array<any>
  upcoming_deadlines?: Array<any>
}

interface ChartData {
  days: string[]
  performance_trends: Array<{
    productivity: number
    efficiency: number
  }>
  activity_data: Array<{
    logged: number
    overtime: number
    missed: number
  }>
}

interface Props {
  dashboardData: DashboardData
  chartData: ChartData
  currentUser: {
    id: number
    name: string
    email: string
  }
}

const props = defineProps<Props>()

// Initialize theme
const {isDark} = useTheme()

// Reactive state
const selectedPeriod = ref('current_month')
const isPollingActive = ref(true)

// Setup polling for real-time updates every 30 seconds
const {start: startPolling, stop: stopPolling} = usePoll(30000, {
  autoStart: true,
  keepAlive: false, // Allow throttling when tab is not active
  onStart: () => {
    console.log('Polling dashboard data...')
  },
  onFinish: () => {
    console.log('Dashboard data updated')
  }
})

// Computed properties
const teamMetrics = computed(() => [
  {
    icon: Users,
    title: 'Team Members',
    value: props.dashboardData.team_summary?.total_members || 0,
    subtitle: `${props.dashboardData.team_summary?.active_members || 0} active`,
    change: `${props.dashboardData.team_summary?.active_members || 0} online`,
    color: 'from-blue-500 to-blue-600',
    bgColor: 'bg-blue-50 dark:bg-blue-900/20',
    iconColor: 'text-blue-600 dark:text-blue-400',
    route: 'manager.team.index'
  },
  {
    icon: CheckCircle,
    title: 'Completion Rate',
    value: Math.round(props.dashboardData.performance_analytics?.completion_rate || 0),
    suffix: '%',
    subtitle: 'Team average',
    change: '+5.2%',
    color: 'from-green-500 to-green-600',
    bgColor: 'bg-green-50 dark:bg-green-900/20',
    iconColor: 'text-green-600 dark:text-green-400'
  },
  {
    icon: Clock,
    title: 'Avg. Hours/Member',
    value: Math.round(props.dashboardData.performance_analytics?.average_hours_per_member || 0),
    suffix: 'h',
    subtitle: 'This period',
    change: '+12%',
    color: 'from-purple-500 to-purple-600',
    bgColor: 'bg-purple-50 dark:bg-purple-900/20',
    iconColor: 'text-purple-600 dark:text-purple-400'
  },
  {
    icon: FileText,
    title: 'Pending Reports',
    value: props.dashboardData.team_summary?.pending_reports || 0,
    subtitle: 'Need review',
    change: 'Urgent',
    color: 'from-orange-500 to-orange-600',
    bgColor: 'bg-orange-50 dark:bg-orange-900/20',
    iconColor: 'text-orange-600 dark:text-orange-400',
    route: 'manager.team-reports'
  }
])

const getTimeOfDay = (): string => {
  const hour = new Date().getHours()
  if (hour < 12) return 'morning'
  if (hour < 17) return 'afternoon'
  return 'evening'
}

const formatDate = (date: string): string => {
  return new Date(date).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const getStatusColor = (status: string): string => {
  const colors = {
    'completed': 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
    'in_progress': 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
    'pending': 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
    'overdue': 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300'
  }
  return colors[status] || 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300'
}

const togglePolling = () => {
  if (isPollingActive.value) {
    stopPolling()
    isPollingActive.value = false
  } else {
    startPolling()
    isPollingActive.value = true
  }
}

const refreshDashboard = () => {
  router.reload({only: ['dashboardData']})
}

// Lifecycle hooks
onMounted(() => {
  nextTick(() => {
    gsap.set('.metric-card', {opacity: 0, y: 20})
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
  <Head title="Manager Dashboard"/>

  <ManagerLayout>
    <!-- Header Section -->
    <div class="my-12">
      <div class="relative z-30 flex flex-col lg:flex-row justify-between items-start lg:items-center gap-6 mb-8">
        <div>
          <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
            Good {{ getTimeOfDay() }}, {{ currentUser.name.split(' ')[0] }}! 👋
          </h1>
          <p class="text-gray-600 dark:text-gray-400 mt-1">
            Here's your team overview and performance insights
          </p>
        </div>

        <!-- Quick Actions -->
        <div class="grid grid-cols-2 gap-3 align-items-end">
          <Link
            :as="Button"
            size="sm"
            :href="route('manager.team-reports')"
            class="cursor-pointer">
            <FileText class="w-5 h-5"/>
            Review Reports
          </Link>

          <Link
            :as="Button"
            size="sm"
            :href="route('manager.team-performance')"
            variant="outline" class="cursor-pointer">
            <BarChart3 class="w-5 h-5"/>
            Analytics
          </Link>

          <Button
            size="sm"
            variant="outline"
            @click="togglePolling"
            :class="{
              'bg-green-50 border-green-200 text-green-700 dark:bg-green-900/20 dark:border-green-800 dark:text-green-300': isPollingActive,
              'bg-gray-50 border-gray-200 text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300': !isPollingActive
            }">
            <Activity class="w-5 h-5" :class="{ 'animate-pulse': isPollingActive }"/>
            {{ isPollingActive ? 'Live Updates On' : 'Live Updates Off' }}
          </Button>

<!--          <Button-->
<!--            variant="ghost"-->
<!--            size="sm"-->
<!--            class="px-4 py-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300"-->
<!--            @click="refreshDashboard">-->
<!--            <RefreshCw class="w-4 h-4"/>-->
<!--          </Button>-->
        </div>
      </div>
    </div>

    <!-- Key Metrics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
      <StatsCard
        v-for="(metric, index) in teamMetrics"
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
        :route="metric.route"
      />
    </div>

    <!-- Main Content -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">

      <!-- Team Performance Overview -->
      <CustomCard
        title="Team Performance"
        description="Key performance indicators for your team"
        :icon="TrendingUp">
        <template #header>
          <Link
            :as="Button"
            :href="route('manager.team-performance')"
            variant="outline" size="sm">
            View
            <ArrowRight class="w-4 h-4 ml-1"/>
          </Link>
        </template>

        <div class="flex-1"></div>

        <div class="space-y-4">
          <div v-if="dashboardData.performance_analytics">
            <div class="flex justify-between items-center mb-2">
              <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Team Productivity</span>
              <span class="text-sm font-bold text-gray-900 dark:text-white">
                  {{ Math.round(dashboardData.performance_analytics.team_productivity || 0) }}%
                </span>
            </div>
            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
              <div
                class="bg-blue-500 h-2 rounded-full transition-all duration-300"
                :style="{ width: `${dashboardData.performance_analytics.team_productivity || 0}%` }"
              />
            </div>
          </div>

          <div v-if="dashboardData.performance_analytics">
            <div class="flex justify-between items-center mb-2">
              <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Efficiency Score</span>
              <span class="text-sm font-bold text-gray-900 dark:text-white">
                  {{ Math.round(dashboardData.performance_analytics.efficiency_score || 0) }}%
                </span>
            </div>
            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
              <div
                class="bg-green-500 h-2 rounded-full transition-all duration-300"
                :style="{ width: `${dashboardData.performance_analytics.efficiency_score || 0}%` }"
              />
            </div>
          </div>
        </div>
      </CustomCard>

      <!-- Performance Trends Chart -->
      <CustomCard
        title="Performance Trends"
        description="Team performance over the last 7 days"
        :icon="BarChart3">
        <div class="h-64">
          <apexchart
            type="area"
            height="100%"
            :options="{
              chart: {
                id: 'performance-trends',
                toolbar: { show: false },
                background: 'transparent'
              },
              theme: {
                mode: isDark ? 'dark' : 'light'
              },
              colors: ['#3B82F6', '#10B981'],
              dataLabels: { enabled: false },
              stroke: {
                curve: 'smooth',
                width: 2
              },
              fill: {
                type: 'gradient',
                gradient: {
                  shadeIntensity: 1,
                  opacityFrom: 0.4,
                  opacityTo: 0.1,
                  stops: [0, 100]
                }
              },
              grid: {
                borderColor: isDark ? '#374151' : '#E5E7EB',
                strokeDashArray: 3
              },
              xaxis: {
                categories: props.chartData.days,
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
                position: 'top',
                horizontalAlign: 'left',
                offsetY: -10
              },
              tooltip: {
                theme: isDark ? 'dark' : 'light'
              }
            }"
            :series="[
              {
                name: 'Productivity',
                data: props.chartData.performance_trends.map(d => d.productivity)
              },
              {
                name: 'Efficiency',
                data: props.chartData.performance_trends.map(d => d.efficiency)
              }
            ]"
          />
        </div>
      </CustomCard>
    </div>

    <!-- Team Activity Chart Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
      <!-- Team Activity Chart -->
      <CustomCard
        title="Daily Team Activity"
        description="Hours logged by team members this week"
        :icon="Activity"
      >
        <div class="h-64">
          <apexchart
            type="bar"
            height="100%"
            :options="{
              chart: {
                id: 'team-activity',
                toolbar: { show: false },
                background: 'transparent'
              },
              theme: {
                mode: isDark ? 'dark' : 'light'
              },
              colors: ['#8B5CF6', '#F59E0B', '#EF4444'],
              dataLabels: { enabled: false },
              plotOptions: {
                bar: {
                  borderRadius: 4,
                  columnWidth: '60%'
                }
              },
              grid: {
                borderColor: isDark ? '#374151' : '#E5E7EB',
                strokeDashArray: 3
              },
              xaxis: {
                categories: props.chartData.days.slice(0, 5),
                axisBorder: { show: false },
                axisTicks: { show: false }
              },
              yaxis: {
                labels: {
                  formatter: (val) => val + 'h'
                }
              },
              legend: {
                position: 'top',
                horizontalAlign: 'left',
                offsetY: -10
              },
              tooltip: {
                theme: isDark ? 'dark' : 'light',
                y: {
                  formatter: (val) => val + ' hours'
                }
              }
            }"
            :series="[
              {
                name: 'Logged Hours',
                data: props.chartData.activity_data.map(d => d.logged)
              },
              {
                name: 'Overtime',
                data: props.chartData.activity_data.map(d => d.overtime)
              },
              {
                name: 'Missed Hours',
                data: props.chartData.activity_data.map(d => d.missed)
              }
            ]"
          />
        </div>
      </CustomCard>

      <!-- Recent Team Activity -->
      <CustomCard
        title="Recent Team Activity"
        description="Latest updates from your team members"
        :icon="Activity">
        <div class="space-y-4">
          <div
            v-for="activity in (dashboardData.recent_team_activity || []).slice(0, 5)"
            :key="activity.id"
            class="flex items-start gap-3 p-3 rounded-lg border border-gray-200 dark:border-gray-700"
          >
            <UserAvatar
              :fallback="getInitials(activity.user?.name || 'U')"
              class="w-8 h-8 flex-shrink-0"
            />
            <div class="flex-1 min-w-0">
              <p class="text-sm text-gray-900 dark:text-white">
                <span class="font-medium">{{ activity.user?.name }}</span>
                {{ activity.description }}
              </p>
              <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                {{ formatDate(activity.created_at) }}
              </p>
            </div>
            <Badge
              v-if="activity.status"
              :class="getStatusColor(activity.status)"
              variant="secondary"
            >
              {{ activity.status }}
            </Badge>
          </div>
        </div>

        <div v-if="!(dashboardData.recent_team_activity || []).length" class="text-center py-8">
          <Activity class="w-12 h-12 text-gray-400 mx-auto mb-3"/>
          <p class="text-gray-600 dark:text-gray-400">No recent activity</p>
        </div>
      </CustomCard>
    </div>

    <!-- Compliance & Reports Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">

      <!-- Compliance Status -->
      <CustomCard
        title="Compliance Status"
        :icon="UserCheck"
      >
        <div v-if="dashboardData.compliance_status" class="space-y-4">
          <div class="text-center">
            <div class="text-3xl font-bold text-gray-900 dark:text-white">
              {{ Math.round(dashboardData.compliance_status.compliance_percentage || 0) }}%
            </div>
            <p class="text-sm text-gray-500 dark:text-gray-400">Compliance Rate</p>
          </div>

          <div class="space-y-2">
            <div class="flex justify-between text-sm">
              <span class="text-gray-600 dark:text-gray-400">Submitted</span>
              <span class="font-medium">{{ dashboardData.compliance_status.submitted_reports }}</span>
            </div>
            <div class="flex justify-between text-sm">
              <span class="text-gray-600 dark:text-gray-400">Due</span>
              <span class="font-medium">{{ dashboardData.compliance_status.total_reports_due }}</span>
            </div>
            <div class="flex justify-between text-sm">
              <span class="text-red-600 dark:text-red-400">Overdue</span>
              <span class="font-medium text-red-600 dark:text-red-400">{{
                  dashboardData.compliance_status.overdue_reports
                }}</span>
            </div>
          </div>
        </div>
      </CustomCard>

      <!-- Top Performers -->
      <CustomCard
        title="Top Performers"
        :icon="Target"
      >
        <div class="space-y-3">
          <div
            v-for="performer in (dashboardData.trending_insights?.top_performers || []).slice(0, 3)"
            :key="performer.id"
            class="flex items-center justify-between"
          >
            <div class="flex items-center gap-3">
              <UserAvatar
                :fallback="getInitials(performer.name)"
                class="w-8 h-8"
              />
              <div>
                <div class="font-medium text-gray-900 dark:text-white text-sm">
                  {{ performer.name }}
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400">
                  {{ performer.department }}
                </div>
              </div>
            </div>
            <div class="text-right">
              <div class="text-sm font-medium text-green-600 dark:text-green-400">
                {{ Math.round(performer.score || 0) }}%
              </div>
            </div>
          </div>
        </div>

        <div v-if="!(dashboardData.trending_insights?.top_performers || []).length" class="text-center py-4">
          <Target class="w-8 h-8 text-gray-400 mx-auto mb-2"/>
          <p class="text-sm text-gray-600 dark:text-gray-400">No performance data</p>
        </div>
      </CustomCard>

      <!-- Upcoming Deadlines -->
      <CustomCard
        title="Upcoming Deadlines"
        :icon="Calendar"
      >
        <div class="space-y-3">
          <div
            v-for="deadline in (dashboardData.upcoming_deadlines || []).slice(0, 4)"
            :key="deadline.id"
            class="flex items-center justify-between p-2 rounded-lg border border-gray-200 dark:border-gray-700"
          >
            <div class="flex-1 min-w-0">
              <div class="font-medium text-gray-900 dark:text-white text-sm truncate">
                {{ deadline.title }}
              </div>
              <div class="text-xs text-gray-500 dark:text-gray-400">
                {{ deadline.assignee }}
              </div>
            </div>
            <div class="text-xs text-gray-500 dark:text-gray-400">
              {{ formatDate(deadline.due_date) }}
            </div>
          </div>
        </div>

        <div v-if="!(dashboardData.upcoming_deadlines || []).length" class="text-center py-4">
          <Calendar class="w-8 h-8 text-gray-400 mx-auto mb-2"/>
          <p class="text-sm text-gray-600 dark:text-gray-400">No upcoming deadlines</p>
        </div>
      </CustomCard>
    </div>

    <!-- Critical Alerts -->
    <div v-if="dashboardData.compliance_status?.overdue_reports > 0" class="mb-8">
      <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
        <div class="flex items-start gap-3">
          <AlertCircle class="w-5 h-5 text-red-600 dark:text-red-400 mt-0.5 flex-shrink-0"/>
          <div class="flex-1">
            <h3 class="font-medium text-red-800 dark:text-red-200">
              {{ dashboardData.compliance_status.overdue_reports }} Overdue
              Report{{ dashboardData.compliance_status.overdue_reports !== 1 ? 's' : '' }}
            </h3>
            <p class="text-sm text-red-700 dark:text-red-300 mt-1">
              Some team members have overdue reports that need immediate attention.
            </p>
            <Link
              :href="route('manager.team-reports', { overdue: true })"
              class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 text-sm font-medium mt-2 inline-block"
            >
              Review Overdue Reports →
            </Link>
          </div>
        </div>
      </div>
    </div>
  </ManagerLayout>
</template>
