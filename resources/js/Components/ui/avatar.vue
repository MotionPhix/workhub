<script setup lang="ts">
import { computed } from 'vue'

interface Props {
  class?: string
}

const props = withDefaults(defineProps<Props>(), {
  class: ''
})

const classes = computed(() => 
  `relative flex h-10 w-10 shrink-0 overflow-hidden rounded-full ${props.class}`
)
</script>

<template>
  <span :class="classes">
    <slot />
  </span>
</template>
