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
            Edit Department: {{ department.name }}
          </h1>
          <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            Update department settings and performance targets
          </p>
        </div>

        <!-- Edit Form -->
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg">
          <Form
            :action="route('admin.departments.update', department.uuid)"
            method="put"
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
                    :value="department.name"
                    placeholder="Enter department name"
                    required
                  />
                </FormField>

                <FormField label="Description" :error="errors.description">
                  <Textarea
                    name="description"
                    :value="department.description || ''"
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
                      :value="department.working_hours_per_day"
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
                      :value="department.daily_tasks_target"
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
                      :value="department.quality_target_percentage"
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

            <!-- Target Impact Preview -->
            <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
              <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                Target Impact Preview
              </h2>

              <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
                <div class="flex items-start">
                  <Info class="w-5 h-5 text-blue-500 mt-0.5 mr-3 flex-shrink-0" />
                  <div>
                    <h3 class="text-sm font-medium text-blue-900 dark:text-blue-300">
                      These targets will affect employee dashboards
                    </h3>
                    <div class="mt-2 text-sm text-blue-700 dark:text-blue-400">
                      <ul class="list-disc list-inside space-y-1">
                        <li>Employees will see <strong>{{ department.working_hours_per_day }}h</strong> as their daily hours target</li>
                        <li>Daily tasks goal will be set to <strong>{{ department.daily_tasks_target }} tasks</strong></li>
                        <li>Quality expectations will be <strong>{{ department.quality_target_percentage }}%</strong> completion rate</li>
                      </ul>
                    </div>
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
                  <Save class="w-4 h-4 mr-2" v-if="!processing" />
                  <div class="w-4 h-4 mr-2 animate-spin border-2 border-white border-t-transparent rounded-full" v-else />
                  {{ processing ? 'Saving...' : 'Save Changes' }}
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
import FormField from '@/components/forms/FormField.vue'
import { Form } from '@inertiajs/vue3'
import {
  ArrowLeft,
  Clock,
  CheckCircle,
  TrendingUp,
  Info,
  Save
} from 'lucide-vue-next'

interface Department {
  id: number
  uuid: string
  name: string
  description: string | null
  working_hours_per_day: number
  daily_tasks_target: number
  quality_target_percentage: number
}

defineProps<{
  department: Department
}>()
</script>