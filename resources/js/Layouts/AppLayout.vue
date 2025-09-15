<script setup>
import MobileNavigation from '@/components/navigation/MobileNavigation.vue'
import DesktopNavigation from '@/components/navigation/DesktopNavigation.vue'
import ResponsiveFooter from '@/components/navigation/ResponsiveFooter.vue'
import { useDeviceDetection } from '@/composables/useDeviceDetection'
import { useTheme } from '@/composables/useTheme'
import {Toaster} from "vue-sonner";
import {router} from "@inertiajs/vue3";
import {PowerIcon, UserIcon} from "lucide-vue-next";
import UserAvatar from "@/components/UserAvatar.vue";
import {getInitials} from "@/lib/stringUtils";
import {ref} from "vue";

const { isMobile, isTablet, isDesktop } = useDeviceDetection()

// Initialize theme for this layout
const { isDark } = useTheme()

// Mobile dropdown state
const isMobileDropdownOpen = ref(false)

const toggleMobileDropdown = () => {
  isMobileDropdownOpen.value = !isMobileDropdownOpen.value
}
</script>

<template>
  <Toaster rich-colors :close-button="true" :expand="true" />

  <div class="min-h-screen bg-gray-50 dark:bg-gray-900 transition-colors duration-300">
    <nav class="sticky top-0 z-50">
      <MobileNavigation v-if="isMobile" />
      <DesktopNavigation v-if="isTablet || isDesktop" />

      <!-- Mobile user menu dropdown -->
      <div v-if="isMobile" class="fixed top-5 right-5">
        <div class="relative">
          <button
            @click="toggleMobileDropdown"
            class="focus:outline-none focus:ring-2 focus:ring-blue-500 rounded-full"
          >
            <UserAvatar
              :fallback="getInitials($page.props.auth.user.name)"
            />
          </button>

          <div
            v-if="isMobileDropdownOpen"
            class="absolute right-0 top-12 w-56 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg py-2 z-50"
          >
            <div class="px-4 py-2 border-b border-gray-200 dark:border-gray-700">
              <div class="grid">
                <strong class="text-gray-900 dark:text-white">{{ $page.props.auth.user.name }}</strong>
                <span class="text-gray-600 dark:text-gray-400">{{ $page.props.auth.user.email }}</span>
              </div>
            </div>

            <button
              @click="() => {
                router.visit(route('profile.index', $page.props.auth.user.uuid), { replace: true })
                isMobileDropdownOpen = false
              }"
              class="w-full text-left px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white flex items-center"
            >
              <UserIcon class="w-4 h-4 mr-2" />
              Profile
            </button>

            <div class="border-t border-gray-200 dark:border-gray-700 my-1"></div>

            <button
              @click="() => {
                router.visit(route('logout'), {
                  method: 'post',
                  replace: true
                })
                isMobileDropdownOpen = false
              }"
              class="w-full text-left px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white flex items-center"
            >
              <PowerIcon class="w-4 h-4 mr-2" />
              Logout
            </button>
          </div>
        </div>
      </div>
    </nav>

    <main class="px-4 py-6 mx-auto sm:px-6 lg:px-8 max-w-4xl bg-transparent">
      <slot />
    </main>

    <footer class="mx-auto max-w-4xl bg-transparent text-gray-600 dark:text-gray-400">
      <ResponsiveFooter />
    </footer>
  </div>
</template>


