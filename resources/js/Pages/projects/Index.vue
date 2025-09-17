<template>
  <AppLayout>
    <Head title="Projects" />

    <!-- Monday.com style header -->
    <div class="mb-8">
      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Projects</h1>
          <p class="text-gray-600 dark:text-gray-400 mt-1">Manage and track all your projects in one place</p>
        </div>
        <Link
          :href="route('projects.create')"
          class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium inline-flex items-center gap-2 transition-colors"
        >
          <PlusIcon class="w-5 h-5" />
          New Project
        </Link>
      </div>

      <!-- Monday.com style filters and search -->
      <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 mb-6">
        <div class="flex flex-wrap gap-4 items-center">
          <!-- Search -->
          <div class="flex-1 min-w-64">
            <div class="relative">
              <SearchIcon class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5" />
              <input
                v-model="filters.search"
                type="text"
                placeholder="Search projects..."
                class="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                @input="debouncedSearch"
              />
            </div>
          </div>

          <!-- Status Filter -->
          <select
            v-model="filters.status"
            @change="applyFilters"
            class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
          >
            <option value="all">All Status</option>
            <option value="planning">Planning</option>
            <option value="active">Active</option>
            <option value="on_hold">On Hold</option>
            <option value="completed">Completed</option>
            <option value="cancelled">Archived</option>
          </select>

          <!-- Priority Filter -->
          <select
            v-model="filters.priority"
            @change="applyFilters"
            class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
          >
            <option value="all">All Priority</option>
            <option value="urgent">Urgent</option>
            <option value="high">High</option>
            <option value="medium">Medium</option>
            <option value="low">Low</option>
          </select>

          <!-- Department Filter -->
          <select
            v-model="filters.department"
            @change="applyFilters"
            class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
            v-if="departments.length > 1"
          >
            <option value="all">All Departments</option>
            <option v-for="dept in departments" :key="dept.uuid" :value="dept.uuid">
              {{ dept.name }}
            </option>
          </select>

          <!-- Overdue Toggle -->
          <label class="flex items-center cursor-pointer">
            <input
              type="checkbox"
              v-model="filters.overdue"
              @change="applyFilters"
              class="sr-only"
            />
            <div class="relative">
              <div class="w-10 h-6 bg-gray-200 rounded-full shadow-inner dark:bg-gray-600"></div>
              <div class="absolute inset-y-0 left-0 w-4 h-4 m-1 bg-white rounded-full shadow transition-transform duration-300" :class="{ 'transform translate-x-4': filters.overdue }"></div>
            </div>
            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Overdue Only</span>
          </label>

          <!-- View Toggle -->
          <div class="flex bg-gray-100 dark:bg-gray-700 rounded-lg p-1">
            <button
              @click="viewMode = 'grid'"
              class="px-3 py-1 rounded text-sm transition-colors"
              :class="viewMode === 'grid' ? 'bg-white dark:bg-gray-600 text-blue-600 dark:text-blue-400 shadow' : 'text-gray-600 dark:text-gray-400'"
            >
              <LayoutGridIcon class="w-4 h-4" />
            </button>
            <button
              @click="viewMode = 'list'"
              class="px-3 py-1 rounded text-sm transition-colors"
              :class="viewMode === 'list' ? 'bg-white dark:bg-gray-600 text-blue-600 dark:text-blue-400 shadow' : 'text-gray-600 dark:text-gray-400'"
            >
              <ListIcon class="w-4 h-4" />
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Projects Grid/List -->
    <div v-if="projects.data.length > 0">
      <!-- Grid View -->
      <div v-if="viewMode === 'grid'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        <ProjectCard
          v-for="project in projects.data"
          :key="project.uuid"
          :project="project"
          @updated="handleProjectUpdate"
        />
      </div>

      <!-- List View -->
      <div v-else class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden mb-8">
        <ProjectTable
          :projects="projects.data"
          :sort-by="filters.sort_by"
          :sort-direction="filters.sort_direction"
          @sort="handleSort"
          @updated="handleProjectUpdate"
        />
      </div>

      <!-- Pagination -->
      <div class="flex items-center justify-between">
        <div class="text-sm text-gray-700 dark:text-gray-300">
          Showing {{ projects.from }} to {{ projects.to }} of {{ projects.total }} projects
        </div>
        <Pagination :links="projects.links" />
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="text-center py-16">
      <FolderOpenIcon class="w-16 h-16 text-gray-400 mx-auto mb-4" />
      <h3 class="text-xl font-medium text-gray-900 dark:text-white mb-2">No projects found</h3>
      <p class="text-gray-600 dark:text-gray-400 mb-8">Get started by creating your first project</p>
      <Link
        :href="route('projects.create')"
        class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium inline-flex items-center gap-2 transition-colors"
      >
        <PlusIcon class="w-5 h-5" />
        Create Project
      </Link>
    </div>
  </AppLayout>
</template>

<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import { debounce } from 'lodash-es'
import AppLayout from '@/layouts/AppLayout.vue'
import ProjectCard from '@/components/projects/ProjectCard.vue'
import ProjectTable from '@/components/projects/ProjectTable.vue'
import Pagination from '@/components/Pagination.vue'
import {
  PlusIcon,
  SearchIcon,
  LayoutGridIcon,
  ListIcon,
  FolderOpenIcon
} from 'lucide-vue-next'

const props = defineProps({
  projects: Object,
  departments: Array,
  filters: Object
})

const viewMode = ref('grid')
const filters = ref({
  search: props.filters.search,
  status: props.filters.status,
  priority: props.filters.priority,
  department: props.filters.department,
  overdue: props.filters.overdue,
  sort_by: props.filters.sort_by,
  sort_direction: props.filters.sort_direction
})

const debouncedSearch = debounce(() => {
  applyFilters()
}, 300)

const applyFilters = () => {
  router.get(route('projects.index'), filters.value, {
    preserveState: true,
    preserveScroll: true
  })
}

const handleSort = (sortBy) => {
  if (filters.value.sort_by === sortBy) {
    filters.value.sort_direction = filters.value.sort_direction === 'asc' ? 'desc' : 'asc'
  } else {
    filters.value.sort_by = sortBy
    filters.value.sort_direction = 'asc'
  }
  applyFilters()
}

const handleProjectUpdate = () => {
  // Refresh the page to show updated data
  router.reload({ only: ['projects'] })
}
</script>
