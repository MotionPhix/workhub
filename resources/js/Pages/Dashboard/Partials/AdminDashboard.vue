<script setup lang="ts">
import {ref, onMounted, nextTick, onBeforeUnmount} from 'vue'
import { gsap } from 'gsap'
import {
  Layout,
  Building2,
  UserCircle
} from 'lucide-vue-next'
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/Components/ui/tabs"
import { Card, CardHeader, CardTitle } from "@/Components/ui/card"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/Components/ui/select"
import MetricsGrid from "./Components/MetricsGrid.vue"
import DepartmentChart from "./Components/DepartmentChart.vue"
import UserActivityChart from "./Components/UserActivityChart.vue"
import DepartmentTable from "./Components/DepartmentTable.vue"
import ProjectsOverview from "./Components/ProjectsOverview.vue"
import UserManagement from "./Components/UserManagement.vue"
import DepartmentAnalytics from "./Components/DepartmentAnalytics.vue"
import {useTabPersistence} from "@/composables/useTabPersistence";

// Keep the interfaces as they are important for type checking
interface AdminDashboardProps {
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
  system_metrics: {
    total_users: number
    active_users: number
    total_departments: number
    verified_users_percentage: number
  }
  organization_metrics: {
    company_wide_efficiency: number
    total_projects: number
    active_projects: number
    department_performance: Array<{
      name: string
      efficiency: number
      total_tasks: number
      completed_tasks: number
      total_hours: number
      member_count: number
    }>
  }
  activity_trends: Array<any>
}

interface EnhancedAdminDashboardProps extends AdminDashboardProps {
  user_metrics: {
    onboarding_status: {
      completed: number
      pending: number
      completion_rate: number
    }
    activity_logs: {
      last_24h: number
      last_7d: number
      trend: 'increasing' | 'decreasing' | 'stable'
    }
    department_distribution: Array<{
      department_id: string
      count: number
      percentage: number
    }>
  }
  department_analytics: {
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
}

const props = defineProps<EnhancedAdminDashboardProps>()

// Reactive state
const { activeTab, handleTabChange, clearTabPersistence } = useTabPersistence()
const selectedTimeframe = ref('month')
const isLoading = ref(true)

// Animation
const animateIn = async () => {
  await nextTick()

  // Set initial states
  gsap.set('.metric-card', {
    opacity: 0,
    y: 20
  })

  gsap.set('.chart-container', {
    opacity: 0,
    scale: 0.95
  })

  // Create animation timeline
  const timeline = gsap.timeline({
    defaults: {
      ease: 'power2.out',
      clearProps: 'all' // Clear inline styles after animation
    }
  })

  timeline
    .to('.metric-card', {
      opacity: 1,
      y: 0,
      duration: 0.6,
      stagger: 0.1
    })
    .to('.chart-container', {
      opacity: 1,
      scale: 1,
      duration: 0.5,
      stagger: 0.1
    }, '-=0.3')
}

onBeforeUnmount(() => {
  clearTabPersistence()
})

onMounted(() => {
  setTimeout(() => {
    isLoading.value = false
    animateIn()
  }, 500)
})
</script>

<template>
  <div class="space-y-6">
    <Card>
      <CardHeader>
        <div class="flex justify-between items-center">
          <CardTitle>Organization Dashboard</CardTitle>

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
      </CardHeader>
    </Card>

    <Tabs v-model="activeTab" class="space-y-6">
      <TabsList class="grid grid-cols-3 lg:w-[400px]">
        <TabsTrigger value="overview" @change="handleTabChange">
          <Layout class="w-4 h-4 mr-2" />
          Overview
        </TabsTrigger>

        <TabsTrigger value="departments" @change="handleTabChange">
          <Building2 class="w-4 h-4 mr-2" />
          Departments
        </TabsTrigger>

        <TabsTrigger value="users" @change="handleTabChange">
          <UserCircle class="w-4 h-4 mr-2" />
          Users
        </TabsTrigger>
      </TabsList>

      <!-- Overview Tab -->
      <TabsContent value="overview" class="space-y-6">
        <MetricsGrid
          :system_metrics="props.system_metrics"
          :organization_metrics="props.organization_metrics"
        />

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <DepartmentChart
            :department-performance="props.organization_metrics.department_performance"
          />

          <UserActivityChart
            :system-metrics="props.system_metrics"
          />
        </div>

        <DepartmentTable
          :department-performance="props.organization_metrics.department_performance"
        />
      </TabsContent>

      <!-- Departments Tab -->
      <TabsContent value="departments" class="space-y-6">
        <ProjectsOverview />
        <DepartmentAnalytics
          :department-analytics="props.department_analytics"
          :department-performance="props.organization_metrics.department_performance"
        />
      </TabsContent>

      <!-- Users Tab -->
      <TabsContent value="users" class="space-y-6">
        <UserManagement
          :user-metrics="props.user_metrics"
        />
      </TabsContent>
    </Tabs>
  </div>
</template>

<style scoped>
.metric-card {
  @apply transition-all duration-300;
  will-change: transform, opacity;
}

.chart-container {
  will-change: transform, opacity;
}

:deep(.dark .apexcharts-theme-light) {
  @apply bg-background;
}
</style>
