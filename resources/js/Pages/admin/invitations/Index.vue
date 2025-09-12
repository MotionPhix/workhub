<template>
  <Head title="User Invitations" />

  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
          User Invitations
        </h2>
        <Link :href="route('admin.invitations.create')" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
          Send New Invitation
        </Link>
      </div>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
          <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
              <div class="flex items-center">
                <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                  {{ statistics.total || 0 }}
                </div>
                <div class="ml-2 text-sm text-gray-500 dark:text-gray-400">Total Invitations</div>
              </div>
            </div>
          </div>
          
          <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
              <div class="flex items-center">
                <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                  {{ statistics.accepted || 0 }}
                </div>
                <div class="ml-2 text-sm text-gray-500 dark:text-gray-400">Accepted</div>
              </div>
            </div>
          </div>
          
          <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
              <div class="flex items-center">
                <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">
                  {{ statistics.pending || 0 }}
                </div>
                <div class="ml-2 text-sm text-gray-500 dark:text-gray-400">Pending</div>
              </div>
            </div>
          </div>
          
          <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
              <div class="flex items-center">
                <div class="text-2xl font-bold text-red-600 dark:text-red-400">
                  {{ statistics.expired || 0 }}
                </div>
                <div class="ml-2 text-sm text-gray-500 dark:text-gray-400">Expired</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Invitations Table -->
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 text-gray-900 dark:text-gray-100">
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                  <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                      Invitee
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                      Status
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                      Department
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                      Invited
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                      Expires
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                      Actions
                    </th>
                  </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                  <tr v-for="invitation in invitations.data" :key="invitation.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="flex items-center">
                        <div class="flex-shrink-0 h-10 w-10">
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
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <span :class="getStatusClass(invitation.status)" class="inline-flex px-2 py-1 text-xs font-semibold rounded-full">
                        {{ invitation.status.charAt(0).toUpperCase() + invitation.status.slice(1) }}
                      </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                      {{ invitation.department?.name || 'N/A' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                      {{ formatDate(invitation.created_at) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                      {{ formatDate(invitation.expires_at) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                      <div class="flex space-x-2">
                        <Link 
                          :href="route('admin.invitations.show', invitation.id)" 
                          class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300"
                        >
                          View
                        </Link>
                        <button
                          v-if="invitation.status === 'pending'"
                          @click="resendInvitation(invitation.id)"
                          class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300"
                          :disabled="processing"
                        >
                          Resend
                        </button>
                        <button
                          v-if="invitation.status === 'pending'"
                          @click="cancelInvitation(invitation.id)"
                          class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                          :disabled="processing"
                        >
                          Cancel
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
            
            <!-- Pagination -->
            <div v-if="invitations.links" class="mt-6">
              <div class="flex items-center justify-between">
                <div class="text-sm text-gray-700 dark:text-gray-300">
                  Showing {{ invitations.from }} to {{ invitations.to }} of {{ invitations.total }} results
                </div>
                <div class="flex space-x-1">
                  <Link
                    v-for="link in invitations.links"
                    :key="link.label"
                    :href="link.url"
                    v-html="link.label"
                    :class="[
                      'px-3 py-2 text-sm leading-4 border rounded hover:bg-gray-100 dark:hover:bg-gray-700',
                      link.active 
                        ? 'bg-blue-500 border-blue-500 text-white' 
                        : 'bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300'
                    ]"
                  />
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup lang="ts">
import { defineProps, ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue'

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

const getInitials = (name) => {
  return name.split(' ').map(word => word.charAt(0)).join('').toUpperCase()
}

const getStatusClass = (status) => {
  const classes = {
    'pending': 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
    'accepted': 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
    'expired': 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
    'cancelled': 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
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
</script>
