<script setup lang="ts">
import {
  Avatar,
  AvatarImage,
  AvatarFallback
} from "@/components/ui/avatar";
import { useUserStore } from '@/stores/user'
import { storeToRefs } from 'pinia'
import { computed, toRaw } from "vue";

const props = withDefaults(
  defineProps<{
    size?: number,
    src?: string,
    alt?: string,
    fallback: string
  }>(), {
    size: 10
  })

const userStore = useUserStore()
const { user } = storeToRefs(userStore)

// Memoized computed properties to prevent unnecessary recalculations
const avatarSize = computed(() => `h-${props.size} w-${props.size}`)

const avatarSource = computed(() => {
  // Use toRaw to prevent potential reactivity issues
  const rawUser = toRaw(user.value)

  if (props.src) {
    return props.src
  }

  return rawUser.avatar
    ? rawUser.avatar
    : rawUser.gender === 'male'
      ? '/default-m-avatar.png'
      : '/default-f-avatar.png'
})

const avatarAlt = computed(() => {
  // Use toRaw to prevent potential reactivity issues
  const rawUser = toRaw(user.value)

  if (props.alt) {
    return props.alt
  }

  return rawUser.name
})

// Simplified background color computation
const avatarBackgroundClass = computed(() => {
  return user.value.gender === 'male' || user.value.gender === null
    ? 'bg-amber-300'
    : 'bg-blue-600'
})
</script>

<template>
  <Avatar
    class="flex items-center justify-center"
    :class="[
      avatarBackgroundClass,
      avatarSize
    ]">
    <AvatarImage
      :src="avatarSource"
      :alt="avatarAlt"
    />

    <AvatarFallback>
      {{ fallback }}
    </AvatarFallback>
  </Avatar>
</template>


