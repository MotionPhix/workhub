<script setup>
import { ref, onMounted } from 'vue'
import MobileNavigation from '@/components/navigation/MobileNavigation.vue'
import DesktopNavigation from '@/components/navigation/DesktopNavigation.vue'
import ResponsiveFooter from '@/components/navigation/ResponsiveFooter.vue'
import { useDeviceDetection } from '@/composables/useDeviceDetection'
import {Toaster} from "vue-sonner";
import {router} from "@inertiajs/vue3";
import {PowerIcon, UserIcon} from "lucide-vue-next";
import UserAvatar from "@/components/UserAvatar.vue";
import {getInitials} from "@/lib/stringUtils";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger
} from "@/components/ui/dropdown-menu";

const darkMode = ref(false)
const { isMobile, isTablet, isDesktop } = useDeviceDetection()

onMounted(() => {
  // Check system preference or stored preference
  darkMode.value = localStorage.getItem('theme') === 'dark' ||
    (window.matchMedia('(prefers-color-scheme: dark)').matches)
})
</script>

<template>
  <Toaster rich-colors />

  <div class="min-h-screen transition-colors duration-300">
    <nav class="sticky top-0 z-50">
      <MobileNavigation v-if="isMobile" />
      <DesktopNavigation v-if="isTablet || isDesktop" />

      <DropdownMenu :modal="false">
        <DropdownMenuTrigger v-if="isMobile" class="fixed top-5 right-5">
          <UserAvatar
            :fallback="getInitials($page.props.auth.user.name)"
          />
        </DropdownMenuTrigger>

        <DropdownMenuContent align="end">
          <DropdownMenuLabel>
            <div class="grid">
              <strong>{{ $page.props.auth.user.name }}</strong>
              <span>{{ $page.props.auth.user.email }}</span>
            </div>
          </DropdownMenuLabel>

          <DropdownMenuSeparator />

          <DropdownMenuItem
            @click="router.visit(route('profile.index', $page.props.auth.user.uuid), { replace: true })">
            <UserIcon />
            Profile
          </DropdownMenuItem>

          <DropdownMenuSeparator />

          <DropdownMenuItem
            @click="router.visit(route('logout'), {
              method: 'post',
              replace: true
            })">
            <PowerIcon />
            Logout
          </DropdownMenuItem>
        </DropdownMenuContent>
      </DropdownMenu>
    </nav>

    <main class="px-4 py-6 mx-auto sm:px-6 lg:px-8 max-w-4xl">
      <slot />
    </main>

    <footer class="mx-auto max-w-4xl">
      <ResponsiveFooter />
    </footer>
  </div>
</template>


