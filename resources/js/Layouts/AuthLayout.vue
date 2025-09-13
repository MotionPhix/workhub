<script setup>
import ApplicationLogo from '@/components/ApplicationLogo.vue'
import ThemeSwitcher from "@/components/ThemeSwitcher.vue";
</script>

<template>
  <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100 dark:bg-gray-900">
    <div>
      <ApplicationLogo class="w-20 h-20 fill-current text-gray-500" />
    </div>

    <div class="w-full sm:max-w-md mt-6 px-6 py-8 bg-white dark:bg-gray-800 shadow-md overflow-hidden sm:rounded-lg">
      <slot></slot>
    </div>

    <div class="mt-6 text-center">
      <ThemeSwitcher />
    </div>
  </div>
</template>


