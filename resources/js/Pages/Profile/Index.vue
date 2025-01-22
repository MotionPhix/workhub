<script setup lang="ts">
import { ref, onMounted } from "vue";
import { useForm } from "@inertiajs/vue3";
import { format } from "date-fns";
import AppLayout from "@/Layouts/AppLayout.vue";
import ProductivityRadarChart from "@/Components/Visualizations/ProductivityRadarChart.vue";
import ProfileTable from "@/Pages/Profile/Partials/ProfileTable.vue";
import StatCard from "@/Pages/Profile/Partials/StatCard.vue";
import ProfileForm from "@/Pages/Profile/Partials/ProfileForm.vue";
import {visitModal} from "@inertiaui/modal-vue"
import {
  Avatar,
  AvatarImage,
  AvatarFallback,
} from "@/Components/ui/avatar";
import {getInitials} from "@/lib/stringUtils"

const props = defineProps({
  user: Object,
});

const isEditProfileOpen = ref(false);
const productivityInsights = ref({});
const stats = ref({
  total_entries: 0,
  total_hours: 0,
});
const recentWorkEntries = ref([]);

const workEntryColumns = [
  {
    accessorKey: "work_date",
    header: "Date",
    cell: ({ row }) => format(new Date(row.original.work_date), "PPP"),
  },
  {
    accessorKey: "project",
    header: "Project",
  },
  {
    accessorKey: "hours_worked",
    header: "Hours Worked",
  },
  {
    accessorKey: "description",
    header: "Description",
  },
];

const openEditProfile = () => {
  visitModal(route('profile.edit', props.user.id), {
    navigate: true
  })
};

const formatDate = (date) => format(new Date(date), "PPP");

onMounted(async () => {
  try {
    // Fetch productivity insights and recent work entries
    const response = await fetch(`/api/user/${props.user.id}/insights`);
    productivityInsights.value = await response.json();

    const entriesResponse = await fetch(`/api/user/${props.user.id}/work-entries`);
    recentWorkEntries.value = await entriesResponse.json();

    // Calculate stats
    stats.value.total_entries = recentWorkEntries.value.length;
    stats.value.total_hours = recentWorkEntries.value.reduce(
      (total, entry) => total + entry.hours_worked,
      0
    );
  } catch (error) {
    console.error("Failed to fetch user data:", error);
  }
});
</script>

<template>
  <AppLayout>
    <div class="py-8 mx-auto space-y-8">
      <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Profile Overview -->
        <Card class="lg:col-span-1">
          <CardHeader>
            <Avatar class="w-16 h-16 mx-auto mb-4">
              <AvatarImage :src="user.avatar ?? ''" :alt="user.name" />
              <AvatarFallback class="text-2xl text-secondary-foreground">{{ getInitials(user.name) }}</AvatarFallback>
            </Avatar>

            <CardTitle class="text-center">{{ user.name }}</CardTitle>

            <CardDescription class="text-center">{{ user.email }}</CardDescription>

          </CardHeader>

          <CardContent class="space-y-4">
            <div>
              <Label>Department</Label>
              <p>{{ user.department || "Not specified" }}</p>
            </div>

            <div>
              <Label>Role</Label>
              <Badge>{{ user.role }}</Badge>
            </div>

            <div>
              <Label>Joined</Label>
              <p>{{ formatDate(user.created_at) }}</p>
            </div>
          </CardContent>

          <CardFooter class="flex justify-center">
            <Button
              @click="openEditProfile" class="w-full md:w-auto">
              Edit Profile
            </Button>
          </CardFooter>
        </Card>

        <!-- Productivity Insights -->
        <Card class="lg:col-span-2">
          <CardHeader>
            <CardTitle>Productivity Insights</CardTitle>
          </CardHeader>
          <CardContent>
            <ProductivityRadarChart :insights="productivityInsights" />
            <div class="grid grid-cols-2 gap-4 mt-6">
              <StatCard title="Total Work Entries" :value="stats.total_entries" />
              <StatCard title="Total Hours Worked" :value="stats.total_hours" />
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Recent Work Entries -->
      <Card>
        <CardHeader>
          <CardTitle>Recent Work Entries</CardTitle>
        </CardHeader>
        <CardContent>
          <ProfileTable :columns="workEntryColumns" :data="recentWorkEntries" />
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
