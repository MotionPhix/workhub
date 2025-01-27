<script setup lang="ts">
import { ref } from 'vue'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger
} from "@/Components/ui/dropdown-menu"
import { Button } from "@/Components/ui/button"
import { DownloadIcon, FileTextIcon, FileSpreadsheetIcon } from 'lucide-vue-next'
import {toast} from "vue-sonner";

interface Props {
  filters?: {
    start_date?: string
    end_date?: string
    department?: string
    status?: string
  }
}

const props = withDefaults(defineProps<Props>(), {
  filters: () => ({})
})

const isExporting = ref(false)

const exportFormats = [
  {
    id: 'pdf',
    label: 'PDF Document',
    icon: FileTextIcon,
    contentType: 'application/pdf'
  },
  {
    id: 'xlsx',
    label: 'Excel Spreadsheet',
    icon: FileSpreadsheetIcon,
    contentType: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
  }
]

const exportReport = async (format: string) => {
  try {
    isExporting.value = true

    // Construct query parameters from filters
    const queryParams = new URLSearchParams({
      ...props.filters,
      format
    }).toString()

    const response = await fetch(`/reports/export?${queryParams}`, {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    })

    if (!response.ok) throw new Error('Export failed')

    // Get the filename from the Content-Disposition header if available
    const contentDisposition = response.headers.get('Content-Disposition')
    const filename = contentDisposition
      ? contentDisposition.split('filename=')[1].replace(/"/g, '')
      : `reports-${new Date().toISOString().split('T')[0]}.${format}`

    // Create blob and download
    const blob = await response.blob()
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', filename)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)

    toast.success("Export Successful",{
      description: `Your report has been exported as ${format.toUpperCase()}`
    })
  } catch (error) {
    console.error('Export error:', error)
    toast.error("Export Failed", {
      description: "There was an error exporting your report. Please try again."
    })
  } finally {
    isExporting.value = false
  }
}
</script>

<template>
  <DropdownMenu>
    <DropdownMenuTrigger asChild>
      <Button variant="outline" :disabled="isExporting">
        <DownloadIcon class="h-4 w-4 mr-2" />
        Export
        <span v-if="isExporting">ing...</span>
      </Button>
    </DropdownMenuTrigger>
    <DropdownMenuContent align="end" class="w-48">
      <DropdownMenuItem
        v-for="format in exportFormats"
        :key="format.id"
        @click="exportReport(format.id)"
        :disabled="isExporting"
      >
        <component :is="format.icon" class="h-4 w-4 mr-2" />
        {{ format.label }}
      </DropdownMenuItem>
    </DropdownMenuContent>
  </DropdownMenu>
</template>
