<template>
  <nav class="fixed bottom-0 left-0 right-0 bg-white dark:bg-gray-800 shadow-lg">
    <div class="flex justify-around py-3">
      <NavigationItem
        v-for="item in mobileNavItems"
        :key="item.route"
        :href="route(item.route)"
        :active="route().current(item.route)">
        <component :is="item.icon" class="h-5 w-5" />
        <span class="text-xs">{{ item.label }}</span>
      </NavigationItem>
    </div>
  </nav>
</template>

<script setup>
import {
  HomeIcon,
  ClipboardListIcon,
  ChartBarIcon,
  UserIcon
} from 'lucide-vue-next'
import NavigationItem from "@/Components/Navigation/NavigationItem.vue";

const mobileNavItems = [
  {
    label: 'Home',
    route: 'dashboard',
    icon: HomeIcon
  },
  {
    label: 'Work Entries',
    route: 'work-entries.index',
    icon: ClipboardListIcon
  },
  {
    label: 'Reports',
    route: 'reports.index',
    icon: ChartBarIcon
  },
  {
    label: 'Profile',
    route: 'profile.index',
    icon: UserIcon
  }
]
</script>
