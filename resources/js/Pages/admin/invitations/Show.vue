<template>
  <Head title="Invitation Details" />

  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
          Invitation Details
        </h2>
        <Link :href="route('admin.invitations.index')" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100">
          Back to Invitations
        </Link>
      </div>
    </template>

    <div class="py-12">
      <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6">
            
            <!-- Status Badge -->
            <div class="flex items-center justify-between mb-6">
              <div class="flex items-center space-x-3">
                <span
                  class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                  :class="invitation.status_badge_class"
                >
                  {{ invitation.status_display }}
                </span>
                <span v-if="invitation.is_expired" class="text-sm text-red-600 dark:text-red-400">
                  Expired {{ invitation.expires_at }}
                </span>
                <span v-else-if="invitation.status === 'pending'" class="text-sm text-gray-500 dark:text-gray-400">
                  Expires {{ invitation.expires_at }}
                </span>
              </div>

              <!-- Actions Dropdown -->
              <div class="relative" v-if="invitation.status === 'pending' && !invitation.is_expired">
                <DropdownMenu>
                  <DropdownMenuTrigger asChild>
                    <Button variant="outline" size="sm">
                      Actions
                      <ChevronDown class="ml-2 h-4 w-4" />
                    </Button>
                  </DropdownMenuTrigger>
                  <DropdownMenuContent>
                    <DropdownMenuItem @click="resendInvitation">
                      Resend Invitation
                    </DropdownMenuItem>
                    <DropdownMenuItem @click="extendExpiry">
                      Extend Expiry
                    </DropdownMenuItem>
                    <DropdownMenuItem @click="cancelInvitation" class="text-red-600 focus:text-red-600">
                      Cancel Invitation
                    </DropdownMenuItem>
                  </DropdownMenuContent>
                </DropdownMenu>
              </div>
            </div>

            <!-- Invitation Details Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
              
              <!-- Basic Information -->
              <div class="space-y-6">
                <div>
                  <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                    Basic Information
                  </h3>
                  <dl class="space-y-3">
                    <div>
                      <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Email</dt>
                      <dd class="text-sm text-gray-900 dark:text-gray-100">{{ invitation.email }}</dd>
                    </div>
                    <div>
                      <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Name</dt>
                      <dd class="text-sm text-gray-900 dark:text-gray-100">{{ invitation.name }}</dd>
                    </div>
                    <div>
                      <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Job Title</dt>
                      <dd class="text-sm text-gray-900 dark:text-gray-100">{{ invitation.job_title || 'Not specified' }}</dd>
                    </div>
                    <div>
                      <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Role</dt>
                      <dd class="text-sm text-gray-900 dark:text-gray-100 capitalize">{{ invitation.role_name }}</dd>
                    </div>
                  </dl>
                </div>

                <!-- Department & Management -->
                <div>
                  <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                    Department & Management
                  </h3>
                  <dl class="space-y-3">
                    <div>
                      <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Department</dt>
                      <dd class="text-sm text-gray-900 dark:text-gray-100">
                        {{ invitation.department?.name || 'Not assigned' }}
                      </dd>
                    </div>
                    <div>
                      <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Manager</dt>
                      <dd class="text-sm text-gray-900 dark:text-gray-100">
                        {{ invitation.manager?.name || 'Not assigned' }}
                        <span v-if="invitation.manager?.email" class="text-gray-500 dark:text-gray-400">
                          ({{ invitation.manager.email }})
                        </span>
                      </dd>
                    </div>
                  </dl>
                </div>
              </div>

              <!-- Invitation Status & Timeline -->
              <div class="space-y-6">
                <div>
                  <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                    Invitation Timeline
                  </h3>
                  <dl class="space-y-3">
                    <div>
                      <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Invited by</dt>
                      <dd class="text-sm text-gray-900 dark:text-gray-100">
                        {{ invitation.inviter?.name }} ({{ invitation.inviter?.email }})
                      </dd>
                    </div>
                    <div>
                      <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Invited at</dt>
                      <dd class="text-sm text-gray-900 dark:text-gray-100">{{ invitation.invited_at }}</dd>
                    </div>
                    <div>
                      <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Expires at</dt>
                      <dd class="text-sm text-gray-900 dark:text-gray-100">{{ invitation.expires_at }}</dd>
                    </div>
                    <div v-if="invitation.accepted_at">
                      <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Accepted at</dt>
                      <dd class="text-sm text-gray-900 dark:text-gray-100">{{ invitation.accepted_at }}</dd>
                    </div>
                    <div v-if="invitation.declined_at">
                      <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Declined at</dt>
                      <dd class="text-sm text-gray-900 dark:text-gray-100">{{ invitation.declined_at }}</dd>
                    </div>
                  </dl>
                </div>

                <!-- Reminder Information -->
                <div v-if="invitation.reminder_count > 0">
                  <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                    Reminders
                  </h3>
                  <dl class="space-y-3">
                    <div>
                      <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Reminders sent</dt>
                      <dd class="text-sm text-gray-900 dark:text-gray-100">{{ invitation.reminder_count }}</dd>
                    </div>
                    <div v-if="invitation.reminder_sent_at">
                      <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Last reminder</dt>
                      <dd class="text-sm text-gray-900 dark:text-gray-100">{{ invitation.reminder_sent_at }}</dd>
                    </div>
                  </dl>
                </div>
              </div>
            </div>

            <!-- Invitation URL -->
            <div class="mt-8 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
              <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-2">
                Invitation URL
              </h4>
              <div class="flex items-center space-x-2">
                <input
                  type="text"
                  :value="invitation.invite_url"
                  readonly
                  class="flex-1 text-sm bg-white dark:bg-gray-600 border border-gray-300 dark:border-gray-500 rounded px-3 py-2"
                />
                <button
                  @click="copyInviteUrl"
                  class="px-3 py-2 bg-blue-600 text-white text-sm rounded hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                  Copy
                </button>
              </div>
            </div>

            <!-- Additional Data -->
            <div v-if="invitation.invite_data && Object.keys(invitation.invite_data).length" class="mt-6">
              <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-2">
                Additional Data
              </h4>
              <pre class="text-xs bg-gray-50 dark:bg-gray-700 p-3 rounded-md overflow-x-auto">{{ JSON.stringify(invitation.invite_data, null, 2) }}</pre>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { ChevronDown, MoreVertical } from 'lucide-vue-next'
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { Button } from '@/components/ui/button'
import { router } from '@inertiajs/vue3'

const props = defineProps({
  invitation: {
    type: Object,
    required: true
  }
})

const copyInviteUrl = async () => {
  try {
    await navigator.clipboard.writeText(props.invitation.invite_url)
    alert('Invitation URL copied to clipboard!')
  } catch (err) {
    console.error('Failed to copy: ', err)
  }
}

const resendInvitation = () => {
  if (confirm('Are you sure you want to resend this invitation?')) {
    router.post(route('admin.invitations.resend', props.invitation.id))
  }
}

const extendExpiry = () => {
  const days = prompt('Extend invitation by how many days?', '7')
  if (days && !isNaN(Number(days))) {
    router.patch(route('admin.invitations.update', props.invitation.id), {
      extend_days: parseInt(days)
    })
  }
}

const cancelInvitation = () => {
  if (confirm('Are you sure you want to cancel this invitation? This action cannot be undone.')) {
    router.post(route('admin.invitations.cancel', props.invitation.id))
  }
}
</script>

