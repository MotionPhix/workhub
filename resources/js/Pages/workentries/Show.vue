<script setup lang="ts">
import { Modal } from '@inertiaui/modal-vue'
import { format } from 'date-fns'
import {
  Calendar,
  Clock,
  CheckCircle,
  User,
  Building,
  Tag,
  FileText,
  Edit,
  Trash2
} from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { ModalLink, visitModal } from '@inertiaui/modal-vue'
import { router } from '@inertiajs/vue3'
import { toast } from 'vue-sonner'

interface Props {
  workEntry: {
    uuid: string
    work_title: string
    start_date_time: string
    end_date_time: string
    hours_worked: number
    description: string
    status: string
    project?: {
      name: string
      uuid: string
    }
    tag_names: string[]
    user: {
      name: string
      email: string
    }
    created_at: string
    updated_at: string
  }
}

const props = defineProps<Props>()

const getStatusColor = (status: string) => {
  switch (status) {
    case 'completed':
      return 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400'
    case 'in_progress':
      return 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400'
    case 'draft':
      return 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400'
    default:
      return 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400'
  }
}

const formatDate = (dateString: string) => {
  return format(new Date(dateString), 'EEEE, MMMM d, yyyy')
}

const formatDateTime = (dateString: string) => {
  return format(new Date(dateString), 'PPpp')
}

const deleteEntry = () => {
  if (confirm('Are you sure you want to delete this work entry?')) {
    router.delete(route('work-entries.destroy', props.workEntry.uuid), {
      onSuccess: () => {
        toast.success('Work entry deleted successfully!')
      }
    })
  }
}
</script>

<template>
  <Modal max-width="3xl" :close-button="true">
    <div class="p-6">
      <!-- Header -->
      <div class="flex items-start justify-between mb-6">
        <div class="flex-1">
          <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
            {{ workEntry.work_title }}
          </h2>
          <div class="flex items-center gap-4 text-sm text-gray-600 dark:text-gray-400">
            <div class="flex items-center gap-1">
              <User class="w-4 h-4" />
              {{ workEntry.user.name }}
            </div>
            <div class="flex items-center gap-1">
              <Calendar class="w-4 h-4" />
              {{ formatDate(workEntry.start_date_time) }}
            </div>
            <div class="flex items-center gap-1">
              <Clock class="w-4 h-4" />
              {{ workEntry.hours_worked }}h
            </div>
          </div>
        </div>

        <div class="flex items-center gap-2 ml-4">
          <Badge :class="getStatusColor(workEntry.status)">
            {{ workEntry.status.replace('_', ' ').toUpperCase() }}
          </Badge>
        </div>
      </div>

      <!-- Project Info (if available) -->
      <div v-if="workEntry.project" class="mb-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
        <div class="flex items-center gap-2 text-blue-800 dark:text-blue-300">
          <Building class="w-4 h-4" />
          <span class="font-medium">Project:</span>
          <span>{{ workEntry.project.name }}</span>
        </div>
      </div>

      <!-- Tags -->
      <div v-if="workEntry.tag_names && workEntry.tag_names.length > 0" class="mb-6">
        <div class="flex items-center gap-2 mb-2">
          <Tag class="w-4 h-4 text-gray-500 dark:text-gray-400" />
          <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Tags</span>
        </div>
        <div class="flex flex-wrap gap-2">
          <span
            v-for="tag in workEntry.tag_names"
            :key="tag"
            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300"
          >
            {{ tag }}
          </span>
        </div>
      </div>

      <!-- Description -->
      <div class="mb-8">
        <div class="flex items-center gap-2 mb-3">
          <FileText class="w-4 h-4 text-gray-500 dark:text-gray-400" />
          <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Work Description</span>
        </div>
        <div
          class="prose prose-sm dark:prose-invert max-w-none text-gray-700 dark:text-gray-300"
          v-html="workEntry.description"
        ></div>
      </div>

      <!-- Metadata -->
      <div class="border-t border-gray-200 dark:border-gray-700 pt-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-500 dark:text-gray-400">
          <div>
            <span class="font-medium">Created:</span>
            {{ formatDateTime(workEntry.created_at) }}
          </div>
          <div>
            <span class="font-medium">Last Updated:</span>
            {{ formatDateTime(workEntry.updated_at) }}
          </div>
        </div>
      </div>

      <!-- Actions -->
      <div class="flex items-center justify-end gap-3 pt-6 border-t border-gray-200 dark:border-gray-700">
        <Button
          variant="outline"
          @click="deleteEntry"
          class="text-red-600 hover:text-red-700 hover:bg-red-50 dark:text-red-400 dark:hover:text-red-300 dark:hover:bg-red-950/50"
        >
          <Trash2 class="w-4 h-4 mr-2" />
          Delete
        </Button>

        <ModalLink
          :href="route('work-entries.edit', workEntry.uuid)"
          as="button"
          class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-md transition-colors"
        >
          <Edit class="w-4 h-4 mr-2" />
          Edit Entry
        </ModalLink>
      </div>
    </div>
  </Modal>
</template>

<style>
.prose h1, .prose h2, .prose h3, .prose h4, .prose h5, .prose h6 {
  margin-top: 1rem;
  margin-bottom: 0.5rem;
}

.prose p {
  margin-top: 0.75rem;
  margin-bottom: 0.75rem;
}

.prose ul, .prose ol {
  margin-top: 0.75rem;
  margin-bottom: 0.75rem;
  padding-left: 1.5rem;
}

.prose li {
  margin-top: 0.25rem;
  margin-bottom: 0.25rem;
}

.prose strong {
  font-weight: 600;
}

.prose em {
  font-style: italic;
}

.prose u {
  text-decoration: underline;
}
</style>
