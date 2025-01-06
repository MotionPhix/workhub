<script setup>
import { computed } from 'vue'

const props = defineProps({
  insights: {
    type: Object,
    default: () => ({})
  }
})

const series = computed(() => [{
  name: 'Productivity Metrics',
  data: [
    props.insights.productivity_score || 0,
    props.insights.energy_patterns?.most_productive_hours?.length || 0,
    props.insights.focus_time_analysis?.total_focus_hours || 0,
    props.insights.burnout_risk?.score || 0,
    props.insights.task_complexity_correlation?.length || 0
  ]
}])

const chartOptions = computed(() => ({
  chart: { type: 'radar' },
  labels: [
    'Productivity Score',
    'Peak Energy Hours',
    'Focus Time',
    'Burnout Risk',
    'Task Complexity'
  ],
  title: { text: 'Comprehensive Productivity Insights' },
  theme: {
    mode: document.documentElement.classList.contains('dark') ? 'dark' : 'light'
  }
}))
</script>

<template>
  <div>
    <apexchart
      type="radar"
      height="350"
      :options="chartOptions"
      :series="series"
    />
  </div>
</template>
