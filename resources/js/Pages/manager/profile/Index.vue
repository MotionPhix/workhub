<script setup lang="ts">
import { ref, computed, onMounted, nextTick } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import { gsap } from 'gsap'
import {
  User,
  Mail,
  Phone,
  MapPin,
  Calendar,
  Briefcase,
  Users,
  Save,
  Eye,
  EyeOff,
  Key,
  Bell,
  Shield,
  Settings
} from 'lucide-vue-next'
import ManagerLayout from '@/layouts/ManagerLayout.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import CustomCard from '@/components/CustomCard.vue'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { Badge } from '@/components/ui/badge'
import { Separator } from '@/components/ui/separator'
import { Switch } from '@/components/ui/switch'
import { toast } from 'vue-sonner'
import { getInitials } from '@/lib/stringUtils'
import { useTheme } from '@/composables/useTheme'

interface Props {
  user: {
    id: number
    name: string
    email: string
    phone?: string
    address?: string
    bio?: string
    avatar?: string
    created_at: string
    email_verified_at?: string
    role: string
    manager_email?: string
    department?: string
    position?: string
  }
  teamStats: {
    total_members: number
    active_projects: number
    pending_approvals: number
    team_performance_score: number
  }
  recentActivity: Array<{
    id: number
    type: string
    description: string
    created_at: string
  }>
}

const props = defineProps<Props>()
// toast is imported directly from vue-sonner
const { isDark } = useTheme()

// Form handling
const profileForm = useForm({
  name: props.user.name,
  email: props.user.email,
  phone: props.user.phone || '',
  address: props.user.address || '',
  bio: props.user.bio || '',
  position: props.user.position || '',
  department: props.user.department || ''
})

const passwordForm = useForm({
  current_password: '',
  password: '',
  password_confirmation: ''
})

// State
const showCurrentPassword = ref(false)
const showNewPassword = ref(false)
const showConfirmPassword = ref(false)
const isEditingProfile = ref(false)

// Notification preferences
const notificationPreferences = ref({
  email_notifications: true,
  push_notifications: true,
  team_updates: true,
  system_alerts: true,
  weekly_reports: true
})

// Computed
const memberSince = computed(() => {
  return new Date(props.user.created_at).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long'
  })
})

const isEmailVerified = computed(() => {
  return !!props.user.email_verified_at
})

// Methods
const updateProfile = () => {
  profileForm.put(route('manager.profile.update'), {
    onSuccess: () => {
      isEditingProfile.value = false
      toast.success('Profile updated successfully!')
    },
    onError: () => {
      toast.error('There was an error updating your profile.')
    }
  })
}

const updatePassword = () => {
  passwordForm.put(route('manager.profile.password.update'), {
    onSuccess: () => {
      passwordForm.reset()
      toast.success('Password updated successfully!')
    },
    onError: () => {
      toast.error('There was an error updating your password.')
    }
  })
}

const cancelEdit = () => {
  isEditingProfile.value = false
  profileForm.reset()
}

// Lifecycle
onMounted(() => {
  nextTick(() => {
    gsap.set('.profile-card', { opacity: 0, y: 20 })
    gsap.to('.profile-card', {
      opacity: 1,
      y: 0,
      duration: 0.6,
      stagger: 0.1,
      ease: 'power2.out'
    })
  })
})
</script>

<template>
  <Head title="Manager Profile" />

  <ManagerLayout>
    <div class="max-w-4xl mx-auto py-6 space-y-8">
      <!-- Profile Header -->
      <div class="profile-card">
        <CustomCard>
          <div class="flex flex-col sm:flex-row items-start sm:items-center gap-6">
            <Avatar class="h-24 w-24">
              <AvatarImage :src="user.avatar" :alt="user.name" />
              <AvatarFallback class="text-xl font-semibold">
                {{ getInitials(user.name) }}
              </AvatarFallback>
            </Avatar>

            <div class="flex-1 space-y-2">
              <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                  {{ user.name }}
                </h1>
                <Badge variant="secondary" class="w-fit">
                  {{ user.role ? user.role.charAt(0).toUpperCase() + user.role.slice(1) : 'Manager' }}
                </Badge>
              </div>

              <p class="text-gray-600 dark:text-gray-400">
                {{ user.position || 'Team Manager' }}
                <span v-if="user.department">• {{ user.department }}</span>
              </p>

              <div class="flex items-center gap-4 text-sm text-gray-500 dark:text-gray-400">
                <div class="flex items-center gap-1">
                  <Calendar class="h-4 w-4" />
                  <span>Member since {{ memberSince }}</span>
                </div>
                <div class="flex items-center gap-1">
                  <Shield :class="isEmailVerified ? 'text-green-500' : 'text-yellow-500'" class="h-4 w-4" />
                  <span>{{ isEmailVerified ? 'Verified' : 'Unverified' }}</span>
                </div>
              </div>
            </div>

            <Button
              @click="isEditingProfile = !isEditingProfile"
              variant="outline"
              class="w-full sm:w-auto"
            >
              <Settings class="h-4 w-4 mr-2" />
              {{ isEditingProfile ? 'Cancel' : 'Edit Profile' }}
            </Button>
          </div>
        </CustomCard>
      </div>

      <!-- Team Statistics -->
      <div class="profile-card">
        <CustomCard
          title="Team Overview"
          description="Your team management statistics"
          :icon="Users"
        >
          <div class="grid grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="text-center">
              <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                {{ teamStats.total_members }}
              </div>
              <p class="text-sm text-gray-600 dark:text-gray-400">Team Members</p>
            </div>
            <div class="text-center">
              <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                {{ teamStats.active_projects }}
              </div>
              <p class="text-sm text-gray-600 dark:text-gray-400">Active Projects</p>
            </div>
            <div class="text-center">
              <div class="text-2xl font-bold text-orange-600 dark:text-orange-400">
                {{ teamStats.pending_approvals }}
              </div>
              <p class="text-sm text-gray-600 dark:text-gray-400">Pending Approvals</p>
            </div>
            <div class="text-center">
              <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">
                {{ teamStats.team_performance_score }}%
              </div>
              <p class="text-sm text-gray-600 dark:text-gray-400">Performance Score</p>
            </div>
          </div>
        </CustomCard>
      </div>

      <div class="grid lg:grid-cols-2 gap-8">
        <!-- Profile Information -->
        <div class="space-y-6">
          <!-- Personal Information -->
          <div class="profile-card">
            <CustomCard
              title="Personal Information"
              :icon="User">
              <section class="space-y-4">
                <div class="space-y-2">
                  <Label htmlFor="name">Full Name</Label>
                  <Input
                    id="name"
                    v-model="profileForm.name"
                    :disabled="!isEditingProfile"
                    placeholder="Enter your full name"
                  />
                  <div v-if="profileForm.errors.name" class="text-sm text-red-500">
                    {{ profileForm.errors.name }}
                  </div>
                </div>

                <div class="space-y-2">
                  <Label htmlFor="email">Email Address</Label>
                  <Input
                    id="email"
                    v-model="profileForm.email"
                    type="email"
                    :disabled="!isEditingProfile"
                    placeholder="Enter your email"
                  />
                  <div v-if="profileForm.errors.email" class="text-sm text-red-500">
                    {{ profileForm.errors.email }}
                  </div>
                </div>

                <div class="space-y-2">
                  <Label htmlFor="phone">Phone Number</Label>
                  <Input
                    id="phone"
                    v-model="profileForm.phone"
                    :disabled="!isEditingProfile"
                    placeholder="Enter your phone number"
                  />
                  <div v-if="profileForm.errors.phone" class="text-sm text-red-500">
                    {{ profileForm.errors.phone }}
                  </div>
                </div>

                <div class="space-y-2">
                  <Label htmlFor="position">Position</Label>
                  <Input
                    id="position"
                    v-model="profileForm.position"
                    :disabled="!isEditingProfile"
                    placeholder="Enter your position"
                  />
                </div>

                <div class="space-y-2">
                  <Label htmlFor="department">Department</Label>
                  <Input
                    id="department"
                    v-model="profileForm.department"
                    :disabled="!isEditingProfile"
                    placeholder="Enter your department"
                  />
                </div>

                <div class="space-y-2">
                  <Label htmlFor="address">Address</Label>
                  <Textarea
                    id="address"
                    v-model="profileForm.address"
                    :disabled="!isEditingProfile"
                    placeholder="Enter your address"
                    rows="2"
                  />
                </div>

                <div class="space-y-2">
                  <Label htmlFor="bio">Bio</Label>
                  <Textarea
                    id="bio"
                    v-model="profileForm.bio"
                    :disabled="!isEditingProfile"
                    placeholder="Tell us about yourself"
                    rows="3"
                  />
                </div>

                <div v-if="isEditingProfile" class="flex gap-3 pt-4">
                  <Button @click="updateProfile" :disabled="profileForm.processing">
                    <Save class="h-4 w-4 mr-2" />
                    {{ profileForm.processing ? 'Saving...' : 'Save Changes' }}
                  </Button>

                  <Button @click="cancelEdit" variant="outline">
                    Cancel
                  </Button>
                </div>
              </section>
            </CustomCard>
          </div>

          <!-- Security Settings -->
          <div class="profile-card">
            <CustomCard
              title="Security Settings"
              description="Update your password to keep your account secure"
              :icon="Key">
              <section class="space-y-4">
                <div class="space-y-2">
                  <Label htmlFor="current_password">Current Password</Label>
                  <div class="relative">
                    <Input
                      id="current_password"
                      v-model="passwordForm.current_password"
                      :type="showCurrentPassword ? 'text' : 'password'"
                      placeholder="Enter current password"
                    />
                    <Button
                      type="button"
                      variant="ghost"
                      size="sm"
                      class="absolute right-0 top-0 h-full px-3 py-2 hover:bg-transparent"
                      @click="showCurrentPassword = !showCurrentPassword"
                    >
                      <Eye v-if="showCurrentPassword" class="h-4 w-4" />
                      <EyeOff v-else class="h-4 w-4" />
                    </Button>
                  </div>
                  <div v-if="passwordForm.errors.current_password" class="text-sm text-red-500">
                    {{ passwordForm.errors.current_password }}
                  </div>
                </div>

                <div class="space-y-2">
                  <Label htmlFor="password">New Password</Label>
                  <div class="relative">
                    <Input
                      id="password"
                      v-model="passwordForm.password"
                      :type="showNewPassword ? 'text' : 'password'"
                      placeholder="Enter new password"
                    />
                    <Button
                      type="button"
                      variant="ghost"
                      size="sm"
                      class="absolute right-0 top-0 h-full px-3 py-2 hover:bg-transparent"
                      @click="showNewPassword = !showNewPassword"
                    >
                      <Eye v-if="showNewPassword" class="h-4 w-4" />
                      <EyeOff v-else class="h-4 w-4" />
                    </Button>
                  </div>
                  <div v-if="passwordForm.errors.password" class="text-sm text-red-500">
                    {{ passwordForm.errors.password }}
                  </div>
                </div>

                <div class="space-y-2">
                  <Label htmlFor="password_confirmation">Confirm New Password</Label>
                  <div class="relative">
                    <Input
                      id="password_confirmation"
                      v-model="passwordForm.password_confirmation"
                      :type="showConfirmPassword ? 'text' : 'password'"
                      placeholder="Confirm new password"
                    />
                    <Button
                      type="button"
                      variant="ghost"
                      size="icon"
                      class="absolute right-0 top-0 h-full px-3 py-2 hover:bg-transparent"
                      @click="showConfirmPassword = !showConfirmPassword">
                      <Eye v-if="showConfirmPassword" class="h-4 w-4" />
                      <EyeOff v-else class="h-4 w-4" />
                    </Button>
                  </div>
                </div>

                <Button @click="updatePassword" :disabled="passwordForm.processing" class="w-full">
                  <Key class="h-4 w-4 mr-2" />
                  {{ passwordForm.processing ? 'Updating...' : 'Update Password' }}
                </Button>
              </section>
            </CustomCard>
          </div>
        </div>

        <!-- Notifications & Activity -->
        <div class="space-y-6">
          <!-- Notification Preferences -->
          <div class="profile-card">
            <CustomCard
              title="Notification Preferences"
              description="Manage how you receive notifications"
              :icon="Bell"
            >
                <div class="space-y-4">
                  <div class="flex items-center justify-between">
                    <div class="space-y-0.5">
                      <Label>Email Notifications</Label>
                      <p class="text-sm text-gray-500 dark:text-gray-400">
                        Receive notifications via email
                      </p>
                    </div>
                    <Switch v-model:checked="notificationPreferences.email_notifications" />
                  </div>

                  <Separator />

                  <div class="flex items-center justify-between">
                    <div class="space-y-0.5">
                      <Label>Push Notifications</Label>
                      <p class="text-sm text-gray-500 dark:text-gray-400">
                        Receive push notifications in browser
                      </p>
                    </div>
                    <Switch v-model:checked="notificationPreferences.push_notifications" />
                  </div>

                  <Separator />

                  <div class="flex items-center justify-between">
                    <div class="space-y-0.5">
                      <Label>Team Updates</Label>
                      <p class="text-sm text-gray-500 dark:text-gray-400">
                        Get notified about team activities
                      </p>
                    </div>
                    <Switch v-model:checked="notificationPreferences.team_updates" />
                  </div>

                  <Separator />

                  <div class="flex items-center justify-between">
                    <div class="space-y-0.5">
                      <Label>System Alerts</Label>
                      <p class="text-sm text-gray-500 dark:text-gray-400">
                        Important system notifications
                      </p>
                    </div>
                    <Switch v-model:checked="notificationPreferences.system_alerts" />
                  </div>

                  <Separator />

                  <div class="flex items-center justify-between">
                    <div class="space-y-0.5">
                      <Label>Weekly Reports</Label>
                      <p class="text-sm text-gray-500 dark:text-gray-400">
                        Receive weekly performance reports
                      </p>
                    </div>
                    <Switch v-model:checked="notificationPreferences.weekly_reports" />
                  </div>
                </div>
            </CustomCard>
          </div>

          <!-- Recent Activity -->
          <div class="profile-card">
            <CustomCard
              title="Recent Activity"
              description="Your latest management activities">
                <div class="space-y-4">
                  <div v-for="activity in recentActivity.slice(0, 5)" :key="activity.id"
                       class="flex items-start space-x-3">
                    <div class="w-2 h-2 bg-blue-500 rounded-full mt-2 flex-shrink-0"></div>
                    <div class="flex-1 min-w-0">
                      <p class="text-sm text-gray-900 dark:text-white">
                        {{ activity.description }}
                      </p>
                      <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        {{ new Date(activity.created_at).toLocaleDateString() }}
                      </p>
                    </div>
                  </div>

                  <div v-if="!recentActivity.length" class="text-center py-4">
                    <p class="text-sm text-gray-500 dark:text-gray-400">No recent activity</p>
                  </div>
                </div>
            </CustomCard>
          </div>
        </div>
      </div>
    </div>
  </ManagerLayout>
</template>
