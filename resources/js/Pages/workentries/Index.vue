<script setup lang="ts">
import { ref, computed, nextTick, watch, onMounted } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { ModalLink } from '@inertiaui/modal-vue'
import DatePicker from '@/components/DatePicker.vue'
import {
  Plus,
  Calendar,
  Clock,
  CheckCircle,
  Edit,
  Eye,
  Trash2,
  Filter,
  Search,
  EllipsisVertical,
  X
} from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue
} from '@/components/ui/select'

interface WorkEntry {
  uuid: string
  start_date_time: string
  end_date_time: string
  work_title: string
  hours_worked: number
  status: string
  description: string
  tag_names: string[]
}

interface Props {
  workEntries: {
    data: WorkEntry[]
    meta: any
    links: any
  }
  filters: {
    search: string
    status: string
    date_from: string
    date_to: string
    project_uuid: string
    tag: string
    sort_by: string
    sort_direction: string
  }
}

const props = defineProps<Props>()
const page = usePage()

// Reactive filter states
const searchQuery = ref(props.filters.search || '')
const selectedStatus = ref(props.filters.status || 'all')
const dateFrom = ref(props.filters.date_from || '')
const dateTo = ref(props.filters.date_to || '')
const selectedTag = ref(props.filters.tag || '')
const sortBy = ref(props.filters.sort_by || 'start_date_time')
const sortDirection = ref(props.filters.sort_direction || 'desc')

// Debounce timer
let searchTimeout: ReturnType<typeof setTimeout> | null = null

// Function to perform the search/filter request
const performSearch = () => {
  const params: Record<string, string> = {}

  if (searchQuery.value.trim()) params.search = searchQuery.value.trim()
  if (selectedStatus.value !== 'all') params.status = selectedStatus.value
  if (dateFrom.value) params.date_from = dateFrom.value
  if (dateTo.value) params.date_to = dateTo.value
  if (selectedTag.value.trim()) params.tag = selectedTag.value.trim()
  if (sortBy.value !== 'start_date_time') params.sort_by = sortBy.value
  if (sortDirection.value !== 'desc') params.sort_direction = sortDirection.value

  router.get(route('work-entries.index'), params, {
    preserveState: true,
    preserveScroll: true,
    replace: true
  })
}

// Debounced search for text inputs
const debouncedSearch = () => {
  if (searchTimeout) {
    clearTimeout(searchTimeout)
  }
  searchTimeout = setTimeout(() => {
    performSearch()
  }, 300) // 300ms delay
}

// Clear all filters
const clearFilters = () => {
  searchQuery.value = ''
  selectedStatus.value = 'all'
  dateFrom.value = ''
  dateTo.value = ''
  selectedTag.value = ''
  sortBy.value = 'start_date_time'
  sortDirection.value = 'desc'
  performSearch()
}

// Watch for changes and trigger search
watch([searchQuery, selectedTag], () => {
  debouncedSearch()
})

watch([selectedStatus, dateFrom, dateTo, sortBy, sortDirection], () => {
  performSearch()
})

// Check if any filters are active
const hasActiveFilters = computed(() => {
  return searchQuery.value.trim() !== '' ||
         selectedStatus.value !== 'all' ||
         dateFrom.value !== '' ||
         dateTo.value !== '' ||
         selectedTag.value.trim() !== ''
})

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

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

// Dropdown state management for action menus
const dropdownStates = ref<Record<string, boolean>>({})

const toggleDropdown = (entryId: string) => {
  dropdownStates.value[entryId] = !dropdownStates.value[entryId]
}

const closeDropdown = (entryId: string) => {
  dropdownStates.value[entryId] = false
}

const deleteEntry = (uuid: string) => {
  closeDropdown(uuid)
  if (confirm('Are you sure you want to delete this work entry?')) {
    nextTick(() => {
      router.delete(route('work-entries.destroy', uuid))
    })
  }
}
</script>

<template>
  <AppLayout>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
        Work Logs
      </h2>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
          <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
              Work Logs
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">
              Track and manage your daily work activities
            </p>
          </div>

          <Button
            :href="route('work-entries.create')"
            :as="Link">
            <Plus class="w-4 h-4" />
            <span>New Work Log</span>
          </Button>
        </div>

        <!-- Filters -->
        <Card>
          <CardHeader>
            <div class="flex items-center justify-between">
              <CardTitle>Filter & Search</CardTitle>
              <Button
                v-if="hasActiveFilters"
                variant="outline"
                size="sm"
                @click="clearFilters"
              >
                <X class="w-4 h-4 mr-1" />
                Clear Filters
              </Button>
            </div>
          </CardHeader>
          <CardContent class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
              <!-- Search -->
              <div class="relative">
                <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4" />
                <Input
                  v-model="searchQuery"
                  placeholder="Search by title, description, or tags..."
                  class="pl-10"
                />
              </div>

              <!-- Status Filter -->
              <div class="relative">
                <Filter class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4 z-10" />
                <Select v-model="selectedStatus">
                  <SelectTrigger class="pl-10">
                    <SelectValue placeholder="All Status" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="all">All Status</SelectItem>
                    <SelectItem value="completed">Completed</SelectItem>
                    <SelectItem value="in_progress">In Progress</SelectItem>
                    <SelectItem value="draft">Draft</SelectItem>
                  </SelectContent>
                </Select>
              </div>

              <!-- Tag Filter -->
              <div class="relative">
                <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4" />
                <Input
                  v-model="selectedTag"
                  placeholder="Filter by tag..."
                  class="pl-10"
                />
              </div>

              <!-- Date From -->
              <div class="flex flex-col">
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                  Date From
                </label>
                <DatePicker
                  v-model="dateFrom"
                  placeholder="Select start date"
                  :max="dateTo || undefined"
                />
              </div>

              <!-- Date To -->
              <div class="flex flex-col">
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                  Date To
                </label>
                <DatePicker
                  v-model="dateTo"
                  placeholder="Select end date"
                  :min="dateFrom || undefined"
                />
              </div>

              <!-- Sort Options -->
              <div class="flex gap-2">
                <Select v-model="sortBy" class="flex-1">
                  <SelectTrigger>
                    <SelectValue placeholder="Sort by" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="start_date_time">Date</SelectItem>
                    <SelectItem value="work_title">Title</SelectItem>
                    <SelectItem value="status">Status</SelectItem>
                    <SelectItem value="hours_worked">Hours</SelectItem>
                    <SelectItem value="created_at">Created</SelectItem>
                  </SelectContent>
                </Select>
                <Button
                  variant="outline"
                  size="icon"
                  @click="sortDirection = sortDirection === 'asc' ? 'desc' : 'asc'"
                >
                  <span class="text-xs">{{ sortDirection === 'asc' ? '↑' : '↓' }}</span>
                </Button>
              </div>
            </div>

            <!-- Active filters display -->
            <div v-if="hasActiveFilters" class="flex flex-wrap gap-2 pt-2 border-t">
              <Badge v-if="searchQuery.trim()" variant="secondary" class="gap-1">
                Search: "{{ searchQuery }}"
                <X class="w-3 h-3 cursor-pointer" @click="searchQuery = ''" />
              </Badge>
              <Badge v-if="selectedStatus !== 'all'" variant="secondary" class="gap-1">
                Status: {{ selectedStatus.replace('_', ' ') }}
                <X class="w-3 h-3 cursor-pointer" @click="selectedStatus = 'all'" />
              </Badge>
              <Badge v-if="selectedTag.trim()" variant="secondary" class="gap-1">
                Tag: "{{ selectedTag }}"
                <X class="w-3 h-3 cursor-pointer" @click="selectedTag = ''" />
              </Badge>
              <Badge v-if="dateFrom" variant="secondary" class="gap-1">
                From: {{ formatDate(dateFrom + 'T00:00:00.000Z') }}
                <X class="w-3 h-3 cursor-pointer" @click="dateFrom = ''" />
              </Badge>
              <Badge v-if="dateTo" variant="secondary" class="gap-1">
                To: {{ formatDate(dateTo + 'T00:00:00.000Z') }}
                <X class="w-3 h-3 cursor-pointer" @click="dateTo = ''" />
              </Badge>
            </div>
          </CardContent>
        </Card>

        <!-- Work Entries List -->
        <div v-if="props.workEntries.data?.length > 0" class="space-y-4">
          <div
            v-for="entry in props.workEntries.data"
            :key="entry.uuid"
            class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6 hover:shadow-md transition-shadow duration-200"
          >
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
              <!-- Main Content -->
              <div class="flex-1">
                <div class="flex items-center gap-3 mb-2">
                  <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    {{ entry.work_title }}
                  </h3>
                  <Badge :class="getStatusColor(entry.status)">
                    {{ entry.status.replace('_', ' ').toUpperCase() }}
                  </Badge>
                </div>

                <div
                  v-html="entry.description"
                  class="text-gray-600 dark:text-gray-400 text-sm mb-3 line-clamp-2"
                />

                <div class="flex items-center gap-4 text-sm text-gray-500 dark:text-gray-400">
                  <div class="flex items-center gap-1">
                    <Calendar class="w-4 h-4" />
                    {{ formatDate(entry.start_date_time) }}
                  </div>
                  <div class="flex items-center gap-1">
                    <Clock class="w-4 h-4" />
                    {{ entry.hours_worked }}h
                  </div>
                  <div v-if="entry.tag_names?.length" class="flex items-center gap-1">
                    <span>Tags:</span>
                    <div class="flex gap-1">
                      <span
                        v-for="tag in entry.tag_names.slice(0, 2)"
                        :key="tag"
                        class="px-2 py-1 bg-gray-100 dark:bg-gray-700 text-xs rounded cursor-pointer hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
                        @click="selectedTag = tag"
                      >
                        {{ tag }}
                      </span>
                      <span v-if="entry.tag_names.length > 2" class="text-xs text-gray-400">
                        +{{ entry.tag_names.length - 2 }} more
                      </span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Actions -->
              <div class="flex items-center gap-2 lg:flex-shrink-0">
                <ModalLink :href="route('work-entries.show', entry.uuid)" as="button" class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                  <Eye class="w-4 h-4 mr-1" />
                  View
                </ModalLink>

                <ModalLink :href="route('work-entries.edit', entry.uuid)" as="button" class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                  <Edit class="w-4 h-4 mr-1" />
                  Edit
                </ModalLink>

                <!-- Simple custom dropdown instead of problematic DropdownMenu -->
                <div class="relative">
                  <button
                    @click="toggleDropdown(entry.uuid)"
                    @blur="closeDropdown(entry.uuid)"
                    class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors focus:outline-none"
                  >
                    <EllipsisVertical class="w-4 h-4" />
                  </button>

                  <!-- Dropdown Menu -->
                  <div
                    v-if="dropdownStates[entry.uuid]"
                    class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-md shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none z-50"
                  >
                    <button
                      @click="deleteEntry(entry.uuid)"
                      class="block w-full text-left px-4 py-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-950/50 transition-colors"
                    >
                      <Trash2 class="w-4 h-4 mr-2 inline" />
                      Delete Entry
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Empty State -->
        <div
          v-else
          class="text-center py-12 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700"
        >
          <CheckCircle class="w-16 h-16 text-gray-400 mx-auto mb-4" />
          <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
            {{ hasActiveFilters ? 'No matching work logs found' : 'No work logs yet' }}
          </h3>
          <p class="text-gray-600 dark:text-gray-400 mb-6">
            {{ hasActiveFilters
              ? 'Try adjusting your search or filter criteria'
              : 'Start tracking your work by creating your first work log entry'
            }}
          </p>
          <Button
            v-if="hasActiveFilters"
            variant="outline"
            @click="clearFilters"
            class="mr-3"
          >
            <X class="w-4 h-4 mr-2" />
            Clear Filters
          </Button>
          <Link
            :as="Button"
            v-if="!hasActiveFilters"
            :href="route('work-entries.create')"
          >
            <Plus class="w-4 h-4 mr-2" />
            Create First Work Log
          </Link>
        </div>

        <!-- Pagination -->
        <div
          v-if="props.workEntries?.links && props.workEntries.links.length > 3"
          class="flex justify-center"
        >
          <nav class="flex items-center space-x-2">
            <template v-for="(link, index) in props.workEntries.links" :key="index">
              <Link
                v-if="link.url"
                :href="link.url"
                :class="[
                  'px-3 py-2 text-sm font-medium rounded-md transition-colors',
                  link.active
                    ? 'bg-blue-600 text-white'
                    : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 border border-gray-300 dark:border-gray-600'
                ]"
                v-html="link.label"
              ></Link>
              <span
                v-else
                :class="[
                  'px-3 py-2 text-sm font-medium rounded-md',
                  'bg-gray-100 dark:bg-gray-700 text-gray-400 cursor-not-allowed'
                ]"
                v-html="link.label"
              ></span>
            </template>
          </nav>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

