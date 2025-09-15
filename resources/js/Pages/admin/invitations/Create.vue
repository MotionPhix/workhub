<script setup lang="ts">
import { Head, useForm, Link } from '@inertiajs/vue3'
import AdminLayout from '@/layouts/AdminLayout.vue'
import InputError from '@/components/InputError.vue'
import { Label } from '@/components/ui/label'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Textarea } from '@/components/ui/textarea'
import { Checkbox } from '@/components/ui/checkbox'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Separator } from '@/components/ui/separator'
import FormCheckbox from '@/components/forms/FormCheckbox.vue'

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

  bulkForm.post(route('admin.invitations.bulk'), {
    forceFormData: true,
  })
}
</script>

<template>
  <Head title="Send Invitation" />

  <AdminLayout>
    <div class="flex items-center justify-between py-6">
      <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
        Send User Invitation
      </h2>

      <Link
        :as="Button"
        :href="route('admin.invitations.index')">
        Cancel
      </Link>
    </div>

    <div class="py-12">
      <div class="max-w-full mx-auto sm:px-6 lg:px-8">
        <Card>
          <CardHeader>
            <CardTitle>Send Single Invitation</CardTitle>
            <CardDescription>
              Send an invitation to a new team member with all the necessary details.
            </CardDescription>
          </CardHeader>
          <CardContent>

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
                  <Label for="job_title">Job Title</Label>
                  <Input
                    id="job_title"
                    v-model="form.job_title"
                    type="text"
                    class="mt-1"
                    required
                    placeholder="Software Engineer"
                  />
                  <InputError class="mt-2" :message="form.errors.job_title" />
                </div>

                <!-- Department -->
                <div>
                  <Label for="department_uuid">Department</Label>
                  <Select v-model="form.department_uuid">
                    <SelectTrigger class="mt-1 w-full">
                      <SelectValue placeholder="Select Department" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem
                        v-for="department in departments"
                        :key="department.uuid"
                        :value="department.uuid"
                      >
                        {{ department.name }}
                      </SelectItem>
                    </SelectContent>
                  </Select>
                  <InputError class="mt-2" :message="form.errors.department_uuid" />
                </div>

                <!-- Manager -->
                <div>
                  <Label for="manager_email">Manager</Label>
                  <Select v-model="form.manager_email">
                    <SelectTrigger class="mt-1 w-full">
                      <SelectValue placeholder="Select Manager" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem
                        v-for="manager in managers"
                        :key="manager.email"
                        :value="manager.email">
                        <div class="grid">
                          <span>{{ manager.name }}</span>
                          <span class="text-gray-500 dark:text-gray-400 text-xs">{{ manager.email }}</span>
                        </div>
                      </SelectItem>
                    </SelectContent>
                  </Select>
                  <InputError class="mt-2" :message="form.errors.manager_email" />
                </div>

                <!-- Role -->
                <div>
                  <Label for="role_name">Role</Label>
                  <Select v-model="form.role_name">
                    <SelectTrigger class="mt-1 w-full">
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem
                        v-for="role in roles"
                        :key="role.id"
                        :value="role.name"
                      >
                        {{ role.name }}
                      </SelectItem>
                    </SelectContent>
                  </Select>
                  <InputError class="mt-2" :message="form.errors.role_name" />
                </div>

                <!-- Expires In Days -->
                <div>
                  <Label for="expires_in_days">Expires In (Days)</Label>
                  <Select v-model="form.expires_in_days">
                    <SelectTrigger class="mt-1 w-full">
                      <SelectValue placeholder="Select Expiration" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="1">1 day</SelectItem>
                      <SelectItem value="3">3 days</SelectItem>
                      <SelectItem value="7">1 week</SelectItem>
                      <SelectItem value="14">2 weeks</SelectItem>
                      <SelectItem value="30">1 month</SelectItem>
                    </SelectContent>
                  </Select>
                  <InputError class="mt-2" :message="form.errors.expires_in_days" />
                </div>
              </div>

              <!-- Welcome Message -->
              <div>
                <Label for="welcome_message">Welcome Message (Optional)</Label>
                <Textarea
                  id="welcome_message"
                  v-model="form.welcome_message"
                  class="mt-1"
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
                <Button variant="outline" asChild>
                  <Link :href="route('admin.invitations.index')">
                    Cancel
                  </Link>
                </Button>

                <Button :disabled="form.processing">
                  <span v-if="form.processing">Sending...</span>
                  <span v-else>Send Invitation</span>
                </Button>
              </div>
            </form>
          </CardContent>
        </Card>

        <!-- Bulk Invitation Section -->
        <Card class="mt-6">
          <CardHeader>
            <CardTitle>Bulk Invitations</CardTitle>
            <CardDescription>
              Send multiple invitations at once by uploading a CSV file with columns: email, name, job_title, department_uuid, manager_email, role_name
            </CardDescription>
          </CardHeader>
          <CardContent>
            <form @submit.prevent="sendBulkInvitations" class="space-y-4">
              <div>
                <Label for="bulk_file">CSV File</Label>
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
          </CardContent>
        </Card>
      </div>
    </div>
  </AdminLayout>
</template>
