<script setup>
import {ref} from 'vue'
import {useForm} from '@inertiajs/vue3'
import {Avatar, AvatarImage} from "@/components/ui/avatar";

const props = defineProps({
  currentAvatar: {
    type: String,
    default: '/default-avatar.png'
  }
})

const avatarInput = ref(null)
const avatarPreview = ref(null)
const error = ref(null)

const form = useForm({
  avatar: null
})

const triggerFileInput = () => {
  avatarInput.value.click()
}

const handleFileUpload = (event) => {
  const file = event.target.files[0]

  // Reset previous error
  error.value = null

  // Validate file
  if (!file) return

  // Check file type
  const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp']
  if (!allowedTypes.includes(file.type)) {
    error.value = 'Invalid file type. Please upload an image.'
    return
  }

  // Check file size (10MB max)
  if (file.size > 10 * 1024 * 1024) {
    error.value = 'File is too large. Maximum size is 10MB.'
    return
  }

  // Create preview
  const reader = new FileReader()
  reader.onload = (e) => {
    avatarPreview.value = e.target.result
  }
  reader.readAsDataURL(file)

  // Prepare for upload
  form.avatar = file
}

const uploadAvatar = () => {
  form.post(route('profile.update-avatar'), {
    preserveScroll: true,
    onSuccess: () => {
      // Reset input and preview
      avatarInput.value.value = null
      avatarPreview.value = null
    },
    onError: (errors) => {
      error.value = errors.avatar
    }
  })
}
</script>

<template>
  <div class="avatar-upload">
    <div class="relative">
      <!-- Avatar Preview -->
      <Avatar>
        <AvatarImage
          :src="avatarPreview || currentAvatar"
          class="w-24 h-24 rounded-full object-cover"
        />
      </Avatar>

      <!-- File Input -->
      <input
        type="file"
        ref="avatarInput"
        @change="handleFileUpload"
        accept="image/*"
        class="hidden"
      />

      <!-- Upload Button -->
      <button
        @click="triggerFileInput"
        class="absolute bottom-0 right-0 bg-blue-500 text-white rounded-full p-2">
        <Icon name="hi-upload" class="w-4 h-4"/>
      </button>
    </div>

    <!-- Error Message -->
    <p v-if="error" class="text-red-500 text-sm mt-2">
      {{ error }}
    </p>
  </div>
</template>


