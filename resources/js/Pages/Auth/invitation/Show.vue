<template>
  <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-50 dark:bg-gray-900">
    <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white dark:bg-gray-800 shadow-md overflow-hidden sm:rounded-lg">
      <div class="mb-6 text-center">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
          Join Our Team
        </h1>
        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
          You've been invited to join as {{ invitation.job_title }}
        </p>
      </div>

      <!-- Invitation Details -->
      <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 mb-6">
        <div class="space-y-2 text-sm">
          <div class="flex justify-between">
            <span class="text-gray-600 dark:text-gray-400">Email:</span>
            <span class="font-medium text-gray-900 dark:text-gray-100">{{ invitation.email }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-600 dark:text-gray-400">Name:</span>
            <span class="font-medium text-gray-900 dark:text-gray-100">{{ invitation.name }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-600 dark:text-gray-400">Department:</span>
            <span class="font-medium text-gray-900 dark:text-gray-100">{{ invitation.department_name || 'Not specified' }}</span>
          </div>
          <div class="flex justify-between" v-if="invitation.manager_name">
            <span class="text-gray-600 dark:text-gray-400">Manager:</span>
            <span class="font-medium text-gray-900 dark:text-gray-100">{{ invitation.manager_name }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-600 dark:text-gray-400">Invited by:</span>
            <span class="font-medium text-gray-900 dark:text-gray-100">{{ invitation.inviter_name }}</span>
          </div>
        </div>
      </div>

      <!-- Expiration Warning -->
      <div v-if="invitation.days_until_expiry <= 3" class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-md p-3 mb-6">
        <div class="flex">
          <div class="shrink-0">
            <AlertTriangle class="h-5 w-5 text-yellow-400" />
          </div>
          <div class="ml-3">
            <p class="text-sm text-yellow-700 dark:text-yellow-200">
              This invitation expires {{ invitation.expires_at }}
            </p>
          </div>
        </div>
      </div>

      <!-- Accept Form -->
      <form @submit.prevent="acceptInvitation" class="space-y-6">
        <!-- Password -->
        <div>
          <Label for="password">Create Password</Label>
          <Input
            id="password"
            v-model="form.password"
            type="password"
            class="mt-1"
            required
            autocomplete="new-password"
          />
          <InputError class="mt-2" :message="form.errors.password" />
          <PasswordStrengthIndicator :password="form.password" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div>
          <Label for="password_confirmation">Confirm Password</Label>
          <Input
            id="password_confirmation"
            v-model="form.password_confirmation"
            type="password"
            class="mt-1"
            required
            autocomplete="new-password"
          />
          <InputError class="mt-2" :message="form.errors.password_confirmation" />
        </div>

        <!-- Terms and Conditions -->
        <div class="flex items-center">
          <Checkbox
            id="terms_accepted"
            v-model:checked="form.terms_accepted"
            :required="true"
          />
          <div class="ml-2">
            <label for="terms_accepted" class="text-sm text-gray-900 dark:text-gray-300">
              I accept the 
              <a href="#" class="underline text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-200">
                Terms and Conditions
              </a> and 
              <a href="#" class="underline text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-200">
                Privacy Policy
              </a>
            </label>
          </div>
        </div>
        <InputError class="mt-2" :message="form.errors.terms_accepted" />

        <!-- Action Buttons -->
        <div class="flex items-center justify-between gap-4">
          <Button variant="outline" @click="declineInvitation" :disabled="form.processing">
            Decline
          </Button>

          <Button :disabled="form.processing || !form.terms_accepted">
            <span v-if="form.processing">Creating Account...</span>
            <span v-else>Accept Invitation</span>
          </Button>
        </div>
      </form>

      <!-- Error Display -->
      <div v-if="form.errors.error" class="mt-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded-md p-3">
        <div class="flex">
          <div class="shrink-0">
            <X class="h-5 w-5 text-red-400" />
          </div>
          <div class="ml-3">
            <p class="text-sm text-red-700 dark:text-red-200">
              {{ form.errors.error }}
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import { AlertTriangle, X } from 'lucide-vue-next'
import InputError from '@/components/InputError.vue'
import { Label } from '@/components/ui/label'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Checkbox } from '@/components/ui/checkbox'
import PasswordStrengthIndicator from '@/pages/auth/PasswordStrengthIndicator.vue'

const props = defineProps({
  invitation: {
    type: Object,
    required: true
  }
})

const form = useForm({
  password: '',
  password_confirmation: '',
  terms_accepted: false,
})

const acceptInvitation = () => {
  form.post(route('invitation.accept', { token: props.invitation.token }), {
    onFinish: () => {
      form.reset('password', 'password_confirmation')
    }
  })
}

const declineInvitation = () => {
  if (confirm('Are you sure you want to decline this invitation? This action cannot be undone.')) {
    form.post(route('invitation.decline', { token: props.invitation.token }))
  }
}
</script>

