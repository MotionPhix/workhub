<script setup lang="ts">
import { ref, reactive, computed, onMounted, nextTick } from 'vue'
import { Head, Link, router, usePoll } from '@inertiajs/vue3'
import { gsap } from 'gsap'
import {
  Users,
  Search,
  Clock,
  FileText,
  CheckCircle,
  AlertCircle,
  Eye,
  Edit,
  UserCog,
  Loader2,
  Filter,
  UserPlus,
  Download,
  MoreHorizontal,
  Activity,
  Target,
  Calendar,
  X
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

interface TeamMember {
  id: number
  name: string
  email: string
  status: string
  department?: {
    id: number
    name: string
  }
  department_id?: number
  performance_stats?: {
    total_hours: number
    total_entries: number
    reports_submitted: number
    reports_pending: number
    efficiency_score: number
    productivity_rating: number
  }
  last_activity?: string
  notes?: string
}

interface Props {
  teamMembers: {
    data: TeamMember[]
    links: Array<any>
    total: number
    per_page: number
    current_page: number
  }
  filters: {
    search?: string
    department_id?: string
    status?: string
  }
  departments: Array<{
    id: number
    name: string
  }>
  teamStats?: {
    total_members: number
    active_members: number
    on_leave: number
    inactive_members: number
    avg_hours_per_member: number
    total_work_entries: number
    pending_reports: number
  }
}

const props = defineProps<Props>()

// Initialize theme
const { isDark } = useTheme()

// Reactive filter state
const filters = reactive({
  search: props.filters.search || '',
  department_id: props.filters.department_id || 'all',
  status: props.filters.status || 'all'
})

// Edit modal state
const editingMember = ref<TeamMember | null>(null)
const processing = ref(false)
const editForm = reactive({
  name: '',
  email: '',
  department_id: 'none',
  status: 'active',
  notes: ''
})


// View mode state
const viewMode = ref<'grid' | 'list'>('grid')
const showFilters = ref(false)

// Setup polling for real-time updates
const { start: startPolling, stop: stopPolling } = usePoll(45000, {
  autoStart: true,
  keepAlive: false
})

// Computed
const teamMetrics = computed(() => [
  {
    icon: Users,
    title: 'Total Members',
    value: props.teamStats?.total_members || 0,
    subtitle: `${props.teamStats?.active_members || 0} active`,
    change: '+2 this month',
    color: 'from-blue-500 to-blue-600',
    bgColor: 'bg-blue-50 dark:bg-blue-900/20',
    iconColor: 'text-blue-600 dark:text-blue-400'
  },
  {
    icon: Clock,
    title: 'Avg Hours/Member',
    value: Math.round(props.teamStats?.avg_hours_per_member || 0),
    suffix: 'h',
    subtitle: 'This month',
    change: '+8%',
    color: 'from-purple-500 to-purple-600',
    bgColor: 'bg-purple-50 dark:bg-purple-900/20',
    iconColor: 'text-purple-600 dark:text-purple-400'
  },
  {
    icon: FileText,
    title: 'Work Entries',
    value: props.teamStats?.total_work_entries || 0,
    subtitle: 'Total submitted',
    change: '+12%',
    color: 'from-green-500 to-green-600',
    bgColor: 'bg-green-50 dark:bg-green-900/20',
    iconColor: 'text-green-600 dark:text-green-400'
  },
  {
    icon: AlertCircle,
    title: 'Pending Reports',
    value: props.teamStats?.pending_reports || 0,
    subtitle: 'Need review',
    change: 'Urgent',
    color: 'from-orange-500 to-orange-600',
    bgColor: 'bg-orange-50 dark:bg-orange-900/20',
    iconColor: 'text-orange-600 dark:text-orange-400'
  }
])

// Search debounce
let searchTimeout: NodeJS.Timeout | null = null

const getStatusColor = (status: string): string => {
  const colors = {
    'active': 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
    'inactive': 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
    'on_leave': 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
    'terminated': 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300'
  }
  return colors[status] || 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300'
}

const getPerformanceColor = (score: number): string => {
  if (score >= 90) return 'text-green-600 dark:text-green-400'
  if (score >= 75) return 'text-blue-600 dark:text-blue-400'
  if (score >= 60) return 'text-yellow-600 dark:text-yellow-400'
  return 'text-red-600 dark:text-red-400'
}

const formatLastActivity = (dateString: string): string => {
  if (!dateString) return 'Never'
  const date = new Date(dateString)
  const now = new Date()
  const diffMs = now.getTime() - date.getTime()
  const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24))

  if (diffDays === 0) return 'Today'
  if (diffDays === 1) return 'Yesterday'
  if (diffDays < 7) return `${diffDays} days ago`
  return date.toLocaleDateString()
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
    department_id: filters.department_id === 'all' ? '' : filters.department_id,
    status: filters.status === 'all' ? '' : filters.status
  }

  router.get(route('manager.team.index'), filterData, {
    preserveState: true,
    replace: true
  })
}

function prepareEditModal(member: TeamMember) {
  editingMember.value = member
  editForm.name = member.name
  editForm.email = member.email
  editForm.department_id = member.department_id ? member.department_id.toString() : 'none'
  editForm.status = member.status || 'active'
  editForm.notes = member.notes || ''
}

function closeEditModal() {
  editingMember.value = null
  editForm.name = ''
  editForm.email = ''
  editForm.department_id = 'none'
  editForm.status = 'active'
  editForm.notes = ''
}

function updateMember(close: () => void) {
  if (!editingMember.value) return

  processing.value = true

  const formData = {
    ...editForm,
    department_id: editForm.department_id === 'none' ? null : editForm.department_id
  }

  router.put(route('manager.team.update', editingMember.value.id), formData, {
    onSuccess: () => {
      closeEditModal()
      close()
    },
    onFinish: () => {
      processing.value = false
    }
  })
}


function exportTeam(format: string, close: () => void) {
  const exportData = {
    format,
    status: filters.status === 'all' ? '' : filters.status,
    department_id: filters.department_id === 'all' ? '' : filters.department_id
  }

  // Create a temporary link to download the file
  const url = route('manager.team.export', exportData)
  window.open(url, '_blank')
  close()
}

// Lifecycle hooks
onMounted(() => {
  nextTick(() => {
    const teamCards = document.querySelectorAll('.team-card')

    if (teamCards.length > 0) {
      gsap.set('.team-card', { opacity: 0, y: 20 })
      gsap.to('.team-card', {
        opacity: 1,
        y: 0,
        duration: 0.6,
        stagger: 0.1,
        ease: 'power2.out',
        delay: 0.1
      })
    }
  })
})
</script>

<template>
  <Head title="Team Members" />

  <ManagerLayout>
    <!-- Header Section -->
    <div class="my-12">
      <div class="relative z-20 flex flex-col lg:flex-row justify-between items-start lg:items-center gap-6 mb-8">
        <div>
          <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
            <Users class="w-8 h-8" />
            Team Members
          </h1>
          <p class="text-gray-600 dark:text-gray-400 mt-1">
            Manage and monitor your team members' performance and status
          </p>
        </div>

        <!-- Quick Actions -->
        <div class="grid grid-cols-2 gap-3">
          <ModalLink
            :href="route('manager.team.invite.create')"
          >
            <Button class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium inline-flex items-center gap-2">
              <UserPlus class="w-5 h-5" />
              Add Member
            </Button>
          </ModalLink>
          <ModalLink
            href="#export-modal"
          >
            <Button variant="outline" class="px-6 py-3 rounded-lg font-medium inline-flex items-center gap-2">
              <Download class="w-5 h-5" />
              Export
            </Button>
          </ModalLink>
          <Button
            variant="outline"
            class="px-6 py-3 rounded-lg font-medium inline-flex items-center gap-2"
            @click="showFilters = !showFilters">
            <Filter class="w-5 h-5" />
            Filters
          </Button>
        </div>
      </div>
    </div>

    <!-- Team Stats -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
      <StatsCard
        v-for="(metric, index) in teamMetrics"
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
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="space-y-2">
          <Label for="search">Search Members</Label>
          <div class="relative">
            <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 h-4 w-4" />
            <Input
              id="search"
              v-model="filters.search"
              @input="debounceSearch"
              placeholder="Search by name or email..."
              class="pl-10"
            />
          </div>
        </div>

        <div class="space-y-2">
          <Label for="department">Department</Label>
          <Select v-model="filters.department_id" @update:model-value="applyFilters">
            <SelectTrigger class="w-full">
              <SelectValue placeholder="All Departments" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="all">All Departments</SelectItem>
              <SelectItem
                v-for="dept in departments"
                :key="dept.id"
                :value="dept.id.toString()"
              >
                {{ dept.name }}
              </SelectItem>
            </SelectContent>
          </Select>
        </div>

        <div class="space-y-2">
          <Label for="status">Status</Label>
          <Select v-model="filters.status" @update:model-value="applyFilters">
            <SelectTrigger class="w-full">
              <SelectValue placeholder="All Statuses" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="all">All Statuses</SelectItem>
              <SelectItem value="active">Active</SelectItem>
              <SelectItem value="inactive">Inactive</SelectItem>
              <SelectItem value="on_leave">On Leave</SelectItem>
              <SelectItem value="terminated">Terminated</SelectItem>
            </SelectContent>
          </Select>
        </div>
      </div>
    </CustomCard>

    <!-- Team Members Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
      <div
        v-for="member in teamMembers.data"
        :key="member.id"
        class="team-card group relative"
      >
        <CustomCard hover>
          <div class="space-y-4">
            <!-- Member Header -->
            <div class="flex items-start justify-between">
              <div class="flex items-center gap-3">
                <UserAvatar
                  :fallback="getInitials(member.name)"
                  class="w-12 h-12"
                />
                <div class="flex-1 min-w-0">
                  <h3 class="font-semibold text-gray-900 dark:text-white truncate">
                    {{ member.name }}
                  </h3>
                  <p class="text-sm text-gray-500 dark:text-gray-400 truncate">
                    {{ member.email }}
                  </p>
                  <div class="flex items-center gap-2 mt-1">
                    <Badge
                      :class="getStatusColor(member.status)"
                      variant="secondary"
                    >
                      {{ member.status?.replace('_', ' ') || 'active' }}
                    </Badge>
                  </div>
                </div>
              </div>
              <Button variant="ghost" size="sm" class="opacity-0 group-hover:opacity-100 transition-opacity">
                <MoreHorizontal class="w-4 h-4" />
              </Button>
            </div>

            <!-- Department -->
            <div v-if="member.department" class="space-y-1">
              <div class="flex items-center gap-2">
                <Target class="w-4 h-4 text-gray-400" />
                <span class="text-sm font-medium text-gray-900 dark:text-white">
                  {{ member.department.name }}
                </span>
              </div>
            </div>

            <!-- Performance Stats -->
            <div v-if="member.performance_stats" class="space-y-3">
              <div class="grid grid-cols-2 gap-4">
                <div class="text-center p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                  <div class="text-lg font-bold text-blue-600 dark:text-blue-400">
                    {{ member.performance_stats.total_hours || 0 }}h
                  </div>
                  <div class="text-xs text-blue-700 dark:text-blue-300">Hours</div>
                </div>
                <div class="text-center p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
                  <div class="text-lg font-bold text-green-600 dark:text-green-400">
                    {{ member.performance_stats.total_entries || 0 }}
                  </div>
                  <div class="text-xs text-green-700 dark:text-green-300">Entries</div>
                </div>
              </div>

              <!-- Performance Score -->
              <div v-if="member.performance_stats.efficiency_score" class="space-y-2">
                <div class="flex justify-between items-center">
                  <span class="text-sm text-gray-600 dark:text-gray-400">Performance</span>
                  <span
                    class="text-sm font-medium"
                    :class="getPerformanceColor(member.performance_stats.efficiency_score)"
                  >
                    {{ Math.round(member.performance_stats.efficiency_score) }}%
                  </span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                  <div
                    class="h-2 rounded-full transition-all duration-300"
                    :class="{
                      'bg-green-500': member.performance_stats.efficiency_score >= 75,
                      'bg-yellow-500': member.performance_stats.efficiency_score >= 60 && member.performance_stats.efficiency_score < 75,
                      'bg-red-500': member.performance_stats.efficiency_score < 60
                    }"
                    :style="{ width: `${member.performance_stats.efficiency_score}%` }"
                  />
                </div>
              </div>

              <!-- Reports Status -->
              <div class="flex justify-between items-center text-sm">
                <span class="text-gray-600 dark:text-gray-400 flex items-center gap-1">
                  <FileText class="w-3 h-3" />
                  Reports:
                </span>
                <div class="flex items-center gap-2">
                  <span class="text-green-600 dark:text-green-400">
                    {{ member.performance_stats.reports_submitted || 0 }} ✓
                  </span>
                  <span class="text-orange-600 dark:text-orange-400">
                    {{ member.performance_stats.reports_pending || 0 }} pending
                  </span>
                </div>
              </div>
            </div>

            <!-- Last Activity -->
            <div v-if="member.last_activity" class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
              <Activity class="w-4 h-4" />
              <span>Last active: {{ formatLastActivity(member.last_activity) }}</span>
            </div>

            <!-- Action Buttons -->
            <div class="flex gap-2 pt-4 border-t border-gray-200 dark:border-gray-700">
              <Button asChild class="flex-1" size="sm">
                <Link :href="route('manager.team.show', member.id)">
                  <Eye class="h-4 w-4 mr-1" />
                  View Details
                </Link>
              </Button>

              <ModalLink
                href="#edit-member-modal"
                @click="prepareEditModal(member)">
                <Button
                  variant="outline"
                  size="sm">
                  <Edit class="h-4 w-4" />
                </Button>
              </ModalLink>
            </div>
          </div>
        </CustomCard>
      </div>
    </div>

    <!-- Empty State -->
    <div v-if="!teamMembers.data || teamMembers.data.length === 0" class="text-center py-12">
      <div class="text-gray-400 mb-4">
        <Users class="w-16 h-16 mx-auto" />
      </div>
      <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No team members found</h3>
      <p class="text-gray-500 dark:text-gray-400 mb-4">
        {{ filters.search || filters.department_id !== 'all' || filters.status !== 'all'
           ? 'Try adjusting your filters'
           : 'Start by adding your first team member' }}
      </p>
      <ModalLink
        :href="route('manager.team.invite.create')"
      >
        <Button class="inline-flex items-center gap-2">
          <UserPlus class="w-4 h-4" />
          Add Team Member
        </Button>
      </ModalLink>
    </div>

    <!-- Pagination -->
    <div v-if="teamMembers.links && teamMembers.links.length > 3" class="mt-8 flex justify-center">
      <div class="flex gap-1">
        <template v-for="link in teamMembers.links" :key="link.label">
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

    <!-- Edit Member Modal -->
    <Modal name="edit-member-modal" max-width="md" #default="{ close }">
      <div class="p-6">
        <div class="flex items-center gap-2 mb-4">
          <UserCog class="h-5 w-5" />
          <h2 class="text-lg font-semibold">Edit Team Member</h2>
        </div>

        <form @submit.prevent="updateMember(close)" class="space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="space-y-2">
              <Label for="edit-name">Name</Label>
              <Input
                id="edit-name"
                v-model="editForm.name"
                placeholder="Member name"
                required
              />
            </div>
            <div class="space-y-2">
              <Label for="edit-email">Email</Label>
              <Input
                id="edit-email"
                v-model="editForm.email"
                type="email"
                placeholder="member@example.com"
                required
              />
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="space-y-2">
              <Label for="edit-department">Department</Label>
              <Select v-model="editForm.department_id">
                <SelectTrigger>
                  <SelectValue placeholder="Select Department" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="none">No Department</SelectItem>
                  <SelectItem
                    v-for="dept in departments"
                    :key="dept.id"
                    :value="dept.id.toString()"
                  >
                    {{ dept.name }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>
            <div class="space-y-2">
              <Label for="edit-status">Status</Label>
              <Select v-model="editForm.status">
                <SelectTrigger>
                  <SelectValue placeholder="Select Status" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="active">Active</SelectItem>
                  <SelectItem value="inactive">Inactive</SelectItem>
                  <SelectItem value="on_leave">On Leave</SelectItem>
                  <SelectItem value="terminated">Terminated</SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>

          <div class="space-y-2">
            <Label for="edit-notes">Manager Notes</Label>
            <Textarea
              id="edit-notes"
              v-model="editForm.notes"
              rows="3"
              placeholder="Add any manager notes..."
            />
          </div>

          <div class="flex gap-3 pt-4">
            <Button
              type="submit"
              :disabled="processing"
              class="flex-1"
            >
              <Loader2 v-if="processing" class="h-4 w-4 mr-2 animate-spin" />
              {{ processing ? 'Updating...' : 'Update Member' }}
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

    <!-- Export Modal -->
    <Modal
      name="export-modal"
      max-width="sm"
      :close-button="false"
      padding-classes="p-0"
      panel-classes="bg-transparent"
      #default="{ close }"
    >
      <CustomCard
        title="Export Team Data"
        description="Export your team members data in the format of your choice. Current filters will be applied."
        :icon="Download"
        padding="p-6"
      >
        <template #header>
          <Button
            variant="ghost"
            size="sm"
            @click="close"
            class="text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300"
          >
            <X class="h-4 w-4" />
          </Button>
        </template>

        <div class="grid grid-cols-1 gap-3">
          <Button
            @click="exportTeam('csv', close)"
            variant="outline"
            class="justify-start"
          >
            <Download class="w-4 h-4 mr-2" />
            Export as CSV
          </Button>
          <Button
            @click="exportTeam('xlsx', close)"
            variant="outline"
            class="justify-start"
            disabled
          >
            <Download class="w-4 h-4 mr-2" />
            Export as Excel (Coming Soon)
          </Button>
          <Button
            @click="exportTeam('json', close)"
            variant="outline"
            class="justify-start"
          >
            <Download class="w-4 h-4 mr-2" />
            Export as JSON
          </Button>
        </div>

        <template #footer>
          <div class="flex justify-end">
            <Button
              type="button"
              variant="outline"
              @click="close"
            >
              Cancel
            </Button>
          </div>
        </template>
      </CustomCard>
    </Modal>
  </ManagerLayout>
</template>
