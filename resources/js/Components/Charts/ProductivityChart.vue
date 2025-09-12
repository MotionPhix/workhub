<template>
  <div>
    <apexchart
      width="100%"
      type="line"
      :options="chartOptions"
      :series="series"
    />
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
  data: {
    type: Array,
    default: () => []
  }
})

const series = computed(() => [{
  name: 'Productivity Hours',
  data: props.data.map(item => item.total_hours)
}])

const chartOptions = computed(() => ({
  chart: {
    type: 'line',
    height: 350,
    toolbar: { show: false }
  },
  theme: {
    mode: document.documentElement.classList.contains('dark') ? 'dark' : 'light'
  },
  xaxis: {
    categories: props.data.map(item => item.date),
    type: 'datetime'
  },
  title: {
    text: 'Daily Productivity',
    align: 'left'
  },
  stroke: {
    curve: 'smooth',
    width: 2
  },
  tooltip: {
    x: {
      format: 'dd/MM/yy'
    }
  }
}))
</script>


