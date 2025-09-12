<script setup lang="ts">
import { computed, ref } from 'vue'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow
} from '@/components/ui/table'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import {
  ArrowUpDown,
  Search,
  TrendingUp,
  TrendingDown,
  Minus
} from 'lucide-vue-next'
import { Button } from '@/components/ui/button'

interface DepartmentPerformance {
  name: string
  efficiency: number
  total_tasks: number
  completed_tasks: number
  total_hours: number
  member_count: number
}

interface Props {
  departmentPerformance: DepartmentPerformance[]
}

const props = defineProps<Props>()

// Sorting and filtering
const searchQuery = ref('')
const sortKey = ref<keyof DepartmentPerformance>('efficiency')
const sortDirection = ref<'asc' | 'desc'>('desc')

// Computed departments with search and sort
const filteredDepartments = computed(() => {
  let departments = [...props.departmentPerformance]

  // Apply search filter
  if (searchQuery.value) {
    departments = departments.filter(dept =>
      dept.name.toLowerCase().includes(searchQuery.value.toLowerCase())
    )
  }

  // Apply sorting
  departments.sort((a, b) => {
    const aValue = a[sortKey.value]
    const bValue = b[sortKey.value]

    if (typeof aValue === 'number' && typeof bValue === 'number') {
      return sortDirection.value === 'asc' ? aValue - bValue : bValue - aValue
    }

    return sortDirection.value === 'asc'
      ? String(aValue).localeCompare(String(bValue))
      : String(bValue).localeCompare(String(aValue))
  })

  return departments
})

// Department metrics
const totalMembers = computed(() =>
  props.departmentPerformance.reduce((sum, dept) => sum + dept.member_count, 0)
)

const averageEfficiency = computed(() => {
  const total = props.departmentPerformance.reduce((sum, dept) => sum + dept.efficiency, 0)
  return Math.round(total / props.departmentPerformance.length)
})

const totalTasks = computed(() =>
  props.departmentPerformance.reduce((sum, dept) => sum + dept.total_tasks, 0)
)

// Sort handling
const toggleSort = (key: keyof DepartmentPerformance) => {
  if (sortKey.value === key) {
    sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortKey.value = key
    sortDirection.value = 'desc'
  }
}

// Efficiency color based on value
const getEfficiencyColor = (efficiency: number): string => {
  if (efficiency >= 75) return 'text-green-500 dark:text-green-400'
  if (efficiency >= 50) return 'text-yellow-500 dark:text-yellow-400'
  return 'text-red-500 dark:text-red-400'
}

// Task completion percentage
const getCompletionPercentage = (completed: number, total: number): number => {
  return total > 0 ? Math.round((completed / total) * 100) : 0
}
</script>

<template>
  <Card>
    <CardHeader>
      <div class="flex items-center justify-between">
        <CardTitle>Department Performance</CardTitle>
        <div class="flex items-center space-x-2">
          <Input
            v-model="searchQuery"
            placeholder="Search departments..."
            class="w-[200px]"
          >
            <Search class="h-4 w-4" />
          </Input>
        </div>
      </div>
      <div class="grid grid-cols-3 gap-4 mt-4">
        <div class="space-y-1">
          <p class="text-sm font-medium text-muted-foreground">Total Members</p>
          <p class="text-2xl font-bold">{{ totalMembers }}</p>
        </div>
        <div class="space-y-1">
          <p class="text-sm font-medium text-muted-foreground">Avg. Efficiency</p>
          <p class="text-2xl font-bold">{{ averageEfficiency }}%</p>
        </div>
        <div class="space-y-1">
          <p class="text-sm font-medium text-muted-foreground">Total Tasks</p>
          <p class="text-2xl font-bold">{{ totalTasks }}</p>
        </div>
      </div>
    </CardHeader>
    <CardContent>
      <Table>
        <TableHeader>
          <TableRow>
            <TableHead>
              <Button variant="ghost" @click="toggleSort('name')">
                Department
                <ArrowUpDown class="ml-2 h-4 w-4" />
              </Button>
            </TableHead>
            <TableHead>
              <Button variant="ghost" @click="toggleSort('member_count')">
                Members
                <ArrowUpDown class="ml-2 h-4 w-4" />
              </Button>
            </TableHead>
            <TableHead>
              <Button variant="ghost" @click="toggleSort('total_tasks')">
                Tasks
                <ArrowUpDown class="ml-2 h-4 w-4" />
              </Button>
            </TableHead>
            <TableHead>
              <Button variant="ghost" @click="toggleSort('total_hours')">
                Hours
                <ArrowUpDown class="ml-2 h-4 w-4" />
              </Button>
            </TableHead>
            <TableHead>
              <Button variant="ghost" @click="toggleSort('efficiency')">
                Efficiency
                <ArrowUpDown class="ml-2 h-4 w-4" />
              </Button>
            </TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          <TableRow
            v-for="department in filteredDepartments"
            :key="department.name"
          >
            <TableCell class="font-medium">{{ department.name }}</TableCell>
            <TableCell>{{ department.member_count }}</TableCell>
            <TableCell>
              {{ department.completed_tasks }}/{{ department.total_tasks }}
              <span class="text-sm text-muted-foreground ml-1">
                ({{ getCompletionPercentage(department.completed_tasks, department.total_tasks) }}%)
              </span>
            </TableCell>
            <TableCell>{{ department.total_hours }}h</TableCell>
            <TableCell>
              <span :class="getEfficiencyColor(department.efficiency)">
                {{ department.efficiency }}%
              </span>
            </TableCell>
          </TableRow>
        </TableBody>
      </Table>
    </CardContent>
  </Card>
</template>

<style scoped>
.department-table {
  @apply w-full overflow-hidden;
}

:deep(.dark) {
  .department-table {
    @apply bg-background border-border;
  }
}
</style>


