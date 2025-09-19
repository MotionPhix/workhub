<script setup lang="ts">
import {defineProps, ref} from 'vue'
import {Head, Link, router} from '@inertiajs/vue3'
import ManagerLayout from '@/layouts/ManagerLayout.vue'
import CustomCard from '@/components/CustomCard.vue'
import {Button} from '@/components/ui/button'
import {Badge} from '@/components/ui/badge'
import {Table, TableBody, TableCell, TableHead, TableHeader, TableRow} from '@/components/ui/table'
import {Input} from '@/components/ui/input'
import {Select, SelectContent, SelectItem, SelectTrigger, SelectValue} from '@/components/ui/select'
import EmptyState from '@/components/EmptyState.vue'
import {UserPlus, Mail, Search, Filter, RefreshCw, X, MoreHorizontal} from 'lucide-vue-next'
import {ModalLink} from '@inertiaui/modal-vue'
import {DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger} from '@/components/ui/dropdown-menu'
import {toast} from 'vue-sonner'

const props = defineProps({
  invitations: {
    type: Object,
    required: true
  },
  filters: {
    type: Object,
    default: () => ({})
  }
})

const processing = ref(false)
const searchQuery = ref(props.filters.search || '')
const statusFilter = ref(props.filters.status || 'all')

// Debounce function for search
let searchTimeout = null

const search = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    router.get(route('manager.invitations.index'), {
      search: searchQuery.value || undefined,
      status: statusFilter.value !== 'all' ? statusFilter.value : undefined
    }, {
      preserveState: true,
      replace: true
    })
  }, 300)
}

const resetFilters = () => {
  searchQuery.value = ''
  statusFilter.value = 'all'
  router.get(route('manager.invitations.index'))
}

const getStatusColor = (status) => {
  switch (status) {
    case 'pending':
      return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200'
    case 'accepted':
      return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
    case 'declined':
      return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'
    case 'expired':
      return 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200'
    default:
      return 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200'
  }
}

const isExpired = (invitation) => {
  return new Date(invitation.expires_at) < new Date()
}

const resendInvitation = (invitation) => {
  if (processing.value) return

  processing.value = true
  router.post(route('manager.team.invitations.resend', invitation.id), {}, {
    onSuccess: () => {
      toast.success('Invitation resent successfully')
    },
    onError: () => {
      toast.error('Failed to resend invitation')
    },
    onFinish: () => {
      processing.value = false
    }
  })
}

const cancelInvitation = (invitation) => {
  if (processing.value) return

  if (!confirm('Are you sure you want to cancel this invitation?')) {
    return
  }

  processing.value = true
  router.delete(route('manager.team.invitations.cancel', invitation.id), {
    onSuccess: () => {
      toast.success('Invitation cancelled successfully')
    },
    onError: () => {
      toast.error('Failed to cancel invitation')
    },
    onFinish: () => {
      processing.value = false
    }
  })
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}
</script>

<template>
  <Head title="Team Invitations"/>

  <ManagerLayout>
    <div class="py-6">
      <!-- Header -->
      <div class="flex justify-between items-center mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Team Invitations</h1>
          <p class="text-gray-600 dark:text-gray-300">Manage invitations sent to potential team members</p>
        </div>

        <ModalLink :href="route('manager.team.invite.create')">
          <Button>
            <UserPlus class="w-4 h-4 mr-2"/>
            Invite Member
          </Button>
        </ModalLink>
      </div>

      <!-- Filters -->
      <CustomCard
        title="Filter Invitations"
        :icon="Filter"
        inline-icon
        class="mb-6"
        padding="p-4">
        <div class="flex flex-col md:flex-row gap-4">
          <!-- Search -->
          <div class="flex-1">
            <div class="relative">
              <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 h-4 w-4"/>
              <Input
                v-model="searchQuery"
                @input="search"
                placeholder="Search by name or email..."
                class="pl-10"
              />
            </div>
          </div>

          <!-- Status Filter -->
          <div class="w-full md:w-48">
            <Select v-model="statusFilter" @update:model-value="search">
              <SelectTrigger class="w-full">
                <SelectValue placeholder="Filter by status"/>
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All Statuses</SelectItem>
                <SelectItem value="pending">Pending</SelectItem>
                <SelectItem value="accepted">Accepted</SelectItem>
                <SelectItem value="declined">Declined</SelectItem>
                <SelectItem value="expired">Expired</SelectItem>
              </SelectContent>
            </Select>
          </div>

          <!-- Reset Button -->
          <Button variant="outline" size="icon" @click="resetFilters">
            <X class="w-4 h-4"/>
            <span class="sr-only">Reset</span>
          </Button>
        </div>
      </CustomCard>

      <!-- Invitations Table -->
      <CustomCard
        title="Sent Invitations"
        :description="`${invitations.total} ${invitations.total === 1 ? 'invitation' : 'invitations'} found`"
        :icon="Mail"
        inline-icon>
        <div v-if="invitations.data.length > 0" class="space-y-4">
          <!-- Desktop Table -->
          <div class="hidden md:block">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Invitee</TableHead>
                  <TableHead>Department</TableHead>
                  <TableHead>Role</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead>Sent</TableHead>
                  <TableHead>Expires</TableHead>
                  <TableHead class="text-right">Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <TableRow v-for="invitation in invitations.data" :key="invitation.id">
                  <TableCell>
                    <div>
                      <div class="font-medium">{{ invitation.name }}</div>
                      <div class="text-sm text-gray-500">{{ invitation.email }}</div>
                    </div>
                  </TableCell>
                  <TableCell>
                    <span class="text-sm">{{ invitation.department?.name || 'No Department' }}</span>
                  </TableCell>
                  <TableCell>
                    <Badge variant="outline">{{ invitation.role_name }}</Badge>
                  </TableCell>
                  <TableCell>
                    <Badge :class="getStatusColor(isExpired(invitation) ? 'expired' : invitation.status)">
                      {{ isExpired(invitation) ? 'Expired' : invitation.status }}
                    </Badge>
                  </TableCell>
                  <TableCell>
                    <span class="text-sm">{{ formatDate(invitation.invited_at) }}</span>
                  </TableCell>
                  <TableCell>
                    <span class="text-sm">{{ formatDate(invitation.expires_at) }}</span>
                  </TableCell>
                  <TableCell class="text-right">
                    <DropdownMenu>
                      <DropdownMenuTrigger as-child>
                        <Button variant="ghost" size="sm">
                          <MoreHorizontal class="w-4 h-4"/>
                        </Button>
                      </DropdownMenuTrigger>
                      <DropdownMenuContent align="end">
                        <DropdownMenuItem
                          v-if="invitation.status === 'pending' && !isExpired(invitation)"
                          @click="resendInvitation(invitation)"
                          :disabled="processing"
                        >
                          <RefreshCw class="w-4 h-4 mr-2"/>
                          Resend
                        </DropdownMenuItem>
                        <DropdownMenuItem
                          v-if="invitation.status === 'pending'"
                          @click="cancelInvitation(invitation)"
                          :disabled="processing"
                          class="text-red-600"
                        >
                          <X class="w-4 h-4 mr-2"/>
                          Cancel
                        </DropdownMenuItem>
                      </DropdownMenuContent>
                    </DropdownMenu>
                  </TableCell>
                </TableRow>
              </TableBody>
            </Table>
          </div>

          <!-- Mobile Cards -->
          <div class="md:hidden space-y-4">
            <div
              v-for="invitation in invitations.data"
              :key="invitation.id"
              class="p-4 border rounded-lg bg-white dark:bg-gray-800"
            >
              <div class="flex justify-between items-start mb-2">
                <div>
                  <div class="font-medium">{{ invitation.name }}</div>
                  <div class="text-sm text-gray-500">{{ invitation.email }}</div>
                </div>
                <Badge :class="getStatusColor(isExpired(invitation) ? 'expired' : invitation.status)">
                  {{ isExpired(invitation) ? 'Expired' : invitation.status }}
                </Badge>
              </div>

              <div class="grid grid-cols-2 gap-2 text-sm">
                <div>
                  <span class="text-gray-500">Department:</span>
                  <span class="ml-1">{{ invitation.department?.name || 'None' }}</span>
                </div>
                <div>
                  <span class="text-gray-500">Role:</span>
                  <span class="ml-1">{{ invitation.role_name }}</span>
                </div>
                <div>
                  <span class="text-gray-500">Sent:</span>
                  <span class="ml-1">{{ formatDate(invitation.invited_at) }}</span>
                </div>
                <div>
                  <span class="text-gray-500">Expires:</span>
                  <span class="ml-1">{{ formatDate(invitation.expires_at) }}</span>
                </div>
              </div>

              <div v-if="invitation.status === 'pending'" class="flex gap-2 mt-3">
                <Button
                  v-if="!isExpired(invitation)"
                  size="sm"
                  variant="outline"
                  @click="resendInvitation(invitation)"
                  :disabled="processing">
                  <RefreshCw class="w-3 h-3 mr-1"/>
                  Resend
                </Button>
                <Button
                  size="sm"
                  variant="outline"
                  @click="cancelInvitation(invitation)"
                  :disabled="processing"
                  class="text-red-600 border-red-200 hover:bg-red-50"
                >
                  <X class="w-3 h-3 mr-1"/>
                  Cancel
                </Button>
              </div>
            </div>
          </div>

          <!-- Pagination -->
          <div v-if="invitations.last_page > 1" class="flex justify-center mt-6">
            <div class="flex space-x-2">
              <Link
                v-for="page in invitations.links"
                :key="page.label"
                :href="page.url"
                :class="[
                    'px-3 py-2 text-sm border rounded',
                    page.active
                      ? 'bg-blue-500 text-white border-blue-500'
                      : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'
                  ]"
                v-html="page.label"
              />
            </div>
          </div>
        </div>

        <!-- Empty State -->
        <EmptyState
          v-else
          title="No invitations found"
          description="You haven't sent any team invitations yet, or none match your current filters."
          :icon="Mail">
          <template #actions>
            <ModalLink :href="route('manager.team.invite.create')">
              <Button>
                <UserPlus class="w-4 h-4 mr-2"/>
                Send First Invitation
              </Button>
            </ModalLink>
          </template>
        </EmptyState>
      </CustomCard>
    </div>
  </ManagerLayout>
</template>
