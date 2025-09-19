<script setup lang="ts">
import {computed, ref} from 'vue'
import {Head, router, useForm} from '@inertiajs/vue3'
import { UserPlus, X, Loader2 } from 'lucide-vue-next'
import { Modal } from '@inertiaui/modal-vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import CustomCard from '@/components/CustomCard.vue'
import InputError from "@/components/InputError.vue";
import {toast} from "vue-sonner";

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
}

const props = defineProps<Props>()
const generalInviteModal = ref(null)

// Form state
const form = useForm({
  name: '',
  email: '',
  department_uuid: null,
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
  return form.processing ? 'Sending Invitation...' : 'Send Invitation'
})

// Methods
function submitInvitation() {
  // Prepare the data for submission
  form.transform((data) => ({
    name: data.name,
    email: data.email,
    department_uuid: data.department_uuid,
    role_name: data.role_name,
    manager_email: data.manager_email === 'none' ? null : (data.manager_email || (props.isManager ? props.currentUser.email : null)),
    expires_in_days: data.expires_in_days,
    welcome_message: data.welcome_message,
    send_immediately: data.send_immediately
  }))

  const submitRoute = props.isManager ? 'manager.team.invite' : 'admin.invitations.store'

  form.post(route(submitRoute), {
    onSuccess: () => {
      generalInviteModal.value?.close()
      toast.success('Invitation sent successfully!')
      form.resetAndClearErrors()
    },
    onError: () => {
      toast.error('Please fix the errors and try again.')
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
    ref="generalInviteModal"
    max-width="2xl"
    :close-button="false"
    padding-classes="p-0"
    :close-explicitly="true"
    panel-classes="bg-transparent"
    v-slot="{ close }">
    <CustomCard
      :icon="UserPlus"
      :title="pageTitle"
      description="Fill out the details below to send an invitation"
      padding="p-8">
      <template #header>
        <Button
          variant="ghost"
          size="icon"
          @click="close">
          <X class="h-4 w-4" />
        </Button>
      </template>
        <form class="space-y-6" @submit.prevent="submitInvitation">
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

              <InputError :message="form.errors.name" class="mt-1" />
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

              <InputError :message="form.errors.email" class="mt-1" />
            </div>
          </div>

          <!-- Role and Department -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-2">
              <Label for="role">Role *</Label>
              <Select v-model="form.role_name">
                <SelectTrigger class="w-full">
                  <SelectValue placeholder="Select role" />
                </SelectTrigger>

                <SelectContent>
                  <SelectItem
                    v-for="role in roles"
                    :key="role.id"
                    :value="role.id">
                    {{ role.name.charAt(0).toUpperCase() + role.name.slice(1) }}
                  </SelectItem>
                </SelectContent>
              </Select>

              <InputError :message="form.errors.role_name" class="mt-1" />
            </div>

            <div class="space-y-2">
              <Label for="department">Department *</Label>
              <Select v-model="form.department_uuid" required>
                <SelectTrigger class="w-full">
                  <SelectValue placeholder="Select department *" />
                </SelectTrigger>

                <SelectContent>
                  <SelectItem
                    v-for="dept in departments"
                    :key="dept.id"
                    :value="dept.id">
                    {{ dept.name }}
                  </SelectItem>
                </SelectContent>
              </Select>

              <InputError :message="form.errors.department_uuid" class="mt-1" />
            </div>
          </div>

          <!-- Manager Assignment (only for admins or when inviting managers) -->
          <div
            v-if="isAdmin && form.role_name !== 'admin'"
            class="space-y-2">
            <Label for="manager">Manager</Label>
            <Select v-model="form.manager_email">
              <SelectTrigger>
                <SelectValue placeholder="Select manager" />
              </SelectTrigger>

              <SelectContent>
                <SelectItem value="none">No Manager</SelectItem>
                <SelectItem
                  v-for="manager in managers"
                  :key="manager.id"
                  :value="manager.email">
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
                <SelectTrigger class="w-full">
                  <SelectValue />
                </SelectTrigger>

                <SelectContent>
                  <SelectItem :value="3">3 days</SelectItem>
                  <SelectItem :value="7">7 days</SelectItem>
                  <SelectItem :value="14">14 days</SelectItem>
                  <SelectItem :value="30">30 days</SelectItem>
                </SelectContent>
              </Select>

              <InputError :message="form.errors.expires_in_days" class="mt-1" />
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

            <InputError :message="form.errors.welcome_message" class="mt-1" />
          </div>
        </form>

        <template #footer>
          <div class="flex gap-3 items-end justify-end">
            <Button
              type="button"
              class="relative z-40 cursor-pointer"
              :disabled="form.processing"
              @click="submitInvitation">
              <Loader2
                v-if="form.processing"
                class="h-4 w-4 mr-2 animate-spin"
              />
              {{ submitText }}
            </Button>

            <Button
              variant="outline"
              @click="generalInviteModal.close(); form.resetAndClearErrors()">
              Cancel
            </Button>
          </div>
        </template>
      </CustomCard>
    </Modal>
</template>
