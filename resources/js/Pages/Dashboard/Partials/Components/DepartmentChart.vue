<script setup lang="ts">
import { computed } from 'vue'
import EmptyState from "@/pages/dashboard/partials/Components/EmptyState.vue";

interface DepartmentPerformance {
  name: string
  efficiency: number
  total_tasks: number
  completed_tasks: number
  total_hours: number
  member_count: number
}

interface Props {
  departmentPerformance: DepartmentPerformance[]
}

const props = defineProps<Props>()

// Theme-aware chart colors
const chartColors = computed(() => ({
  primary: 'rgb(var(--primary))',
  muted: 'rgb(var(--muted))',
  background: 'rgb(var(--background))',
  foreground: 'rgb(var(--foreground))'
}))

// Ensure departmentPerformance is not null before accessing
const safeDepartmentPerformance = computed(() => props.departmentPerformance || [])

const chartOptions = computed(() => ({
  chart: {
    type: 'bar',
    height: 350,
    toolbar: { show: false },
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
  plotOptions: {
    bar: {
      horizontal: true,
      borderRadius: 6,
      dataLabels: {
        position: 'top'
      },
      barHeight: '70%'
    }
  },
  colors: [chartColors.value.primary],
  dataLabels: {
    enabled: true,
    formatter: (val: number) => `${val}%`,
    offsetX: 30,
    style: {
      colors: [chartColors.value.foreground],
      fontWeight: 600
    }
  },
  xaxis: {
    categories: safeDepartmentPerformance.value.map(d => d.name),
    labels: {
      style: {
        colors: chartColors.value.foreground
      }
    },
    axisBorder: {
      show: false
    },
    axisTicks: {
      show: false
    }
  },
  yaxis: {
    max: 100,
    labels: {
      style: {
        colors: chartColors.value.foreground
      }
    }
  },
  grid: {
    borderColor: chartColors.value.muted,
    xaxis: {
      lines: {
        show: true
      }
    },
    yaxis: {
      lines: {
        show: false
      }
    },
    padding: {
      top: 0,
      right: 0,
      bottom: 0,
      left: 0
    }
  },
  theme: {
    mode: 'light'
  },
  tooltip: {
    theme: 'dark',
    y: {
      formatter: (val: number) => `${val}% Efficiency`
    }
  }
}))

// Add this computed property to check for empty state
const hasData = computed(() => {
  return safeDepartmentPerformance.value &&
    safeDepartmentPerformance.value.length > 0
})

const chartSeries = computed(() => [{
  name: 'Efficiency',
  data: safeDepartmentPerformance.value.map(d => d.efficiency)
}])

// Additional metrics for the info panel with null checks
const departmentMetrics = computed(() => {
  const departments = safeDepartmentPerformance.value
  const totalMembers = departments.reduce((sum, dept) => sum + (dept.member_count || 0), 0)
  const avgEfficiency = departments.length > 0
    ? departments.reduce((sum, dept) => sum + (dept.efficiency || 0), 0) / departments.length
    : 0
  const totalTasks = departments.reduce((sum, dept) => sum + (dept.total_tasks || 0), 0)

  return {
    totalMembers,
    avgEfficiency: Math.round(avgEfficiency),
    totalTasks
  }
})
</script>

<template>
  <EmptyState
    v-if="!hasData"
    title="No Department Data"
    description="There is no department performance data to display at the moment."
  />

  <Card class="department-chart" v-else>
    <CardHeader>
      <div class="flex items-center justify-between">
        <CardTitle>Department Performance</CardTitle>

        <div class="flex gap-4 text-sm text-muted-foreground">
          <div class="flex items-center gap-2">
            <div class="h-3 w-3 rounded-full bg-primary"></div>
            <span>Efficiency</span>
          </div>
        </div>
      </div>

      <div class="mt-4 grid grid-cols-3 gap-4 text-sm">
        <div class="flex flex-col">
          <span class="text-muted-foreground">
            Total Members
          </span>

          <span class="text-xl font-bold">
            {{ departmentMetrics.totalMembers }}
          </span>
        </div>

        <div class="flex flex-col">
          <span class="text-muted-foreground">
            Avg. Efficiency
          </span>

          <span class="text-xl font-bold">
            {{ departmentMetrics.avgEfficiency }}%
          </span>
        </div>

        <div class="flex flex-col">
          <span class="text-muted-foreground">
            Total Tasks
          </span>

          <span class="text-xl font-bold">
            {{ departmentMetrics.totalTasks }}
          </span>
        </div>
      </div>
    </CardHeader>

    <CardContent>
      <apexchart
        type="bar"
        height="250"
        :options="chartOptions"
        :series="chartSeries"
        class="mt-4 chart-container"
      />
    </CardContent>
  </Card>
</template>

<style scoped>
.department-chart {
  @apply transition-all duration-300;
}

:deep(.apexcharts-text) {
  @apply fill-foreground;
}

:deep(.apexcharts-grid-borders line),
:deep(.apexcharts-grid line) {
  @apply stroke-border;
}

:deep(.dark .apexcharts-theme-light) {
  @apply bg-background;
}

:deep(.apexcharts-tooltip) {
  @apply bg-popover text-popover-foreground border-border;
}
</style>


