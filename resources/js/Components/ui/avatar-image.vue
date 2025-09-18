<script setup lang="ts">
import { ref, onMounted } from 'vue'

interface Props {
  src: string
  alt: string
  class?: string
}

const props = withDefaults(defineProps<Props>(), {
  class: ''
})

const imageLoaded = ref(false)
const hasError = ref(false)

function onLoad() {
  imageLoaded.value = true
}

function onError() {
  hasError.value = true
}

onMounted(() => {
  const img = new Image()
  img.onload = onLoad
  img.onerror = onError
  img.src = props.src
})
</script>

<template>
  <img
    v-if="!hasError"
    :src="src"
    :alt="alt"
    :class="['aspect-square', 'h-full', 'w-full', props.class]"
    @load="onLoad"
    @error="onError"
  />
</template>

