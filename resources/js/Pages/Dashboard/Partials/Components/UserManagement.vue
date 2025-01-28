<script setup lang="ts">
import { ref, computed } from 'vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Progress } from '@/components/ui/progress'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow
} from '@/components/ui/table'
import {
  UserCheck,
  UserCog,
  Activity,
  TrendingUp,
  TrendingDown,
  Minus
} from 'lucide-vue-next'
import UserOnboarding from "@/Components/UserOnboarding.vue";

interface UserMetrics {
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

const props = defineProps<{
  userMetrics: UserMetrics
}>()

// Computed properties for user statistics
const userStats = computed(() => [
  {
    title: 'Onboarded Users',
    value: props.userMetrics.onboarding_status.completed,
    icon: UserCheck,
    progress: props.userMetrics.onboarding_status.completion_rate,
    trend: {
      direction: props.userMetrics.activity_logs.trend,
      value: props.userMetrics.activity_logs.last_24h
    }
  },
  {
    title: 'Pending Onboarding',
    value: props.userMetrics.onboarding_status.pending,
    icon: UserCog,
    trend: {
      direction: 'stable',
      value: 0
    }
  },
  {
    title: '24h Activity',
    value: props.userMetrics.activity_logs.last_24h,
    icon: Activity,
    trend: {
      direction: props.userMetrics.activity_logs.trend,
      value: props.userMetrics.activity_logs.last_7d
    }
  }
])

const getTrendIcon = (trend: string) => {
  switch (trend) {
    case 'increasing': return TrendingUp
    case 'decreasing': return TrendingDown
    default: return Minus
  }
}

const getTrendColor = (trend: string) => {
  switch (trend) {
    case 'increasing': return 'text-green-500 dark:text-green-400'
    case 'decreasing': return 'text-red-500 dark:text-red-400'
    default: return 'text-muted-foreground'
  }
}

// Sort department distribution by percentage
const sortedDepartments = computed(() => {
  return [...props.userMetrics.department_distribution]
    .sort((a, b) => b.percentage - a.percentage)
})
</script>

<template>
  <div class="space-y-6">
    <!-- User Statistics Cards -->
    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
      <Card v-for="stat in userStats" :key="stat.title">
        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
          <CardTitle class="text-sm font-medium">
            {{ stat.title }}
          </CardTitle>

          <component
            :is="stat.icon"
            class="h-4 w-4 text-muted-foreground"
          />
        </CardHeader>

        <CardContent>
          <div class="text-2xl font-bold">{{ stat.value }}</div>
          <div v-if="stat.progress" class="mt-2">
            <Progress :value="stat.progress" class="h-2" />
            <p class="mt-1 text-xs text-muted-foreground">
              {{ stat.progress }}% completion rate
            </p>
          </div>

          <div v-if="stat.trend" class="mt-2 flex items-center text-xs">
            <component
              :is="getTrendIcon(stat.trend.direction)"
              class="mr-1 h-3 w-3"
              :class="getTrendColor(stat.trend.direction)"
            />

            <span :class="getTrendColor(stat.trend.direction)">
              {{ stat.trend.value }} users
            </span>
          </div>
        </CardContent>
      </Card>
    </div>

    <UserOnboarding :user-onboarding="userMetrics.onboarding_status" />

    <!-- Department Distribution -->
    <Card>
      <CardHeader>
        <CardTitle>Department Distribution</CardTitle>
      </CardHeader>
      <CardContent>
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Department</TableHead>
              <TableHead>Users</TableHead>
              <TableHead>Distribution</TableHead>
              <TableHead class="text-right">Percentage</TableHead>
            </TableRow>
          </TableHeader>

          <TableBody>
            <TableRow
              v-for="dept in sortedDepartments"
              :key="dept.department_id">
              <TableCell class="font-medium">
                {{ dept.department_id }}
              </TableCell>

              <TableCell>{{ dept.count }}</TableCell>

              <TableCell>
                <div class="w-full h-2 bg-muted rounded-full">
                  <div
                    class="h-full bg-primary rounded-full"
                    :style="{ width: `${dept.percentage}%` }"
                  />
                </div>
              </TableCell>

              <TableCell class="text-right">
                {{ dept.percentage }}%
              </TableCell>
            </TableRow>
          </TableBody>
        </Table>
      </CardContent>
    </Card>
  </div>
</template>

<style scoped>
.trend-indicator {
  @apply flex items-center text-xs font-medium;
}

:deep(.progress-indicator) {
  @apply transition-all duration-300;
}
</style>
