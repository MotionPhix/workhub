<script setup lang="ts">
import { Link } from "@inertiajs/vue3";

defineProps<{
  href: string
  active?: boolean
}>()
</script>

<template>
  <Link
    as="button"
    :href="href"
    class="flex flex-col sm:flex-row sm:gap-1 items-center hover:text-blue-500 dark:hover:text-blue-400 transition-colors"
    :class="active ? 'text-blue-600 dark:text-blue-400 font-semibold' : 'text-gray-600 dark:text-gray-300'">
    <slot/>
  </Link>
</template>


