<script setup lang="ts">
import { ref, reactive, computed, onMounted, nextTick } from 'vue'
import { Head, Link, router, usePoll } from '@inertiajs/vue3'
import { gsap } from 'gsap'
import {
  FileText,
  Search,
  Filter,
  Check,
  X,
  Eye,
  Download,
  Calendar,
  User,
  BarChart3,
  Clock,
  CheckCircle,
  AlertCircle,
  Users,
  TrendingUp,
  Loader2,
  MessageSquare,
  MoreHorizontal
} from 'lucide-vue-next'
import ManagerLayout from '@/layouts/ManagerLayout.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Badge } from '@/components/ui/badge'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Textarea } from '@/components/ui/textarea'
import { Modal, ModalLink } from '@inertiaui/modal-vue'
import CustomCard from '@/components/CustomCard.vue'
import StatsCard from '@/components/StatsCard.vue'
import UserAvatar from '@/components/UserAvatar.vue'
import { getInitials } from '@/lib/stringUtils'
import { useTheme } from '@/composables/useTheme'

interface Report {
  id: number
  title: string
  description?: string
  status: string
  report_type?: string
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
  email: string
}

interface Props {
  reports: {
    data: Report[]
    links: Array<any>
    total: number
    per_page: number
    current_page: number
  }
  teamMembers: TeamMember[]
  filters: {
    search?: string
    status?: string
    user_id?: string
    start_date?: string
    end_date?: string
  }
  stats: {
    total_reports: number
    pending_reports: number
    approved_reports: number
    rejected_reports: number
    approval_rate: number
    avg_review_time: number
  }
}

const props = defineProps<Props>()

// Initialize theme
const { isDark } = useTheme()

// Reactive filter state
const filters = reactive({
  search: props.filters.search || '',
  status: props.filters.status || '',
  user_id: props.filters.user_id || '',
  start_date: props.filters.start_date || '',
  end_date: props.filters.end_date || ''
})

// Modal and processing state
const showRejectModal = ref(false)
const rejectingReport = ref<Report | null>(null)
const processing = ref(false)
const bulkProcessing = ref(false)
const selectedReports = ref<number[]>([])
const showFilters = ref(false)

const rejectForm = reactive({
  reason: ''
})

// Setup polling for real-time updates
const { start: startPolling, stop: stopPolling } = usePoll(30000, {
  autoStart: true,
  keepAlive: false
})

// Computed
const reportMetrics = computed(() => [
  {
    icon: FileText,
    title: 'Total Reports',
    value: props.stats.total_reports || 0,
    subtitle: 'All time',
    change: `+${Math.round((props.stats.pending_reports / (props.stats.total_reports || 1)) * 100)}% pending`,
    color: 'from-blue-500 to-blue-600',
    bgColor: 'bg-blue-50 dark:bg-blue-900/20',
    iconColor: 'text-blue-600 dark:text-blue-400'
  },
  {
    icon: Clock,
    title: 'Pending Review',
    value: props.stats.pending_reports || 0,
    subtitle: 'Need attention',
    change: 'Urgent',
    color: 'from-orange-500 to-orange-600',
    bgColor: 'bg-orange-50 dark:bg-orange-900/20',
    iconColor: 'text-orange-600 dark:text-orange-400'
  },
  {
    icon: CheckCircle,
    title: 'Approved',
    value: props.stats.approved_reports || 0,
    subtitle: 'This month',
    change: '+15%',
    color: 'from-green-500 to-green-600',
    bgColor: 'bg-green-50 dark:bg-green-900/20',
    iconColor: 'text-green-600 dark:text-green-400'
  },
  {
    icon: TrendingUp,
    title: 'Approval Rate',
    value: Math.round(props.stats.approval_rate || 0),
    suffix: '%',
    subtitle: 'Team average',
    change: '+5.2%',
    color: 'from-purple-500 to-purple-600',
    bgColor: 'bg-purple-50 dark:bg-purple-900/20',
    iconColor: 'text-purple-600 dark:text-purple-400'
  }
])

const filteredReports = computed(() => {
  return props.reports.data || []
})

const allSelected = computed(() => {
  return filteredReports.value.length > 0 && selectedReports.value.length === filteredReports.value.length
})

const someSelected = computed(() => {
  return selectedReports.value.length > 0 && selectedReports.value.length < filteredReports.value.length
})

// Search debounce
let searchTimeout: NodeJS.Timeout | null = null

const getStatusColor = (status: string): string => {
  const colors = {
    'approved': 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
    'rejected': 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
    'pending': 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
    'draft': 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300',
    'sent': 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300'
  }
  return colors[status] || 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300'
}

const formatDate = (dateString: string): string => {
  try {
    return new Date(dateString).toLocaleDateString('en-US', {
      month: 'short',
      day: 'numeric',
      year: 'numeric'
    })
  } catch {
    return dateString
  }
}

const formatDateTime = (dateString: string): string => {
  try {
    return new Date(dateString).toLocaleDateString('en-US', {
      month: 'short',
      day: 'numeric',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    })
  } catch {
    return dateString
  }
}

function debounceSearch() {
  if (searchTimeout) clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    applyFilters()
  }, 300)
}

function applyFilters() {
  const filterData = {
    search: filters.search,
    status: filters.status,
    user_id: filters.user_id,
    start_date: filters.start_date,
    end_date: filters.end_date
  }

  router.get(route('manager.team-reports.index'), filterData, {
    preserveState: true,
    replace: true
  })
}

function quickApprove(reportId: number) {
  processing.value = true
  router.post(route('manager.team-reports.approve', reportId), {}, {
    onSuccess: () => {
      // Remove from selected if it was selected
      selectedReports.value = selectedReports.value.filter(id => id !== reportId)
    },
    onFinish: () => {
      processing.value = false
    }
  })
}

function openRejectModal(report: Report) {
  rejectingReport.value = report
  rejectForm.reason = ''
  showRejectModal.value = true
}

function closeRejectModal() {
  showRejectModal.value = false
  rejectingReport.value = null
  rejectForm.reason = ''
}

function rejectReport() {
  if (!rejectingReport.value) return

  processing.value = true

  router.post(route('manager.team-reports.reject', rejectingReport.value.id), rejectForm, {
    onSuccess: () => {
      closeRejectModal()
      // Remove from selected if it was selected
      selectedReports.value = selectedReports.value.filter(id => id !== rejectingReport.value?.id)
    },
    onFinish: () => {
      processing.value = false
    }
  })
}

function toggleSelectAll() {
  if (allSelected.value) {
    selectedReports.value = []
  } else {
    selectedReports.value = filteredReports.value.map(report => report.id)
  }
}

function toggleSelectReport(reportId: number) {
  const index = selectedReports.value.indexOf(reportId)
  if (index > -1) {
    selectedReports.value.splice(index, 1)
  } else {
    selectedReports.value.push(reportId)
  }
}

function bulkApprove() {
  if (selectedReports.value.length === 0) return

  bulkProcessing.value = true
  router.post(route('manager.team-reports.bulk-approve'), {
    report_ids: selectedReports.value
  }, {
    onSuccess: () => {
      selectedReports.value = []
    },
    onFinish: () => {
      bulkProcessing.value = false
    }
  })
}

// Lifecycle hooks
onMounted(() => {
  nextTick(() => {
    gsap.set('.report-card', { opacity: 0, y: 20 })
    gsap.to('.report-card', {
      opacity: 1,
      y: 0,
      duration: 0.6,
      stagger: 0.05,
      ease: 'power2.out',
      delay: 0.1
    })
  })
})
</script>

<template>
  <Head title="Team Reports" />

  <ManagerLayout>
    <!-- Header Section -->
    <div class="mb-8">
      <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-6 mb-6">
        <div>
          <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
            <FileText class="w-8 h-8" />
            Team Reports
          </h1>
          <p class="text-gray-600 dark:text-gray-400 mt-1">
            Review and manage your team's reports and submissions
          </p>
        </div>

        <!-- Quick Actions -->
        <div class="flex flex-wrap gap-3">
          <Button
            v-if="selectedReports.length > 0"
            @click="bulkApprove"
            :disabled="bulkProcessing"
            class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-medium inline-flex items-center gap-2"
          >
            <Loader2 v-if="bulkProcessing" class="w-4 h-4 animate-spin" />
            <Check v-else class="w-5 h-5" />
            Approve {{ selectedReports.length }} Report{{ selectedReports.length !== 1 ? 's' : '' }}
          </Button>
          <Button variant="outline" class="px-6 py-3 rounded-lg font-medium inline-flex items-center gap-2">
            <Download class="w-5 h-5" />
            Export
          </Button>
          <Button
            variant="outline"
            class="px-6 py-3 rounded-lg font-medium inline-flex items-center gap-2"
            @click="showFilters = !showFilters"
          >
            <Filter class="w-5 h-5" />
            Filters
          </Button>
        </div>
      </div>
    </div>

    <!-- Report Stats -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
      <StatsCard
        v-for="(metric, index) in reportMetrics"
        :key="index"
        :icon="metric.icon"
        :title="metric.title"
        :value="metric.value"
        :suffix="metric.suffix"
        :subtitle="metric.subtitle"
        :change="metric.change"
        :color="metric.color"
        :bg-color="metric.bgColor"
        :icon-color="metric.iconColor"
        :clickable="false"
      />
    </div>

    <!-- Filters Section -->
    <CustomCard v-if="showFilters" class="mb-8" title="Filters" :icon="Filter">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
        <div class="space-y-2">
          <Label for="search">Search Reports</Label>
          <div class="relative">
            <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 h-4 w-4" />
            <Input
              id="search"
              v-model="filters.search"
              @input="debounceSearch"
              placeholder="Search reports..."
              class="pl-10"
            />
          </div>
        </div>

        <div class="space-y-2">
          <Label for="status">Status</Label>
          <Select v-model="filters.status" @update:model-value="applyFilters">
            <SelectTrigger>
              <SelectValue placeholder="All Statuses" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="">All Statuses</SelectItem>
              <SelectItem value="draft">Draft</SelectItem>
              <SelectItem value="pending">Pending</SelectItem>
              <SelectItem value="approved">Approved</SelectItem>
              <SelectItem value="rejected">Rejected</SelectItem>
              <SelectItem value="sent">Sent</SelectItem>
            </SelectContent>
          </Select>
        </div>

        <div class="space-y-2">
          <Label for="user">Team Member</Label>
          <Select v-model="filters.user_id" @update:model-value="applyFilters">
            <SelectTrigger>
              <SelectValue placeholder="All Members" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="">All Members</SelectItem>
              <SelectItem
                v-for="member in teamMembers"
                :key="member.id"
                :value="member.id.toString()"
              >
                {{ member.name }}
              </SelectItem>
            </SelectContent>
          </Select>
        </div>

        <div class="space-y-2">
          <Label for="start_date">Start Date</Label>
          <Input
            id="start_date"
            v-model="filters.start_date"
            @change="applyFilters"
            type="date"
          />
        </div>

        <div class="space-y-2">
          <Label for="end_date">End Date</Label>
          <Input
            id="end_date"
            v-model="filters.end_date"
            @change="applyFilters"
            type="date"
          />
        </div>
      </div>
    </CustomCard>

    <!-- Reports List -->
    <CustomCard>
      <!-- Bulk Actions Header -->
      <div v-if="filteredReports.length > 0" class="flex items-center justify-between mb-6 pb-4 border-b border-gray-200 dark:border-gray-700">
        <div class="flex items-center gap-4">
          <label class="flex items-center gap-2 cursor-pointer">
            <input
              type="checkbox"
              :checked="allSelected"
              :indeterminate="someSelected"
              @change="toggleSelectAll"
              class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
            />
            <span class="text-sm text-gray-600 dark:text-gray-400">
              {{ selectedReports.length > 0 ? `${selectedReports.length} selected` : 'Select all' }}
            </span>
          </label>
        </div>
        <div class="text-sm text-gray-500 dark:text-gray-400">
          {{ reports.total }} total reports
        </div>
      </div>

      <!-- Reports Grid -->
      <div class="space-y-4">
        <div
          v-for="report in filteredReports"
          :key="report.id"
          class="report-card group relative"
        >
          <div class="flex items-start gap-4 p-6 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 hover:shadow-md transition-all duration-200">
            <!-- Checkbox -->
            <div class="flex items-start pt-1">
              <input
                type="checkbox"
                :checked="selectedReports.includes(report.id)"
                @change="toggleSelectReport(report.id)"
                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
              />
            </div>

            <!-- Report Content -->
            <div class="flex-1 min-w-0">
              <div class="flex items-start justify-between mb-3">
                <div class="flex-1 min-w-0">
                  <h3 class="font-semibold text-gray-900 dark:text-white truncate">
                    {{ report.title }}
                  </h3>
                  <p v-if="report.description" class="text-sm text-gray-600 dark:text-gray-400 mt-1 line-clamp-2">
                    {{ report.description }}
                  </p>
                </div>
                <div class="flex items-center gap-3 ml-4">
                  <Badge
                    :class="getStatusColor(report.status)"
                    variant="secondary"
                  >
                    {{ report.status || 'draft' }}
                  </Badge>
                  <Button variant="ghost" size="sm" class="opacity-0 group-hover:opacity-100 transition-opacity">
                    <MoreHorizontal class="w-4 h-4" />
                  </Button>
                </div>
              </div>

              <!-- Report Meta -->
              <div class="flex items-center gap-6 text-sm text-gray-500 dark:text-gray-400 mb-4">
                <div class="flex items-center gap-2">
                  <UserAvatar
                    :fallback="getInitials(report.user.name)"
                    class="w-5 h-5"
                  />
                  <span>{{ report.user.name }}</span>
                </div>
                <div class="flex items-center gap-1">
                  <Calendar class="w-4 h-4" />
                  <span>{{ formatDate(report.created_at) }}</span>
                </div>
                <div v-if="report.report_type" class="flex items-center gap-1">
                  <FileText class="w-4 h-4" />
                  <span>{{ report.report_type }}</span>
                </div>
              </div>

              <!-- Actions -->
              <div class="flex items-center gap-3">
                <Button asChild size="sm" variant="outline">
                  <Link :href="route('manager.team-reports.show', report.id)">
                    <Eye class="h-4 w-4 mr-1" />
                    View Details
                  </Link>
                </Button>

                <Button
                  v-if="report.status === 'pending'"
                  @click="quickApprove(report.id)"
                  :disabled="processing"
                  size="sm"
                  class="bg-green-600 hover:bg-green-700 text-white"
                >
                  <Loader2 v-if="processing" class="h-4 w-4 mr-1 animate-spin" />
                  <Check v-else class="h-4 w-4 mr-1" />
                  Approve
                </Button>

                <Button
                  v-if="report.status === 'pending'"
                  @click="openRejectModal(report)"
                  size="sm"
                  variant="destructive"
                >
                  <X class="h-4 w-4 mr-1" />
                  Reject
                </Button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-if="!filteredReports.length" class="text-center py-12">
        <div class="text-gray-400 mb-4">
          <FileText class="w-16 h-16 mx-auto" />
        </div>
        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No reports found</h3>
        <p class="text-gray-500 dark:text-gray-400 mb-4">
          {{ filters.search || filters.status || filters.user_id
             ? 'Try adjusting your filters to see more reports'
             : 'Reports will appear here when team members submit them' }}
        </p>
      </div>

      <!-- Pagination -->
      <div v-if="reports.links && reports.links.length > 3" class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
        <div class="flex justify-center">
          <div class="flex gap-1">
            <template v-for="link in reports.links" :key="link.label">
              <Button
                v-if="link.url"
                :variant="link.active ? 'default' : 'outline'"
                size="sm"
                asChild
              >
                <Link :href="link.url" v-html="link.label" />
              </Button>
              <Button
                v-else
                variant="outline"
                size="sm"
                disabled
                v-html="link.label"
              />
            </template>
          </div>
        </div>
      </div>
    </CustomCard>

    <!-- Reject Modal -->
    <Modal name="reject-report-modal" max-width="md" #default="{ close }">
      <div class="p-6">
        <div class="flex items-center gap-2 mb-4">
          <MessageSquare class="h-5 w-5 text-red-600" />
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Reject Report</h2>
        </div>

        <div v-if="rejectingReport" class="mb-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
          <h3 class="font-medium text-gray-900 dark:text-white">{{ rejectingReport.title }}</h3>
          <p class="text-sm text-gray-600 dark:text-gray-400">by {{ rejectingReport.user.name }}</p>
        </div>

        <form @submit.prevent="rejectReport(); close()" class="space-y-4">
          <div class="space-y-2">
            <Label for="reason">Reason for rejection</Label>
            <Textarea
              id="reason"
              v-model="rejectForm.reason"
              rows="4"
              required
              placeholder="Please provide a clear reason for rejecting this report..."
            />
          </div>

          <div class="flex gap-3 pt-4">
            <Button
              type="submit"
              :disabled="processing"
              variant="destructive"
              class="flex-1"
            >
              <Loader2 v-if="processing" class="h-4 w-4 mr-2 animate-spin" />
              {{ processing ? 'Rejecting...' : 'Reject Report' }}
            </Button>
            <Button
              type="button"
              variant="outline"
              @click="close"
              class="flex-1"
            >
              Cancel
            </Button>
          </div>
        </form>
      </div>
    </Modal>

    <!-- Use ModalLink to open reject modal -->
    <ModalLink
      v-if="showRejectModal"
      href="#reject-report-modal"
      @click="showRejectModal = false"
      style="display: none;"
    />
  </ManagerLayout>
</template>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>