<script setup lang="ts">
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import AdminDashboard from '@/Pages/Dashboard/Partials/AdminDashboard.vue'
import ManagerDashboard from '@/Pages/Dashboard/Partials/ManagerDashboard.vue'
import EmployeeDashboard from '@/Pages/Dashboard/Partials/EmployeeDashboard.vue'
import AppLayout from '@/Layouts/AppLayout.vue'

interface User {
  id: number
  uuid: string
  name: string
  email: string
  gender: string
  avatar: string
  roles: Array<{
    id: number
    name: string
    guard_name: string
  }>
}

// Get the authenticated user from Inertia shared props
const user = computed<User>(() => usePage().props.auth.user)

// Determine which dashboard to render based on user role
const currentDashboard = computed(() => {
  const userRoles = user.value.roles.map(role => role.name)

  if (userRoles.includes('admin')) {
    return AdminDashboard
  }

  if (userRoles.includes('managing director') || userRoles.includes('general manager')) {
    return ManagerDashboard
  }

  return EmployeeDashboard
})

// Get role-specific title
const dashboardTitle = computed(() => {
  const userRoles = user.value.roles.map(role => role.name)

  if (userRoles.includes('admin')) {
    return 'Organization Dashboard'
  }

  if (userRoles.includes('managing director') || userRoles.includes('general manager')) {
    return 'Team Performance Dashboard'
  }

  return 'Personal Dashboard'
})
</script>

<template>
  <AppLayout>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
        {{ dashboardTitle }}
      </h2>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <component
          :is="currentDashboard"
          v-bind="$page.props.dashboard"
          :user="$page.props.auth.user"
        />
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
.py-12 {
  @apply transition-all duration-300;
}
</style>
