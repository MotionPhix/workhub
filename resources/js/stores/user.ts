import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

export const useUserStore = defineStore('user', () => {
  const user = ref(usePage().props.auth.user)

  // Computed property for safer access
  const userProfile = computed(() => ({
    ...user.value,
    avatar: user.value.avatar || (
      user.value.gender === 'male'
        ? '/default-m-avatar.png'
        : '/default-f-avatar.png'
    )
  }))

  const updateUser = (updatedUser) => {
    // Create a new object to trigger reactivity
    user.value = { ...user.value, ...updatedUser }
  }

  const updateAvatar = (avatarUrl) => {
    // Ensure a new object is created
    user.value = {
      ...user.value,
      avatar: avatarUrl
    }
  }

  return {
    user: userProfile, // Use computed property
    updateUser,
    updateAvatar
  }
})
