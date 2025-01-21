<script setup>
import NavigationItem from "@/Components/Navigation/NavigationItem.vue";
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
  <nav class="bg-white dark:bg-gray-800 shadow-md">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex justify-between items-center py-4">

        <div class="text-lg font-bold">
          {{ appName }}
        </div>

        <div class="hidden md:flex space-x-4">
          <NavigationItem
            v-for="item in desktopNavItems"
            :key="item.route"
            :active="route().current(item.route)"
            :href="route(item.route)">
            <component v-if="item.icon" :is="item.icon" />

            {{ item.label }}
          </NavigationItem>
        </div>
      </div>
    </div>
  </nav>
</template>
