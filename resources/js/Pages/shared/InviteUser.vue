<script setup lang="ts">
import { reactive, computed } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import { UserPlus, X, Loader2 } from 'lucide-vue-next'
import { Modal } from '@inertiaui/modal-vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import CustomCard from '@/components/CustomCard.vue'

interface Department {
  id: string
  name: string
}

interface Role {
  id: string
  name: string
}

interface Manager {
  id: number
  name: string
  email: string
}

interface Props {
  departments: Department[]
  roles: Role[]
  managers: Manager[]
  currentUser: {
    id: number
    name: string
    email: string
  }
  isManager: boolean
  isAdmin: boolean
  returnUrl: string
}

const props = defineProps<Props>()

// Form state
const processing = reactive({ value: false })
const form = reactive({
  name: '',
  email: '',
  department_id: '',
  role_name: 'employee',
  manager_email: '',
  expires_in_days: 7,
  welcome_message: '',
  send_immediately: true
})

// Computed
const pageTitle = computed(() => {
  return props.isManager ? 'Invite Team Member' : 'Invite User'
})

const submitText = computed(() => {
  return processing.value ? 'Sending Invitation...' : 'Send Invitation'
})

// Methods
function submitInvitation(close: () => void) {
  processing.value = true

  const submitData = {
    ...form,
    department_uuid: form.department_id || null,
    manager_email: form.manager_email || (props.isManager ? props.currentUser.email : null)
  }

  // Remove empty values
  Object.keys(submitData).forEach(key => {
    if (submitData[key] === '') {
      delete submitData[key]
    }
  })

  const submitRoute = props.isManager ? 'manager.team.invite' : 'admin.invitations.store'

  router.post(route(submitRoute), submitData, {
    onSuccess: () => {
      close()
    },
    onFinish: () => {
      processing.value = false
    }
  })
}

// Set default manager for manager role users
if (props.isManager) {
  form.manager_email = props.currentUser.email
}
</script>

<template>
  <Head :title="pageTitle" />

  <Modal
    max-width="2xl"
    :close-button="false"
    padding-classes="p-0"
    panel-classes="bg-transparent"
    #default="{ close }"
  >
    <CustomCard
      :icon="UserPlus"
      :title="pageTitle"
      description="Fill out the details below to send an invitation"
      padding="p-8"
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
        <form @submit.prevent="submitInvitation" class="space-y-6">
          <!-- Basic Information -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-2">
              <Label for="name">Full Name *</Label>
              <Input
                id="name"
                v-model="form.name"
                placeholder="Enter full name"
                required
              />
            </div>
            <div class="space-y-2">
              <Label for="email">Email Address *</Label>
              <Input
                id="email"
                v-model="form.email"
                type="email"
                placeholder="user@example.com"
                required
              />
            </div>
          </div>

          <!-- Role and Department -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-2">
              <Label for="role">Role *</Label>
              <Select v-model="form.role_name">
                <SelectTrigger>
                  <SelectValue placeholder="Select role" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem
                    v-for="role in roles"
                    :key="role.id"
                    :value="role.id"
                  >
                    {{ role.name.charAt(0).toUpperCase() + role.name.slice(1) }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>
            <div class="space-y-2">
              <Label for="department">Department</Label>
              <Select v-model="form.department_id">
                <SelectTrigger>
                  <SelectValue placeholder="Select department" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="">No Department</SelectItem>
                  <SelectItem
                    v-for="dept in departments"
                    :key="dept.id"
                    :value="dept.id"
                  >
                    {{ dept.name }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>

          <!-- Manager Assignment (only for admins or when inviting managers) -->
          <div
            v-if="isAdmin && form.role_name !== 'admin'"
            class="space-y-2"
          >
            <Label for="manager">Manager</Label>
            <Select v-model="form.manager_email">
              <SelectTrigger>
                <SelectValue placeholder="Select manager" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="">No Manager</SelectItem>
                <SelectItem
                  v-for="manager in managers"
                  :key="manager.id"
                  :value="manager.email"
                >
                  {{ manager.name }} ({{ manager.email }})
                </SelectItem>
              </SelectContent>
            </Select>
          </div>

          <!-- Advanced Settings -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-2">
              <Label for="expires">Invitation Expires (days)</Label>
              <Select v-model="form.expires_in_days">
                <SelectTrigger>
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem :value="3">3 days</SelectItem>
                  <SelectItem :value="7">7 days</SelectItem>
                  <SelectItem :value="14">14 days</SelectItem>
                  <SelectItem :value="30">30 days</SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>

          <!-- Welcome Message -->
          <div class="space-y-2">
            <Label for="message">Welcome Message (Optional)</Label>
            <Textarea
              id="message"
              v-model="form.welcome_message"
              rows="3"
              placeholder="Add a personal welcome message..."
            />
          </div>
        </form>

        <template #footer>
          <div class="flex gap-3">
            <Button
              type="submit"
              :disabled="processing.value"
              class="flex-1"
              @click="submitInvitation(close)"
            >
              <Loader2 v-if="processing.value" class="h-4 w-4 mr-2 animate-spin" />
              {{ submitText }}
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
        </template>
      </CustomCard>
    </Modal>
</template>