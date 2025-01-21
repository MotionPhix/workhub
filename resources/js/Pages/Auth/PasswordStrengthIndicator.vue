<script setup>
import { ref, computed, watch } from 'vue'
import {
  CheckIcon,
  XIcon
} from 'lucide-vue-next'

const props = defineProps({
  password: {
    type: String,
    default: ''
  }
})

const passwordRequirements = ref([
  {
    id: 'length',
    message: 'At least 8 characters long',
    met: false,
    check: (password) => password.length >= 8
  },
  {
    id: 'uppercase',
    message: 'Contains an uppercase letter',
    met: false,
    check: (password) => /[A-Z]/.test(password)
  },
  {
    id: 'lowercase',
    message: 'Contains a lowercase letter',
    met: false,
    check: (password) => /[a-z]/.test(password)
  },
  {
    id: 'number',
    message: 'Contains a number',
    met: false,
    check: (password) => /[0-9]/.test(password)
  },
  {
    id: 'special',
    message: 'Contains a special character',
    met: false,
    check: (password) => /[!@#$%^&*(),.?":{}|<>]/.test(password)
  }
])

// Watch password and update requirements
watch(() => props.password, (newPassword) => {
  passwordRequirements.value.forEach(req => {
    req.met = req.check(newPassword)
  })
})

// Calculate strength percentage
const strengthPercentage = computed(() => {
  const metRequirements = passwordRequirements.value.filter(req => req.met)
  return (metRequirements.length / passwordRequirements.value.length) * 100
})

// Strength bar color
const strengthBarClass = computed(() => {
  if (strengthPercentage.value <= 20) return 'bg-red-500 h-1'
  if (strengthPercentage.value <= 40) return 'bg-orange-500 h-1'
  if (strengthPercentage.value <= 60) return 'bg-yellow-500 h-1'
  if (strengthPercentage.value <= 80) return 'bg-green-500 h-1'
  return 'bg-green-600 h-1'
})
</script>

<template>
  <div>
    <div class="h-1 w-full bg-gray-200 rounded-full overflow-hidden mb-2">
      <div
        :class="strengthBarClass"
        :style="{ width: `${strengthPercentage}%` }" />
    </div>

    <div class="flex justify-between text-xs text-gray-600">
      <span>Weak</span>
      <span>Strong</span>
    </div>

    <div class="mt-2">
      <ul class="space-y-1 text-sm text-gray-600">
        <li
          v-for="requirement in passwordRequirements"
          :key="requirement.id"
          class="flex items-center">
          <CheckIcon
            v-if="requirement.met"
            class="h-4 w-4 text-green-500 mr-2"
          />
          <XIcon
            v-else
            class="h-4 w-4 text-red-500 mr-2"
          />
          <span>{{ requirement.message }}</span>
        </li>
      </ul>
    </div>
  </div>
</template>
