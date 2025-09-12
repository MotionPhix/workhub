<template>
  <Head title="Send Invitation" />

  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
          Send User Invitation
        </h2>
        <Link :href="route('admin.invitations.index')" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100">
          Back to Invitations
        </Link>
      </div>
    </template>

    <div class="py-12">
      <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 text-gray-900 dark:text-gray-100">
            
            <!-- Single Invitation Form -->
            <form @submit.prevent="sendInvitation" class="space-y-6">
              <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                
                <!-- Email -->
                <div class="sm:col-span-2">
                  <Label for="email">Email Address</Label>
                  <Input
                    id="email"
                    v-model="form.email"
                    type="email"
                    class="mt-1"
                    required
                    autocomplete="email"
                    placeholder="user@example.com"
                  />
                  <InputError class="mt-2" :message="form.errors.email" />
                </div>

                <!-- Name -->
                <div>
                  <Label for="name">Full Name</Label>
                  <Input
                    id="name"
                    v-model="form.name"
                    type="text"
                    class="mt-1"
                    required
                    autocomplete="name"
                    placeholder="John Doe"
                  />
                  <InputError class="mt-2" :message="form.errors.name" />
                </div>

                <!-- Job Title -->
                <div>
                  <InputLabel for="job_title" value="Job Title" />
                  <TextInput
                    id="job_title"
                    v-model="form.job_title"
                    type="text"
                    class="mt-1 block w-full"
                    required
                    placeholder="Software Engineer"
                  />
                  <InputError class="mt-2" :message="form.errors.job_title" />
                </div>

                <!-- Department -->
                <div>
                  <InputLabel for="department_uuid" value="Department" />
                  <SelectInput
                    id="department_uuid"
                    v-model="form.department_uuid"
                    class="mt-1 block w-full"
                  >
                    <option value="">Select Department</option>
                    <option
                      v-for="department in departments"
                      :key="department.uuid"
                      :value="department.uuid"
                    >
                      {{ department.name }}
                    </option>
                  </SelectInput>
                  <InputError class="mt-2" :message="form.errors.department_uuid" />
                </div>

                <!-- Manager -->
                <div>
                  <InputLabel for="manager_email" value="Manager" />
                  <SelectInput
                    id="manager_email"
                    v-model="form.manager_email"
                    class="mt-1 block w-full"
                  >
                    <option value="">Select Manager</option>
                    <option
                      v-for="manager in managers"
                      :key="manager.email"
                      :value="manager.email"
                    >
                      {{ manager.name }} ({{ manager.email }})
                    </option>
                  </SelectInput>
                  <InputError class="mt-2" :message="form.errors.manager_email" />
                </div>

                <!-- Role -->
                <div>
                  <InputLabel for="role_name" value="Role" />
                  <SelectInput
                    id="role_name"
                    v-model="form.role_name"
                    class="mt-1 block w-full"
                    required
                  >
                    <option
                      v-for="role in roles"
                      :key="role.id"
                      :value="role.name"
                    >
                      {{ role.name }}
                    </option>
                  </SelectInput>
                  <InputError class="mt-2" :message="form.errors.role_name" />
                </div>

                <!-- Expires In Days -->
                <div>
                  <InputLabel for="expires_in_days" value="Expires In (Days)" />
                  <SelectInput
                    id="expires_in_days"
                    v-model="form.expires_in_days"
                    class="mt-1 block w-full"
                    required
                  >
                    <option :value="1">1 day</option>
                    <option :value="3">3 days</option>
                    <option :value="7">1 week</option>
                    <option :value="14">2 weeks</option>
                    <option :value="30">1 month</option>
                  </SelectInput>
                  <InputError class="mt-2" :message="form.errors.expires_in_days" />
                </div>
              </div>

              <!-- Welcome Message -->
              <div>
                <InputLabel for="welcome_message" value="Welcome Message (Optional)" />
                <Textarea
                  id="welcome_message"
                  v-model="form.welcome_message"
                  class="mt-1 block w-full"
                  rows="4"
                  placeholder="Welcome to our team! We're excited to have you join us..."
                />
                <InputError class="mt-2" :message="form.errors.welcome_message" />
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                  This message will be included in the invitation email.
                </p>
              </div>

              <!-- Send Options -->
              <div class="flex items-center">
                <Checkbox
                  id="send_immediately"
                  v-model:checked="form.send_immediately"
                />
                <div class="ml-2">
                  <label for="send_immediately" class="text-sm text-gray-900 dark:text-gray-300">
                    Send invitation immediately
                  </label>
                  <p class="text-xs text-gray-500 dark:text-gray-400">
                    If unchecked, the invitation will be created but not sent until manually triggered.
                  </p>
                </div>
              </div>

              <!-- Submit Buttons -->
              <div class="flex items-center justify-end gap-4">
                <Button variant="outline" :href="route('admin.invitations.index')">
                  Cancel
                </Button>

                <Button :disabled="form.processing">
                  <span v-if="form.processing">Sending...</span>
                  <span v-else>Send Invitation</span>
                </Button>
              </div>
            </form>
            
            <!-- Bulk Invitation Section -->
            <div class="mt-12 pt-8 border-t border-gray-200 dark:border-gray-700">
              <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                Bulk Invitations
              </h3>
              <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                Send multiple invitations at once by uploading a CSV file with columns: email, name, job_title, department_uuid, manager_email, role_name
              </p>
              
              <form @submit.prevent="sendBulkInvitations" class="space-y-4">
                <div>
                  <InputLabel for="bulk_file" value="CSV File" />
                  <input
                    id="bulk_file"
                    @change="handleFileUpload"
                    type="file"
                    accept=".csv"
                    class="mt-1 block w-full text-sm text-gray-500 dark:text-gray-400
                           file:mr-4 file:py-2 file:px-4
                           file:rounded-full file:border-0
                           file:text-sm file:font-semibold
                           file:bg-blue-50 file:text-blue-700
                           hover:file:bg-blue-100
                           dark:file:bg-blue-900 dark:file:text-blue-300"
                  />
                  <InputError class="mt-2" :message="bulkForm.errors.file" />
                </div>

                <div class="flex items-center justify-end">
                  <Button :disabled="bulkForm.processing || !bulkForm.file">
                    <span v-if="bulkForm.processing">Processing...</span>
                    <span v-else>Send Bulk Invitations</span>
                  </Button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue'
import InputError from '@/components/InputError.vue'
import { Label } from '@/components/ui/label'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Textarea } from '@/components/ui/textarea'
import { Checkbox } from '@/components/ui/checkbox'

const props = defineProps({
  departments: {
    type: Array,
    default: () => []
  },
  managers: {
    type: Array,
    default: () => []
  },
  roles: {
    type: Array,
    default: () => []
  }
})

const form = useForm({
  email: '',
  name: '',
  department_uuid: '',
  manager_email: '',
  job_title: '',
  role_name: 'employee',
  expires_in_days: 7,
  welcome_message: '',
  send_immediately: true,
})

const bulkForm = useForm({
  file: null,
})

const sendInvitation = () => {
  form.post(route('admin.invitations.store'), {
    onSuccess: () => {
      // Redirect handled by controller
    }
  })
}

const handleFileUpload = (event) => {
  const file = event.target.files[0]
  bulkForm.file = file
}

const sendBulkInvitations = () => {
  if (!bulkForm.file) return
  
  const formData = new FormData()
  formData.append('file', bulkForm.file)
  
  bulkForm.post(route('admin.invitations.bulk'), {
    data: formData,
    forceFormData: true,
  })
}
</script>

