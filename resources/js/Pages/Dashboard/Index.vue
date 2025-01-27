<script setup lang="ts">
import { computed, ref, onMounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import StatCard from '@/Pages/Dashboard/Partials/StatCard.vue';
import AppLayout from "@/Layouts/AppLayout.vue";
import WorkLogsTable from "@/Pages/Dashboard/Partials/WorkLogsTable.vue";
import TeamOverviewCard from "@/Pages/Dashboard/Partials/TeamOverviewCard.vue";
import { formatInTimeZone } from 'date-fns-tz';
import {ActivityIcon, ClipboardListIcon, ClockIcon, TrendingDownIcon, TrendingUpIcon} from "lucide-vue-next";

interface Props {
  total_work_entries: number;
  total_hours_worked: number;
  recent_entries: Array<{
    uuid: string;
    work_title: string;
    hours_worked: number;
    status: string;
  }>;
  team_size?: number;
  team_hours_worked?: number;
  team_work_entries?: number;
  top_performers?: Array<{
    user: {
      id: number;
      name: string;
      email: string;
    };
    total_hours: number;
  }>;
  recent_team_entries?: Array<any>;
  productivity_insights?: {
    average_daily_hours: number;
    most_productive_days: Record<string, number>;
    productivity_trend: string;
    recommended_work_hours: number;
  };
}

const props = defineProps<Props>();

// Inertia page props
const page = usePage();
const user = computed(() => page.props.auth.user);
const isManager = computed(() => user.value?.roles?.[0]?.name === 'manager');

// Date and time handling
const currentDate = ref(new Date());

// Productivity insights computed properties
const productivityInsights = computed(() => {
  if (!props.productivity_insights) return null;

  return {
    averageDailyHours: props.productivity_insights.average_daily_hours,
    productivityTrend: props.productivity_insights.productivity_trend,
    recommendedWorkHours: props.productivity_insights.recommended_work_hours,
  };
});

// Chart options for "Most Productive Days"
const productivityChartOptions = computed(() => {
  const productiveDays = props.productivity_insights?.most_productive_days || {};
  const dates = Object.keys(productiveDays);
  const hours = Object.values(productiveDays);

  return {
    chart: {
      type: 'bar',
      height: 350,
      toolbar: {
        show: true,
      },
      zoom: {
        enabled: false,
      },
    },
    plotOptions: {
      bar: {
        horizontal: false,
        columnWidth: '55%',
        endingShape: 'rounded',
        borderRadius: 4,
      },
    },
    dataLabels: {
      enabled: false,
    },
    stroke: {
      show: true,
      width: 2,
      colors: ['transparent'],
    },
    xaxis: {
      categories: dates,
      labels: {
        rotate: -45,
        trim: true,
      },
    },
    yaxis: {
      title: {
        text: 'Hours Worked',
      },
    },
    fill: {
      opacity: 1,
    },
    tooltip: {
      y: {
        formatter: (val: number) => `${val} hours`,
      },
    },
    theme: {
      mode: 'light',
    },
  };
});

// Series for "Most Productive Days" chart
const mostProductiveDaysSeries = computed(() => {
  const productiveDays = props.productivity_insights?.most_productive_days || {};
  return [{
    name: 'Hours Worked',
    data: Object.values(productiveDays),
  }];
});

// Team performance metrics for managers
const teamMetrics = computed(() => ({
  totalHoursWorked: props.team_hours_worked || 0,
  teamSize: props.team_size || 0,
  workEntries: props.team_work_entries || 0,
}));

onMounted(() => {
  // Update current time every second
  setInterval(() => {
    currentDate.value = new Date();
  }, 1000);
});
</script>

<template>
  <AppLayout>
    <div class="my-12">
      <!-- Header -->
      <header class="py-6 px-8 bg-secondary text-secondary-foreground shadow-sm rounded-lg">
        <h1 class="text-3xl font-medium">
          Welcome back, {{ user.name }}!
        </h1>
        <p class="text-sm text-muted-foreground">
          Today is {{ formatInTimeZone(currentDate, page.props.timezone as string, "do MMM, y | 'The time is ' HH:mm:ss") }}
        </p>
      </header>

      <!-- Main Dashboard -->
      <main class="p-6">
        <!-- Common Stats Section -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
          <StatCard
            title="Total Work Logs"
            :value="props.total_work_entries"
            :icon="ClipboardListIcon"
          />

          <StatCard
            title="Total Hours Worked"
            :value="props.total_hours_worked"
            :icon="ClockIcon"
          />

          <StatCard
            title="Productivity Trend"
            trend-value="Declining"
            :value="productivityInsights?.productivityTrend"
            :trend="productivityInsights?.productivityTrend === 'Declining' ? 'down' : 'up'"
            :icon="productivityInsights?.productivityTrend === 'Declining' ? TrendingDownIcon : TrendingUpIcon"
          />

          <StatCard
            title="Avg. Daily Hours"
            :value="productivityInsights?.averageDailyHours ?? 0"
            :icon="ActivityIcon"
          />
        </div>

        <!-- Manager Dashboard -->
        <div v-if="isManager" class="space-y-6">
          <!-- Team Overview -->
          <TeamOverviewCard
            :team-size="teamMetrics.teamSize"
            :team-hours-worked="teamMetrics.totalHoursWorked"
            :top-performers="props.top_performers"
          />

          <!-- Most Productive Days Chart -->
          <div class="p-6 bg-card text-card-foreground rounded-lg shadow">
            <h3 class="text-xl font-semibold mb-4">Team Productivity Overview</h3>
            <apexchart
              type="bar"
              :options="productivityChartOptions"
              :series="mostProductiveDaysSeries"
              height="350"
            />
          </div>

          <!-- Team Work Entries -->
          <div class="bg-card text-card-foreground rounded-lg shadow">
            <h3 class="p-6 text-xl font-semibold border-b">Recent Team Entries</h3>
            <WorkLogsTable :work-logs="props.recent_team_entries" />
          </div>
        </div>

        <!-- Employee Dashboard -->
        <div v-else class="space-y-6">
          <!-- Productivity Insights -->
          <div class="p-6 bg-card text-card-foreground rounded-lg shadow">
            <h3 class="text-xl font-semibold mb-4">My Productivity Insights</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="space-y-2">
                <p class="text-sm text-muted-foreground">Average Daily Hours</p>
                <p class="text-2xl font-semibold">
                  {{ productivityInsights?.averageDailyHours?.toFixed(1) }} hours
                </p>
              </div>

              <div class="space-y-2">
                <p class="text-sm text-muted-foreground">Recommended Work Hours</p>
                <p class="text-2xl font-semibold">
                  {{ productivityInsights?.recommendedWorkHours.toFixed(1) }} hours
                </p>
              </div>
            </div>
          </div>

          <!-- Personal Work Entries -->
          <div class="bg-card text-card-foreground rounded-lg shadow">
            <h3 class="p-6 text-xl font-semibold border-b">My Recent Work Logs</h3>
            <WorkLogsTable :work-logs="props.recent_entries" />
          </div>
        </div>
      </main>
    </div>
  </AppLayout>
</template>
