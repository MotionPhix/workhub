<script setup>
import { ref, computed } from 'vue'

const appName = import.meta.env.VITE_APP_NAME || 'Work Tracker'
const currentYear = new Date().getFullYear()

const footerLinks = [
  { label: 'Privacy Policy', href: '/privacy' },
  { label: 'Terms of Service', href: '/terms' },
  { label: 'Contact', href: '/contact' }
]
</script>

<template>
  <footer class="py-6">
    <div class="container px-4 mx-auto sm:px-6 lg:px-8">
      <div class="flex flex-col items-center justify-between md:flex-row">
        <div class="text-center md:text-left">
          <p class="text-gray-600 dark:text-gray-300">
            © {{ currentYear }} {{ appName }}. All rights reserved.
          </p>
        </div>

        <div class="flex mt-4 space-x-4 md:mt-0">
          <a
            v-for="link in footerLinks"
            :key="link.href"
            :href="link.href"
            class="text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white">
            {{ link.label }}
          </a>
        </div>
      </div>
    </div>
  </footer>
</template>


