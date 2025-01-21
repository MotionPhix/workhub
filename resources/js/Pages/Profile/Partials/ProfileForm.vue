<script setup lang="ts">
import { ref, watch } from "vue";
import { useForm } from "@inertiajs/vue3";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";
import { Button } from "@/Components/ui/button";

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

const emit = defineEmits(["update:open", "update:profile"]);

// Reactive form with Inertia.js
const form = useForm({
  name: "",
  email: "",
  department: "",
});

// Sync props.user data with form
watch(
  () => props.user,
  (newUser) => {
    form.name = newUser.name || "";
    form.email = newUser.email || "";
    form.department = newUser.department || "";
  },
  { immediate: true }
);

// Handle form submission
const saveProfile = async () => {
  // Simulate form submission; replace with Inertia POST/PUT if needed
  emit("update:profile", {
    ...props.user,
    ...form,
  });

  emit("update:open", false);
};
</script>

<template>
  <GlobalModal
    padding-classes="0" v-slot="{ close }"
    :close-explicitly="true"
    :close-button="false">
    <CardHeader>
      <CardTitle>
        Edit Profile
      </CardTitle>
    </CardHeader>

    <CardContent>
      <form @submit.prevent="saveProfile" class="space-y-4">
        <div>
          <Label for="name">Name</Label>
          <Input id="name" v-model="form.name" type="text" required />
        </div>

        <div>
          <Label for="email">Email</Label>
          <Input id="email" v-model="form.email" type="email" required />
        </div>

        <div>
          <FormField
            label="Department"
            placeholder="Pick a department"
            v-model="form.department"
          />
        </div>
      </form>
    </CardContent>

    <CardFooter class="gap-2 justify-end">
      <Button type="button" variant="secondary" @click="close">
        Cancel
      </Button>

      <Button type="button" @click="saveProfile">
        Save Changes
      </Button>
    </CardFooter>
  </GlobalModal>
</template>

<style scoped>
/* Optional: Customize modal appearance */
</style>
