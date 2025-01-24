<script setup lang="ts">
import { router } from '@inertiajs/vue3'
import { format } from 'date-fns'
import { EllipsisIcon, PlusCircleIcon } from 'lucide-vue-next'
import AppLayout from "@/Layouts/AppLayout.vue";
import {
  DropdownMenu,
  DropdownMenuItem,
  DropdownMenuContent,
  DropdownMenuTrigger,
} from "@/Components/ui/dropdown-menu";
import DataTable from "@/Components/DataTable.vue";

const props = defineProps<{
  workEntries: {
    current_page: number
    data: Array<{}>
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
    cell: ({ row }) => format(new Date(row.original.work_date), 'PPP')
  },
  {
    accessorKey: 'project',
    header: 'Project'
  },
  {
    accessorKey: 'hours_worked',
    header: 'Hours'
  },
  {
    accessorKey: 'description',
    header: 'Description'
  },
  {
    id: 'actions',
    header: ''
  }
]

const addNewEntry = () => {
  router.visit(route('work-entries.create'));
};

const viewEntry = (entry) => {
  router.visit(route('work-entries.show', entry.id), {
    replace: true
  })
}

const editEntry = (entry) => {
  router.visit(route('work-entries.edit', entry.id), {
    preserveScroll: true
  })
}

const deleteEntry = (entry) => {
  router.delete(route('work-entries.destroy', entry.id), {
    onBefore: () => confirm('Are you sure you want to delete this entry?')
  })
}
</script>

<template>
  <AppLayout>
    <div class="my-12">
      <!-- Header Section -->
      <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold">Work Entries</h1>
        <Button @click="addNewEntry">
          <PlusCircleIcon class="w-5 h-5 mr-2" />
          Add Work Entry
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
                  <DropdownMenuItem @click="viewEntry(row.original)">
                    View Details
                  </DropdownMenuItem>
                  <DropdownMenuItem @click="editEntry(row.original)">
                    Edit Entry
                  </DropdownMenuItem>
                  <DropdownMenuItem @click="deleteEntry(row.original)">
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
