<script setup lang="ts">
import {computed, ref} from "vue";
import {useForm, usePage} from "@inertiajs/vue3";
import ModalHeader from "@/Components/ModalHeader.vue";
import {useDeviceDetection} from "@/composables/useDeviceDetection";
import {useUserStore} from "@/stores/user";
import {toast} from "vue-sonner";
import {XIcon, SaveIcon} from "lucide-vue-next";

interface User {
  id: number
  uuid: string
  name: string
  email: string
  avatar? : string
  job_title? : string
  gender? : string
  department_uuid? : string
  settings? : {
    notifications? : {
      sms: boolean,
      email: boolean
    }
    timezone? : string
    play_sound? : boolean
  }
}

const props = defineProps<{
  departments: Array<{
    uuid: string
    name: string
    description?: string
  }>
}>();

const user: User = usePage().props.user as User;

const profileFormRef = ref()
const userStore = useUserStore()
const {isMobile} = useDeviceDetection()

const departmentOptions = computed(() =>
  props.departments.map(department => ({
    value: department.uuid,
    label: department.name,
    description: department.description
  }))
);

const timezones = [
  {
    label: 'North America',
    items: [
      {value: 'America/New_York', label: 'Eastern Standard Time (EST)'},
      {value: 'America/Chicago', label: 'Central Standard Time (CST)'},
      {value: 'America/Denver', label: 'Mountain Standard Time (MST)'},
      {value: 'America/Los_Angeles', label: 'Pacific Standard Time (PST)'},
      {value: 'America/Anchorage', label: 'Alaska Standard Time (AKST)'},
      {value: 'Pacific/Honolulu', label: 'Hawaii Standard Time (HST)'}
    ]
  },
  {
    label: 'Europe & Africa',
    items: [
      {value: 'Europe/London', label: 'Greenwich Mean Time (GMT)'},
      {value: 'Europe/Berlin', label: 'Central European Time (CET)'},
      {value: 'Europe/Helsinki', label: 'Eastern European Time (EET)'},
      {value: 'Europe/Lisbon', label: 'Western European Summer Time (WEST)'},
      {value: 'Africa/Harare', label: 'Central Africa Time (CAT)'},
      {value: 'Africa/Nairobi', label: 'East Africa Time (EAT)'}
    ]
  },
  {
    label: 'Asia',
    items: [
      {value: 'Europe/Moscow', label: 'Moscow Time (MSK)'},
      {value: 'Asia/Kolkata', label: 'India Standard Time (IST)'},
      {value: 'Asia/Shanghai', label: 'China Standard Time (CST)'},
      {value: 'Asia/Tokyo', label: 'Japan Standard Time (JST)'},
      {value: 'Asia/Seoul', label: 'Korea Standard Time (KST)'},
      {value: 'Asia/Makassar', label: 'Indonesia Central Standard Time (WITA)'}
    ]
  },
  {
    label: 'Australia & Pacific',
    items: [
      {value: 'Australia/Perth', label: 'Australian Western Standard Time (AWST)'},
      {value: 'Australia/Sydney', label: 'Australian Eastern Standard Time (AEST)'},
      {value: 'Pacific/Auckland', label: 'New Zealand Standard Time (NZST)'},
      {value: 'Pacific/Fiji', label: 'Fiji Time (FJT)'}
    ]
  },
  {
    label: 'South America',
    items: [
      {value: 'America/Argentina/Buenos_Aires', label: 'Argentina Time (ART)'},
      {value: 'America/La_Paz', label: 'Bolivia Time (BOT)'},
      {value: 'America/Sao_Paulo', label: 'Brasilia Time (BRT)'},
      {value: 'America/Santiago', label: 'Chile Standard Time (CLT)'}
    ]
  }
];

// Reactive form with Inertia.js
const form = useForm({
  name: user.name ?? "",
  email: user.email ?? "",
  job_title: user.job_title ?? "",
  gender: user.gender ?? "",
  department_uuid: user.department_uuid ?? "",
  settings: {
    notifications: {
      email: user.settings.notifications?.email ?? false,
      sms: user.settings.notifications?.sms ?? false
    },
    timezone: user.settings.timezone ?? "Africa/Harare",
    play_sound: user.settings.play_sound ?? false
  },
});

const onClose = () => {
  profileFormRef.value.onClose()
}

// Handle form submission
const saveProfile = async () => {
  form.patch(route('profile.update'), {
    onSuccess: (page) => {
      // Update user store
      userStore.updateUser({
        ...page.props.auth.user,
      })

      onClose()

      toast.success('Profile updated successfully')
    },

    onError: (error) => {
      console.log(error)
    }
  })
};
</script>

<template>
  <GlobalModal
    ref="profileFormRef"
    max-width="md"
    padding="px-4 pb-4 sm:px-5 sm:pb-5"
    :close-explicitly="true"
    :close-button="false">
    <!-- Header -->
    <ModalHeader heading="Edit Profile">
      <template #action>

        <Button
          type="button"
          variant="secondary"
          :size="isMobile ? 'icon' : 'sm'"
          @click="onClose">
          <span v-if="isMobile">
            <XIcon/>
          </span>

          <span v-else>
            Cancel
          </span>
        </Button>

        <Button
          type="button"
          :size="isMobile ? 'icon' : 'sm'"
          @click="saveProfile">
          <span v-if="isMobile">
            <SaveIcon/>
          </span>

          <span v-else>
          Save Changes
          </span>
        </Button>

      </template>
    </ModalHeader>

    <!-- Content -->
    <form class="space-y-6">
      <!-- Basic Information -->
      <section class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <FormField label="Name" v-model="form.name" :error="form.errors.name" required/>
        </div>

        <div>
          <FormField label="Email" v-model="form.email" type="email" :error="form.errors.email" required/>
        </div>
      </section>

      <div>

        <FormField
          label="Gender"
          v-model="form.gender" type="select"
          placeholder="Specify your gender"
          :error="form.errors.gender"
          :options="[
            { value: 'male', label: 'Male' },
            { value: 'female', label: 'Female' }
          ]"
        />

      </div>

      <section>
        <div>
          <FormField
            type="select"
            label="Department"
            placeholder="Pick a department"
            :error="form.errors.department_uuid"
            :options="departmentOptions"
            v-model="form.department_uuid"
          />
        </div>

        <div>
          <FormField
            label="Job Title"
            placeholder="Enter your position"
            v-model="form.job_title"
            :error="form.errors.job_title"
          />
        </div>
      </section>

      <div class="mt-6">
        <FormField
          has-groups
          type="select"
          label="Timezone"
          :error="form.errors.settings?.timezone"
          v-model="form.settings.timezone"
          :options="timezones"
        />
      </div>
    </form>
  </GlobalModal>
</template>
