<script setup>
import { ref, onMounted } from 'vue'
import ProductivityChart from '@/Components/Charts/ProductivityChart.vue'
import WorkHeatmap from '@/Components/Heatmaps/WorkHeatmap.vue'
import ProductivityInsights from '@/Components/Insights/ProductivityInsights.vue'
import {Card, CardContent, CardHeader, CardTitle} from "@/Components/ui/card";

const chartData = ref([])
const workEntries = ref([])
const insights = ref({})

onMounted(async () => {
  const response = await fetch('/api/reports/dashboard')
  const data = await response.json()

  chartData.value = data.chartData
  workEntries.value = data.workEntries
  insights.value = data.insights
})
</script>

<template>
  <Card>
    <CardHeader>
      <CardTitle>Productivity Dashboard</CardTitle>
    </CardHeader>

    <CardContent>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <ProductivityChart :data="chartData" />
        <WorkHeatmap :entries="workEntries" />
        <ProductivityInsights :insights="insights" />
      </div>
    </CardContent>
  </Card>
</template>
