<script setup lang="ts">
import { computed } from 'vue'
import { Users, Building2, CheckCircle, Activity } from 'lucide-vue-next'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'

interface MetricsGridProps {
  system_metrics: {
    total_users: number
    active_users: number
    total_departments: number
    verified_users_percentage: number
  }
  organization_metrics: {
    company_wide_efficiency: number
  }
}

const props = defineProps<MetricsGridProps>()

const metrics = computed(() => [
  {
    icon: Users,
    title: 'Total Users',
    value: props.system_metrics.total_users,
    subtitle: `${props.system_metrics.active_users} active`,
    trend: (props.system_metrics.active_users / props.system_metrics.total_users) * 100
  },
  {
    icon: Building2,
    title: 'Departments',
    value: props.system_metrics.total_departments,
    subtitle: 'Total departments'
  },
  {
    icon: CheckCircle,
    title: 'Verified Users',
    value: props.system_metrics.verified_users_percentage,
    subtitle: 'Verification rate',
    suffix: '%'
  },
  {
    icon: Activity,
    title: 'Company Efficiency',
    value: props.organization_metrics.company_wide_efficiency,
    subtitle: 'Overall performance',
    suffix: '%'
  }
])
</script>

<template>
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
    <Card
      v-for="metric in metrics"
      :key="metric.title"
      class="metric-card"
    >
      <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
        <CardTitle class="text-sm font-medium">
          {{ metric.title }}
        </CardTitle>
        <component
          :is="metric.icon"
          class="h-4 w-4 text-muted-foreground"
        />
      </CardHeader>
      <CardContent>
        <div class="text-2xl font-bold">
          {{ metric.value }}{{ metric.suffix || '' }}
        </div>
        <p class="text-xs text-muted-foreground">
          {{ metric.subtitle }}
        </p>
        <div v-if="metric.trend" class="mt-2">
          <div class="h-2 w-full bg-muted rounded">
            <div
              class="h-2 bg-primary rounded"
              :style="{ width: `${metric.trend}%` }"
            />
          </div>
        </div>
      </CardContent>
    </Card>
  </div>
</template>

<style scoped>
.metric-card {
  @apply transition-all duration-300;
}

.metric-card:hover {
  @apply shadow-md;
  transform: translateY(-2px);
}
</style>
