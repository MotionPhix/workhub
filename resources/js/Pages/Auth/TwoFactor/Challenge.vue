<script setup>
import { ref, computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import AuthLayout from '@/Layouts/AuthLayout.vue'
import {Label} from "@/Components/ui/label";
import {Input} from '@/Components/ui/input'

const form = useForm({
  code: '',
  recovery_code: ''
})

const showRecoveryOption = ref(false)
const error = ref(null)
const errors = computed(() => form.errors)

const submitChallenge = () => {
  const route = showRecoveryOption.value
    ? route('two-factor.verify-recovery')
    : route('two-factor.verify')

  form.post(route, {
    onError: (err) => {
      error.value = err.message || 'Authentication failed'
      form.reset('code', 'recovery_code')
    }
  })
}
</script>

<template>
  <AuthLayout>
    <div class="text-center mb-6">
      <h2 class="text-2xl font-bold">Two-Factor Authentication</h2>
      <p class="text-gray-600">
        Please enter the 6-digit code from your authenticator app
      </p>
    </div>

    <form @submit.prevent="submitChallenge" class="space-y-6">
      <div>
        <Label for="code">Authentication Code</Label>
        <Input
          id="code"
          type="text"
          v-model="form.code"
          placeholder="Enter 6-digit code"
          maxlength="6"
          required
          :error="errors.code"
        />
      </div>

      <div class="text-center">
        <Button
          type="submit"
          class="w-full"
          :disabled="form.processing"
        >
          {{ form.processing ? 'Verifying...' : 'Verify Code' }}
        </Button>
      </div>

      <div class="text-center">
        <button
          type="button"
          @click="showRecoveryOption = !showRecoveryOption"
          class="text-sm text-blue-600 hover:underline"
        >
          Use a recovery code instead
        </button>
      </div>

      <div v-if="showRecoveryOption" class="mt-4">
        <Label for="recovery-code">Recovery Code</Label>
        <Input
          id="recovery-code"
          type="text"
          v-model="form.recovery_code"
          placeholder="Enter recovery code"
          :error="errors.recovery_code"
        />
      </div>

      <div v-if="error" class="alert alert-danger">
        {{ error }}
      </div>
    </form>
  </AuthLayout>
</template>
