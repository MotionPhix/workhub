<script setup lang="ts">
import { defineProps, ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/layouts/AdminLayout.vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card/index.js'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Input } from '@/components/ui/input'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import EmptyState from '@/components/dashboard/EmptyState.vue'
import { UserPlus, Users, Search, Filter, Download, MoreHorizontal } from 'lucide-vue-next'
import { ModalLink } from '@inertiaui/modal-vue'
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu'

const props = defineProps({
  invitations: {
    type: Object,
    required: true
  },
  departments: {
    type: Array,
    default: () => []
  },
  roles: {
    type: Array,
    default: () => []
  },
  managers: {
    type: Array,
    default: () => []
  },
  statistics: {
    type: Object,
    default: () => ({})
  }
})

const processing = ref(false)
const searchQuery = ref('')
const statusFilter = ref('all')
const departmentFilter = ref('all')

// Debounce function for search
let searchTimeout = null

const getInitials = (name) => {
  return name.split(' ').map(word => word.charAt(0)).join('').toUpperCase()
}

const getStatusVariant = (status) => {
  const variants = {
    'pending': 'secondary',
    'accepted': 'default',
    'expired': 'destructive',
    'cancelled': 'outline',
  }
  return variants[status] || 'outline'
}

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString()
}

const resendInvitation = (id) => {
  if (processing.value) return

  processing.value = true
  router.post(route('admin.invitations.resend', id), {}, {
    onFinish: () => {
      processing.value = false
    }
  })
}

const cancelInvitation = (id) => {
  if (processing.value) return
  if (!confirm('Are you sure you want to cancel this invitation?')) return

  processing.value = true
  router.delete(route('admin.invitations.destroy', id), {
    onFinish: () => {
      processing.value = false
    }
  })
}

const handleSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    applyFilters()
  }, 300)
}

const handleFilterChange = () => {
  applyFilters()
}

const applyFilters = () => {
  const params = {}

  if (searchQuery.value) {
    params.search = searchQuery.value
  }

  if (statusFilter.value && statusFilter.value !== 'all') {
    params.status = statusFilter.value
  }

  if (departmentFilter.value && departmentFilter.value !== 'all') {
    params.department = departmentFilter.value
  }

  router.get(route('admin.invitations.index'), params, {
    preserveState: true,
    preserveScroll: true,
  })
}

const confirmCancel = (invitation) => {
  if (confirm(`Are you sure you want to cancel the invitation for ${invitation.name}?`)) {
    cancelInvitation(invitation.id)
  }
}

const exportInvitations = () => {
  // This would typically call a dedicated export endpoint
  router.get(route('admin.invitations.export'), {}, {
    preserveState: true,
  })
}
</script>

<template>
  <Head title="User Invitations" />

  <AdminLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
          User Invitations
        </h2>
        <Button asChild>
          <Link :href="route('admin.invitations.create')">
            Send New Invitation
          </Link>
        </Button>
      </div>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Search and Filters -->
        <div class="mb-6 flex flex-col sm:flex-row gap-4">
          <div class="flex-1">
            <div class="relative">
              <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 h-4 w-4" />
              <Input
                placeholder="Search by name or email..."
                class="pl-10"
                v-model="searchQuery"
                @input="handleSearch"
              />
            </div>
          </div>
          <div class="flex gap-2">
            <Select v-model="statusFilter" @update:modelValue="handleFilterChange">
              <SelectTrigger class="w-40">
                <SelectValue placeholder="All Status" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All Status</SelectItem>
                <SelectItem value="pending">Pending</SelectItem>
                <SelectItem value="accepted">Accepted</SelectItem>
                <SelectItem value="expired">Expired</SelectItem>
                <SelectItem value="cancelled">Cancelled</SelectItem>
              </SelectContent>
            </Select>

            <Select v-model="departmentFilter" @update:modelValue="handleFilterChange">
              <SelectTrigger class="w-40">
                <SelectValue placeholder="All Departments" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All Departments</SelectItem>
                <SelectItem v-for="department in departments" :key="department.uuid" :value="department.uuid">
                  {{ department.name }}
                </SelectItem>
              </SelectContent>
            </Select>

            <Button variant="outline" @click="exportInvitations">
              <Download class="mr-2 h-4 w-4" />
              Export
            </Button>
          </div>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
          <Card>
            <CardContent>
              <div class="flex items-center">
                <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                  {{ statistics.total || 0 }}
                </div>
                <div class="ml-2 text-sm text-gray-500 dark:text-gray-400">Total Invitations</div>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardContent>
              <div class="flex items-center">
                <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                  {{ statistics.accepted || 0 }}
                </div>
                <div class="ml-2 text-sm text-gray-500 dark:text-gray-400">Accepted</div>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardContent>
              <div class="flex items-center">
                <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">
                  {{ statistics.pending || 0 }}
                </div>
                <div class="ml-2 text-sm text-gray-500 dark:text-gray-400">Pending</div>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardContent>
              <div class="flex items-center">
                <div class="text-2xl font-bold text-red-600 dark:text-red-400">
                  {{ statistics.expired || 0 }}
                </div>
                <div class="ml-2 text-sm text-gray-500 dark:text-gray-400">Expired</div>
              </div>
            </CardContent>
          </Card>
        </div>        <!-- Invitations Table -->
        <Card>
          <CardContent>
            <div v-if="invitations.data && invitations.data.length > 0" class="overflow-x-auto">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Invitee</TableHead>
                    <TableHead>Status</TableHead>
                    <TableHead>Department</TableHead>
                    <TableHead>Invited</TableHead>
                    <TableHead>Expires</TableHead>
                    <TableHead>Actions</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  <TableRow v-for="invitation in invitations.data" :key="invitation.id">
                    <TableCell>
                      <div class="flex items-center">
                        <div class="shrink-0 h-10 w-10">
                          <div class="h-10 w-10 rounded-full bg-gray-300 dark:bg-gray-600 flex items-center justify-center">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                              {{ getInitials(invitation.name) }}
                            </span>
                          </div>
                        </div>
                        <div class="ml-4">
                          <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                            {{ invitation.name }}
                          </div>
                          <div class="text-sm text-gray-500 dark:text-gray-400">
                            {{ invitation.email }}
                          </div>
                        </div>
                      </div>
                    </TableCell>
                    <TableCell>
                      <Badge :variant="getStatusVariant(invitation.status)">
                        {{ invitation.status.charAt(0).toUpperCase() + invitation.status.slice(1) }}
                      </Badge>
                    </TableCell>
                    <TableCell>
                      {{ invitation.department?.name || 'N/A' }}
                    </TableCell>
                    <TableCell>
                      {{ formatDate(invitation.created_at) }}
                    </TableCell>
                    <TableCell>
                      {{ formatDate(invitation.expires_at) }}
                    </TableCell>
                    <TableCell>
                      <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                          <Button variant="ghost" size="sm">
                            <MoreHorizontal class="h-4 w-4" />
                          </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                          <DropdownMenuItem asChild>
                            <ModalLink :href="route('admin.invitations.show', invitation.id)">
                              View Details
                            </ModalLink>
                          </DropdownMenuItem>
                          <DropdownMenuItem
                            v-if="invitation.status === 'pending'"
                            @click="resendInvitation(invitation.id)"
                            :disabled="processing"
                          >
                            Resend Invitation
                          </DropdownMenuItem>
                          <DropdownMenuItem
                            v-if="invitation.status === 'pending'"
                            @click="confirmCancel(invitation)"
                            :disabled="processing"
                            class="text-red-600"
                          >
                            Cancel Invitation
                          </DropdownMenuItem>
                        </DropdownMenuContent>
                      </DropdownMenu>
                    </TableCell>
                  </TableRow>
                </TableBody>
              </Table>
            </div>

            <!-- Empty State -->
            <div v-else class="py-12">
              <EmptyState
                title="No Invitations Yet"
                description="No user invitations have been sent yet. Start by sending your first invitation to get users onboard."
                :icon="Users"
              />
              <div class="mt-6 text-center">
                <Button asChild>
                  <Link :href="route('admin.invitations.create')">
                    <UserPlus class="mr-2 h-4 w-4" />
                    Send First Invitation
                  </Link>
                </Button>
              </div>
            </div>

            <!-- Pagination -->
            <div v-if="invitations.links" class="mt-6">
              <div class="flex items-center justify-between">
                <div class="text-sm text-gray-700 dark:text-gray-300">
                  Showing {{ invitations.from }} to {{ invitations.to }} of {{ invitations.total }} results
                </div>
                <div class="flex space-x-1">
                  <template v-for="link in invitations.links" :key="link.label">
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
          </CardContent>
        </Card>
      </div>
    </div>
  </AdminLayout>
</template>
