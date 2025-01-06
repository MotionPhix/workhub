<script setup lang="ts">
defineProps<{
  href: string
  active?: boolean
}>()
</script>

<template>
  <a
    :href="href"
    class="flex flex-col items-center hover:text-blue-500"
    :class="active ? 'text-blue-600' : 'text-gray-600'">
    <slot/>
  </a>
</template>
