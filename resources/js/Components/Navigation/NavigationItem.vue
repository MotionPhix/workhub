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
    class="flex flex-col sm:flex-row sm:gap-1 items-center hover:text-blue-500"
    :class="active ? 'text-blue-600 font-semibold' : 'text-gray-600'">
    <slot/>
  </Link>
</template>


