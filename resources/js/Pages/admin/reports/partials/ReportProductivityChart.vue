<script setup lang="ts">
import { computed } from 'vue'
import { format, parseISO } from 'date-fns'
import { TrendingDownIcon, TrendingUpIcon } from "lucide-vue-next"

interface ProductivityData {
  date: string
  score: number
}

interface Props {
  data: ProductivityData[]
  isDark?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  isDark: false
})

const chartData = computed(() => {
  return props.data
    .sort((a, b) => parseISO(a.date).getTime() - parseISO(b.date).getTime())
    .map(item => ({
      x: parseISO(item.date).getTime(),
      y: item.score
    }))
})

// Calculate trend for tooltip with null checks
const calculateTrend = (currentValue: number | null, index: number, data: any[]) => {
  if (currentValue === null || index === 0 || !data[index - 1]) {
    return { trend: 0, color: 'text-gray-500', icon: null }
  }

  const previousValue = data[index - 1].y
  if (!previousValue) {
    return { trend: 0, color: 'text-gray-500', icon: null }
  }

  const trend = ((currentValue - previousValue) / previousValue) * 100
  return {
    trend: Math.abs(trend).toFixed(1),
    color: trend >= 0 ? 'text-emerald-500' : 'text-red-500',
    icon: trend >= 0 ? TrendingUpIcon : TrendingDownIcon
  }
}

const chartOptions = computed(() => ({
  chart: {
    type: 'line',
    height: 350,
    animations: { enabled: true },
    toolbar: { show: false },
    zoom: { enabled: false }
  },
  stroke: {
    curve: 'smooth',
    width: 2
  },
  xaxis: {
    type: 'datetime',
    labels: {
      datetimeUTC: false,
      format: 'dd MMM',
      style: {
        colors: props.isDark ? '#cbd5e1' : '#64748b'
      }
    },
    axisBorder: { show: true },
    axisTicks: { show: true }
  },
  yaxis: {
    min: 0,
    max: 100,
    tickAmount: 5,
    labels: {
      formatter: (value: number) => `${value}%`,
      style: {
        colors: props.isDark ? '#cbd5e1' : '#64748b'
      }
    }
  },
  grid: {
    borderColor: props.isDark ? '#334155' : '#e2e8f0',
    strokeDashArray: 4,
    xaxis: { lines: { show: true } },
    yaxis: { lines: { show: true } }
  },
  tooltip: {
    custom: function({ series, seriesIndex, dataPointIndex, w }) {
      try {
        // Check if data exists
        if (!w?.globals?.initialSeries?.[seriesIndex]?.data?.[dataPointIndex]) {
          return ''
        }

        const data = w.globals.initialSeries[seriesIndex].data[dataPointIndex]
        if (!data?.x || !data?.y) {
          return ''
        }

        const date = new Date(data.x)
        const value = data.y
        const { trend, color, icon: TrendIcon } = calculateTrend(
          value,
          dataPointIndex,
          w.globals.initialSeries[seriesIndex].data
        )

        // Only show trend if we have a valid TrendIcon
        const trendHtml = trend !== '0' && TrendIcon ? `
          <div class="flex items-center gap-1 text-sm ${color}">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              ${TrendIcon.name === 'TrendingUpIcon'
          ? '<path d="M23 6L13.5 15.5L8.5 10.5L1 18M23 6H17M23 6V12" stroke-linecap="round" stroke-linejoin="round"/>'
          : '<path d="M23 18L13.5 8.5L8.5 13.5L1 6M23 18H17M23 18V12" stroke-linecap="round" stroke-linejoin="round"/>'}
            </svg>
            ${trend}% from previous
          </div>
        ` : ''

        return `
          <div class="${props.isDark ? 'dark' : ''}">
            <div class="rounded-lg border bg-background p-2 shadow-md min-w-[200px]
                        ${props.isDark ? 'border-gray-800 bg-gray-900' : 'border-gray-200 bg-white'}">
              <div class="flex flex-col gap-1">
                <p class="text-sm font-medium ${props.isDark ? 'text-gray-200' : 'text-gray-900'}">
                  ${format(date, 'MMMM d, yyyy')}
                </p>
                <div class="flex items-center justify-between">
                  <span class="text-sm ${props.isDark ? 'text-gray-400' : 'text-gray-500'}">
                    Productivity Score
                  </span>
                  <span class="text-base font-semibold ${props.isDark ? 'text-gray-200' : 'text-gray-900'}">
                    ${value}%
                  </span>
                </div>
                ${trendHtml}
              </div>
            </div>
          </div>
        `
      } catch (error) {
        console.error('Tooltip error:', error)
        return ''
      }
    }
  },
  markers: {
    size: 4,
    strokeWidth: 0,
    hover: { size: 6 }
  },
  theme: {
    mode: props.isDark ? 'dark' : 'light'
  }
}))

const series = computed(() => [{
  name: 'Productivity Score',
  data: chartData.value
}])
</script>

<template>
  <Card>
    <CardContent>
      <div class="h-[350px] w-full">
        <apexchart
          width="100%"
          height="350"
          type="line"
          :options="chartOptions"
          :series="series"
        />
      </div>
    </CardContent>
  </Card>
</template>


