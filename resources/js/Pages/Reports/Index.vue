<script setup lang="ts">
import {ref, computed, type Ref} from 'vue'
import { router } from '@inertiajs/vue3'
import {
  EllipsisIcon,
  DownloadIcon,
  FilterIcon,
  TrendingUpIcon,
  AlertCircleIcon,
  ClockIcon,
  BrainIcon,
  BarChart3Icon
} from 'lucide-vue-next'
import {
  DropdownMenu,
  DropdownMenuItem,
  DropdownMenuContent,
  DropdownMenuTrigger,
  DropdownMenuSeparator
} from "@/components/ui/dropdown-menu"
import { Card, CardHeader, CardTitle, CardContent } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { RangeCalendar } from '@/components/ui/range-calendar'
import { getLocalTimeZone, today, CalendarDate, parseDate } from '@internationalized/date'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogTrigger
} from "@/components/ui/dialog"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { useDeviceDetection } from '@/composables/useDeviceDetection'
import AppLayout from "@/layouts/AppLayout.vue"
import ReportTable from "@/pages/reports/partials/ReportTable.vue"
import {useDark} from "@vueuse/core";
import {format, format as simpleFormat} from "date-fns";
import ReportProductivityChart from "@/pages/reports/partials/ReportProductivityChart.vue";
import BurnoutRiskCard from "@/pages/reports/partials/BurnoutRiskCard.vue";
import FocusAnalyticsCard from "@/pages/reports/partials/FocusAnalyticsCard.vue";
import ExportReports from "@/pages/reports/partials/ExportReports.vue";

// Interfaces remain the same...
interface Report {
  id: number
  title: string
  created_at: string
  department: string
  total_hours: number
  status: string
  insights: {
    productivity_score: number
    burnout_risk: {
      risk_level: string
      score: number
    }
    focus_time_analysis: {
      total_focus_hours: number
      average_focus_session: number
    }
    energy_patterns: {
      most_productive_hours: number[]
      least_productive_hours: number[]
    }
  }
}

interface Props {
  reports: {
    data: Report[]
    current_page: number
    last_page: number
    total: number
    links: Array<{ active: boolean; url?: string; label: string }>
  }
  stats: {
    total_hours: number
    average_hours_per_day: number
    departments: Array<{ name: string; count: number }>
    productivity_trends: Array<{ date: string; score?: number }>
    focus_time_analytics: {
      summary: {
        average_focus_hours: number
        team_focus_percentage: number
      }
    }
  }
  filters: {
    start_date?: string
    end_date?: string
    department?: string
  }
}

// Update the dateRange ref and type
interface DateRangeValue {
  start: CalendarDate | null;
  end: CalendarDate | null;
}

const props = defineProps<Props>()

// State management
const isDark = useDark()
const start = today(getLocalTimeZone())
const end = start.add({ days: 7 })

// Initialize dateRange with CalendarDate objects
const dateRange = ref<DateRangeValue>({
  start: props.filters.start_date
    ? parseDate(props.filters.start_date)
    : null,
  end: props.filters.end_date
    ? parseDate(props.filters.end_date)
    : null
});

const { isMobile, isTablet, isDesktop } = useDeviceDetection()
const selectedDepartment = ref(props.filters.department || '')
const showFilters = ref(false)

// Stats cards data
const statsCards = computed(() => {
  // Helper function to calculate trend
  const calculateTrend = (current: number, previous: number) => {
    if (!previous) return { value: '0.0%', isPositive: true };
    const trend = ((current - previous) / previous) * 100;
    return {
      value: `${trend >= 0 ? '+' : ''}${trend.toFixed(1)}%`,
      isPositive: trend >= 0
    };
  };

  return [
    {
      title: 'Total Work Hours',
      value: typeof props.stats.total_hours === 'float'
        ? parseInt(props.stats.total_hours).toFixed(1)
        : props.stats.total_hours,
      icon: ClockIcon,
      trend: { value: '—', isPositive: true }
    },
    {
      title: 'Avg. Daily Hours',
      value: typeof parseInt(props.stats.average_hours_per_day as string) === 'number'
        ? parseFloat(props.stats.average_hours_per_day as string).toFixed(1)
        : props.stats.average_hours_per_day,
      icon: BarChart3Icon,
      trend: { value: '—', isPositive: true }
    },
    {
      title: 'Productivity Score',
      value: `${props.stats.productivity_trends[props.stats.productivity_trends.length - 1]?.score || 0}%`,
      icon: TrendingUpIcon,
      trend: { value: '—', isPositive: true }
    },
    {
      title: 'Focus Time',
      value: `${props.stats.focus_time_analytics.summary.average_focus_hours}hrs`,
      icon: BrainIcon,
      trend: { value: '—', isPositive: true }
    }
  ]
})

// Table columns with Vue syntax
const columns = [
  {
    key: 'work_title',
    label: 'Work Title'
  },
  {
    key: 'user.department.name', // Updated to access nested department name
    label: 'Department',
    format: (value: string) => `${value}` || '—'
  },
  {
    key: 'hours_worked', // Updated to match the actual data field
    label: 'Total Hours',
    format: (value: any) => `${parseFloat(value as string)?.toFixed(1)} hrs` || '0.0'
  },
  {
    key: 'work_date',
    label: 'Work Date',
    format: (value: string) => format(value, 'dd MMM yyyy')
  },
  {
    key: 'status',
    label: 'Status',
    format: (value: string) => ({
      template: 'Badge',
      data: {
        variant: getStatusVariant(value),
        value: value
      }
    })
  }
]

// Actions remain the same...
const viewReport = (report: Report) => {
  router.visit(route('reports.show', report.id))
}

const downloadReport = (report: Report) => {
  window.open(route('reports.download', report.id), '_blank')
}

const applyFilters = () => {
  router.get(route('reports.index'), {
    start_date: simpleFormat(dateRange.value.start, 'yyyy-MM-dd') || '',
    end_date: simpleFormat(dateRange.value.end, 'yyyy-MM-dd') || '',
    department: selectedDepartment.value
  }, {
    replace: true,
    preserveState: true,
    preserveScroll: true,
    onFinish: () => {
      showFilters.value = false
    }
  });
};

// Update the resetFilters function
const resetFilters = () => {
  dateRange.value = {
    start: null,
    end: null
  };
  selectedDepartment.value = '';
  router.get(route('reports.index'));
  showFilters.value = false;
};
const getStatusVariant = (status: string) => {
  return {
    completed: 'success',
    pending: 'warning',
    failed: 'destructive'
  }[status] || 'default'
}
</script>

<template>
  <AppLayout>
    <div class="container mx-auto p-6 my-12 space-y-8">
      <!-- Header with filters -->
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold">Reports Dashboard</h1>
        <div class="flex items-center space-x-4">
          <ExportReports :filters="filters" />

          <Dialog v-model:open="showFilters">
            <DialogTrigger asChild>
              <Button variant="outline">
                <FilterIcon class="h-4 w-4 mr-2" />
                Filters
              </Button>
            </DialogTrigger>
            <DialogContent :class="isMobile ? 'max-w-lg' : 'max-w-xl'">
              <DialogHeader>
                <DialogTitle>Filter Reports</DialogTitle>
                <DialogDescription>
                  Filter out reports according to date, departments, etc
                </DialogDescription>
              </DialogHeader>
              <div class="space-y-4 py-4">
                <div class="space-y-2">
                  <label class="text-sm font-medium">Date Range</label>
                  <RangeCalendar
                    v-model="dateRange"
                    class="rounded-md border"
                    :numberOfMonths="isMobile ? 1 : 2"
                    :defaultValue="{
                      start: props.filters.start_date ? new Date(props.filters.start_date) : undefined,
                      end: props.filters.end_date ? new Date(props.filters.end_date) : undefined
                    }"
                  />
                </div>
                <div class="space-y-2">
                  <label class="text-sm font-medium">Department</label>
                  <Select v-model="selectedDepartment">
                    <SelectTrigger>
                      <SelectValue placeholder="Select department" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem
                        v-for="dept in props.stats.departments"
                        :key="dept.name"
                        :value="dept.name">
                        {{ dept.name }} ({{ dept.count }})
                      </SelectItem>
                    </SelectContent>
                  </Select>
                </div>
                <div class="flex justify-end space-x-2">
                  <Button variant="outline" @click="resetFilters">Reset</Button>
                  <Button @click="applyFilters">Apply Filters</Button>
                </div>
              </div>
            </DialogContent>
          </Dialog>
        </div>
      </div>

      <!-- Stats Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <Card v-for="stat in statsCards" :key="stat.title">
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">{{ stat.title }}</CardTitle>
            <component :is="stat.icon" class="h-4 w-4 text-muted-foreground" />
          </CardHeader>

          <CardContent>
            <div class="text-2xl font-bold">{{ stat.value }}</div>
            <p class="text-xs text-muted-foreground">
               <span
                 v-if="stat.trend.value !== '—'"
                 :class="stat.trend.isPositive ? 'text-green-500' : 'text-red-500'">
                {{ stat.trend.value }}
              </span>

              <span v-else class="text-muted-foreground">
                No trend data available
              </span>
            </p>
          </CardContent>
        </Card>
      </div>

      <!-- Productivity Chart -->
      <Card class="overflow-hidden">
        <CardHeader>
          <CardTitle>Productivity Trends</CardTitle>
        </CardHeader>

        <ReportProductivityChart
          :data="props.stats.productivity_trends"
          :isDark="isDark"
        />
      </Card>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <BurnoutRiskCard :risks="props.stats.burnout_risks" />
        <FocusAnalyticsCard :analytics="props.stats.focus_time_analytics" />
      </div>

      <!-- Reports Table -->
      <Card>
        <CardHeader>
          <CardTitle>Reports</CardTitle>
        </CardHeader>
        <CardContent>
          <ReportTable
            :columns="columns"
            :data="props.reports">
            <template #actions="{ row }">
              <DropdownMenu>
                <DropdownMenuTrigger asChild>
                  <Button variant="ghost" size="sm">
                    <EllipsisIcon class="h-4 w-4" />
                  </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent>
                  <DropdownMenuItem @click="viewReport(row)">
                    View Report
                  </DropdownMenuItem>
                  <DropdownMenuItem @click="downloadReport(row)">
                    Download Report
                  </DropdownMenuItem>
                  <DropdownMenuSeparator />
                  <DropdownMenuItem>
                    View Insights
                  </DropdownMenuItem>
                </DropdownMenuContent>
              </DropdownMenu>
            </template>
          </ReportTable>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>


