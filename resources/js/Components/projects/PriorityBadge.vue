<template>
  <span
    class="px-2 py-1 text-xs font-medium rounded-full"
    :class="getPriorityClass(priority)"
  >
    <component :is="getPriorityIcon(priority)" class="w-3 h-3 inline mr-1" />
    {{ getPriorityLabel(priority) }}
  </span>
</template>

<script setup>
import {
  AlertTriangleIcon,
  ArrowUpIcon,
  MinusIcon,
  ArrowDownIcon
} from 'lucide-vue-next'

const props = defineProps({
  priority: String
})

const getPriorityClass = (priority) => {
  const classes = {
    'urgent': 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
    'high': 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
    'medium': 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
    'low': 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
  }
  return classes[priority] || classes['medium']
}

const getPriorityIcon = (priority) => {
  const icons = {
    'urgent': AlertTriangleIcon,
    'high': ArrowUpIcon,
    'medium': MinusIcon,
    'low': ArrowDownIcon
  }
  return icons[priority] || MinusIcon
}

const getPriorityLabel = (priority) => {
  const labels = {
    'urgent': 'Urgent',
    'high': 'High',
    'medium': 'Medium',
    'low': 'Low'
  }
  return labels[priority] || 'Medium'
}
</script>
