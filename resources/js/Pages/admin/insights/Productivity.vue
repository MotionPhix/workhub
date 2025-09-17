<script setup>
import { computed } from 'vue'
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card'
import ProductivityInsights from '@/components/Insights/ProductivityInsights.vue'
import ProductivityChart from '@/components/Charts/ProductivityChart.vue'

const props = defineProps({
  insights: { type: Object, required: true }
})

// Derived metrics (example - guard against undefined)
const averageDaily = computed(() => props.insights?.average_daily_hours ?? 0)
</script>

<template>
  <Card>
    <CardHeader>
      <CardTitle>System Productivity (Last 14 Days)</CardTitle>
    </CardHeader>
    <CardContent>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="md:col-span-2">
          <ProductivityChart :data="props.insights?.daily_hours ?? []" />
        </div>
        <div>
          <ProductivityInsights :insights="props.insights" />
        </div>
      </div>
      <div class="mt-4 text-sm text-muted-foreground">
        Average Daily Hours: <span class="font-medium">{{ averageDaily }}</span>
      </div>
    </CardContent>
  </Card>
</template>
