<template>
  <AppLayout>
    <Head :title="`Edit ${project.name}`" />

    <div class="max-w-4xl mx-auto">
      <!-- Header -->
      <div class="mb-8">
        <!-- Breadcrumb -->
        <nav class="flex items-center text-sm text-gray-500 dark:text-gray-400 mb-4">
          <Link :href="route('projects.index')" class="hover:text-gray-700 dark:hover:text-gray-300">
            Projects
          </Link>
          <ChevronRightIcon class="w-4 h-4 mx-2" />
          <Link :href="route('projects.show', project.uuid)" class="hover:text-gray-700 dark:hover:text-gray-300">
            {{ project.name }}
          </Link>
          <ChevronRightIcon class="w-4 h-4 mx-2" />
          <span class="text-gray-900 dark:text-white">Edit</span>
        </nav>

        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Edit Project</h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">Update project information and settings</p>
      </div>

      <!-- Project Form -->
      <form @submit.prevent="submit" class="space-y-8">

        <!-- Basic Information -->
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Basic Information</h2>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Project Name -->
            <div class="md:col-span-2">
              <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Project Name *
              </label>
              <input
                id="name"
                v-model="form.name"
                type="text"
                required
                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                placeholder="Enter project name"
              />
              <InputError :message="form.errors.name" class="mt-1" />
            </div>

            <!-- Department -->
            <div>
              <label for="department_uuid" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Department *
              </label>
              <select
                id="department_uuid"
                v-model="form.department_uuid"
                required
                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
              >
                <option value="">Select Department</option>
                <option v-for="dept in departments" :key="dept.uuid" :value="dept.uuid">
                  {{ dept.name }}
                </option>
              </select>
              <InputError :message="form.errors.department_uuid" class="mt-1" />
            </div>

            <!-- Manager -->
            <div>
              <label for="manager_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Project Manager *
              </label>
              <select
                id="manager_id"
                v-model="form.manager_id"
                required
                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
              >
                <option value="">Select Manager</option>
                <option v-for="manager in managers" :key="manager.id" :value="manager.id">
                  {{ manager.name }} ({{ manager.email }})
                </option>
              </select>
              <InputError :message="form.errors.manager_id" class="mt-1" />
            </div>

            <!-- Project Description -->
            <div class="md:col-span-2">
              <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Description
              </label>
              <textarea
                id="description"
                v-model="form.description"
                rows="4"
                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                placeholder="Describe the project goals, objectives, and key deliverables..."
              />
              <InputError :message="form.errors.description" class="mt-1" />
            </div>
          </div>
        </div>

        <!-- Timeline & Priority -->
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Timeline & Priority</h2>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Start Date -->
            <div>
              <label for="start_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Start Date *
              </label>
              <DatePicker
                id="start_date"
                v-model="form.start_date"
                placeholder="Select start date"
                class="w-full"
                required
              />
              <InputError :message="form.errors.start_date" class="mt-1" />
            </div>

            <!-- Due Date -->
            <div>
              <label for="due_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Due Date *
              </label>
              <DatePicker
                id="due_date"
                v-model="form.due_date"
                placeholder="Select due date"
                class="w-full"
                required
              />
              <InputError :message="form.errors.due_date" class="mt-1" />
            </div>

            <!-- Status -->
            <div>
              <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Status *
              </label>
              <select
                id="status"
                v-model="form.status"
                required
                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
              >
                <option value="planning">Planning</option>
                <option value="active">Active</option>
                <option value="on_hold">On Hold</option>
                <option value="completed">Completed</option>
                <option value="cancelled">Archived</option>
              </select>
              <InputError :message="form.errors.status" class="mt-1" />
            </div>

            <!-- Priority -->
            <div>
              <label for="priority" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Priority *
              </label>
              <select
                id="priority"
                v-model="form.priority"
                required
                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
              >
                <option value="low">Low</option>
                <option value="medium">Medium</option>
                <option value="high">High</option>
                <option value="urgent">Urgent</option>
              </select>
              <InputError :message="form.errors.priority" class="mt-1" />
            </div>

            <!-- Estimated Hours -->
            <div>
              <label for="estimated_hours" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Estimated Hours
              </label>
              <input
                id="estimated_hours"
                v-model="form.estimated_hours"
                type="number"
                min="0"
                step="0.5"
                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                placeholder="0"
              />
              <InputError :message="form.errors.estimated_hours" class="mt-1" />
            </div>

            <!-- Shared Project Toggle -->
            <div class="flex items-center">
              <input
                id="is_shared"
                v-model="form.is_shared"
                type="checkbox"
                class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700"
              />
              <label for="is_shared" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                Make this project visible to all departments
              </label>
            </div>
          </div>
        </div>

        <!-- Tags -->
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Tags</h2>

          <div>
            <label for="tags" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Project Tags
            </label>
            <input
              id="tags"
              v-model="tagInput"
              type="text"
              class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
              placeholder="Add tags separated by commas..."
              @keydown.enter.prevent="addTag"
              @keydown.comma.prevent="addTag"
            />
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
              Press Enter or comma to add tags. Tags help organize and filter projects.
            </p>

            <!-- Tag Display -->
            <div v-if="form.tags.length > 0" class="flex flex-wrap gap-2 mt-3">
              <span
                v-for="(tag, index) in form.tags"
                :key="index"
                class="inline-flex items-center gap-1 px-3 py-1 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 text-sm rounded-full"
              >
                {{ tag }}
                <button
                  type="button"
                  @click="removeTag(index)"
                  class="hover:text-blue-600 dark:hover:text-blue-400"
                >
                  <XIcon class="w-3 h-3" />
                </button>
              </span>
            </div>
          </div>
        </div>

        <!-- Form Actions -->
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-4">
            <Link
              :href="route('projects.show', project.uuid)"
              class="px-6 py-3 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 font-medium transition-colors"
            >
              Cancel
            </Link>
          </div>

          <div class="flex items-center gap-4">
            <!-- Archive/Reactivate Button -->
            <button
              type="button"
              @click="handleArchive"
              class="px-6 py-3 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 font-medium transition-colors flex items-center gap-2"
            >
              <ArchiveIcon class="w-4 h-4" />
              {{ project.status === 'cancelled' ? 'Reactivate' : 'Archive' }}
            </button>

            <!-- Update Button -->
            <button
              type="submit"
              :disabled="form.processing"
              class="bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 text-white px-6 py-3 rounded-lg font-medium inline-flex items-center gap-2 transition-colors"
            >
              <LoaderIcon v-if="form.processing" class="w-4 h-4 animate-spin" />
              <SaveIcon v-else class="w-4 h-4" />
              {{ form.processing ? 'Updating...' : 'Update Project' }}
            </button>
          </div>
        </div>
      </form>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import DatePicker from '@/components/DatePicker.vue'
import InputError from '@/components/InputError.vue'
import {
  ChevronRightIcon,
  SaveIcon,
  XIcon,
  LoaderIcon,
  ArchiveIcon
} from 'lucide-vue-next'

interface Tag {
  name: string
}

interface Project {
  id?: number
  uuid?: string
  name?: string
  description?: string
  department_uuid?: string
  manager_id?: number
  start_date?: string
  due_date?: string
  status?: string
  priority?: string
  estimated_hours?: number
  is_shared?: boolean
  tags?: Tag[]
}

interface Department {
  uuid: string
  name: string
}

interface Manager {
  id: number
  name: string
  email: string
}

interface Props {
  project: Project
  departments: Department[]
  managers: Manager[]
}

const props = defineProps<Props>()

const form = useForm({
  name: props.project.name || '',
  description: props.project.description || '',
  department_uuid: props.project.department_uuid || '',
  manager_id: props.project.manager_id || '',
  start_date: props.project.start_date || '',
  due_date: props.project.due_date || '',
  status: props.project.status || 'planning',
  priority: props.project.priority || 'medium',
  estimated_hours: props.project.estimated_hours || '',
  is_shared: props.project.is_shared || false,
  tags: props.project.tags ? props.project.tags.map(tag => tag.name) : []
})

const tagInput = ref('')

const addTag = () => {
  const tag = tagInput.value.trim().replace(/,$/, '')
  if (tag && !form.tags.includes(tag)) {
    form.tags.push(tag)
  }
  tagInput.value = ''
}

const removeTag = (index) => {
  form.tags.splice(index, 1)
}

const submit = () => {
  form.put(route('projects.update', props.project.uuid))
}

const handleArchive = () => {
  router.post(route('projects.archive', props.project.uuid), {}, {
    preserveScroll: true,
    onSuccess: () => {
      // Redirect to project show page after archive action
      router.visit(route('projects.show', props.project.uuid))
    }
  })
}
</script>
