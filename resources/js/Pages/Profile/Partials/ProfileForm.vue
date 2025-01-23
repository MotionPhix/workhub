<script setup lang="ts">
import {watch} from "vue";
import {useForm} from "@inertiajs/vue3";

const props = defineProps({
  user: {
    type: Object,
    required: true,
  },
});

const timezones = [
  {
    label: 'North America',
    items: [
      {value: 'est', label: 'Eastern Standard Time (EST)'},
      {value: 'cst', label: 'Central Standard Time (CST)'},
      {value: 'mst', label: 'Mountain Standard Time (MST)'},
      {value: 'pst', label: 'Pacific Standard Time (PST)'},
      {value: 'akst', label: 'Alaska Standard Time (AKST)'},
      {value: 'hst', label: 'Hawaii Standard Time (HST)'}
    ]
  },
  {
    label: 'Europe & Africa',
    items: [
      {value: 'gmt', label: 'Greenwich Mean Time (GMT)'},
      {value: 'cet', label: 'Central European Time (CET)'},
      {value: 'eet', label: 'Eastern European Time (EET)'},
      {value: 'west', label: 'Western European Summer Time (WEST)'},
      {value: 'cat', label: 'Central Africa Time (CAT)'},
      {value: 'eat', label: 'East Africa Time (EAT)'}
    ]
  },
  {
    label: 'Asia',
    items: [
      {value: 'msk', label: 'Moscow Time (MSK)'},
      {value: 'ist', label: 'India Standard Time (IST)'},
      {value: 'cst_china', label: 'China Standard Time (CST)'},
      {value: 'jst', label: 'Japan Standard Time (JST)'},
      {value: 'kst', label: 'Korea Standard Time (KST)'},
      {value: 'ist_indonesia', label: 'Indonesia Central Standard Time (WITA)'}
    ]
  },
  {
    label: 'EAustralia & Pacific',
    items: [
      {value: 'acst', label: 'Australian Western Standard Time (AWST)'},
      {value: 'aest', label: 'Australian Eastern Standard Time (AEST)'},
      {value: 'nzst', label: 'New Zealand Standard Time (NZST)'},
      {value: 'fjt', label: 'Fiji Time (FJT)'}
    ]
  },
  {
    label: 'South America',
    items: [
      {value: 'art', label: 'Argentina Time (ART)'},
      {value: 'bot', label: 'Bolivia Time (BOT)'},
      {value: 'brt', label: 'NBrasilia Time (BRT)'},
      {value: 'clt', label: 'Chile Standard Time (CLT)'}
    ]
  }
]

// Reactive form with Inertia.js
const form = useForm({
  name: "",
  email: "",
  department: "",
  settings: {
    notifications: {
      email: true,
      sms: false,
    },
    timezone: "UTC",
  },
});

// Sync props.user data with form
watch(
  () => props.user,
  (newUser) => {
    form.name = newUser.name || "";
    form.email = newUser.email || "";
    form.department = newUser.department || "";
    form.settings = {
      notifications: {
        email: newUser.settings?.notifications?.email ?? true,
        sms: newUser.settings?.notifications?.sms ?? false,
      },
      timezone: newUser.settings?.timezone ?? "cat",
    };
  },
  {immediate: true}
);

// Handle form submission
const saveProfile = async () => {
  form.patch(route('profile.update'), {
    onSuccess: () => {
      console.log('done')
    },

    onError: (error) => {
      console.log(error)
    }
  })
};
</script>

<template>
  <GlobalModal
    padding-classes="0" v-slot="{ close }"
    panel-classes="dark:bg-gray-800 rounded-xl bg-white"
    :close-explicitly="true"
    :close-button="false">
    <!-- Header -->
    <CardHeader>
      <CardTitle>Edit Profile</CardTitle>
    </CardHeader>

    <!-- Content -->
    <CardContent>
      <form @submit.prevent="saveProfile" class="space-y-6">
        <!-- Basic Information -->
        <div>
          <Label for="name">Name</Label>
          <Input id="name" v-model="form.name" type="text" required/>
        </div>

        <div>
          <Label for="email">Email</Label>
          <Input id="email" v-model="form.email" type="email" required/>
        </div>

        <div>
          <FormField
            label="Department"
            placeholder="Pick a department"
            v-model="form.department"
          />
        </div>

        <!-- Settings Section -->
        <div class="mt-6">
          <h3 class="text-lg font-medium">Notifications</h3>
          <div class="flex items-center space-x-4 mt-2">
            <label class="flex items-center space-x-2">
              <Checkbox
                v-model:checked="form.settings.notifications.email"
                class="h-5 w-5"
              />
              <span>Email</span>
            </label>

            <label class="flex items-center space-x-2">
              <Checkbox
                v-model:checked="form.settings.notifications.sms"
                class="h-5 w-5"
              />
              <span>SMS</span>
            </label>
          </div>
        </div>

        <div class="mt-6">
          <FormField
            has-groups
            type="select"
            label="Timezone"
            v-model="form.settings.timezone"
            :options="timezones"
          />
        </div>
      </form>
    </CardContent>

    <!-- Footer -->
    <CardFooter class="gap-2 justify-end">
      <Button type="button" variant="secondary" @click="close">
        Cancel
      </Button>

      <Button type="submit" @click="saveProfile">
        Save Changes
      </Button>
    </CardFooter>
  </GlobalModal>
</template>
