import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

window.Pusher = Pusher

class NotificationService {
  constructor() {
    this.echo = new Echo({
      broadcaster: 'pusher',
      key: import.meta.env.VITE_PUSHER_APP_KEY,
      cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
      forceTLS: true
    })
  }

  listenForWorkEntryNotifications(userId) {
    this.echo.private(`App.Models.User.${userId}`)
      .notification((notification) => {
        this.handleNotification(notification)
      })
  }

  handleNotification(notification) {
    // Display toast or desktop notification
    this.$toast.success(notification.message, {
      position: 'top-right',
      duration: 3000
    })
  }
}

export default new NotificationService()
