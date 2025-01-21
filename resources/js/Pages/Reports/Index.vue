<script setup lang="ts">
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { EllipsisIcon } from 'lucide-vue-next'
import {
  DropdownMenu,
  DropdownMenuItem,
  DropdownMenuContent,
  DropdownMenuTrigger
} from "@/Components/ui/dropdown-menu";
import AppLayout from "@/Layouts/AppLayout.vue";
import DataTable from "@/Components/DataTable.vue";

const props = defineProps<{
  reports: {
    current_page: number
    first_page_url?: string
    from?: number
    last_page: number
    last_page_url?: string
    next_page_url?: string
    per_page: number
    previous_page_url?: string
    to: number
    total: number
    path: string
    links: Array<{
      active: boolean
      url?: string
      label: string
    }>
    data: Array<{}>
  }
}>()

const columns = [
  {
    accessorKey: 'title',
    header: 'Report Title'
  },
  {
    accessorKey: 'created_at',
    header: 'Date Created'
  },
  {
    id: 'actions',
    header: 'Actions'
  }
]

const viewReport = (report) => {
  router.visit(route('reports.show', report.id))
}

const downloadReport = (report) => {
  window.open(route('reports.download', report.id), '_blank')
}
</script>

<template>
  <AppLayout>
    <div class="container mx-auto p-6 my-12">

      <h1 class="text-2xl font-bold mb-4">Reports</h1>

      <DataTable
        :columns="columns"
        :data="reports">
        <template #actions="{ row }">
          <DropdownMenu>
            <DropdownMenuTrigger as-child>
              <Button variant="ghost" size="sm">
                <EllipsisIcon class="h-4 w-4" />
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent>
              <DropdownMenuItem @click="viewReport(row.original)">
                View Report
              </DropdownMenuItem>
              <DropdownMenuItem @click="downloadReport(row.original)">
                Download Report
              </DropdownMenuItem>
            </DropdownMenuContent>
          </DropdownMenu>
        </template>
      </DataTable>

    </div>
  </AppLayout>
</template>
