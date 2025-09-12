<script setup lang="ts">
import { router } from '@inertiajs/vue3'
import { format } from 'date-fns'
import { EllipsisIcon, PlusCircleIcon } from 'lucide-vue-next'
import AppLayout from "@/layouts/AppLayout.vue";
import {visitModal} from '@inertiaui/modal-vue'
import {
  DropdownMenu,
  DropdownMenuItem,
  DropdownMenuContent,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";

const props = defineProps<{
  workEntries: {
    current_page: number
    data: Array<{
      id: number
      uuid: string
      work_date: Date | string
      work_title: string
      description: string
      hours_worked: number
      tags?: Array<{}>,
      status?: string
    }>
    first_page_url: string
    from?: number
    last_page: number
    last_page_url: string
    links: Array<{
      url?: string
      label: string
      active: boolean
    }>
    next_page_url?: string
    path: string
    per_page: number
    prev_page_url?: string
    to?: number
    total: number
  }
}>()

const columns = [
  {
    accessorKey: 'work_date',
    header: 'Date',
    cell: ({ row }) => format(row.work_date, 'PP')
  },
  {
    accessorKey: 'work_title',
    header: 'Worked on'
  },
  {
    accessorKey: 'hours_worked',
    header: 'Hours'
  },
  {
    accessorKey: 'status',
    header: 'Status'
  },
  {
    id: 'actions',
    header: ''
  }
]

const onAddNewWorkLog = () => {
  visitModal(route('work-entries.create'), {
    navigate: true
  })
}

const onViewWorkLog = (entry) => {
  router.visit(route('work-entries.show', entry.uuid), { replace: true })
}

const onEditWorkLog = (entry) => {
  visitModal(route('work-entries.edit', entry.uuid), {
    navigate: true
  })
}

const onDeleteWorkLog = (entry) => {
  router.delete(route('work-entries.destroy', entry.uuid), {
    onBefore: () => confirm('Are you sure you want to delete this entry?')
  })
}
</script>

<template>
  <AppLayout>
    <div class="my-12">
      <!-- Header Section -->
      <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold">Work Logs</h1>
        <Button @click="onAddNewWorkLog">
          <PlusCircleIcon />
          New Work Log
        </Button>
      </div>

      <Card>
        <CardContent>
          <DataTable
            :columns="columns"
            :data="workEntries">
            <template #actions="{ row }">
              <DropdownMenu>
                <DropdownMenuTrigger as-child>
                  <Button variant="ghost" size="sm">
                    <EllipsisIcon class="w-4 h-4" />
                  </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent>
                  <DropdownMenuItem @click="onViewWorkLog(row)">
                    View Details
                  </DropdownMenuItem>
                  <DropdownMenuItem @click="onEditWorkLog(row)">
                    Edit Entry
                  </DropdownMenuItem>
                  <DropdownMenuItem @click="onDeleteWorkLog(row)">
                    Delete Entry
                  </DropdownMenuItem>
                </DropdownMenuContent>
              </DropdownMenu>
            </template>
          </DataTable>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>


