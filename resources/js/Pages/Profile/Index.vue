<script setup lang="ts">
import {ref, onMounted} from "vue";
import {format} from "date-fns";
import AppLayout from "@/layouts/AppLayout.vue";
import ProductivityRadarChart from "@/components/Visualizations/ProductivityRadarChart.vue";
import ProfileTable from "@/pages/profile/partials/ProfileTable.vue";
import StatCard from "@/components/StatCard.vue";
import {visitModal} from "@inertiaui/modal-vue";
import {getInitials} from "@/lib/stringUtils";
import Divider from "@/components/Divider.vue";
import UpdatePasswordForm from "@/pages/profile/partials/UpdatePasswordForm.vue";
import UserAvatar from "@/components/UserAvatar.vue";
import ProfileSettings from "@/pages/profile/partials/ProfileSettings.vue";
import {PencilIcon} from "lucide-vue-next";

const props = defineProps<{
  user: {
    id: number
    uuid: string
    name: string
    avatar?: string
    email: string
    department?: string
    department_name?: string
    department_description?: string
    gender?: string
    job_title?: string
    joined_at?: string
    roles?: Array<{
      id: number
      name: string
    }>
    settings?: {
      notifications?: {
        sms: boolean
        email: boolean
      },
      play_sound?: boolean
      timezone?: string
    }
  },
}>();

const recentWorkLogs = ref({});
const productivityInsights = ref({});

const stats = ref({
  total_entries: 0,
  total_hours: 0,
});

const workEntryColumns = [
  {
    accessorKey: "work_date",
    header: "Date",
    cell: ({row}) => format(new Date(row.work_date), "PPP"),
  },
  {
    accessorKey: "work_title",
    header: "Work Title",
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
  visitModal(route("profile.edit", props.user.uuid), {
    navigate: true,
  });
};

const formatDate = (date: string) => format(new Date(date), "do MMMM, y");

onMounted(async () => {
  try {
    // Fetch productivity insights and recent work entries
    const response = await fetch(route('api.user.insights', props.user.uuid));
    productivityInsights.value = await response.json();

    const entriesResponse = await fetch(route('api.user.work-logs', props.user.uuid));
    recentWorkLogs.value = await entriesResponse.json();

    // Calculate stats
    stats.value.total_entries = recentWorkLogs.value.total || 0;
    stats.value.total_hours = recentWorkLogs.value.data.reduce(
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
          <CardHeader class="px-0 py-0">
            <div class="flex flex-col items-center justify-center p-6">
              <UserAvatar
                class="w-24 h-24 mb-4 text-2xl font-semibold"
                :src="user.avatar ?? ''" :alt="user.name"
                :fallback="getInitials(user.name)"
              />

              <CardTitle class="text-2xl font-bold">
                {{ user.name }}
                <Button
                  size="icon"
                  variant="outline"
                  class="rounded-full"
                  @click="openEditProfile">
                  <PencilIcon/>
                </Button>
              </CardTitle>

              <CardDescription class="text-sm">
                {{ user.email }}
              </CardDescription>
            </div>
          </CardHeader>

          <Divider/>

          <CardContent class="p-6">
            <div>
              <Label class="text-gray-500">Department</Label>
              <p class="text-lg font-medium text-muted-foreground flex flex-row md:flex-col items-center md:items-start gap-2 sm:gap-0">
                <span>{{ user.department_name ?? "Not specified" }}</span>

                <span
                  class="text-sm text-gray-400 flex items-center gap-2"
                  v-if="user.department_name && user.department_description">
                  <span class="md:hidden">|</span>
                  <span>
                    {{ user.department_description }}
                  </span>
                </span>
              </p>
            </div>

            <Divider/>

            <div>
              <Label class="text-gray-500">Job Title</Label>
              <p class="text-lg font-medium text-muted-foreground">
                {{ user.job_title ?? 'Not Specified' }}
              </p>
            </div>

            <Divider/>

            <div>
              <Label class="text-gray-500">Joined</Label>
              <p class="text-lg font-medium text-muted-foreground">
                {{ (user.joined_at && formatDate(user.joined_at)) ?? 'Not Available yet' }}
              </p>
            </div>

            <Divider/>

            <div class="pb-4 items-center gap-6">
              <Label class="text-gray-500">Roles</Label>

              <p
                :key="role.id"
                v-for="(role) in user.roles"
                class="text-lg font-medium text-muted-foreground capitalize">
                {{ role.name }}
              </p>
            </div>
          </CardContent>
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

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-6">

              <StatCard
                title="Total Work Logs"
                :value="stats.total_entries"
              />

              <StatCard
                title="Total Hours Worked"
                :value="stats.total_hours"
              />

              <!-- Productivity Trend Stat Card -->
              <StatCard
                title="Productivity Trend"
                :value="productivityInsights?.productivity_trend ?? 'Unknown'"
                :trend="productivityInsights?.productivity_trend"
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
            v-if="recentWorkLogs.data?.length"
            :columns="workEntryColumns"
            :data="recentWorkLogs"
          />

          <p v-else>No recent work logs available.</p>

        </CardContent>

      </Card>

      <ProfileSettings
        :settings="user.settings"
      />

      <Card>
        <CardHeader class="pb-0">
          <CardTitle>Update Password</CardTitle>
          <CardDescription>
            Ensure your account is using a long, random password to stay
            secure.
          </CardDescription>
        </CardHeader>

        <Divider/>

        <CardContent>
          <UpdatePasswordForm/>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>


