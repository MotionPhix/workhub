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
import { Power, UserIcon, FolderKanban, ChevronDown, Plus, Users, Mail, BarChart3, TrendingUp, FileText } from 'lucide-vue-next'
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
        <div class="hidden md:flex items-center space-x-4">
          <!-- Dashboard Link -->
          <Link :href="route('admin.dashboard')"
                :class="[
                  'px-3 py-2 rounded-md text-sm font-medium transition-colors',
                  route().current('admin.dashboard')
                    ? 'bg-blue-100 text-blue-700 dark:bg-blue-800 dark:text-blue-200'
                    : 'text-gray-600 hover:text-blue-600 dark:text-gray-300 dark:hover:text-blue-400'
                ]">
          Dashboard
          </Link>

          <!-- Project Management Dropdown -->
          <DropdownMenu>
            <DropdownMenuTrigger as-child>
              <button
                :class="[
                  'px-3 py-2 rounded-md text-sm font-medium transition-colors flex items-center space-x-1',
                  route().current('admin.projects.*')
                    ? 'bg-blue-100 text-blue-700 dark:bg-blue-800 dark:text-blue-200'
                    : 'text-gray-600 hover:text-blue-600 dark:text-gray-300 dark:hover:text-blue-400'
                ]"
              >
                <FolderKanban class="w-4 h-4" />
                <span>Projects</span>
                <ChevronDown class="w-3 h-3" />
              </button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="start" class="w-48">
              <DropdownMenuItem disabled class="opacity-50">
                <div class="flex items-center space-x-2">
                  <FolderKanban class="w-4 h-4" />
                  <span>All Projects (Coming Soon)</span>
                </div>
              </DropdownMenuItem>
              <DropdownMenuItem disabled class="opacity-50">
                <div class="flex items-center space-x-2">
                  <Plus class="w-4 h-4" />
                  <span>New Project (Coming Soon)</span>
                </div>
              </DropdownMenuItem>
            </DropdownMenuContent>
          </DropdownMenu>

          <!-- User Management Dropdown -->
          <DropdownMenu>
            <DropdownMenuTrigger as-child>
              <button
                :class="[
                  'px-3 py-2 rounded-md text-sm font-medium transition-colors flex items-center space-x-1',
                  route().current('admin.users.*') || route().current('admin.invitations.*')
                    ? 'bg-blue-100 text-blue-700 dark:bg-blue-800 dark:text-blue-200'
                    : 'text-gray-600 hover:text-blue-600 dark:text-gray-300 dark:hover:text-blue-400'
                ]"
              >
                <Users class="w-4 h-4" />
                <span>Users</span>
                <ChevronDown class="w-3 h-3" />
              </button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="start" class="w-48">
              <DropdownMenuItem as-child>
                <Link :href="route('admin.users.index')" class="flex items-center space-x-2">
                  <Users class="w-4 h-4" />
                  <span>All Users</span>
                </Link>
              </DropdownMenuItem>
              <DropdownMenuItem as-child>
                <Link :href="route('admin.invitations.index')" class="flex items-center space-x-2">
                  <Mail class="w-4 h-4" />
                  <span>Invitations</span>
                </Link>
              </DropdownMenuItem>
            </DropdownMenuContent>
          </DropdownMenu>

          <!-- Analytics & Reports Dropdown -->
          <DropdownMenu>
            <DropdownMenuTrigger as-child>
              <button
                :class="[
                  'px-3 py-2 rounded-md text-sm font-medium transition-colors flex items-center space-x-1',
                  route().current('admin.reports.*') || route().current('admin.insights.*')
                    ? 'bg-blue-100 text-blue-700 dark:bg-blue-800 dark:text-blue-200'
                    : 'text-gray-600 hover:text-blue-600 dark:text-gray-300 dark:hover:text-blue-400'
                ]"
              >
                <BarChart3 class="w-4 h-4" />
                <span>Analytics</span>
                <ChevronDown class="w-3 h-3" />
              </button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="start" class="w-48">
              <DropdownMenuItem as-child>
                <Link :href="route('admin.insights.index')" class="flex items-center space-x-2">
                  <TrendingUp class="w-4 h-4" />
                  <span>Organization Insights</span>
                </Link>
              </DropdownMenuItem>
              <DropdownMenuItem as-child>
                <Link :href="route('admin.reports.index')" class="flex items-center space-x-2">
                  <FileText class="w-4 h-4" />
                  <span>Reports</span>
                </Link>
              </DropdownMenuItem>
            </DropdownMenuContent>
          </DropdownMenu>
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

        <!-- Mobile menu button -->
        <div class="md:hidden">
          <button
            @click="showingNavigationDropdown = !showingNavigationDropdown"
            class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 dark:text-gray-500 hover:text-gray-500 dark:hover:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-900 focus:outline-hidden focus:bg-gray-100 dark:focus:bg-gray-900 focus:text-gray-500 dark:focus:text-gray-400 transition duration-150 ease-in-out"
          >
            <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
              <path :class="{'hidden': showingNavigationDropdown, 'inline-flex': ! showingNavigationDropdown }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
              <path :class="{'hidden': ! showingNavigationDropdown, 'inline-flex': showingNavigationDropdown }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
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
          <ResponsiveNavLink :href="route('admin.users.index')"
                             :active="route().current('admin.users.*')">
            Users
          </ResponsiveNavLink>
          <ResponsiveNavLink :href="route('admin.invitations.index')"
                             :active="route().current('admin.invitations.*')">
            Invitations
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
