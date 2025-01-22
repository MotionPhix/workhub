<script setup lang="ts">
import { ref, onMounted } from "vue";
import { format } from "date-fns";
import AppLayout from "@/Layouts/AppLayout.vue";
import ProductivityRadarChart from "@/Components/Visualizations/ProductivityRadarChart.vue";
import ProfileTable from "@/Pages/Profile/Partials/ProfileTable.vue";
import StatCard from "@/Pages/Profile/Partials/StatCard.vue";
import { visitModal } from "@inertiaui/modal-vue";
import {
  Avatar,
  AvatarImage,
  AvatarFallback,
} from "@/Components/ui/avatar";
import { getInitials } from "@/lib/stringUtils";

const props = defineProps({
  user: Object,
});

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
  visitModal(route("profile.edit", props.user.id), {
    navigate: true,
  });
};

const formatDate = (date: string) => format(new Date(date), "PPP");

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
      <!-- Profile Overview Section -->
      <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <Card class="lg:col-span-1 overflow-hidden">
          <CardHeader class="px-0 pt-0">
            <div class="flex flex-col items-center justify-center p-6 bg-gradient-to-r from-blue-500 to-indigo-600 text-white">
              <Avatar class="w-24 h-24 mb-4">
                <AvatarImage :src="user.avatar ?? ''" :alt="user.name" />
                <AvatarFallback class="text-2xl font-semibold">
                  {{ getInitials(user.name) }}
                </AvatarFallback>
              </Avatar>
              <CardTitle class="text-2xl font-bold">{{ user.name }}</CardTitle>
              <CardDescription class="text-sm">{{ user.email }}</CardDescription>
            </div>
          </CardHeader>

          <CardContent class="p-6 space-y-6">
            <div class="border-b pb-4">
              <Label class="text-gray-500">Department</Label>
              <p class="text-lg font-medium text-muted-foreground">
                {{ user.department ?? "Not specified" }}
              </p>
            </div>

            <div class="border-b pb-4 items-center gap-6">
              <Label class="text-gray-500">Role</Label>
              <p class="text-lg font-medium text-muted-foreground capitalize">
                {{ user.roles[0].name }}
              </p>
            </div>

            <div>
              <Label class="text-gray-500">Joined</Label>
              <p class="text-lg font-medium text-muted-foreground">
                {{ (user.joined_at && formatDate(user.joined_at)) ?? 'Not Available yet' }}
              </p>
            </div>
          </CardContent>

          <CardFooter>
            <Button
              size="lg"
              class="w-full"
              @click="openEditProfile">
              Edit Profile
            </Button>
          </CardFooter>
        </Card>

        <!-- Productivity Insights -->
        <Card class="lg:col-span-2">

          <CardHeader>

            <CardTitle>
              Productivity Insights
            </CardTitle>

          </CardHeader>

          <CardContent>

            <ProductivityRadarChart
              :insights="productivityInsights"
            />

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

      </div>

      <!-- Recent Work Entries Section -->
      <Card>

        <CardHeader>

          <CardTitle>
            Recent Work Entries
          </CardTitle>

        </CardHeader>

        <CardContent>

          <ProfileTable
            :columns="workEntryColumns"
            :data="recentWorkEntries"
          />

        </CardContent>

      </Card>

      <!-- Settings Section -->
      <Card>
        <CardHeader>
          <CardTitle>Settings</CardTitle>
        </CardHeader>

        <CardContent class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
          <!-- Notifications -->
          <div>
            <Label>Notifications</Label>
            <p class="text-sm text-gray-600">
              Email:
              <span class="font-semibold">
                {{ user.settings?.notifications.email ? "Enabled" : "Disabled" }}
              </span>
            </p>

            <p class="text-sm text-gray-600">
              SMS:
              <span class="font-semibold">
                {{ user.settings?.notifications.sms ? "Enabled" : "Disabled" }}
              </span>
            </p>
          </div>

          <!-- Timezone -->
          <div>
            <Label>Timezone</Label>
            <p class="text-sm text-gray-600">
              {{ user.settings?.timezone || "Not specified" }}
            </p>
          </div>

          <!-- Preferences -->
          <div>
            <Label>Preferences</Label>
            <p class="text-sm text-gray-600">
              Customize your profile settings and notifications to suit your needs.
            </p>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle> Cookies Settings </CardTitle>
          <CardDescription>
            Manage your cookies preferences.
          </CardDescription>
        </CardHeader>
        
        <CardContent>
          <div class="grid grid-rows-3 gap-y-5">
            <div class="flex items-center justify-between space-x-2">
              <Label for="strictly_necessary" class="flex flex-col">
                Strictly Necessary
                <span
                  class="mt-1 max-w-[18rem] text-xs text-muted-foreground"
                >
                        These cookies are essential in order to use the website
                        and use its features.
                      </span>
              </Label>
              <Switch
                id="strictly_necessary"
                v-model:checked="strictlyNecessarySwitch"
              />
            </div>
            <div class="flex items-center justify-between space-x-2">
              <Label for="functional_cookies" class="flex flex-col">
                Functional Cookies
                <span
                  class="mt-1 max-w-[18rem] text-xs text-muted-foreground"
                >
                        These cookies enable the website to provide enhanced
                        functionality and personalization.
                      </span>
              </Label>
              <Switch
                id="functional_cookies"
                v-model:checked="functionalCookiesSwitch"
              />
            </div>
            <div class="flex items-center justify-between space-x-2">
              <Label for="performance_cookies" class="flex flex-col">
                Performance Cookies
                <span
                  class="mt-1 max-w-[18rem] text-xs text-muted-foreground"
                >
                        These cookies are used to collect information about how
                        you use our website.
                      </span>
              </Label>
              <Switch
                id="performance_cookies"
                v-model:checked="performanceCookiesSwitch"
              />
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
