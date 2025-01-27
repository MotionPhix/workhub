<script setup lang=ts>
import {Separator} from "@/Components/ui/separator";

withDefaults(
  defineProps<{
    spacing?: string
  }>(), {
    spacing: 'my-4'
  }
)
</script>

<template>

  <Separator :class="spacing" />

</template>
