<template>
  <AdminLayout>
    <div class="py-6 sm:py-8">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="mb-6">
          <div class="flex items-center justify-between">
            <div>
              <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                Department Management
              </h1>
              <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Manage department settings and performance targets
              </p>
            </div>
            <Link :href="route('admin.departments.create')">
              <Button class="flex items-center space-x-2">
                <Plus class="w-4 h-4" />
                <span>Add Department</span>
              </Button>
            </Link>
          </div>
        </div>

        <!-- Departments Table -->
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden">
          <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
              Departments & Targets
            </h2>
          </div>

          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
              <thead class="bg-gray-50 dark:bg-gray-900">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                    Department
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                    Working Hours/Day
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                    Daily Tasks Target
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                    Quality Target
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                    Actions
                  </th>
                </tr>
              </thead>
              <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                <tr
                  v-for="department in departments"
                  :key="department.id"
                  class="hover:bg-gray-50 dark:hover:bg-gray-700"
                >
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div>
                      <div class="text-sm font-medium text-gray-900 dark:text-white">
                        {{ department.name }}
                      </div>
                      <div class="text-sm text-gray-500 dark:text-gray-400">
                        {{ department.description }}
                      </div>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                      <Clock class="w-4 h-4 text-blue-500 mr-2" />
                      <span class="text-sm font-medium text-gray-900 dark:text-white">
                        {{ department.working_hours_per_day }}h
                      </span>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                      <CheckCircle class="w-4 h-4 text-green-500 mr-2" />
                      <span class="text-sm font-medium text-gray-900 dark:text-white">
                        {{ department.daily_tasks_target }} tasks
                      </span>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                      <TrendingUp class="w-4 h-4 text-purple-500 mr-2" />
                      <span class="text-sm font-medium text-gray-900 dark:text-white">
                        {{ department.quality_target_percentage }}%
                      </span>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                    <div class="flex items-center space-x-2">
                      <Link :href="route('admin.departments.edit', department.uuid)">
                        <Button variant="outline" size="sm">
                          <Edit class="w-4 h-4 mr-1" />
                          Edit
                        </Button>
                      </Link>
                      <Button
                        variant="outline"
                        size="sm"
                        class="text-red-600 border-red-300 hover:bg-red-50"
                        @click="deleteDepartment(department)"
                      >
                        <Trash2 class="w-4 h-4 mr-1" />
                        Delete
                      </Button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Empty State -->
          <div
            v-if="departments.length === 0"
            class="text-center py-12"
          >
            <Building class="mx-auto h-12 w-12 text-gray-400" />
            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No departments</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
              Get started by creating a new department.
            </p>
            <div class="mt-6">
              <Link :href="route('admin.departments.create')">
                <Button>
                  <Plus class="w-4 h-4 mr-2" />
                  Add Department
                </Button>
              </Link>
            </div>
          </div>
        </div>

        <!-- Department Performance Overview -->
        <div class="mt-8 bg-white dark:bg-gray-800 shadow-sm rounded-lg">
          <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
              Department Performance Overview
            </h2>
          </div>
          <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
              <div
                v-for="department in departments"
                :key="department.id"
                class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4"
              >
                <div class="flex items-center justify-between mb-3">
                  <h3 class="text-sm font-medium text-gray-900 dark:text-white">
                    {{ department.name }}
                  </h3>
                  <Badge :variant="getPerformanceBadgeVariant(department)">
                    {{ getPerformanceStatus(department) }}
                  </Badge>
                </div>
                <div class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                  <div class="flex justify-between">
                    <span>Target Hours:</span>
                    <span class="font-medium">{{ department.working_hours_per_day }}h/day</span>
                  </div>
                  <div class="flex justify-between">
                    <span>Target Tasks:</span>
                    <span class="font-medium">{{ department.daily_tasks_target }}/day</span>
                  </div>
                  <div class="flex justify-between">
                    <span>Quality Goal:</span>
                    <span class="font-medium">{{ department.quality_target_percentage }}%</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/layouts/AdminLayout.vue'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import {
  Plus,
  Edit,
  Trash2,
  Clock,
  CheckCircle,
  TrendingUp,
  Building
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
  departments: Department[]
}>()

const deleteDepartment = (department: Department) => {
  if (confirm(`Are you sure you want to delete the ${department.name} department?`)) {
    router.delete(route('admin.departments.destroy', department.uuid))
  }
}

const getPerformanceStatus = (department: Department): string => {
  // Simple logic to categorize departments based on their targets
  const hoursScore = department.working_hours_per_day >= 8 ? 1 : 0
  const tasksScore = department.daily_tasks_target >= 5 ? 1 : 0
  const qualityScore = department.quality_target_percentage >= 90 ? 1 : 0
  const totalScore = hoursScore + tasksScore + qualityScore

  if (totalScore >= 3) return 'High Performance'
  if (totalScore >= 2) return 'Standard'
  return 'Flexible'
}

const getPerformanceBadgeVariant = (department: Department): string => {
  const status = getPerformanceStatus(department)
  return status === 'High Performance' ? 'destructive' :
         status === 'Standard' ? 'default' :
         'secondary'
}
</script>