<script setup lang="ts">
import { Loader2Icon } from 'lucide-vue-next'

defineProps<{
  isLoading: boolean
}>()
</script>

<template>

  <Loader2Icon
    v-if="isLoading"
    class="mr-2 h-4 w-4 animate-spin"
  />

</template>
