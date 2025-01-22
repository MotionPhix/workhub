<script setup>
import NavigationItem from "@/Components/Navigation/NavigationItem.vue";
import ThemeSwitcher from "@/Layouts/ThemeSwitcher.vue";
import {UserIcon} from "lucide-vue-next"

const appName = import.meta.env.VITE_APP_NAME || 'WorkHub'

const desktopNavItems = [
  {label: 'Dashboard', route: 'dashboard'},
  {label: 'Work Entries', route: 'work-entries.index'},
  {label: 'Reports', route: 'reports.index'},
  {label: 'Profile', icon: UserIcon, route: 'profile.index'}
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
        </div>
      </div>
    </div>
  </nav>
</template>
