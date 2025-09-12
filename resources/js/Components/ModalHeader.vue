<template>
  <div class="flex items-center justify-between p-6 border-b">
    <div class="flex items-center gap-3">
      <slot name="icon">
        <div class="w-6 h-6 bg-primary/10 rounded flex items-center justify-center">
          <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
          </svg>
        </div>
      </slot>
      <div>
        <h3 class="text-lg font-semibold text-foreground">
          <slot name="title">Modal Title</slot>
        </h3>
        <p v-if="$slots.description" class="text-sm text-muted-foreground">
          <slot name="description"></slot>
        </p>
      </div>
    </div>
    <button
      type="button"
      @click="$emit('close')"
      class="text-muted-foreground hover:text-foreground transition-colors p-1 rounded hover:bg-muted"
    >
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
      </svg>
    </button>
  </div>
</template>

<script setup lang="ts">
defineEmits<{
  close: []
}>()
</script>