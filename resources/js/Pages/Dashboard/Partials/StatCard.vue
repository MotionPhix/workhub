<script setup lang="ts">
import { type Component } from "vue";
import { computed } from 'vue';

interface Props {
  title: string;
  value: string | number;
  icon?: Component;
  trend?: 'up' | 'down' | 'neutral';
  trendValue?: string;
}

const props = withDefaults(defineProps<Props>(), {
  trend: 'neutral',
  trendValue: undefined
});

const trendColor = computed(() => {
  switch (props.trend) {
    case 'up':
      return 'text-emerald-500 dark:text-emerald-400';
    case 'down':
      return 'text-rose-500 dark:text-rose-400';
    default:
      return 'text-gray-500 dark:text-gray-400';
  }
});

const trendIcon = computed(() => {
  switch (props.trend) {
    case 'up':
      return '↑';
    case 'down':
      return '↓';
    default:
      return '→';
  }
});
</script>

<template>
  <div class="relative p-6 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm hover:shadow-md transition-all duration-300 group">
    <div class="absolute inset-0 bg-gradient-to-br from-primary/5 to-transparent dark:from-primary/10 rounded-xl opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>

    <div class="relative flex flex-row lg:flex-col items-start space-x-4 lg:space-x-0">
      <!-- Icon Container -->
      <div
        v-if="icon"
        :class="trendColor"
        class="flex-shrink-0 w-12 h-12 bg-primary/10 dark:bg-primary/20 text-primary dark:text-primary-foreground rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
        <component :is="icon" class="w-6 h-6" />
      </div>

      <div class="flex-1 min-w-0 lg:mt-4">
        <!-- Title -->
        <h4 class="font-medium text-muted-foreground dark:text-gray-400 mb-1">
          {{ title }}
        </h4>

        <!-- Value -->
        <div class="flex items-baseline space-x-2">
          <p class="text-2xl font-bold text-foreground dark:text-white truncate">
            {{ typeof value === 'float' ? value.toFixed(2) : value }}
          </p>

          <!-- Trend Indicator -->
          <div
            v-if="trendValue"
            :class="['text-sm font-medium flex items-center space-x-1', trendColor]">
            <span>{{ trendIcon }}</span>
            <!--span>{{ trendValue }}</span-->
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
