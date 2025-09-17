<script setup lang="ts">
import NavigationItem from "@/components/navigation/NavigationItem.vue";
import ThemeSwitcher from "@/components/ThemeSwitcher.vue";
import {UserIcon, PowerIcon} from "lucide-vue-next"
import UserAvatar from "@/components/UserAvatar.vue";
import {getInitials} from "@/lib/stringUtils";
import {router} from "@inertiajs/vue3";
import { nextTick, ref } from 'vue';

const appName = import.meta.env.VITE_APP_NAME || 'WorkHub'

const desktopNavItems = [
  {label: 'Dashboard', route: 'dashboard', pattern: 'dashboard'},
  {label: 'Projects', route: 'projects.index', pattern: 'projects.*'},
  {label: 'Tasks', route: 'work-entries.index', pattern: 'work-entries.*'},
  {label: 'Reports', route: 'reports.index', pattern: 'reports.*'},
]

const dropdownOpen = ref(false)

const toggleDropdown = () => {
  dropdownOpen.value = !dropdownOpen.value
}

const closeDropdown = () => {
  dropdownOpen.value = false
}

const handleProfileClick = () => {
  closeDropdown()
  nextTick(() => {
    router.visit(route('profile.index', $page.props.auth.user.uuid), { replace: true })
  })
}

const handleLogoutClick = () => {
  closeDropdown()
  nextTick(() => {
    router.visit(route('logout'), {
      method: 'post',
      replace: true
    })
  })
}
</script>

<template>
  <nav class="bg-white shadow-md dark:bg-gray-800">
    <div class="container px-4 mx-auto sm:px-6 lg:px-8">
      <div class="flex items-center justify-between py-2">

        <div class="text-lg font-bold text-gray-900 dark:text-white">
          {{ appName }}
        </div>

        <div class="hidden gap-4 sm:flex sm:items-center">
          <NavigationItem
            v-for="item in desktopNavItems"
            :key="item.route"
            :active="route().current(item.pattern)"
            :href="route(item.route)">
            <component v-if="item.icon" :is="item.icon" />

            {{ item.label }}
          </NavigationItem>

          <ThemeSwitcher />

          <!-- Simple custom dropdown instead of problematic DropdownMenu -->
          <div class="relative">
            <button
              @click="toggleDropdown"
              @blur="closeDropdown"
              class="focus:outline-none"
            >
              <UserAvatar
                :fallback="getInitials($page.props.auth.user.name)"
              />
            </button>

            <!-- Dropdown Menu -->
            <div
              v-if="dropdownOpen"
              class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-md shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none z-50"
            >
              <!-- User Info -->
              <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700">
                <div class="grid">
                  <strong class="text-gray-900 dark:text-white">{{ $page.props.auth.user.name }}</strong>
                  <span class="text-gray-600 dark:text-gray-400">{{ $page.props.auth.user.email }}</span>
                </div>
              </div>

              <!-- Profile Link -->
              <a
                :href="route('profile.index', $page.props.auth.user.id)"
                class="block px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white transition-colors"
                @click="closeDropdown"
              >
                <UserIcon class="w-4 h-4 mr-2 inline" />
                Profile
              </a>

              <!-- Separator -->
              <div class="border-t border-gray-100 dark:border-gray-700"></div>

              <!-- Logout Link -->
              <a
                :href="route('logout')"
                data-method="post"
                class="block px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white transition-colors"
                @click="closeDropdown"
              >
                <PowerIcon class="w-4 h-4 mr-2 inline" />
                Logout
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </nav>
</template>


