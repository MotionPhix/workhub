<template>
  <AdminLayout>
    <div class="py-6 sm:py-8">
      <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="mb-6">
          <Link
            :href="route('admin.departments.index')"
            class="inline-flex items-center text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200 mb-4"
          >
            <ArrowLeft class="w-4 h-4 mr-1" />
            Back to Departments
          </Link>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
            Create New Department
          </h1>
          <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            Add a new department with custom performance targets
          </p>
        </div>

        <!-- Create Form -->
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg">
          <Form
            :action="route('admin.departments.store')"
            method="post"
            class="space-y-6 p-6"
            #default="{ errors, processing }"
          >
            <!-- Basic Information -->
            <div>
              <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                Basic Information
              </h2>

              <div class="grid grid-cols-1 gap-6">
                <FormField label="Department Name" :error="errors.name" required>
                  <Input
                    type="text"
                    name="name"
                    placeholder="Enter department name"
                    required
                  />
                </FormField>

                <FormField label="Description" :error="errors.description">
                  <Textarea
                    name="description"
                    placeholder="Enter department description"
                    rows="3"
                  />
                </FormField>
              </div>
            </div>

            <!-- Performance Targets -->
            <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
              <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                Performance Targets
              </h2>

              <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <FormField
                  label="Working Hours per Day"
                  :error="errors.working_hours_per_day"
                  required
                  description="Standard working hours expected from employees in this department"
                >
                  <div class="relative">
                    <Input
                      type="number"
                      name="working_hours_per_day"
                      value="8.0"
                      step="0.5"
                      min="0"
                      max="24"
                      placeholder="8.0"
                      required
                    />
                    <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                      <Clock class="w-4 h-4 text-gray-400" />
                    </div>
                  </div>
                </FormField>

                <FormField
                  label="Daily Tasks Target"
                  :error="errors.daily_tasks_target"
                  required
                  description="Expected number of tasks to be completed daily"
                >
                  <div class="relative">
                    <Input
                      type="number"
                      name="daily_tasks_target"
                      value="5"
                      min="1"
                      max="50"
                      placeholder="5"
                      required
                    />
                    <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                      <CheckCircle class="w-4 h-4 text-gray-400" />
                    </div>
                  </div>
                </FormField>

                <FormField
                  label="Quality Target (%)"
                  :error="errors.quality_target_percentage"
                  required
                  description="Minimum quality percentage expected for completed tasks"
                >
                  <div class="relative">
                    <Input
                      type="number"
                      name="quality_target_percentage"
                      value="90.0"
                      step="0.1"
                      min="0"
                      max="100"
                      placeholder="90.0"
                      required
                    />
                    <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                      <TrendingUp class="w-4 h-4 text-gray-400" />
                    </div>
                  </div>
                </FormField>
              </div>
            </div>

            <!-- Department Templates -->
            <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
              <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                Quick Templates
              </h2>

              <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div
                  v-for="template in departmentTemplates"
                  :key="template.name"
                  @click="applyTemplate(template)"
                  class="cursor-pointer border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:border-blue-500 dark:hover:border-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors"
                >
                  <div class="flex items-center mb-2">
                    <component :is="template.icon" class="w-5 h-5 text-blue-500 mr-2" />
                    <h3 class="font-medium text-gray-900 dark:text-white">
                      {{ template.name }}
                    </h3>
                  </div>
                  <div class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                    <div>{{ template.hours }}h/day • {{ template.tasks }} tasks • {{ template.quality }}% quality</div>
                    <div class="text-xs">{{ template.description }}</div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Form Actions -->
            <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
              <div class="flex items-center justify-between">
                <Link :href="route('admin.departments.index')">
                  <Button variant="outline" type="button">
                    Cancel
                  </Button>
                </Link>

                <Button type="submit" :disabled="processing">
                  <Plus class="w-4 h-4 mr-2" v-if="!processing" />
                  <div class="w-4 h-4 mr-2 animate-spin border-2 border-white border-t-transparent rounded-full" v-else />
                  {{ processing ? 'Creating...' : 'Create Department' }}
                </Button>
              </div>
            </div>
          </Form>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import AdminLayout from '@/layouts/AdminLayout.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'
import FormField from '@/components/Forms/FormField.vue'
import { Form } from '@inertiajs/vue3'
import {
  ArrowLeft,
  Clock,
  CheckCircle,
  TrendingUp,
  Plus,
  Users,
  Code,
  DollarSign,
  Palette,
  Settings
} from 'lucide-vue-next'

const departmentTemplates = [
  {
    name: 'Standard',
    icon: Users,
    hours: 8.0,
    tasks: 5,
    quality: 90.0,
    description: 'Standard business hours with balanced expectations'
  },
  {
    name: 'Technology',
    icon: Code,
    hours: 8.5,
    tasks: 7,
    quality: 95.0,
    description: 'Tech-focused department with higher performance targets'
  },
  {
    name: 'Finance',
    icon: DollarSign,
    hours: 8.0,
    tasks: 4,
    quality: 98.0,
    description: 'Accuracy-focused with extremely high quality requirements'
  },
  {
    name: 'Creative',
    icon: Palette,
    hours: 7.5,
    tasks: 3,
    quality: 85.0,
    description: 'Flexible hours for creative work with quality focus'
  },
  {
    name: 'Operations',
    icon: Settings,
    hours: 8.0,
    tasks: 6,
    quality: 92.0,
    description: 'Operational efficiency with solid quality standards'
  }
]

const applyTemplate = (template: any) => {
  // Find form inputs and update their values
  const hoursInput = document.querySelector<HTMLInputElement>('input[name="working_hours_per_day"]')
  const tasksInput = document.querySelector<HTMLInputElement>('input[name="daily_tasks_target"]')
  const qualityInput = document.querySelector<HTMLInputElement>('input[name="quality_target_percentage"]')

  if (hoursInput) hoursInput.value = template.hours.toString()
  if (tasksInput) tasksInput.value = template.tasks.toString()
  if (qualityInput) qualityInput.value = template.quality.toString()
}
</script>