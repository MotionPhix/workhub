<script setup lang="ts">
import { Checkbox } from "@/components/ui/checkbox";

defineProps<{
  modelValue?: boolean
  label?: string
}>()

defineEmits(['update:modelValue'])
</script>

<template>
  <label class="flex items-center space-x-2 text-sm text-[--color-foreground]">
    <Checkbox
      :checked="modelValue"
      @update:checked="$emit('update:modelValue', $event)"
      class="accent-[--color-checkbox-check] bg-[--color-checkbox-bg] border-[--color-checkbox-border] rounded focus:ring-2 focus:ring-[--color-ring]"
    />
    <span>{{ label }}</span>
  </label>
</template>
