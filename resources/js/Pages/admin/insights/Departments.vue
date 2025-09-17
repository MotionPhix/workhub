<script setup>
import { computed } from 'vue'
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card'

const props = defineProps({
  insights: { type: Array, required: true }
})

const sorted = computed(() => {
  return [...props.insights].sort((a, b) => b.total_hours - a.total_hours)
})
</script>

<template>
  <Card>
    <CardHeader>
      <CardTitle>Department Productivity (Last 30 Days)</CardTitle>
    </CardHeader>
    <CardContent>
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="text-left border-b">
              <th class="py-2 pr-4">Department</th>
              <th class="py-2 pr-4 text-right">Total Hours</th>
              <th class="py-2 pr-4 text-right">Avg Daily</th>
              <th class="py-2 pr-4 text-right">Entries</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="dept in sorted" :key="dept.department_id" class="border-b last:border-0">
              <td class="py-2 pr-4 font-medium">
                {{ dept.department_id === 'unassigned' ? 'Unassigned' : dept.department_id }}
              </td>
              <td class="py-2 pr-4 text-right">{{ dept.total_hours }}</td>
              <td class="py-2 pr-4 text-right">{{ dept.average_daily_hours }}</td>
              <td class="py-2 pr-4 text-right">{{ dept.entries }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </CardContent>
  </Card>
</template>
