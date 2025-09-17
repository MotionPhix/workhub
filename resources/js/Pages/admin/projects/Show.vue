<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue'
import { Head, Link } from '@inertiajs/vue3'
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card'
import { Button } from '@/components/ui/button'

const props = defineProps({
  project: { type: Object, required: true },
  stats: { type: Object, required: true }
})
</script>

<template>
  <Head :title="`Project: ${props.project.name}`" />
  <AdminLayout>
    <div class="flex items-center justify-between mb-6">
      <div class="space-y-1">
        <h1 class="text-2xl font-semibold tracking-tight">{{ project.name }}</h1>
        <p class="text-sm text-muted-foreground max-w-2xl">{{ project.description }}</p>
      </div>
      <Link :href="route('admin.projects.edit', project.uuid)">
        <Button size="sm">Edit</Button>
      </Link>
    </div>

    <div class="grid gap-4 md:grid-cols-4 mb-8">
      <Card v-for="stat in [
        { label: 'Status', value: project.status },
        { label: 'Completion', value: project.completion_percentage + '%' },
        { label: 'Total Hours', value: stats.total_hours },
        { label: 'Team Members', value: stats.team_members }
      ]" :key="stat.label">
        <CardHeader class="pb-2">
          <p class="text-xs font-medium text-muted-foreground uppercase tracking-wide">{{ stat.label }}</p>
          <CardTitle class="text-lg font-semibold leading-none">{{ stat.value }}</CardTitle>
        </CardHeader>
      </Card>
    </div>

    <Card>
      <CardHeader class="pb-2">
        <CardTitle class="text-base">Recent Work Entries</CardTitle>
      </CardHeader>
      <CardContent class="p-0">
        <div v-if="project.work_entries?.length" class="divide-y divide-border">
          <div v-for="entry in project.work_entries" :key="entry.id" class="p-4 flex items-center justify-between">
            <div>
              <p class="font-medium text-sm">{{ entry.work_title }}</p>
              <p class="text-xs text-muted-foreground">{{ entry.user?.name }}</p>
            </div>
            <div class="text-xs text-muted-foreground">{{ entry.start_date_time }}</div>
          </div>
        </div>
        <div v-else class="p-6 text-sm text-muted-foreground text-center">No recent entries.</div>
      </CardContent>
    </Card>
  </AdminLayout>
</template>
