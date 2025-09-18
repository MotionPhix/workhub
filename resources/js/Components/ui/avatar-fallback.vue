<script setup lang="ts">
import { computed } from 'vue'

interface Props {
  class?: string
}

const props = withDefaults(defineProps<Props>(), {
  class: ''
})

const classes = computed(() =>
  `flex h-full w-full items-center justify-center rounded-full bg-muted ${props.class}`
)
</script>

<template>
  <span :class="classes">
    <slot />
  </span>
</template>

