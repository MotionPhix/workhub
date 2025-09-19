<script setup lang="ts">
import { ref, computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import ManagerLayout from '@/layouts/ManagerLayout.vue'
import CustomCard from '@/components/CustomCard.vue'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Input } from '@/components/ui/input'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import EmptyState from '@/components/EmptyState.vue'
import DatePicker from '@/components/DatePicker.vue'
import {
  Search,
  Filter,
  Clock,
  Users,
  CheckCircle,
  AlertCircle,
  TrendingUp,
  TrendingDown,
  Minus,
  Eye,
  Edit,
  MoreHorizontal
} from 'lucide-vue-next'
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu'

interface WorkEntry {
  id: number
  work_date: string
  hours: number
  description: string
  is_approved: boolean
  manager_notes?: string
  user: {
    id: number
    name: string
    email: string
  }
  project: {
    id: number
    name: string
    description?: string
  }
}

interface TeamMember {
  id: number
  name: string
}

interface Project {
  id: number
  name: string
}

interface Stats {
  total_entries: number
  total_hours: number
  average_hours_per_entry: number
  approved_entries: number
  pending_approval: number
  unique_projects: number
  most_productive_day: {
    date: string | null
    hours: number
  }
}

interface Props {
  workEntries: {
    data: WorkEntry[]
    links: any[]
    total: number
    from: number
    to: number
  }
  teamMembers: TeamMember[]
  projects: Project[]
  filters: {
    search?: string
    user_id?: number
    project_id?: number
    start_date?: string
    end_date?: string
    min_hours?: number
    max_hours?: number
    sort_by?: string
    sort_direction?: string
  }
  stats: Stats
}

const props = defineProps<Props>()

// Filter state
const searchQuery = ref(props.filters.search || '')
const userFilter = ref(props.filters.user_id || 'all')
const projectFilter = ref(props.filters.project_id || 'all')
const startDate = ref(props.filters.start_date || '')
const endDate = ref(props.filters.end_date || '')
const minHours = ref(props.filters.min_hours || '')
const maxHours = ref(props.filters.max_hours || '')
const sortBy = ref(props.filters.sort_by || 'date')
const sortDirection = ref(props.filters.sort_direction || 'desc')

// Debounce function for search
let searchTimeout: number | null = null

const applyFilters = () => {
  const params: any = {}

  if (searchQuery.value) params.search = searchQuery.value
  if (userFilter.value && userFilter.value !== 'all') params.user_id = userFilter.value
  if (projectFilter.value && projectFilter.value !== 'all') params.project_id = projectFilter.value
  if (startDate.value) params.start_date = startDate.value
  if (endDate.value) params.end_date = endDate.value
  if (minHours.value) params.min_hours = minHours.value
  if (maxHours.value) params.max_hours = maxHours.value
  if (sortBy.value) params.sort_by = sortBy.value
  if (sortDirection.value) params.sort_direction = sortDirection.value

  router.get(route('manager.team.work-entries.index'), params, {
    preserveState: true,
    preserveScroll: true,
  })
}

const handleSearch = () => {
  if (searchTimeout) clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    applyFilters()
  }, 300)
}

const resetFilters = () => {
  searchQuery.value = ''
  userFilter.value = 'all'
  projectFilter.value = 'all'
  startDate.value = ''
  endDate.value = ''
  minHours.value = ''
  maxHours.value = ''
  sortBy.value = 'date'
  sortDirection.value = 'desc'

  router.get(route('manager.team.work-entries.index'))
}

const getStatusVariant = (isApproved: boolean) => {
  return isApproved ? 'default' : 'secondary'
}

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString()
}

const formatHours = (hours: number) => {
  return `${hours}h`
}

const approvalRate = computed(() => {
  if (props.stats.total_entries === 0) return 0
  return Math.round((props.stats.approved_entries / props.stats.total_entries) * 100)
})
</script>

<template>
  <Head title="Team Work Entries" />

  <ManagerLayout>
    <div class="py-6">
      <!-- Header -->
      <div class="flex justify-between items-center mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Team Work Entries</h1>
          <p class="text-gray-600 dark:text-gray-300">Review and manage your team's work entries</p>
        </div>
      </div>

      <!-- Statistics Cards -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <CustomCard>
          <div class="flex items-center">
            <div class="p-2 rounded-lg bg-blue-50 dark:bg-blue-900/20">
              <Clock class="w-5 h-5 text-blue-600 dark:text-blue-400" />
            </div>
            <div class="ml-3">
              <div class="text-2xl font-bold text-gray-900 dark:text-white">
                {{ stats.total_hours }}h
              </div>
              <div class="text-sm text-gray-500 dark:text-gray-400">Total Hours</div>
            </div>
          </div>
        </CustomCard>

        <CustomCard>
          <div class="flex items-center">
            <div class="p-2 rounded-lg bg-green-50 dark:bg-green-900/20">
              <Users class="w-5 h-5 text-green-600 dark:text-green-400" />
            </div>
            <div class="ml-3">
              <div class="text-2xl font-bold text-gray-900 dark:text-white">
                {{ stats.total_entries }}
              </div>
              <div class="text-sm text-gray-500 dark:text-gray-400">Total Entries</div>
            </div>
          </div>
        </CustomCard>

        <CustomCard>
          <div class="flex items-center">
            <div class="p-2 rounded-lg bg-yellow-50 dark:bg-yellow-900/20">
              <CheckCircle class="w-5 h-5 text-yellow-600 dark:text-yellow-400" />
            </div>
            <div class="ml-3">
              <div class="text-2xl font-bold text-gray-900 dark:text-white">
                {{ approvalRate }}%
              </div>
              <div class="text-sm text-gray-500 dark:text-gray-400">Approved</div>
            </div>
          </div>
        </CustomCard>

        <CustomCard>
          <div class="flex items-center">
            <div class="p-2 rounded-lg bg-red-50 dark:bg-red-900/20">
              <AlertCircle class="w-5 h-5 text-red-600 dark:text-red-400" />
            </div>
            <div class="ml-3">
              <div class="text-2xl font-bold text-gray-900 dark:text-white">
                {{ stats.pending_approval }}
              </div>
              <div class="text-sm text-gray-500 dark:text-gray-400">Pending</div>
            </div>
          </div>
        </CustomCard>
      </div>

      <!-- Filters -->
      <CustomCard
        title="Filter Work Entries"
        :icon="Filter"
        inline-icon
        class="mb-6"
        padding="p-4"
      >
        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4">
          <!-- Search -->
          <div class="relative">
            <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 h-4 w-4" />
            <Input
              v-model="searchQuery"
              @input="handleSearch"
              placeholder="Search entries..."
              class="pl-10"
            />
          </div>

          <!-- Team Member Filter -->
          <Select v-model="userFilter" @update:model-value="applyFilters">
            <SelectTrigger>
              <SelectValue placeholder="All Team Members" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="all">All Team Members</SelectItem>
              <SelectItem
                v-for="member in teamMembers"
                :key="member.id"
                :value="member.id.toString()"
              >
                {{ member.name }}
              </SelectItem>
            </SelectContent>
          </Select>

          <!-- Project Filter -->
          <Select v-model="projectFilter" @update:model-value="applyFilters">
            <SelectTrigger>
              <SelectValue placeholder="All Projects" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="all">All Projects</SelectItem>
              <SelectItem
                v-for="project in projects"
                :key="project.id"
                :value="project.id.toString()"
              >
                {{ project.name }}
              </SelectItem>
            </SelectContent>
          </Select>

          <!-- Date Range -->
          <div class="flex gap-2">
            <DatePicker
              v-model="startDate"
              @update:model-value="applyFilters"
              placeholder="Start date"
              class="flex-1"
            />
            <DatePicker
              v-model="endDate"
              @update:model-value="applyFilters"
              placeholder="End date"
              :min="startDate || undefined"
              class="flex-1"
            />
          </div>
        </div>

        <div class="mt-4 flex justify-between">
          <div class="flex gap-2">
            <Input
              v-model="minHours"
              @input="handleSearch"
              type="number"
              step="0.25"
              min="0"
              placeholder="Min hours"
              class="w-24"
            />
            <Input
              v-model="maxHours"
              @input="handleSearch"
              type="number"
              step="0.25"
              min="0"
              placeholder="Max hours"
              class="w-24"
            />
          </div>

          <Button variant="outline" @click="resetFilters">
            Reset Filters
          </Button>
        </div>
      </CustomCard>

      <!-- Work Entries Table -->
      <CustomCard
        title="Work Entries"
        :description="`${workEntries.total} ${workEntries.total === 1 ? 'entry' : 'entries'} found`"
        :icon="Clock"
        inline-icon
      >
        <div v-if="workEntries.data.length > 0">
          <div class="overflow-x-auto">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Employee</TableHead>
                  <TableHead>Project</TableHead>
                  <TableHead>Date</TableHead>
                  <TableHead>Hours</TableHead>
                  <TableHead>Description</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead class="text-right">Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <TableRow v-for="entry in workEntries.data" :key="entry.id">
                  <TableCell>
                    <div>
                      <div class="font-medium">{{ entry.user.name }}</div>
                      <div class="text-sm text-gray-500">{{ entry.user.email }}</div>
                    </div>
                  </TableCell>
                  <TableCell>
                    <div class="font-medium">{{ entry.project.name }}</div>
                  </TableCell>
                  <TableCell>
                    {{ formatDate(entry.work_date) }}
                  </TableCell>
                  <TableCell>
                    <Badge variant="outline">
                      {{ formatHours(entry.hours) }}
                    </Badge>
                  </TableCell>
                  <TableCell>
                    <div class="max-w-xs truncate">
                      {{ entry.description }}
                    </div>
                  </TableCell>
                  <TableCell>
                    <Badge :variant="getStatusVariant(entry.is_approved)">
                      {{ entry.is_approved ? 'Approved' : 'Pending' }}
                    </Badge>
                  </TableCell>
                  <TableCell class="text-right">
                    <DropdownMenu>
                      <DropdownMenuTrigger as-child>
                        <Button variant="ghost" size="sm">
                          <MoreHorizontal class="w-4 h-4" />
                        </Button>
                      </DropdownMenuTrigger>
                      <DropdownMenuContent align="end">
                        <DropdownMenuItem as-child>
                          <Link :href="route('manager.team.work-entries.show', entry.id)">
                            <Eye class="w-4 h-4 mr-2" />
                            View Details
                          </Link>
                        </DropdownMenuItem>
                        <DropdownMenuItem
                          v-if="!entry.is_approved"
                          @click="router.post(route('manager.team.work-entries.approve', entry.id))"
                        >
                          <CheckCircle class="w-4 h-4 mr-2" />
                          Approve
                        </DropdownMenuItem>
                      </DropdownMenuContent>
                    </DropdownMenu>
                  </TableCell>
                </TableRow>
              </TableBody>
            </Table>
          </div>

          <!-- Pagination -->
          <div v-if="workEntries.links" class="mt-6">
            <div class="flex items-center justify-between">
              <div class="text-sm text-gray-700 dark:text-gray-300">
                Showing {{ workEntries.from }} to {{ workEntries.to }} of {{ workEntries.total }} results
              </div>
              <div class="flex space-x-1">
                <template v-for="link in workEntries.links" :key="link.label">
                  <Link
                    v-if="link.url"
                    :href="link.url"
                    v-html="link.label"
                    :class="[
                      'px-3 py-2 text-sm leading-4 border rounded hover:bg-gray-100 dark:hover:bg-gray-700',
                      link.active
                        ? 'bg-blue-500 border-blue-500 text-white'
                        : 'bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300'
                    ]"
                  />
                  <span
                    v-else
                    v-html="link.label"
                    class="px-3 py-2 text-sm leading-4 border rounded bg-gray-100 dark:bg-gray-700 border-gray-300 dark:border-gray-600 text-gray-400 dark:text-gray-500 cursor-not-allowed"
                  />
                </template>
              </div>
            </div>
          </div>
        </div>

        <!-- Empty State -->
        <EmptyState
          v-else
          title="No Work Entries Found"
          description="No work entries match your current filters, or your team hasn't logged any work yet."
          :icon="Clock"
        />
      </CustomCard>
    </div>
  </ManagerLayout>
</template>