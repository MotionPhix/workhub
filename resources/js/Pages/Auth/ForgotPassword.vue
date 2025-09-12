<script setup lang="ts">
import { ref, computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { Link } from '@inertiajs/vue3'
import { CheckCircle } from 'lucide-vue-next'
import {Label} from "@/components/ui/label";
import InputError from "@/components/InputError.vue";
import {Input} from "@/components/ui/input"
import {Button} from "@/components/ui/button"

// Form setup
const form = useForm({
  email: '',
})

// Success message state
const successMessage = ref('')
const showSuccessMessage = computed(() => !!successMessage.value)

// Submit handler
const submitPasswordResetRequest = () => {
  form.post(route('password.email'), {
    preserveScroll: true,
    onSuccess: (page) => {
      // Handle success scenario
      form.reset()

      // Set success message from flash data
      successMessage.value = page.props.flash.status ||
        'Password reset link has been sent to your email.'

      // Optional: Auto-clear success message
      setTimeout(() => {
        successMessage.value = ''
      }, 5000)
    },
    onError: (errors) => {
      // Errors are automatically handled by the form
      console.error('Password reset request failed', errors)
    }
  })
}
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-100 px-4 py-8">
    <div class="w-full max-w-md">
      <div class="bg-white shadow-md rounded-xl p-8 space-y-6">
        <div class="text-center">
          <h2 class="text-2xl font-bold text-gray-900">
            Reset Your Password
          </h2>
          <p class="mt-2 text-sm text-gray-600">
            Enter your email address below and we'll send you a link to reset your password.
          </p>
        </div>

        <form @submit.prevent="submitPasswordResetRequest" class="space-y-6">
          <!-- Email Input -->
          <div>
            <Label for="email">Email address</Label>
            <Input
              id="email"
              type="email"
              v-model="form.email"
              placeholder="Enter your email"
            />

            <!-- Email Error Message -->
            <InputError class="mt-2" :message="form.errors.email" />
          </div>

          <!-- Submit Button -->
          <Button
            type="submit"
            class="w-full"
            :disabled="form.processing">
            {{ form.processing ? 'Sending Reset Link...' : 'Send Reset Link' }}
          </Button>
        </form>

        <!-- Return to Login -->
        <div class="text-center">
          <Link
            as="button"
            :href="route('login')"
            class="text-sm text-blue-600 hover:underline">
            Return to login
          </Link>
        </div>
      </div>

      <!-- Success Message -->
      <Transition
        name="fade"
        appear
      >
        <div v-if="showSuccessMessage"
          class="mt-4 bg-green-50 border-l-4 border-green-400 p-4">
          <div class="flex">
            <div class="flex-shrink-0">
              <CheckCircle
                class="h-5 w-5 text-green-400"
                aria-hidden="true"
              />
            </div>

            <div class="ml-3">
              <p class="text-sm text-green-700">
                {{ successMessage }}
              </p>
            </div>
          </div>
        </div>
      </Transition>
    </div>
  </div>
</template>


