<script setup lang="ts">
import {ref, computed} from 'vue'
import {Link} from '@inertiajs/vue3'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import {Users, BarChart3, ChevronDown, FileText, Clock, TrendingUp, Mail, User, Settings, LogOut} from 'lucide-vue-next'
import ResponsiveNavLink from '@/components/ResponsiveNavLink.vue'
import ThemeSwitcher from '@/components/ThemeSwitcher.vue'
import UserAvatar from '@/components/UserAvatar.vue'
import {Button} from '@/components/ui/button'
import {DropdownMenuLabel} from '@/components/ui/dropdown-menu'
import {Separator} from '@/components/ui/separator'
import {Toaster} from "vue-sonner";

const showingNavigationDropdown = ref(false)

const appName = computed(() => window?.AppConfig?.name || 'WorkHub')
</script>

<template>
  <Toaster rich-colors :close-button="true" :expand="true"/>

  <!-- Manager Navigation -->
  <nav class="fixed inset-x-0 z-50 bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700 shadow-xs">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex justify-between items-center h-16">
        <!-- Left Section - Brand -->
        <div class="flex items-center">
          <Link :href="route('manager.dashboard')" class="flex items-center">
            <h1 class="text-xl font-bold text-gray-900 dark:text-white flex items-baseline space-x-2">
              <span>{{ appName }}</span> <span class="text-sm text-blue-500 dark:text-blue-400">Manager</span>
            </h1>
          </Link>
        </div>

        <!-- Center Section - Manager Navigation -->
        <div class="hidden md:flex items-center space-x-4">
          <!-- Dashboard Link -->
          <Link
            :href="route('manager.dashboard')"
            :class="[
                'px-3 py-2 rounded-md text-sm font-medium transition-colors',
                route().current('manager.dashboard')
                  ? 'bg-blue-100 text-blue-700 dark:bg-blue-800 dark:text-blue-200'
                  : 'text-gray-600 hover:text-blue-600 dark:text-gray-300 dark:hover:text-blue-400'
            ]">
            Dashboard
          </Link>

          <!-- Team Management Dropdown -->
          <DropdownMenu>
            <DropdownMenuTrigger as-child>
              <button
                :class="[
                   'px-3 py-2 rounded-md text-sm font-medium transition-colors flex items-center space-x-1',
                    route().current('manager.team.*')
                      ? 'bg-blue-100 text-blue-700 dark:bg-blue-800 dark:text-blue-200'
                      : 'text-gray-600 hover:text-blue-600 dark:text-gray-300 dark:hover:text-blue-400'
                ]">
                <Users class="w-4 h-4"/>
                <span>Team</span>
                <ChevronDown class="w-3 h-3"/>
              </button>
            </DropdownMenuTrigger>

            <DropdownMenuContent align="start">
              <DropdownMenuItem as-child>
                <Link :href="route('manager.team.index')" class="flex items-center space-x-2">
                  <Users class="w-4 h-4"/>
                  <span>Overview</span>
                </Link>
              </DropdownMenuItem>

              <DropdownMenuItem as-child>
                <Link :href="route('manager.team.work-entries.index')" class="flex items-center space-x-2">
                  <Clock class="w-4 h-4"/>
                  <span>Work Entries</span>
                </Link>
              </DropdownMenuItem>

              <DropdownMenuItem as-child>
                <Link :href="route('manager.team.performance')" class="flex items-center space-x-2">
                  <TrendingUp class="w-4 h-4"/>
                  <span>Performance</span>
                </Link>
              </DropdownMenuItem>

              <DropdownMenuItem as-child>
                <Link :href="route('manager.team.invitations')" class="flex items-center space-x-2">
                  <Mail class="w-4 h-4"/>
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
                   route().current('manager.reports.*') || route().current('manager.insights.*')
                      ? 'bg-blue-100 text-blue-700 dark:bg-blue-800 dark:text-blue-200'
                      : 'text-gray-600 hover:text-blue-600 dark:text-gray-300 dark:hover:text-blue-400'
                ]">
                <BarChart3 class="w-4 h-4"/>
                <span>Analytics</span>
                <ChevronDown class="w-3 h-3"/>
              </button>
            </DropdownMenuTrigger>

            <DropdownMenuContent align="start">
              <DropdownMenuItem as-child>
                <Link :href="route('manager.insights.index')" class="flex items-center space-x-2">
                  <TrendingUp class="w-4 h-4"/>
                  <span>Insights</span>
                </Link>
              </DropdownMenuItem>

              <DropdownMenuItem as-child>
                <Link :href="route('manager.reports.index')" class="flex items-center space-x-2">
                  <FileText class="w-4 h-4"/>
                  <span>Reports</span>
                </Link>
              </DropdownMenuItem>
            </DropdownMenuContent>
          </DropdownMenu>
        </div>

        <!-- Right Section - User Menu -->
        <div class="flex items-center space-x-4">
          <!-- Theme Switcher -->
          <ThemeSwitcher/>

          <!-- Back to Employee View -->
          <Link
            :href="route('dashboard')"
            class="px-3 py-2 text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition-colors"
          >
            Employee View
          </Link>

          <!-- Admin View (if user has admin role) -->
          <Link
            v-if="$page.props.auth.user.roles && $page.props.auth.user.roles.some(role => role.name === 'Admin')"
            :href="route('admin.dashboard')"
            class="px-3 py-2 text-sm text-emerald-500 hover:text-emerald-700 dark:text-emerald-400 dark:hover:text-emerald-200 transition-colors"
          >
            Admin View
          </Link>

          <!-- User Menu -->
          <div class="relative">
            <DropdownMenu>
              <DropdownMenuTrigger as-child>
                <Button
                  variant="outline"
                  class="flex items-center justify-center size-10 rounded-full"
                  type="button">
                  <UserAvatar :user="$page.props.auth.user" :fallback="$page.props.auth.user.name?.charAt(0) || 'U'"/>
                </Button>
              </DropdownMenuTrigger>

              <DropdownMenuContent align="end">
                <DropdownMenuLabel class="grid">
                  <span>{{ $page.props.auth.user.name }}</span>
                  <span class="font-light">{{ $page.props.auth.user.email }}</span>
                </DropdownMenuLabel>

                <Separator/>

                <DropdownMenuItem as-child>
                  <Link :href="route('manager.profile.index')">
                    <User class="w-4 h-4 mr-2" />
                    Profile
                  </Link>
                </DropdownMenuItem>

                <DropdownMenuItem as-child>
                  <Link :href="route('manager.profile.settings')">
                    <Settings class="w-4 h-4 mr-2" />
                    Settings
                  </Link>
                </DropdownMenuItem>

                <Separator/>

                <DropdownMenuItem as-child>
                  <Link :href="route('logout')" method="post" as="button" class="w-full text-left">
                    <LogOut class="w-4 h-4 mr-2" />
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
              <path :class="{'hidden': showingNavigationDropdown, 'inline-flex': ! showingNavigationDropdown }"
                    stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
              <path :class="{'hidden': ! showingNavigationDropdown, 'inline-flex': showingNavigationDropdown }"
                    stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>
      </div>

      <!-- Mobile Navigation Menu -->
      <div class="md:hidden" v-show="showingNavigationDropdown">
        <div class="pt-2 pb-3 space-y-1">
          <ResponsiveNavLink :href="route('manager.dashboard')" :active="route().current('manager.dashboard')">
            Dashboard
          </ResponsiveNavLink>
          <ResponsiveNavLink :href="route('manager.team.index')" :active="route().current('manager.team.*')">
            My Team
          </ResponsiveNavLink>
          <ResponsiveNavLink :href="route('manager.reports.index')" :active="route().current('manager.reports.*')">
            Team Reports
          </ResponsiveNavLink>
          <ResponsiveNavLink :href="route('manager.team.work-entries.index')"
                             :active="route().current('manager.team.work-entries.*')">
            Work Entries
          </ResponsiveNavLink>
          <ResponsiveNavLink :href="route('manager.team.performance')"
                             :active="route().current('manager.team.performance')">
            Team Performance
          </ResponsiveNavLink>
          <ResponsiveNavLink :href="route('manager.team.invitations')"
                             :active="route().current('manager.team.invitations')">
            Invitations
          </ResponsiveNavLink>
          <ResponsiveNavLink :href="route('manager.insights.index')" :active="route().current('manager.insights.*')">
            Team Insights
          </ResponsiveNavLink>
        </div>

        <!-- Responsive User Options -->
        <div class="pt-4 pb-1 border-t border-gray-200 dark:border-gray-600">
          <div class="px-4">
            <div class="font-medium text-base text-gray-800 dark:text-gray-200">{{ $page.props.auth.user.name }}</div>
            <div class="font-medium text-sm text-gray-500">{{ $page.props.auth.user.email }}</div>
          </div>

          <div class="mt-3 space-y-1">
            <ResponsiveNavLink :href="route('profile.index', $page.props.auth.user.uuid)">
              Profile
            </ResponsiveNavLink>

            <ResponsiveNavLink :href="route('dashboard')">
              Employee View
            </ResponsiveNavLink>

            <ResponsiveNavLink
              v-if="$page.props.auth.user.roles && $page.props.auth.user.roles.some(role => role.name === 'Admin')"
              :href="route('admin.dashboard')">
              Admin View
            </ResponsiveNavLink>

            <ResponsiveNavLink
              class="w-full"
              :href="route('logout')"
              method="post" as="button">
              Log Out
            </ResponsiveNavLink>
          </div>
        </div>
      </div>
    </div>
  </nav>

  <div class="bg-gray-100 dark:bg-gray-900 fixed inset-0 top-16 overflow-y-auto">
    <!-- Page Content -->
    <main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 my-12">
      <slot/>
    </main>
  </div>
</template>
