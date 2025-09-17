<script setup lang="ts">
import {
  HomeIcon,
  FolderIcon,
  ClipboardListIcon,
  ChartBarIcon,
  UserIcon
} from 'lucide-vue-next'
import NavigationItem from "@/components/navigation/NavigationItem.vue";
import ThemeSwitcher from "@/components/ThemeSwitcher.vue";

const mobileNavItems = [
  {
    label: 'Home',
    route: 'dashboard',
    current: 'dashboard.*',
    icon: HomeIcon
  },
  {
    label: 'Projects',
    route: 'projects.index',
    current: 'projects.*',
    icon: FolderIcon
  },
  {
    label: 'Tasks',
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
        :href="item.route === 'profile.index' ? route(item.route, $page.props.auth.user.id) : route(item.route)"
        :active="route().current(item.current)">
        <component :is="item.icon" class="w-5 h-5" />
        <span class="text-xs">{{ item.label }}</span>
      </NavigationItem>

      <ThemeSwitcher />
    </div>
  </nav>
</template>


