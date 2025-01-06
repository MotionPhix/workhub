<script setup>
import NavigationItem from "@/Components/Navigation/NavigationItem.vue";

const appName = import.meta.env.VITE_APP_NAME || 'WorkHub'

const desktopNavItems = [
  {label: 'Dashboard', route: 'dashboard'},
  {label: 'Work Entries', route: 'work-entries.index'},
  {label: 'Reports', route: 'reports.index'},
  {label: 'Profile', route: 'profile.edit'}
]
</script>

<template>
  <nav class="bg-white dark:bg-gray-800 shadow-md">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex justify-between items-center py-4">
        <div class="text-lg font-bold">{{ appName }}</div>
        <div class="hidden md:flex space-x-4">
          <NavigationItem v-for="item in desktopNavItems" :key="item.route" :href="route(item.route)">
            {{ item.label }}
          </NavigationItem>
        </div>
      </div>
    </div>
  </nav>
</template>
