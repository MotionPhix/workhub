<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { format } from 'date-fns'
import { EllipsisIcon } from 'lucide-vue-next'
import {Card, CardContent} from "@/Components/ui/card";
import AppLayout from "@/Layouts/AppLayout.vue";
import {Button} from "@/Components/ui/button";
import {
  DropdownMenu,
  DropdownMenuItem,
  DropdownMenuContent,
  DropdownMenuTrigger,
} from "@/Components/ui/dropdown-menu";
import DataTable from "@/Components/DataTable.vue";

const props = defineProps({
  workEntries: {
    type: Array,
    default: () => []
  }
})

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
    header: 'Actions'
  }
]

const viewEntry = (entry) => {
  router.visit(route('work-entries.show', entry.id))
}

const editEntry = (entry) => {
  router.visit(route('work-entries.edit', entry.id))
}

const deleteEntry = (entry) => {
  router.delete(route('work-entries.destroy', entry.id), {
    onBefore: () => confirm('Are you sure you want to delete this entry?')
  })
}
</script>

<template>
  <AppLayout>
    <div class="container mx-auto p-6">
      <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Work Entries</h1>
<!--        <CreateWorkEntryModal />-->
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
                    <EllipsisIcon class="h-4 w-4" />
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
