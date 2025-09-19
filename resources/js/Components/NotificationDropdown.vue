<template>
  <div class="relative">
    <!-- Notification Bell Button -->
    <button
      @click="toggleDropdown"
      class="relative p-2 text-gray-600 hover:text-gray-800 dark:text-gray-300 dark:hover:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 rounded-lg transition-colors"
    >
      <Bell class="w-5 h-5" />

      <!-- Unread Badge -->
      <span
        v-if="hasUnread"
        class="absolute -top-1 -right-1 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white bg-red-500 rounded-full min-w-[1.25rem] h-5"
      >
        {{ unreadCount > 99 ? '99+' : unreadCount }}
      </span>
    </button>

    <!-- Dropdown -->
    <div
      v-if="isOpen"
      class="absolute right-0 top-full mt-2 w-80 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg z-50"
    >
      <!-- Header -->
      <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
            Notifications
          </h3>
          <div class="flex items-center space-x-2">
            <button
              v-if="hasUnread"
              @click="handleMarkAllAsRead"
              class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
            >
              Mark all read
            </button>
            <button
              @click="clearAll"
              class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300"
            >
              Clear all
            </button>
          </div>
        </div>
      </div>

      <!-- Loading State -->
      <div
        v-if="isLoading"
        class="flex items-center justify-center py-8"
      >
        <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-500"></div>
      </div>

      <!-- Notifications List -->
      <div
        v-else-if="notifications.length > 0"
        class="max-h-96 overflow-y-auto"
      >
        <div
          v-for="notification in notifications"
          :key="notification.id"
          class="px-4 py-3 border-b border-gray-100 dark:border-gray-700 last:border-b-0 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
          :class="{ 'bg-blue-50 dark:bg-blue-900/20': !notification.read_at }"
        >
          <div class="flex items-start space-x-3">
            <!-- Notification Icon -->
            <div class="flex-shrink-0 mt-1">
              <div
                class="w-8 h-8 rounded-full flex items-center justify-center"
                :class="getNotificationIconClass(notification.type)"
              >
                <component :is="getNotificationIcon(notification.type)" class="w-4 h-4" />
              </div>
            </div>

            <!-- Notification Content -->
            <div class="flex-1 min-w-0">
              <div class="flex items-start justify-between">
                <div class="flex-1">
                  <p class="text-sm font-medium text-gray-900 dark:text-white">
                    {{ notification.title }}
                  </p>
                  <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    {{ notification.message }}
                  </p>
                  <p class="text-xs text-gray-500 dark:text-gray-500 mt-2">
                    {{ formatDate(notification.created_at) }}
                  </p>
                </div>

                <!-- Actions -->
                <div class="flex items-center space-x-1 ml-2">
                  <button
                    v-if="notification.action_url"
                    @click="handleAction(notification)"
                    class="text-xs text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
                  >
                    {{ notification.action_text || 'View' }}
                  </button>
                  <button
                    @click="deleteNotification(notification.id)"
                    class="text-gray-400 hover:text-red-500 dark:text-gray-500 dark:hover:text-red-400"
                  >
                    <X class="w-4 h-4" />
                  </button>
                </div>
              </div>
            </div>

            <!-- Unread Indicator -->
            <div
              v-if="!notification.read_at"
              class="w-2 h-2 bg-blue-500 rounded-full flex-shrink-0 mt-2"
            ></div>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div
        v-else
        class="px-4 py-8 text-center"
      >
        <Bell class="w-12 h-12 text-gray-300 dark:text-gray-600 mx-auto mb-4" />
        <p class="text-gray-500 dark:text-gray-400">No notifications yet</p>
      </div>

      <!-- Footer -->
      <div
        v-if="notifications.length > 5"
        class="px-4 py-3 border-t border-gray-200 dark:border-gray-700"
      >
        <button
          @click="viewAll"
          class="w-full text-center text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
        >
          View all notifications
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { Bell, X, FileText, Users, Calendar, AlertTriangle, CheckCircle, Info } from 'lucide-vue-next'
import { useNotifications } from '@/composables/useNotifications'
import { router } from '@inertiajs/vue3'

const isOpen = ref(false)

const {
  notifications,
  unreadCount,
  isLoading,
  hasUnread,
  fetchNotifications,
  markAsRead,
  markAllAsRead,
  deleteNotification,
  clearAllNotifications,
  handleNotificationAction,
} = useNotifications()

const toggleDropdown = () => {
  isOpen.value = !isOpen.value
  if (isOpen.value && notifications.value.length === 0) {
    fetchNotifications()
  }
}

const handleMarkAllAsRead = async () => {
  await markAllAsRead()
}

const clearAll = async () => {
  if (confirm('Are you sure you want to clear all notifications?')) {
    await clearAllNotifications()
  }
}

const handleAction = async (notification: any) => {
  await handleNotificationAction(notification)
  isOpen.value = false
}

const viewAll = () => {
  router.visit('/notifications')
  isOpen.value = false
}

const getNotificationIcon = (type: string) => {
  switch (type) {
    case 'work_entry_created':
    case 'work_entry_updated':
      return FileText
    case 'team_updates':
      return Users
    case 'project_deadlines':
      return Calendar
    case 'security_alerts':
      return AlertTriangle
    case 'task_completions':
      return CheckCircle
    default:
      return Info
  }
}

const getNotificationIconClass = (type: string) => {
  switch (type) {
    case 'work_entry_created':
    case 'work_entry_updated':
      return 'bg-blue-100 text-blue-600 dark:bg-blue-900 dark:text-blue-400'
    case 'team_updates':
      return 'bg-green-100 text-green-600 dark:bg-green-900 dark:text-green-400'
    case 'project_deadlines':
      return 'bg-orange-100 text-orange-600 dark:bg-orange-900 dark:text-orange-400'
    case 'security_alerts':
      return 'bg-red-100 text-red-600 dark:bg-red-900 dark:text-red-400'
    case 'task_completions':
      return 'bg-green-100 text-green-600 dark:bg-green-900 dark:text-green-400'
    default:
      return 'bg-gray-100 text-gray-600 dark:bg-gray-900 dark:text-gray-400'
  }
}

const formatDate = (dateString: string) => {
  const date = new Date(dateString)
  const now = new Date()
  const diffInMinutes = Math.floor((now.getTime() - date.getTime()) / (1000 * 60))

  if (diffInMinutes < 1) {
    return 'Just now'
  } else if (diffInMinutes < 60) {
    return `${diffInMinutes}m ago`
  } else if (diffInMinutes < 1440) {
    return `${Math.floor(diffInMinutes / 60)}h ago`
  } else {
    return `${Math.floor(diffInMinutes / 1440)}d ago`
  }
}

// Close dropdown when clicking outside
const handleClickOutside = (event: Event) => {
  const target = event.target as Element
  if (!target.closest('.relative')) {
    isOpen.value = false
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>