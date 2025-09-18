<script setup lang="ts">
import { computed } from 'vue'
import { type LucideIcon } from 'lucide-vue-next'

interface Props {
  title?: string
  description?: string
  icon?: LucideIcon
  headerActions?: boolean
  hover?: boolean
  gradient?: string
  padding?: string
}

const props = withDefaults(defineProps<Props>(), {
  hover: true,
  gradient: 'from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-900',
  padding: 'p-6'
})

const cardClasses = computed(() => [
  'bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm transition-all duration-300',
  {
    'hover:shadow-lg hover:shadow-gray-200/50 dark:hover:shadow-gray-900/50': props.hover,
  }
])

const headerClasses = computed(() => [
  'flex items-center justify-between mb-6',
  {
    'mb-4': !props.description
  }
])
</script>

<template>
  <div :class="cardClasses">
    <!-- Subtle gradient background -->
    <div
      v-if="hover"
      :class="[
        'absolute inset-0 opacity-0 hover:opacity-[0.02] transition-opacity duration-300 bg-gradient-to-br rounded-xl',
        gradient
      ]">
    </div>

    <!-- Card Content -->
    <div
      :class="['relative z-10', padding]">
      <!-- Header Section -->
      <div v-if="title || icon || $slots.header" :class="headerClasses">
        <div class="flex-1 min-w-0">
          <div v-if="title || icon" class="flex items-center gap-3">
            <div
              v-if="icon"
              class="p-2 rounded-lg bg-blue-50 dark:bg-blue-900/20 self-start">
              <component
                :is="icon"
                class="w-5 h-5 text-blue-600 dark:text-blue-400"
              />
            </div>

            <div v-if="title">
              <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                {{ title }}
              </h2>

              <p
                v-if="description"
                class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                {{ description }}
              </p>
            </div>
          </div>
        </div>

        <!-- Header Actions Slot -->
        <div v-if="$slots.header" class="flex items-center gap-2 self-start">
          <slot name="header" />
        </div>
      </div>

      <!-- Main Content -->
      <div class="relative">
        <slot />
      </div>

      <!-- Footer Actions -->
      <div v-if="$slots.footer" class="mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
        <slot name="footer" />
      </div>
    </div>
  </div>
</template>
