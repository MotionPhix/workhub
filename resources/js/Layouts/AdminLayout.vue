<script setup>
import { ref, computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import ResponsiveNavLink from '@/components/ResponsiveNavLink.vue'
import ThemeSwitcher from '@/components/ThemeSwitcher.vue'
import UserAvatar from '@/components/UserAvatar.vue'
import { Button } from '@/components/ui/button'
import { DropdownMenuLabel } from '@/components/ui/dropdown-menu'
import { Separator } from '@/components/ui/separator'
import { Power, UserIcon } from 'lucide-vue-next'
import { useTheme } from '@/composables/useTheme'

const showingNavigationDropdown = ref(false)

const appName = computed(() => window?.AppConfig?.name || 'WorkHub')

// Initialize theme for this layout
const { isDark } = useTheme()
</script>

<template>

  <!-- Admin Navigation -->
  <nav class="sticky top-0 z-50 bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700 shadow-xs">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex justify-between items-center h-16">
        <!-- Left Section - Brand -->
        <div class="flex items-center">
          <Link :href="route('admin.dashboard')"
                class="flex items-center">
          <h1 class="text-xl font-bold text-gray-900 dark:text-white">
            {{ appName }} <span class="text-sm text-gray-500 dark:text-gray-400">Admin</span>
          </h1>
          </Link>
        </div>

        <!-- Center Section - Admin Navigation -->
        <div class="hidden md:flex space-x-8">
          <Link :href="route('admin.dashboard')"
                :class="[
                  'px-3 py-2 rounded-md text-sm font-medium transition-colors',
                  route().current('admin.dashboard')
                    ? 'bg-blue-100 text-blue-700 dark:bg-blue-800 dark:text-blue-200'
                    : 'text-gray-600 hover:text-blue-600 dark:text-gray-300 dark:hover:text-blue-400'
                ]">
          Dashboard
          </Link>

          <Link :href="route('admin.invitations.index')"
                :class="[
                  'px-3 py-2 rounded-md text-sm font-medium transition-colors',
                  route().current('admin.invitations.*')
                    ? 'bg-blue-100 text-blue-700 dark:bg-blue-800 dark:text-blue-200'
                    : 'text-gray-600 hover:text-blue-600 dark:text-gray-300 dark:hover:text-blue-400'
                ]">
          Invitations
          </Link>

          <Link :href="route('admin.users.index')"
                :class="[
                  'px-3 py-2 rounded-md text-sm font-medium transition-colors',
                  route().current('admin.users.*')
                    ? 'bg-blue-100 text-blue-700 dark:bg-blue-800 dark:text-blue-200'
                    : 'text-gray-600 hover:text-blue-600 dark:text-gray-300 dark:hover:text-blue-400'
                ]">
          Users
          </Link>

          <Link :href="route('admin.reports.index')"
                :class="[
                  'px-3 py-2 rounded-md text-sm font-medium transition-colors',
                  route().current('admin.reports.*')
                    ? 'bg-blue-100 text-blue-700 dark:bg-blue-800 dark:text-blue-200'
                    : 'text-gray-600 hover:text-blue-600 dark:text-gray-300 dark:hover:text-blue-400'
                ]">
          Reports
          </Link>

          <Link :href="route('admin.insights.index')"
                :class="[
                  'px-3 py-2 rounded-md text-sm font-medium transition-colors',
                  route().current('admin.insights.*')
                    ? 'bg-blue-100 text-blue-700 dark:bg-blue-800 dark:text-blue-200'
                    : 'text-gray-600 hover:text-blue-600 dark:text-gray-300 dark:hover:text-blue-400'
                ]">
          Insights
          </Link>
        </div>

        <!-- Right Section - User Menu -->
        <div class="flex items-center space-x-4">
          <!-- Theme Switcher -->
          <ThemeSwitcher />

          <!-- Back to Member View -->
          <Link :href="route('dashboard')"
                class="px-3 py-2 text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition-colors">
          Member View
          </Link>

          <!-- User Menu -->
          <div class="relative">
            <DropdownMenu>
              <DropdownMenuTrigger as-child>
                <Button type="button"
                        class="rounded-full size-6 flex items-center justify-center">
                  <UserAvatar :user="$page.props.auth.user"
                              :fallback="$page.props.auth.user.name?.charAt(0) || 'U'" />
                </Button>
              </DropdownMenuTrigger>

              <DropdownMenuContent align="end">
                <DropdownMenuLabel class="grid">
                  <span>{{ $page.props.auth.user.name }}</span>
                  <span class="font-light">{{ $page.props.auth.user.email }}</span>
                </DropdownMenuLabel>

                <Separator />

                <DropdownMenuItem as-child>
                  <Link as="button"
                        class="cursor-pointer w-full"
                        :href="route('admin.profile.index', $page.props.auth.user.uuid)">
                  <UserIcon />
                  Profile
                  </Link>
                </DropdownMenuItem>

                <DropdownMenuItem as-child>
                  <Link class="cursor-pointer w-full"
                        :href="route('logout')"
                        method="post"
                        as="button">
                  <Power />
                  Log Out
                  </Link>
                </DropdownMenuItem>
              </DropdownMenuContent>
            </DropdownMenu>
          </div>
        </div>
      </div>

      <!-- Mobile Navigation Menu -->
      <div class="md:hidden"
           v-show="showingNavigationDropdown">
        <div class="pt-2 pb-3 space-y-1">
          <ResponsiveNavLink :href="route('admin.dashboard')"
                             :active="route().current('admin.dashboard')">
            Dashboard
          </ResponsiveNavLink>
          <ResponsiveNavLink :href="route('admin.invitations.index')"
                             :active="route().current('admin.invitations.*')">
            Invitations
          </ResponsiveNavLink>
          <ResponsiveNavLink :href="route('admin.users.index')"
                             :active="route().current('admin.users.*')">
            Users
          </ResponsiveNavLink>
          <ResponsiveNavLink :href="route('admin.reports.index')"
                             :active="route().current('admin.reports.*')">
            Reports
          </ResponsiveNavLink>
          <ResponsiveNavLink :href="route('admin.insights.index')"
                             :active="route().current('admin.insights.*')">
            Insights
          </ResponsiveNavLink>
        </div>
      </div>
    </div>
  </nav>

  <!-- Main Content Area -->
  <div class="inset-0 fixed top-16 bg-gray-100 dark:bg-gray-900 overflow-y-auto">

    <main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
      <slot />
    </main>

  </div>
</template>
