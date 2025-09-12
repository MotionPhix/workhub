<script setup>
import { computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import PasswordStrengthIndicator from '@/pages/auth/PasswordStrengthIndicator.vue'
import {Label} from "@/components/ui/label";
import {Input} from "@/components/ui/input";
import {Button} from "@/components/ui/button";

// Define props from the page
const props = defineProps({
  token: String,
  email: String
})

// Create form
const form = useForm({
  token: props.token,
  email: props.email,
  password: '',
  password_confirmation: ''
})

// Validate form
const isFormValid = computed(() => {
  return form.password &&
    form.password_confirmation &&
    form.password === form.password_confirmation &&
    form.password.length >= 12
})

// Submit handler
const submitResetPassword = () => {
  form.post(route('password.update'), {
    onSuccess: () => {
      // Redirect to login with success message
      form.reset()
    },
    onError: (errors) => {
      // Errors are automatically handled
      console.error('Password reset failed', errors)
    }
  })
}
</script>

<template>
  <div class="flex items-center justify-center min-h-screen px-4 py-8 bg-gray-100">
    <div class="w-full max-w-md">
      <div class="p-8 space-y-6 bg-white shadow-md rounded-xl">
        <h2 class="text-2xl font-bold text-center">
          Reset Your Password
        </h2>

        <form @submit.prevent="submitResetPassword" class="space-y-6">
          <!-- Hidden email field -->
          <input
            type="hidden"
            name="email"
            :value="email"
          />

          <!-- Hidden token field -->
          <input
            type="hidden"
            name="token"
            :value="token"
          />

          <!-- Password Input -->
          <div>
            <Label for="password">New Password</Label>
            <Input
              id="password"
              type="password"
              v-model="form.password"
              required
              placeholder="Enter new password"
              :error="errors.password"
            />

            <!-- Password Strength Indicator -->
            <PasswordStrengthIndicator
              :password="form.password"
              class="mt-2"
            />
          </div>

          <!-- Confirm Password Input -->
          <div>
            <Label for="password_confirmation">
              Confirm New Password
            </Label>

            <Input
              id="password_confirmation"
              type="password"
              v-model="form.password_confirmation"
              required
              placeholder="Confirm new password"
              :error="errors.password_confirmation"
            />
          </div>

          <!-- Submit Button -->
          <Button
            type="submit"
            class="w-full"
            :disabled="!isFormValid">
            Reset Password
          </Button>
        </form>

        <!-- Error Message -->
        <div
          v-if="Object.keys(errors).length > 0"
          class="p-4 border-l-4 border-red-400 bg-red-50">
          <p class="text-sm text-red-700">
            {{ Object.values(errors)[0] }}
          </p>
        </div>
      </div>
    </div>
  </div>
</template>


