<script setup lang="ts">
import { computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { ArrowRight, type LucideIcon } from 'lucide-vue-next'

interface Props {
  icon: LucideIcon
  title: string
  value: string | number
  suffix?: string
  subtitle?: string
  change?: string
  color: string
  bgColor: string
  iconColor: string
  route?: string
  clickable?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  clickable: true
})

const isClickable = computed(() => props.clickable && props.route)

const handleClick = () => {
  if (isClickable.value) {
    router.visit(route(props.route!))
  }
}
</script>

<template>
  <div
    class="metric-card group relative overflow-hidden bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm hover:shadow-lg transition-all duration-300 border border-gray-200 dark:border-gray-700"
    :class="{
      'cursor-pointer': isClickable,
      'hover:shadow-xl hover:scale-[1.02]': isClickable
    }"
    @click="handleClick"
  >
    <!-- Gradient Background -->
    <div
      :class="[
        'absolute inset-0 opacity-0 group-hover:opacity-5 transition-opacity duration-300 bg-gradient-to-br',
        color
      ]"
    ></div>

    <!-- Card Content -->
    <div class="relative z-10">
      <div class="flex items-start justify-between mb-4">
        <div :class="['p-3 rounded-lg', bgColor]">
          <component
            :is="icon"
            :class="['w-6 h-6', iconColor]"
          />
        </div>
        <div class="flex items-center gap-2">
          <div
            v-if="change"
            class="text-xs font-medium text-green-600 dark:text-green-400"
          >
            {{ change }}
          </div>
          <ArrowRight
            v-if="isClickable"
            class="w-4 h-4 text-gray-400 opacity-0 group-hover:opacity-100 transition-opacity"
          />
        </div>
      </div>

      <div>
        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">
          {{ title }}
        </h3>
        <p class="text-3xl font-bold text-gray-900 dark:text-white">
          {{ value }}<span
            v-if="suffix"
            class="text-lg font-medium text-gray-500 dark:text-gray-400"
          >{{ suffix }}</span>
        </p>
        <p
          v-if="subtitle"
          class="text-sm text-gray-500 dark:text-gray-400 mt-1"
        >
          {{ subtitle }}
        </p>
      </div>
    </div>

    <!-- Hover Effect Ring -->
    <div
      v-if="isClickable"
      :class="[
        'absolute inset-0 rounded-xl opacity-0 group-hover:opacity-20 transition-opacity duration-300 ring-2 ring-inset',
        iconColor.replace('text-', 'ring-')
      ]"
    ></div>
  </div>
</template>