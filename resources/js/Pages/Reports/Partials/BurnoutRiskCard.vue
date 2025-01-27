<script setup lang="ts">
import { Card, CardHeader, CardTitle, CardContent } from "@/Components/ui/card"
import { AlertCircleIcon } from 'lucide-vue-next'
import { Badge } from "@/Components/ui/badge"

interface BurnoutRisk {
  user_id: number
  name: string
  risk_level: string
  risk_score: number
  factors: {
    average_daily_hours: number
    work_pattern_variance: number
    consecutive_long_days: number
    weekend_work_frequency: number
  }
  recommendations: Array<{
    type: string
    message: string
  }>
}

defineProps<{
  risks: {
    team_risks: BurnoutRisk[]
    summary: {
      high_risk_count: number
      moderate_risk_count: number
      low_risk_count: number
      average_risk_score: number
    }
  }
}>()
</script>

<template>
  <Card>
    <CardHeader>
      <CardTitle class="flex items-center gap-2">
        <AlertCircleIcon class="h-5 w-5" />
        Team Burnout Risk Analysis
      </CardTitle>
    </CardHeader>
    <CardContent>
      <div class="space-y-6">
        <!-- Risk Summary -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
          <div v-for="(count, type) in risks.summary" :key="type" class="space-y-1">
            <p class="text-sm text-muted-foreground">{{ type.replace(/_/g, ' ').toUpperCase() }}</p>
            <p class="text-2xl font-bold">{{ count }}</p>
          </div>
        </div>

        <!-- Individual Risk Cards -->
        <div class="space-y-4">
          <div v-for="risk in risks.team_risks" :key="risk.user_id"
               class="rounded-lg border p-4 space-y-3">
            <div class="flex items-center justify-between">
              <h3 class="font-medium">{{ risk.name }}</h3>
              <Badge :variant="risk.risk_level.toLowerCase()">
                {{ risk.risk_level }} Risk
              </Badge>
            </div>

            <!-- Risk Factors -->
            <div class="grid grid-cols-2 gap-2 text-sm">
              <div v-for="(value, factor) in risk.factors" :key="factor">
                <span class="text-muted-foreground">
                  {{ factor.replace(/_/g, ' ') }}:
                </span>
                <span class="font-medium ml-1">
                  {{ typeof value === 'number' ? value.toFixed(1) : value }}
                </span>
              </div>
            </div>


            <Divider />

            <!-- Recommendations -->
            <div class="space-y-2">
              <p class="text-sm font-medium">Recommendations:</p>
              <ul class="text-sm space-y-1">
                <li v-for="(rec, index) in risk.recommendations" :key="index"
                    class="text-muted-foreground">
                  • {{ rec.message }}
                </li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </CardContent>
  </Card>
</template>
