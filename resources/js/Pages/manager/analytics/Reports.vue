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
  FileText,
  Users,
  CheckCircle,
  AlertCircle,
  Clock,
  Eye,
  Download,
  XIcon,
  MoreHorizontal
} from 'lucide-vue-next'
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu'

interface Report {
  id: number
  uuid: string
  title: string
  description: string
  status: string
  report_type: string
  created_at: string
  updated_at: string
  user: {
    id: number
    name: string
    email: string
  }
  department?: {
    id: number
    name: string
  }
}

interface TeamMember {
  id: number
  name: string
}

interface Stats {
  total_reports: number
  pending_approval: number
  approved_reports: number
  rejected_reports: number
  draft_reports: number
}

interface Props {
  reports: {
    data: Report[]
    links: any[]
    total: number
    from: number
    to: number
  }
  teamMembers: TeamMember[]
  filters: {
    search?: string
    status?: string
    report_type?: string
    user_id?: number
    start_date?: string
    end_date?: string
    sort_by?: string
    sort_direction?: string
  }
  stats: Stats
}

const props = defineProps<Props>()

// Filter state
const searchQuery = ref(props.filters.search || '')
const statusFilter = ref(props.filters.status || 'all')
const userFilter = ref(props.filters.user_id || 'all')
const startDate = ref(props.filters.start_date || '')
const endDate = ref(props.filters.end_date || '')
const sortBy = ref(props.filters.sort_by || 'created_at')
const sortDirection = ref(props.filters.sort_direction || 'desc')

// Debounce function for search
let searchTimeout: number | null = null

const applyFilters = () => {
  const params: any = {}

  if (searchQuery.value) params.search = searchQuery.value
  if (statusFilter.value && statusFilter.value !== 'all') params.status = statusFilter.value
  if (userFilter.value && userFilter.value !== 'all') params.user_id = userFilter.value
  if (startDate.value) params.start_date = startDate.value
  if (endDate.value) params.end_date = endDate.value
  if (sortBy.value) params.sort_by = sortBy.value
  if (sortDirection.value) params.sort_direction = sortDirection.value

  router.get(route('manager.reports.index'), params, {
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
  statusFilter.value = 'all'
  userFilter.value = 'all'
  startDate.value = ''
  endDate.value = ''
  sortBy.value = 'created_at'
  sortDirection.value = 'desc'

  router.get(route('manager.reports.index'))
}

const getStatusVariant = (status: string) => {
  switch (status) {
    case 'approved': return 'default'
    case 'pending': return 'secondary'
    case 'rejected': return 'destructive'
    case 'draft': return 'outline'
    default: return 'secondary'
  }
}

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString()
}

const approvalRate = computed(() => {
  if (props.stats.total_reports === 0) return 0
  return Math.round((props.stats.approved_reports / props.stats.total_reports) * 100)
})
</script>

<template>
  <Head title="Team Reports" />

  <ManagerLayout>
    <div>
      <!-- Header -->
      <div class="flex justify-between items-center mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Team Reports</h1>
          <p class="text-gray-600 dark:text-gray-300">Review and manage your team's reports</p>
        </div>
      </div>

      <!-- Statistics Cards -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <CustomCard>
          <div class="flex items-center">
            <div class="p-2 rounded-lg bg-blue-50 dark:bg-blue-900/20">
              <FileText class="w-5 h-5 text-blue-600 dark:text-blue-400" />
            </div>
            <div class="ml-3">
              <div class="text-2xl font-bold text-gray-900 dark:text-white">
                {{ stats.total_reports }}
              </div>
              <div class="text-sm text-gray-500 dark:text-gray-400">Total Reports</div>
            </div>
          </div>
        </CustomCard>

        <CustomCard>
          <div class="flex items-center">
            <div class="p-2 rounded-lg bg-yellow-50 dark:bg-yellow-900/20">
              <Clock class="w-5 h-5 text-yellow-600 dark:text-yellow-400" />
            </div>
            <div class="ml-3">
              <div class="text-2xl font-bold text-gray-900 dark:text-white">
                {{ stats.pending_approval }}
              </div>
              <div class="text-sm text-gray-500 dark:text-gray-400">Pending</div>
            </div>
          </div>
        </CustomCard>

        <CustomCard>
          <div class="flex items-center">
            <div class="p-2 rounded-lg bg-green-50 dark:bg-green-900/20">
              <CheckCircle class="w-5 h-5 text-green-600 dark:text-green-400" />
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
                {{ stats.rejected_reports }}
              </div>
              <div class="text-sm text-gray-500 dark:text-gray-400">Rejected</div>
            </div>
          </div>
        </CustomCard>
      </div>

      <!-- Filters -->
      <CustomCard
        title="Filter Reports"
        :icon="Filter"
        inline-icon
        class="mb-6"
        padding="p-4">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          <!-- Search -->
          <div class="relative md:col-span-2 lg:col-span-4">
            <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 h-4 w-4" />
            <Input
              v-model="searchQuery"
              @input="handleSearch"
              placeholder="Search reports..."
              class="pl-10"
            />
          </div>

          <!-- Status Filter -->
          <Select v-model="statusFilter" @update:model-value="applyFilters">
            <SelectTrigger class="w-full">
              <SelectValue placeholder="All Statuses" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="all">All Statuses</SelectItem>
              <SelectItem value="draft">Draft</SelectItem>
              <SelectItem value="pending">Pending</SelectItem>
              <SelectItem value="approved">Approved</SelectItem>
              <SelectItem value="rejected">Rejected</SelectItem>
            </SelectContent>
          </Select>

          <!-- Team Member Filter -->
          <Select v-model="userFilter" @update:model-value="applyFilters">
            <SelectTrigger class="w-full">
              <SelectValue placeholder="All Team Members" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="all">All Team Members</SelectItem>
              <SelectItem
                v-for="member in teamMembers"
                :key="member.id"
                :value="member.id.toString()">
                {{ member.name }}
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

        <div class="mt-4 flex justify-end">
          <Button
            size="icon"
            variant="outline"
            @click="resetFilters">
            <XIcon class="w-4 h-4" />
            <span class="sr-only">Reset Filters</span>
          </Button>
        </div>
      </CustomCard>

      <!-- Reports Table -->
      <CustomCard
        title="Reports"
        :description="`${reports.total} ${reports.total === 1 ? 'report' : 'reports'} found`"
        :icon="FileText"
        inline-icon
      >
        <div v-if="reports.data.length > 0">
          <div class="overflow-x-auto">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Employee</TableHead>
                  <TableHead>Title</TableHead>
                  <TableHead>Type</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead>Created</TableHead>
                  <TableHead class="text-right">Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <TableRow v-for="report in reports.data" :key="report.id">
                  <TableCell>
                    <div>
                      <div class="font-medium">{{ report.user.name }}</div>
                      <div class="text-sm text-gray-500">{{ report.user.email }}</div>
                    </div>
                  </TableCell>
                  <TableCell>
                    <div class="font-medium">{{ report.title }}</div>
                    <div class="text-sm text-gray-500 max-w-xs truncate">{{ report.description }}</div>
                  </TableCell>
                  <TableCell>
                    <Badge variant="outline">{{ report.report_type }}</Badge>
                  </TableCell>
                  <TableCell>
                    <Badge :variant="getStatusVariant(report.status)">
                      {{ report.status.charAt(0).toUpperCase() + report.status.slice(1) }}
                    </Badge>
                  </TableCell>
                  <TableCell>
                    {{ formatDate(report.created_at) }}
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
                          <Link :href="route('manager.reports.show', report.uuid)">
                            <Eye class="w-4 h-4 mr-2" />
                            View Details
                          </Link>
                        </DropdownMenuItem>
                        <DropdownMenuItem>
                          <Download class="w-4 h-4 mr-2" />
                          Export
                        </DropdownMenuItem>
                      </DropdownMenuContent>
                    </DropdownMenu>
                  </TableCell>
                </TableRow>
              </TableBody>
            </Table>
          </div>

          <!-- Pagination -->
          <div v-if="reports.links" class="mt-6">
            <div class="flex items-center justify-between">
              <div class="text-sm text-gray-700 dark:text-gray-300">
                Showing {{ reports.from }} to {{ reports.to }} of {{ reports.total }} results
              </div>
              <div class="flex space-x-1">
                <template v-for="link in reports.links" :key="link.label">
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
          title="No Reports Found"
          description="No reports match your current filters, or your team hasn't submitted any reports yet."
          :icon="FileText"
        />
      </CustomCard>
    </div>
  </ManagerLayout>
</template>
