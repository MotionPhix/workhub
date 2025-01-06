<script setup>
import { computed } from 'vue'
import {Card, CardContent, CardHeader, CardTitle} from "@/Components/ui/card";

const props = defineProps({
  entries: {
    type: Array,
    default: () => []
  }
})

const heatmapData = computed(() => {
  // Transform work entries into heatmap data
  const intensityMap = props.entries.reduce((acc, entry) => {
    const date = entry.work_date
    acc[date] = (acc[date] || 0) + entry.hours_worked
    return acc
  }, {})

  return Object.entries(intensityMap).map(([date, hours]) => ({
    date,
    hours,
    intensity: calculateIntensity(hours)
  }))
})

const calculateIntensity = (hours) => {
  if (hours <= 2) return 0.2
  if (hours <= 4) return 0.4
  if (hours <= 6) return 0.6
  if (hours <= 8) return 0.8
  return 1
}

const getColor = (intensity) => {
  const isDark = document.documentElement.classList.contains('dark')
  const baseColors = isDark
    ? ['#1f2937', '#10b981', '#059669', '#047857', '#064e3b']
    : ['#e5e7eb', '#10b981', '#059669', '#047857', '#064e3b']

  return baseColors[Math.floor(intensity * (baseColors.length - 1))]
}
</script>

<template>
  <Card>
    <CardHeader>
      <CardTitle>Work Heatmap</CardTitle>
    </CardHeader>
    <CardContent>
      <div class="grid grid-cols-7 gap-1">
        <div
          v-for="(day, index) in heatmapData"
          :key="index"
          class="h-8 w-full"
          :style="{ backgroundColor: getColor(day.intensity) }"
          :title="`${day.date}: ${day.hours} hours`"
        />
      </div>
    </CardContent>
  </Card>
</template>
