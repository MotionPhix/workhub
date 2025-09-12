<script setup>
import { computed } from 'vue'
import {Card, CardContent, CardHeader, CardTitle} from "@/components/ui/card";
import {Label} from "@/components/ui/label";
import {Badge} from "@/components/ui/badge"

const props = defineProps({
  insights: {
    type: Object,
    default: () => ({})
  }
})

// Additional computed properties for more detailed insights
const insightLevel = computed(() => {
  const avgHours = props.insights.average_daily_hours || 0
  if (avgHours < 4) return 'Low Productivity'
  if (avgHours < 6) return 'Moderate Productivity'
  return 'High Productivity'
})
</script>

<template>
  <Card>
    <CardHeader>
      <CardTitle>Productivity Insights</CardTitle>
    </CardHeader>
    <CardContent>
      <div class="space-y-4">
        <div>
          <Label>Average Daily Hours</Label>
          <p>{{ insights.average_daily_hours?.toFixed(2) || 'N/A' }} hrs</p>
        </div>

        <div>
          <Label>Most Productive Days</Label>
          <ul>
            <li
              v-for="(hours, day) in insights.most_productive_days"
              :key="day">
              {{ day }}: {{ hours.toFixed(2) }} hrs
            </li>
          </ul>
        </div>

        <div>
          <Label>Productivity Trend</Label>
          <Badge
            :variant="insights.productivity_trend === 'Improving' ? 'success' : 'destructive'"
          >
            {{ insights.productivity_trend }}
          </Badge>
        </div>

        <div>
          <Label>Recommended Work Hours</Label>
          <p>{{ insights.recommended_work_hours?.toFixed(1) || 'N/A' }} hrs</p>
        </div>
      </div>
    </CardContent>
  </Card>
</template>


