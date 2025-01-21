<script setup>
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'

const props = defineProps({
  secretKey: String,
  qrCodeSvg: String
})

const form = useForm({
  code: '',
  secret_key: props.secretKey
})

const enableTwoFactor = () => {
  form.post(route('two-factor.enable.confirm'), {
    preserveScroll: true,
    onSuccess: () => {
      // Redirect to recovery codes page
    },
    onError: (errors) => {
      // Handle validation errors
    }
  })
}
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-100">
    <div class="w-full max-w-md p-8 space-y-6 bg-white shadow-md rounded-xl">
      <h2 class="text-2xl font-bold text-center">
        Enable Two-Factor Authentication
      </h2>

      <div class="text-center">
        <div
          v-html="qrCodeSvg"
          class="mx-auto w-64 h-64"
        />

        <p class="mt-4 text-sm text-gray-600">
          Scan this QR code with your authenticator app
        </p>
      </div>

      <form @submit.prevent="enableTwoFactor" class="space-y-4">
        <div>
          <Label>Verification Code</Label>
          <Input
            v-model="form.code"
            placeholder="Enter 6-digit code from your app"
            type="text"
            maxlength="6"
            :error="errors.code"
          />
        </div>

        <div class="text-sm text-gray-600">
          <p>
            Can't scan the QR code?
            <span class="font-semibold">Manual Entry Key:</span>
            {{ secretKey }}
          </p>
        </div>

        <Button
          type="submit"
          class="w-full"
          :disabled="!form.code"
        >
          Verify and Enable
        </Button>
      </form>
    </div>
  </div>
</template>
