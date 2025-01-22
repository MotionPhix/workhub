<script setup lang="ts">
import NavigationItem from "@/Components/Navigation/NavigationItem.vue";
import ThemeSwitcher from "@/Layouts/ThemeSwitcher.vue";
import {UserIcon} from "lucide-vue-next"
import UserAvatar from "@/Layouts/UserAvatar.vue";
import {getInitials} from "@/lib/stringUtils";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger
} from "@/Components/ui/dropdown-menu";
import {router} from "@inertiajs/vue3";

const appName = import.meta.env.VITE_APP_NAME || 'WorkHub'

const desktopNavItems = [
  {label: 'Dashboard', route: 'dashboard'},
  {label: 'Work Entries', route: 'work-entries.index'},
  {label: 'Reports', route: 'reports.index'},
]
</script>

<template>
  <nav class="bg-white shadow-md dark:bg-gray-800">
    <div class="container px-4 mx-auto sm:px-6 lg:px-8">
      <div class="flex items-center justify-between py-4">

        <div class="text-lg font-bold">
          {{ appName }}
        </div>

        <div class="hidden space-x-4 md:flex">
          <NavigationItem
            v-for="item in desktopNavItems"
            :key="item.route"
            :active="route().current(item.route)"
            :href="route(item.route)">
            <component v-if="item.icon" :is="item.icon" />

            {{ item.label }}
          </NavigationItem>

          <ThemeSwitcher />

          <DropdownMenu :modal="false">
            <DropdownMenuTrigger>
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

              <DropdownMenuItem @click="router.visit(route('profile.index'), { replace: true })">
                <UserIcon />
                Profile
              </DropdownMenuItem>
            </DropdownMenuContent>
          </DropdownMenu>
        </div>
      </div>
    </div>
  </nav>
</template>
