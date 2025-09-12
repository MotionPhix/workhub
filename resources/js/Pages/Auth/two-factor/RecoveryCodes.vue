<script setup>
import {useForm} from '@inertiajs/vue3'

const props = defineProps({
  recoveryCodes: Array
})

const regenerateCodes = () => {
  const form = useForm({})
  form.post(route('two-factor.recovery-codes.regenerate'), {
    preserveScroll: true,
    onSuccess: () => {
      // Optionally show a success message
    }
  })
}

const copyToClipboard = () => {
  navigator.clipboard.writeText(props.recoveryCodes.join('\n'))
    .then(() => {
      // Show copied notification
      alert('Recovery codes copied to clipboard')
    })
    .catch(err => {
      console.error('Failed to copy:', err)
    })
}
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-100">
    <div class="w-full max-w-md p-8 space-y-6 bg-white shadow-md rounded-xl">
      <h2 class="text-2xl font-bold text-center">
        Two-Factor Recovery Codes
      </h2>

      <div class="text-center">
        <p class="text-sm text-gray-600 mb-4">
          Store these recovery codes in a secure location.
          They can be used to regain access to your account.
        </p>

        <div class="grid grid-cols-2 gap-2">
          <code
            v-for="code in recoveryCodes"
            :key="code"
            class="bg-gray-100 p-2 rounded text-center font-mono"
          >
            {{ code }}
          </code>
        </div>

        <Button
          @click="regenerateCodes"
          class="mt-4 w-full"
        >
          Regenerate Recovery Codes
        </Button>
      </div>
    </div>
  </div>
</template>


