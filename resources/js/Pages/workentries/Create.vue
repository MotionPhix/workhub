<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import { Modal } from '@inertiaui/modal-vue'
import { toast } from 'vue-sonner'
import { format } from 'date-fns'
import { onMounted } from 'vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'

interface Props {
  projects?: Array<{
    uuid: string
    name: string
  }>
}

const props = defineProps<Props>()

// Clear any stored form data from localStorage on component mount
onMounted(() => {
  // Clear Inertia form data that might be persisted
  const keys = Object.keys(localStorage)
  keys.forEach(key => {
    if (key.includes('inertia') || key.includes('form') || key.includes('work-entry')) {
      localStorage.removeItem(key)
    }
  })
})

const form = useForm({
  work_title: '',
  work_date: format(new Date(), 'yyyy-MM-dd'),
  hours_worked: 8,
  description: '',
  status: 'in_progress',
  project_uuid: '',
  tags: ''
})

const submitForm = () => {
  form.post(route('work-entries.store'), {
    onSuccess: () => {
      toast.success('Work entry created successfully!')
    },
    onError: (errors) => {
      toast.error('Please fix the errors and try again.')
    }
  })
}
</script>

<template>
  <Modal>
    <div class="p-6">
      <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-800 dark:text-white">
          Create Work Entry
        </h2>
        <p class="text-gray-600 dark:text-gray-400 mt-1">
          Log your daily work activities and achievements
        </p>
      </div>

      <form @submit.prevent="submitForm" class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Work Title -->
          <div>
            <Label for="work_title">Work Title *</Label>
            <Input
              id="work_title"
              v-model="form.work_title"
              type="text"
              placeholder="Brief title for your work"
              required
              class="mt-1"
              :class="{ 'border-red-500': form.errors.work_title }"
            />
            <div v-if="form.errors.work_title" class="mt-1 text-sm text-red-600">
              {{ form.errors.work_title }}
            </div>
          </div>

          <!-- Work Date -->
          <div>
            <Label for="work_date">Work Date *</Label>
            <Input
              id="work_date"
              v-model="form.work_date"
              type="date"
              required
              class="mt-1"
              :class="{ 'border-red-500': form.errors.work_date }"
            />
            <div v-if="form.errors.work_date" class="mt-1 text-sm text-red-600">
              {{ form.errors.work_date }}
            </div>
          </div>

          <!-- Hours Worked -->
          <div>
            <Label for="hours_worked">Hours Worked *</Label>
            <Input
              id="hours_worked"
              v-model="form.hours_worked"
              type="number"
              min="0.1"
              max="24"
              step="0.1"
              required
              class="mt-1"
              :class="{ 'border-red-500': form.errors.hours_worked }"
            />
            <div v-if="form.errors.hours_worked" class="mt-1 text-sm text-red-600">
              {{ form.errors.hours_worked }}
            </div>
          </div>

          <!-- Status -->
          <div>
            <Label for="status">Status</Label>
            <select
              id="status"
              v-model="form.status"
              class="mt-1 block w-full rounded-md border-gray-300 bg-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
              :class="{ 'border-red-500': form.errors.status }"
            >
              <option value="in_progress">In Progress</option>
              <option value="completed">Completed</option>
              <option value="draft">Draft</option>
            </select>
          </div>
        </div>

        <!-- Project -->
        <div v-if="projects && projects.length > 0">
          <Label for="project">Project (Optional)</Label>
          <select
            id="project"
            v-model="form.project_uuid"
            class="mt-1 block w-full rounded-md border-gray-300 bg-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
          >
            <option value="">No Project</option>
            <option v-for="project in projects" :key="project.uuid" :value="project.uuid">
              {{ project.name }}
            </option>
          </select>
        </div>

        <!-- Tags -->
        <div>
          <Label for="tags">Tags (Optional)</Label>
          <Input
            id="tags"
            v-model="form.tags"
            type="text"
            placeholder="meeting, development, research (comma separated)"
            class="mt-1"
          />
          <p class="text-xs text-gray-500 mt-1">
            Separate multiple tags with commas
          </p>
        </div>

        <!-- Description -->
        <div>
          <Label for="description">Work Description</Label>
          <Textarea
            id="description"
            v-model="form.description"
            rows="4"
            placeholder="Describe your work activities, achievements, and notes..."
            class="mt-1"
            :class="{ 'border-red-500': form.errors.description }"
          />
          <div v-if="form.errors.description" class="mt-1 text-sm text-red-600">
            {{ form.errors.description }}
          </div>
        </div>

        <!-- Form Actions -->
        <div class="flex items-center justify-end gap-3 pt-6 border-t border-gray-200">
          <Button type="submit" :disabled="form.processing">
            {{ form.processing ? 'Creating...' : 'Create Entry' }}
          </Button>
        </div>
      </form>
    </div>
  </Modal>
</template>
