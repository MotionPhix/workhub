<script setup>
import {ref, onMounted} from 'vue'
import {useForm} from '@inertiajs/vue3'
import {format} from 'date-fns'
import AppLayout from "@/Layouts/AppLayout.vue";
import {
  Card,
  CardContent,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle
} from "@/Components/ui/card";
import {Label} from "@/Components/ui/label";
import {Badge} from "@/Components/ui/badge";
import ProductivityRadarChart from "@/Components/Visualizations/ProductivityRadarChart.vue";
import {
  Avatar,
  AvatarImage,
  AvatarFallback
} from "@/Components/ui/avatar";

const props = defineProps({
  user: Object
})

const isEditProfileOpen = ref(false)
const productivityInsights = ref({})
const stats = ref({
  total_entries: 0,
  total_hours: 0
})
const recentWorkEntries = ref([])

const workEntryColumns = [
  {
    accessorKey: 'work_date',
    header: 'Date',
    cell: ({row}) => format(new Date(row.original.work_date), 'PPP')
  }, {
    accessorKey: 'project',
    header: 'Project'
  },
  {
    accessorKey: 'hours_worked',
    header: 'Hours Worked'
  },
  {
    accessorKey: 'description',
    header: 'Description'
  }
]

const openEditProfile = () => {
  isEditProfileOpen.value = true
}

const updateProfile = (updatedUser) => {
  // Update user data in the local state
  props.user = updatedUser
}

onMounted(async () => {
  // Fetch productivity insights and recent work entries
  const response = await fetch(`/api/user/${props.user.id}/insights`)
  productivityInsights.value = await response.json()

  const entriesResponse = await fetch(`/api/user/${props.user.id}/work-entries`)
  recentWorkEntries.value = await entriesResponse.json()

  // Calculate stats
  stats.value.total_entries = recentWorkEntries.value.length
  stats.value.total_hours = recentWorkEntries.value.reduce((total, entry) => total + entry.hours_worked, 0)
})
</script>

<template>
  <AppLayout>
    <div class="container mx-auto px-4 py-8">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Profile Overview -->
        <Card class="md:col-span-1">
          <CardHeader>
            <Avatar>
              <AvatarImage :src="user.avatar" :alt="user.name" />
              <AvatarFallback>
                {{ user.name.charAt(0) }}
              </AvatarFallback>
            </Avatar>
            <CardTitle>{{ user.name }}</CardTitle>
            <CardDescription>{{ user.email }}</CardDescription>
          </CardHeader>
          <CardContent>
            <div class="space-y-4">
              <div>
                <Label>Department</Label>
                <p>{{ user.department || 'Not specified' }}</p>
              </div>
              <div>
                <Label>Role</Label>
                <Badge>{{ user.role }}</Badge>
              </div>
              <div>
                <Label>Joined</Label>
                <p>{{ formatDate(user.created_at) }}</p>
              </div>
            </div>
          </CardContent>
          <CardFooter>
            <Button @click="openEditProfile">Edit Profile</Button>
          </CardFooter>
        </Card>

        <!-- Productivity Insights -->
        <Card class="md:col-span-2">
          <CardHeader>
            <CardTitle>Productivity Insights</CardTitle>
          </CardHeader>
          <CardContent>
            <ProductivityRadarChart :insights="productivityInsights" />

            <div class="grid grid-cols-2 gap-4 mt-6">
              <StatCard
                title="Total Work Entries"
                :value="stats.total_entries"
              />
              <StatCard
                title="Total Hours Worked"
                :value="stats.total_hours"
              />
            </div>
          </CardContent>
        </Card>

        <!-- Recent Work Entries -->
        <Card class="md:col-span-3">
          <CardHeader>
            <CardTitle>Recent Work Entries</CardTitle>
          </CardHeader>
          <CardContent>
            <DataTable
              :columns="workEntryColumns"
              :data="recentWorkEntries"
            />
          </CardContent>
        </Card>
      </div>

      <!-- Edit Profile Modal -->
      <EditProfileModal
        v-model:open="isEditProfileOpen"
        :user="user"
        @update:profile="updateProfile"
      />
    </div>
  </AppLayout>
</template>
