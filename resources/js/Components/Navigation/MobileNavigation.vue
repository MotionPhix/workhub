<script setup lang="ts">
import {
  HomeIcon,
  ClipboardListIcon,
  ChartBarIcon,
  UserIcon
} from 'lucide-vue-next'
import NavigationItem from "@/Components/Navigation/NavigationItem.vue";
import ThemeSwitcher from "@/Layouts/ThemeSwitcher.vue";

const mobileNavItems = [
  {
    label: 'Home',
    route: 'dashboard',
    current: 'dashboard.*',
    icon: HomeIcon
  },
  {
    label: 'Work Logs',
    route: 'work-entries.index',
    current: 'work-entries.*',
    icon: ClipboardListIcon
  },
  {
    label: 'Reports',
    route: 'reports.index',
    current: 'reports.*',
    icon: ChartBarIcon
  },
  {
    label: 'Profile',
    route: 'profile.index',
    current: 'profile.*',
    icon: UserIcon
  }
]
</script>

<template>
  <nav class="fixed bottom-0 left-0 right-0 bg-white shadow-lg dark:bg-gray-800">
    <div class="flex justify-around py-3">
      <NavigationItem
        v-for="item in mobileNavItems"
        :key="item.route"
        :href="item.route === 'profile.index' ? route(item.route, $page.props.auth.user.uuid) : route(item.route)"
        :active="route().current(item.current)">
        <component :is="item.icon" class="w-5 h-5" />
        <span class="text-xs">{{ item.label }}</span>
      </NavigationItem>

      <ThemeSwitcher />
    </div>
  </nav>
</template>
