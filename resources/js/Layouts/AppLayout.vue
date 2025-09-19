<script setup>
import { ref, computed } from 'vue'
import { Head } from '@inertiajs/vue3'

defineProps({
  title: {
    type: String,
    default: ''
  }
})
import { Link } from '@inertiajs/vue3'
import ResponsiveFooter from '@/components/navigation/ResponsiveFooter.vue'
import { useDeviceDetection } from '@/composables/useDeviceDetection'
import { useTheme } from '@/composables/useTheme'
import { Toaster } from "vue-sonner"
import {
  Sheet,
  SheetContent,
  SheetHeader,
  SheetTitle,
  SheetTrigger,
  SheetClose
} from '@/components/ui/sheet'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
  DropdownMenuLabel,
} from '@/components/ui/dropdown-menu'
import { Button } from '@/components/ui/button'
import { Separator } from '@/components/ui/separator'
import ThemeSwitcher from '@/components/ThemeSwitcher.vue'
import UserAvatar from '@/components/UserAvatar.vue'
import NotificationDropdown from '@/components/NotificationDropdown.vue'
import {
  Menu,
  Home,
  FolderKanban,
  FileText,
  BarChart3,
  User,
  Settings,
  Power,
  X
} from "lucide-vue-next"

const { isMobile, isTablet, isDesktop } = useDeviceDetection()
const { isDark } = useTheme()

const appName = computed(() => window?.AppConfig?.name || 'WorkHub')
const isSheetOpen = ref(false)

// Navigation items
const navItems = [
  { label: 'Dashboard', route: 'dashboard', pattern: 'dashboard', icon: Home },
  { label: 'Projects', route: 'projects.index', pattern: 'projects.*', icon: FolderKanban },
  { label: 'Tasks', route: 'work-entries.index', pattern: 'work-entries.*', icon: FileText },
  { label: 'Reports', route: 'reports.index', pattern: 'reports.*', icon: BarChart3 },
]

const closeSheet = () => {
  isSheetOpen.value = false
}
</script>

<template>
  <Head :title="title" />
  <Toaster rich-colors :close-button="true" :expand="true" />

  <div class="min-h-screen bg-gray-50 dark:bg-gray-900 transition-colors duration-300">
    <!-- Navigation -->
    <nav class="sticky top-0 z-50 bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700 shadow-sm">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
          <!-- Left Section - Brand & Mobile Menu -->
          <div class="flex items-center">
            <!-- Mobile Menu Sheet -->
            <Sheet v-model:open="isSheetOpen">
              <SheetTrigger as-child>
                <Button variant="ghost" size="icon" class="md:hidden mr-2">
                  <Menu class="h-5 w-5" />
                </Button>
              </SheetTrigger>
              <SheetContent side="left" class="w-80">
                <SheetHeader class="text-left">
                  <SheetTitle class="text-lg font-semibold">
                    {{ appName }}
                  </SheetTitle>
                </SheetHeader>

                <!-- Mobile Navigation -->
                <div class="mt-6 space-y-1">
                  <Link
                    v-for="item in navItems"
                    :key="item.route"
                    :href="route(item.route)"
                    @click="closeSheet"
                    :class="[
                      'flex items-center px-3 py-2 rounded-md text-sm font-medium transition-colors',
                      route().current(item.pattern)
                        ? 'bg-blue-100 text-blue-700 dark:bg-blue-800 dark:text-blue-200'
                        : 'text-gray-600 hover:text-blue-600 hover:bg-gray-50 dark:text-gray-300 dark:hover:text-blue-400 dark:hover:bg-gray-700'
                    ]"
                  >
                    <component :is="item.icon" class="w-4 h-4 mr-3" />
                    {{ item.label }}
                  </Link>
                </div>

                <Separator class="my-6" />

                <!-- Mobile User Menu -->
                <div class="space-y-1">
                  <div class="px-3 py-2">
                    <div class="flex items-center space-x-3">
                      <UserAvatar
                        :user="$page.props.auth.user"
                        :fallback="$page.props.auth.user.name?.charAt(0) || 'U'"
                        class="h-8 w-8"
                      />
                      <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                          {{ $page.props.auth.user.name }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                          {{ $page.props.auth.user.email }}
                        </p>
                      </div>
                    </div>
                  </div>

                  <Link
                    :href="route('profile.index', $page.props.auth.user.uuid)"
                    @click="closeSheet"
                    class="flex items-center px-3 py-2 text-sm text-gray-600 hover:text-gray-900 hover:bg-gray-50 dark:text-gray-300 dark:hover:text-white dark:hover:bg-gray-700 rounded-md"
                  >
                    <User class="w-4 h-4 mr-3" />
                    Profile
                  </Link>

                  <Link
                    :href="route('profile.edit', $page.props.auth.user.uuid)"
                    @click="closeSheet"
                    class="flex items-center px-3 py-2 text-sm text-gray-600 hover:text-gray-900 hover:bg-gray-50 dark:text-gray-300 dark:hover:text-white dark:hover:bg-gray-700 rounded-md"
                  >
                    <Settings class="w-4 h-4 mr-3" />
                    Settings
                  </Link>

                  <Separator class="my-2" />

                  <Link
                    :href="route('logout')"
                    method="post"
                    @click="closeSheet"
                    class="flex items-center px-3 py-2 text-sm text-red-600 hover:text-red-900 hover:bg-red-50 dark:text-red-400 dark:hover:text-red-300 dark:hover:bg-red-900/20 rounded-md"
                  >
                    <Power class="w-4 h-4 mr-3" />
                    Logout
                  </Link>
                </div>
              </SheetContent>
            </Sheet>

            <!-- Brand -->
            <Link :href="route('dashboard')" class="flex items-center">
              <h1 class="text-xl font-bold text-gray-900 dark:text-white">
                {{ appName }}
              </h1>
            </Link>
          </div>

          <!-- Center Section - Desktop Navigation -->
          <div class="hidden md:flex items-center space-x-4">
            <Link
              v-for="item in navItems"
              :key="item.route"
              :href="route(item.route)"
              :class="[
                'px-3 py-2 rounded-md text-sm font-medium transition-colors',
                route().current(item.pattern)
                  ? 'bg-blue-100 text-blue-700 dark:bg-blue-800 dark:text-blue-200'
                  : 'text-gray-600 hover:text-blue-600 dark:text-gray-300 dark:hover:text-blue-400'
              ]"
            >
              {{ item.label }}
            </Link>
          </div>

          <!-- Right Section - Actions & User Menu -->
          <div class="flex items-center space-x-3">
            <!-- Theme Switcher -->
            <ThemeSwitcher />

            <!-- Notifications -->
            <NotificationDropdown />

            <!-- Desktop User Menu -->
            <div class="relative hidden md:block">
              <DropdownMenu>
                <DropdownMenuTrigger as-child>
                  <Button variant="ghost" size="sm" class="rounded-full h-8 w-8 p-0">
                    <UserAvatar
                      :user="$page.props.auth.user"
                      :fallback="$page.props.auth.user.name?.charAt(0) || 'U'"
                      class="h-8 w-8"
                    />
                  </Button>
                </DropdownMenuTrigger>

                <DropdownMenuContent align="end" class="w-56">
                  <DropdownMenuLabel>
                    <div class="flex flex-col space-y-1">
                      <p class="text-sm font-medium leading-none">
                        {{ $page.props.auth.user.name }}
                      </p>
                      <p class="text-xs leading-none text-muted-foreground">
                        {{ $page.props.auth.user.email }}
                      </p>
                    </div>
                  </DropdownMenuLabel>

                  <Separator />

                  <DropdownMenuItem as-child>
                    <Link :href="route('profile.index', $page.props.auth.user.uuid)" class="cursor-pointer">
                      <User class="mr-2 h-4 w-4" />
                      Profile
                    </Link>
                  </DropdownMenuItem>

                  <DropdownMenuItem as-child>
                    <Link :href="route('profile.edit', $page.props.auth.user.uuid)" class="cursor-pointer">
                      <Settings class="mr-2 h-4 w-4" />
                      Settings
                    </Link>
                  </DropdownMenuItem>

                  <Separator />

                  <DropdownMenuItem as-child>
                    <Link
                      :href="route('logout')"
                      method="post"
                      class="cursor-pointer text-red-600 focus:text-red-600 dark:text-red-400"
                    >
                      <Power class="mr-2 h-4 w-4" />
                      Logout
                    </Link>
                  </DropdownMenuItem>
                </DropdownMenuContent>
              </DropdownMenu>
            </div>
          </div>
        </div>
      </div>
    </nav>

    <!-- Main Content -->
    <main class="px-4 py-6 mx-auto sm:px-6 lg:px-8 max-w-4xl bg-transparent">
      <slot />
    </main>

    <!-- Footer -->
    <footer class="mx-auto max-w-4xl bg-transparent text-gray-600 dark:text-gray-400">
      <ResponsiveFooter />
    </footer>
  </div>
</template>


