<script setup lang="ts">
import {computed} from 'vue';
import {useDark} from "@vueuse/core";
import {useDeviceDetection} from "@/composables/useDeviceDetection";

const props = defineProps<{
  insights: {
    most_productive_days: number
    average_daily_hours: number
    recommended_work_hours: number
  }
}>()

const isDarkMode = useDark();
const { isMobile } = useDeviceDetection()

// Prepare series data for the most productive days (bar chart)
const mostProductiveDays = computed(() => {
  return Object.entries(props.insights.most_productive_days || {}).map(([date, hours]) => ({
    x: date,
    y: hours
  }));
});

// Prepare series data for the productivity trend (if available)
const productivityTrend = computed(() => ({
  name: 'Productivity Trend',
  data: [
    props.insights.average_daily_hours || 0,
    props.insights.recommended_work_hours || 0
  ]
}));

const chartOptions = computed(() => ({
  chart: {type: 'bar'},
  title: {text: 'Most Productive Days'},
  theme: {
    mode: isDarkMode.value ? 'dark' : 'light'
  },
  xaxis: {
    type: 'category'
  },
  yaxis: {
    title: {
      text: 'Hours Worked'
    }
  }
}));
</script>

<template>
  <!-- Most Productive Days Bar Chart -->
  <div>
    <apexchart
      type="bar"
      height="350"
      :options="chartOptions"
      :series="[{ name: 'Productivity Hours', data: mostProductiveDays }]"/>
  </div>

  <!-- Display Average Daily Hours vs Recommended Work Hours -->
  <div class="mt-4 py-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
    <section class="flex gap-4">

      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" class="stroke-current" fill="none">
        <path d="M11.0065 21H9.60546C6.02021 21 4.22759 21 3.11379 19.865C2 18.7301 2 16.9034 2 13.25C2 9.59661 2 7.76992 3.11379 6.63496C4.22759 5.5 6.02021 5.5 9.60546 5.5H13.4082C16.9934 5.5 18.7861 5.5 19.8999 6.63496C20.7568 7.50819 20.9544 8.7909 21 11" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
        <path d="M18.85 18.85L17.5 17.95V15.7M13 17.5C13 19.9853 15.0147 22 17.5 22C19.9853 22 22 19.9853 22 17.5C22 15.0147 19.9853 13 17.5 13C15.0147 13 13 15.0147 13 17.5Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
        <path d="M16 5.5L15.9007 5.19094C15.4056 3.65089 15.1581 2.88087 14.5689 2.44043C13.9796 2 13.197 2 11.6316 2H11.3684C9.80304 2 9.02036 2 8.43111 2.44043C7.84186 2.88087 7.59436 3.65089 7.09934 5.19094L7 5.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
      </svg>

      <div>
        <p class="font-medium text-gray-700 dark:text-gray-400">
          Avg. Daily Hours
        </p>

        <p>
          {{ props.insights.average_daily_hours || 0 }} hours
        </p>
      </div>
    </section>

    <Divider spacing="my-2" v-if="isMobile" />

    <section class="flex gap-4">

      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" class="stroke-accent" fill="none">
        <path d="M14.9805 7.01556C14.9805 7.01556 15.4805 7.51556 15.9805 8.51556C15.9805 8.51556 17.5687 6.01556 18.9805 5.51556" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
        <path d="M9.99491 2.02134C7.49644 1.91556 5.56618 2.20338 5.56618 2.20338C4.34733 2.29053 2.01152 2.97385 2.01154 6.96454C2.01156 10.9213 1.9857 15.7993 2.01154 17.7439C2.01154 18.932 2.74716 21.7033 5.29332 21.8518C8.38816 22.0324 13.9628 22.0708 16.5205 21.8518C17.2052 21.8132 19.4847 21.2757 19.7732 18.7956C20.0721 16.2263 20.0126 14.4407 20.0126 14.0157" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
        <path d="M21.9999 7.01556C21.9999 9.77698 19.7592 12.0156 16.9951 12.0156C14.231 12.0156 11.9903 9.77698 11.9903 7.01556C11.9903 4.25414 14.231 2.01556 16.9951 2.01556C19.7592 2.01556 21.9999 4.25414 21.9999 7.01556Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
        <path d="M6.98053 13.0156H10.9805" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
        <path d="M6.98053 17.0156H14.9805" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
      </svg>

      <div>
        <p class="font-medium text-gray-700 dark:text-gray-400">Rec. Work Hours</p>
        <p>{{ props.insights.recommended_work_hours || 0 }} hours</p>
      </div>
    </section>
  </div>
</template>


