<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { ModalLink, visitModal } from '@inertiaui/modal-vue'
import { toast } from 'vue-sonner'
import { ref, computed } from 'vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card'
import Table from '@/components/ui/table/Table.vue'
import TableHeader from '@/components/ui/table/TableHeader.vue'
import TableBody from '@/components/ui/table/TableBody.vue'
import TableRow from '@/components/ui/table/TableRow.vue'
import TableHead from '@/components/ui/table/TableHead.vue'
import TableCell from '@/components/ui/table/TableCell.vue'
import TableCaption from '@/components/ui/table/TableCaption.vue'

const props = defineProps({
  projects: { type: Object, required: true },
  filters: { type: Object, required: true },
  departments: { type: Array, default: () => [] }
})

const search = ref(props.filters.search || '')
const hasResults = computed(() => props.projects.data && props.projects.data.length > 0)

function apply() {
  router.get(route('admin.projects.index'), { search: search.value }, { preserveState: true, replace: true })
}

function archive(project: any) {
  if (!confirm(`Archive project: ${project.name}?`)) { return }
  router.patch(route('admin.projects.archive', { project: project.uuid }), {}, {
    preserveScroll: true,
    onSuccess: () => toast.success('Project archived'),
    onError: () => toast.error('Failed to archive project'),
  })
}
</script>

<template>
  <Head title="Admin Projects" />
  <AdminLayout>
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-semibold tracking-tight">Projects</h1>
      <div class="flex gap-2">
        <Link :href="route('admin.projects.create')">
          <Button size="sm" variant="secondary">Full Page</Button>
        </Link>
        <ModalLink :href="route('admin.projects.create')">
          <Button size="sm">Quick Create</Button>
        </ModalLink>
      </div>
    </div>

    <Card class="mb-6">
      <CardHeader class="pb-2">
        <CardTitle class="text-base">Filters</CardTitle>
      </CardHeader>
      <CardContent class="flex flex-wrap items-end gap-3">
        <div class="space-y-1">
          <label class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Search</label>
          <Input v-model="search" @keyup.enter="apply" placeholder="Project name..." class="w-64" />
        </div>
        <div class="flex gap-2">
          <Button size="sm" variant="secondary" @click="apply">Apply</Button>
        </div>
      </CardContent>
    </Card>

    <Card>
      <CardHeader class="pb-0">
        <CardTitle class="text-base">Project List</CardTitle>
      </CardHeader>
      <CardContent class="pt-4">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Name</TableHead>
              <TableHead>Status</TableHead>
              <TableHead>Priority</TableHead>
              <TableHead>Completion</TableHead>
              <TableHead>Manager</TableHead>
              <TableHead>Due</TableHead>
              <TableHead class="text-right">Actions</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody v-if="hasResults">
            <TableRow v-for="p in projects.data" :key="p.uuid">
              <TableCell class="max-w-[260px]">
                <div class="flex flex-col gap-0.5">
                  <Link :href="route('admin.projects.show', { project: p.uuid })" class="font-medium hover:underline text-primary">{{ p.name }}</Link>
                  <span class="text-xs text-muted-foreground truncate">{{ p.description }}</span>
                </div>
              </TableCell>
              <TableCell>{{ p.status }}</TableCell>
              <TableCell>{{ p.priority }}</TableCell>
              <TableCell>{{ p.completion_percentage }}%</TableCell>
              <TableCell>{{ p.manager?.name || '—' }}</TableCell>
              <TableCell>{{ p.due_date }}</TableCell>
              <TableCell class="text-right">
                <div class="flex gap-2 justify-end">
                  <Link :href="route('admin.projects.edit', { project: p.uuid })">
                    <Button size="xs" variant="outline">Edit</Button>
                  </Link>
                  <Button size="xs" variant="destructive" @click="() => archive(p)">Archive</Button>
                </div>
              </TableCell>
            </TableRow>
          </TableBody>
          <TableBody v-else>
            <TableRow>
              <TableCell colspan="7" class="text-center text-sm text-muted-foreground py-8">No projects found</TableCell>
            </TableRow>
          </TableBody>
          <TableCaption v-if="projects.meta">Showing {{ projects.meta.from || 0 }} - {{ projects.meta.to || 0 }} of {{ projects.meta.total || 0 }}</TableCaption>
        </Table>
      </CardContent>
    </Card>
  </AdminLayout>
</template>
