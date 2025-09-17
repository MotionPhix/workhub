<template>
  <span
    class="px-2 py-1 text-xs font-medium rounded-full"
    :class="getStatusClass(status)"
  >
    {{ getStatusLabel(status) }}
  </span>
</template>

<script setup>
const props = defineProps({
  status: String
})

const getStatusClass = (status) => {
  const classes = {
    'planning': 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
    'active': 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
    'on_hold': 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
    'completed': 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
    'cancelled': 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200'
  }
  return classes[status] || classes['planning']
}

const getStatusLabel = (status) => {
  const labels = {
    'planning': 'Planning',
    'active': 'Active',
    'on_hold': 'On Hold',
    'completed': 'Completed',
    'cancelled': 'Archived'
  }
  return labels[status] || 'Planning'
}
</script>
