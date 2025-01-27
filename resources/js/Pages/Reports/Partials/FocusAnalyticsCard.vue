<script setup lang="ts">
import { Card, CardHeader, CardTitle, CardContent } from "@/Components/ui/card"
import { BrainIcon, ClockIcon } from 'lucide-vue-next'

interface FocusAnalytics {
  team_focus_analytics: Array<{
    user_id: number
    name: string
    metrics: {
      total_focus_hours: number
      average_session_length: number
      focus_sessions_count: number
      focus_time_percentage: number
    }
    optimal_focus_times: Array<{
      hour: string
      average_duration: number
      success_rate: number
    }>
    patterns: {
      most_productive_days: Array<{
        day: string
        average_sessions: number
        total_hours: number
      }>
    }
  }>
  summary: {
    average_focus_hours: number
    average_session_length: number
    team_focus_percentage: number
  }
}

defineProps<{
  analytics: FocusAnalytics
}>()
</script>

<template>
  <Card>
    <CardHeader>
      <CardTitle class="flex items-center gap-2">
        <BrainIcon class="h-5 w-5" />
        Focus Time Analytics
      </CardTitle>
    </CardHeader>

    <CardContent>
      <div class="space-y-6">
        <!-- Team Summary -->
        <div class="grid grid-cols-3 gap-4">
          <div class="space-y-1">
            <p class="text-sm text-muted-foreground">Avg Focus Hours</p>
            <p class="text-2xl font-bold">
              {{ analytics.summary.average_focus_hours }}
            </p>
          </div>
          <div class="space-y-1">
            <p class="text-sm text-muted-foreground">Avg Session Length</p>
            <p class="text-2xl font-bold">
              {{ analytics.summary.average_session_length }}h
            </p>
          </div>
          <div class="space-y-1">
            <p class="text-sm text-muted-foreground">Team Focus %</p>
            <p class="text-2xl font-bold">
              {{ analytics.summary.team_focus_percentage }}%
            </p>
          </div>
        </div>

        <!-- Individual Analytics -->
        <div class="space-y-4">
          <div v-for="member in analytics.team_focus_analytics" :key="member.user_id"
               class="rounded-lg border p-4 space-y-4">
            <h3 class="font-medium">{{ member.name }}</h3>

            <!-- Metrics -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
              <div v-for="(value, metric) in member.metrics" :key="metric">
                <span class="text-muted-foreground">
                  {{ metric.replace(/_/g, ' ') }}:
                </span>
                <span class="font-medium ml-1">
                  {{ typeof value === 'number' ? value.toFixed(1) : value }}
                  {{ metric.includes('percentage') ? '%' : '' }}
                </span>
              </div>
            </div>

            <!-- Optimal Times -->
            <div class="space-y-2">
              <p class="text-sm font-medium">Optimal Focus Times:</p>
              <div class="flex gap-4">
                <div v-for="time in member.optimal_focus_times" :key="time.hour"
                     class="flex items-center gap-2">
                  <ClockIcon class="h-4 w-4 text-muted-foreground" />
                  <span>{{ time.hour }}:00</span>
                  <span class="text-muted-foreground">
                    ({{ (time.success_rate * 100).toFixed(0) }}% success)
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </CardContent>
  </Card>
</template>
