<script setup lang="ts">
import { computed } from 'vue'
import { Progress } from '@/components/ui/progress'
import { Users, Activity, TrendingUp, TrendingDown } from 'lucide-vue-next'
import EmptyState from "@/pages/dashboard/partials/Components/EmptyState.vue";

interface SystemMetrics {
  total_users: number
  active_users: number
  total_departments: number
  verified_users_percentage: number
}

interface Props {
  systemMetrics: SystemMetrics
}

const props = defineProps<Props>()

// Theme-aware chart colors
const chartColors = computed(() => ({
  primary: 'rgb(var(--primary))',
  muted: 'rgb(var(--muted))',
  background: 'rgb(var(--background))',
  foreground: 'rgb(var(--foreground))'
}))

// Chart series data - ensure non-null values
const chartSeries = computed(() => {
  const activeUsers = props.systemMetrics?.active_users ?? 0
  const totalUsers = props.systemMetrics?.total_users ?? 0
  const inactiveUsers = Math.max(0, totalUsers - activeUsers)
  return [activeUsers, inactiveUsers]
})

// Donut chart options
const chartOptions = computed(() => ({
  chart: {
    type: 'donut',
    height: 350,
    animations: {
      enabled: true,
      easing: 'easeinout',
      speed: 800,
      animateGradually: {
        enabled: true,
        delay: 150
      },
      dynamicAnimation: {
        enabled: true,
        speed: 350
      }
    },
    background: 'transparent',
    fontFamily: 'inherit'
  },
  labels: ['Active Users', 'Inactive Users'],
  colors: [chartColors.value.primary, chartColors.value.muted],
  plotOptions: {
    pie: {
      donut: {
        size: '70%',
        labels: {
          show: true,
          total: {
            show: true,
            label: 'Total Users',
            formatter: () => (props.systemMetrics?.total_users ?? 0).toString()
          },
          value: {
            formatter: (val: number) => Math.round(val).toString()
          }
        }
      }
    }
  },
  legend: {
    position: 'bottom',
    labels: {
      colors: chartColors.value.foreground
    }
  },
  tooltip: {
    theme: 'dark',
    y: {
      formatter: (val: number) => `${Math.round(val)} users`
    }
  },
  stroke: {
    width: 2
  },
  dataLabels: {
    enabled: true,
    formatter: (val: number) => `${Math.round(val)}%`
  }
}))

// Additional metrics with null checks
const activityMetrics = computed(() => {
  const total = props.systemMetrics?.total_users ?? 0
  const active = props.systemMetrics?.active_users ?? 0
  const verified = props.systemMetrics?.verified_users_percentage ?? 0

  return {
    activePercentage: total > 0 ? Math.round((active / total) * 100) : 0,
    verifiedPercentage: verified,
    inactiveUsers: Math.max(0, total - active)
  }
})

const getTrendIcon = (percentage: number) => {
  if (percentage >= 70) return TrendingUp
  if (percentage <= 30) return TrendingDown
  return Activity
}

const getTrendColor = (percentage: number) => {
  if (percentage >= 70) return 'text-green-500 dark:text-green-400'
  if (percentage <= 30) return 'text-red-500 dark:text-red-400'
  return 'text-yellow-500 dark:text-yellow-400'
}

// Add this computed property to check for empty state
const hasData = computed(() => {
  return props.systemMetrics &&
    props.systemMetrics.total_users > 0
})
</script>

<template>
  <EmptyState
    v-if="!hasData"
    title="No User Activity"
    description="There is no user activity data to display at the moment."
  />

  <Card class="user-activity-chart" v-else>
    <CardHeader>
      <div class="flex items-center justify-between">
        <CardTitle class="flex items-center gap-2">
          <Users class="h-5 w-5 text-muted-foreground" />
          User Activity Distribution
        </CardTitle>
      </div>

      <div class="mt-4 grid grid-cols-3 gap-4 text-sm">
        <div class="flex flex-col">
          <span class="text-muted-foreground">Total Users</span>
          <span class="text-xl font-bold">{{ props.systemMetrics?.total_users ?? 0 }}</span>
        </div>

        <div class="flex flex-col">
          <span class="text-muted-foreground">Active Users</span>
          <span class="text-xl font-bold">{{ props.systemMetrics?.active_users ?? 0 }}</span>
        </div>

        <div class="flex flex-col">
          <span class="text-muted-foreground">Active Rate</span>
          <div class="flex items-center gap-1">
            <span class="text-xl font-bold">{{ activityMetrics.activePercentage }}%</span>
            <component
              :is="getTrendIcon(activityMetrics.activePercentage)"
              class="h-4 w-4"
              :class="getTrendColor(activityMetrics.activePercentage)"
            />
          </div>
        </div>
      </div>
    </CardHeader>

    <CardContent>
      <apexchart
        type="donut"
        height="250"
        :options="chartOptions"
        :series="chartSeries"
        class="mt-4 chart-container"
      />
      <div class="mt-6 space-y-4">
        <div class="space-y-2">
          <div class="flex items-center justify-between text-sm">
            <span class="text-muted-foreground">Verified Users</span>
            <span class="font-medium">{{ activityMetrics.verifiedPercentage }}%</span>
          </div>
          <Progress :value="activityMetrics.verifiedPercentage" class="h-2" />
        </div>
        <p class="text-center text-sm text-muted-foreground">
          {{ activityMetrics.inactiveUsers }} users haven't been active recently
        </p>
      </div>
    </CardContent>
  </Card>
</template>

<style scoped>
.user-activity-chart {
  @apply transition-all duration-300;
}

:deep(.apexcharts-text) {
  @apply fill-foreground;
}

:deep(.apexcharts-legend-text) {
  @apply text-foreground;
}

:deep(.apexcharts-tooltip) {
  @apply bg-popover text-popover-foreground border-border;
}

:deep(.dark .apexcharts-theme-light) {
  @apply bg-background;
}
</style>


