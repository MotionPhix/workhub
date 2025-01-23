<script setup lang="ts">
import {watch} from "vue";
import {useForm} from "@inertiajs/vue3";
import {Input} from "@/Components/ui/input";

const props = defineProps({
  open: {
    type: Boolean,
    required: true,
  },
  user: {
    type: Object,
    required: true,
  },
});

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
    panel-classes="dark:bg-gray-800 rounded-xl"
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
              <input
                type="checkbox"
                v-model="form.settings.notifications.email"
                class="form-checkbox"
              />
              <span>Email</span>
            </label>
            <label class="flex items-center space-x-2">
              <input
                type="checkbox"
                v-model="form.settings.notifications.sms"
                class="form-checkbox"
              />
              <span>SMS</span>
            </label>
          </div>
        </div>

        <div class="mt-6">
          <FormField
            type="select"
            label="Timezone"
            v-model="form.settings.timezone"
            :options="[
              { value: 'utc', label: 'UTC' },
            ]"
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
