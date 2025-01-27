<script setup lang="ts">
import {ref} from "vue";
import {router} from "@inertiajs/vue3";

const props = defineProps<{
  settings: {
    notifications: {
      email: boolean
      sms: boolean
    }
    play_sound?: boolean
    timezone?: string
  }
}>();

const fallback = ref({
  notifications: {
    email: props.settings.notifications?.email ?? false,
    sms: props.settings.notifications?.sms ?? false
  },
  play_sound: props.settings?.play_sound ?? false,
  timezone: props.settings?.timezone ?? 'Africa/Harare'
})

// Create a local copy of settings for two-way binding
const localSettings = ref(JSON.parse(JSON.stringify(fallback.value)));

// Watch for changes and sync updates to the server
function updateSettings(key: string, value: any) {
  const keys = key.split('.'); // Handle nested keys
  let obj = localSettings.value;

  // Traverse the object to the target key
  for (let i = 0; i < keys.length - 1; i++) {
    obj = obj[keys[i]];
  }

  // Update the target key
  obj[keys[keys.length - 1]] = value;

  // Send update to server
  router.put(route('profile.settings'), {
    settings: localSettings.value,
  }, {preserveScroll: true});
}
</script>

<template>
  <Card>
    <CardHeader class="pb-0">
      <CardTitle>Settings</CardTitle>
      <CardDescription>
        Manage your settings and preferences.
      </CardDescription>
    </CardHeader>

    <Divider />

    <CardContent>
      <div class="grid grid-rows-3 gap-y-5">
        <!-- Email Notifications -->
        <div class="flex items-center justify-between space-x-2">
          <Label for="strictly_necessary" class="flex flex-col">
            Email Notifications
            <span
              class="mt-1 max-w-[18rem] text-xs text-muted-foreground">
              Primary communication will be done through email.
            </span>
          </Label>
          <Switch
            id="strictly_necessary"
            v-model:checked="localSettings.notifications.email"
            @update:checked="(value) => updateSettings('notifications.email', value)"
          />
        </div>

        <!-- SMS Notifications -->
        <div class="flex items-center justify-between space-x-2">
          <Label for="functional_cookies" class="flex flex-col">
            SMS Notifications
            <span
              class="mt-1 max-w-[18rem] text-xs text-muted-foreground">
              Enable this option to receive notifications via SMS on your phone.
              This feature requires that you provide your phone number.
            </span>
          </Label>
          <Switch
            id="functional_cookies"
            v-model:checked="localSettings.notifications.sms"
            @update:checked="(value) => updateSettings('notifications.sms', value)"
          />
        </div>

        <!-- Play Sound -->
        <div class="flex items-center justify-between space-x-2">
          <Label for="performance_cookies" class="flex flex-col">
            Play Sound
            <span
              class="mt-1 max-w-[18rem] text-xs text-muted-foreground">
              For In-App notifications, if this option is enabled, the system will play
              a sound when you have a new notification.
            </span>
          </Label>
          <Switch
            id="performance_cookies"
            v-model:checked="localSettings.play_sound"
            @update:checked="(value) => updateSettings('play_sound', value)"
          />
        </div>
      </div>

      <Divider />

      <!-- Timezone -->
      <div class="mt-4">
        <Label>Timezone</Label>
        <p class="text-sm dark:text-gray-500 text-gray-400">
          {{ settings?.timezone || "Not specified" }}
        </p>
      </div>
    </CardContent>
  </Card>
</template>
