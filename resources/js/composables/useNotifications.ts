import { ref, computed, onMounted, onUnmounted } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { toast } from 'vue-sonner'

interface Notification {
  id: string
  type: string
  title: string
  message: string
  data: any
  action_url?: string
  action_text?: string
  read_at?: string
  created_at: string
}

export function useNotifications() {
  const notifications = ref<Notification[]>([])
  const unreadCount = ref(0)
  const isLoading = ref(false)
  const isListening = ref(false)

  // Computed
  const unreadNotifications = computed(() =>
    notifications.value.filter(n => !n.read_at)
  )

  const hasUnread = computed(() => unreadCount.value > 0)

  // Functions
  const fetchNotifications = async () => {
    try {
      isLoading.value = true
      const response = await fetch('/api/notifications', {
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
      })

      if (response.ok) {
        const data = await response.json()
        notifications.value = data.notifications.data || []
      }
    } catch (error) {
      console.error('Failed to fetch notifications:', error)
    } finally {
      isLoading.value = false
    }
  }

  const fetchUnreadCount = async () => {
    try {
      const response = await fetch('/api/notifications/unread', {
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
      })

      if (response.ok) {
        const data = await response.json()
        unreadCount.value = data.count
      }
    } catch (error) {
      console.error('Failed to fetch unread count:', error)
    }
  }

  const markAsRead = async (notificationId: string) => {
    try {
      const response = await fetch(`/api/notifications/${notificationId}/read`, {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
      })

      if (response.ok) {
        // Update local state
        const notification = notifications.value.find(n => n.id === notificationId)
        if (notification) {
          notification.read_at = new Date().toISOString()
          unreadCount.value = Math.max(0, unreadCount.value - 1)
        }
      }
    } catch (error) {
      console.error('Failed to mark notification as read:', error)
    }
  }

  const markAllAsRead = async () => {
    try {
      const response = await fetch('/api/notifications/mark-all-read', {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
      })

      if (response.ok) {
        // Update local state
        notifications.value.forEach(notification => {
          notification.read_at = new Date().toISOString()
        })
        unreadCount.value = 0
      }
    } catch (error) {
      console.error('Failed to mark all notifications as read:', error)
    }
  }

  const deleteNotification = async (notificationId: string) => {
    try {
      const response = await fetch(`/api/notifications/${notificationId}`, {
        method: 'DELETE',
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
      })

      if (response.ok) {
        // Remove from local state
        const index = notifications.value.findIndex(n => n.id === notificationId)
        if (index !== -1) {
          const notification = notifications.value[index]
          if (!notification.read_at) {
            unreadCount.value = Math.max(0, unreadCount.value - 1)
          }
          notifications.value.splice(index, 1)
        }
      }
    } catch (error) {
      console.error('Failed to delete notification:', error)
    }
  }

  const clearAllNotifications = async () => {
    try {
      const response = await fetch('/api/notifications/clear', {
        method: 'DELETE',
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
      })

      if (response.ok) {
        notifications.value = []
        unreadCount.value = 0
      }
    } catch (error) {
      console.error('Failed to clear notifications:', error)
    }
  }

  const handleNotificationAction = async (notification: Notification) => {
    // Mark as read first
    if (!notification.read_at) {
      await markAsRead(notification.id)
    }

    // Navigate to action URL if available
    if (notification.action_url) {
      router.visit(notification.action_url)
    }
  }

  const playNotificationSound = () => {
    // Create a simple notification sound
    const audio = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmAaATiS2e/LeSsFJHfI7+DQRD8NGnC18NqJSSAILHjc8M+ERxgJNnbK9NpjIAk9f9br+dQ3FQ==')
    audio.volume = 0.3
    audio.play().catch(() => {
      // Ignore errors if audio playback fails
    })
  }

  const showNotificationToast = (notification: Notification) => {
    toast(notification.title, {
      description: notification.message,
      action: notification.action_url ? {
        label: notification.action_text || 'View',
        onClick: () => handleNotificationAction(notification)
      } : undefined,
    })
  }

  // Real-time listening setup
  const startListening = () => {
    if (isListening.value || !window.Echo) {
      return
    }

    try {
      // Try to get user ID from meta tag first
      let userId = document.querySelector('meta[name="user-id"]')?.getAttribute('content')

      // Fallback to page props if meta tag is not available
      if (!userId) {
        const page = usePage()
        userId = page.props.auth?.user?.id || page.props.auth?.user?.uuid
      }

      if (!userId) {
        console.warn('User ID not found, cannot listen for notifications')
        return
      }

      window.Echo.private(`user.${userId}`)
        .listen('notification.received', (event: any) => {
          console.log('Received notification:', event)

          // Add to notifications list
          notifications.value.unshift(event)
          unreadCount.value++

          // Show toast notification
          showNotificationToast(event)

          // Play sound
          playNotificationSound()
        })

      isListening.value = true
      console.log('Started listening for notifications')
    } catch (error) {
      console.error('Failed to start listening for notifications:', error)
    }
  }

  const stopListening = () => {
    if (!isListening.value || !window.Echo) {
      return
    }

    try {
      // Try to get user ID from meta tag first
      let userId = document.querySelector('meta[name="user-id"]')?.getAttribute('content')

      // Fallback to page props if meta tag is not available
      if (!userId) {
        const page = usePage()
        userId = page.props.auth?.user?.id || page.props.auth?.user?.uuid
      }

      if (userId) {
        window.Echo.leave(`user.${userId}`)
      }
      isListening.value = false
      console.log('Stopped listening for notifications')
    } catch (error) {
      console.error('Failed to stop listening for notifications:', error)
    }
  }

  // Lifecycle
  onMounted(() => {
    fetchUnreadCount()
    startListening()
  })

  onUnmounted(() => {
    stopListening()
  })

  return {
    // State
    notifications,
    unreadCount,
    isLoading,
    isListening,

    // Computed
    unreadNotifications,
    hasUnread,

    // Functions
    fetchNotifications,
    fetchUnreadCount,
    markAsRead,
    markAllAsRead,
    deleteNotification,
    clearAllNotifications,
    handleNotificationAction,
    startListening,
    stopListening,
  }
}