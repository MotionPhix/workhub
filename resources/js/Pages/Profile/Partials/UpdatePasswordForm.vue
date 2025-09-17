<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {useForm} from '@inertiajs/vue3';
import {ref} from 'vue';

const passwordInput = ref(null);
const currentPasswordInput = ref(null);

const form = useForm({
  current_password: '',
  password: '',
  password_confirmation: '',
});

const updatePassword = () => {
  form.put(route('password.update'), {
    preserveScroll: true,
    onSuccess: () => form.reset(),
    onError: () => {
      if (form.errors.password) {
        form.reset('password', 'password_confirmation');
        passwordInput.value.focus();
      }
      if (form.errors.current_password) {
        form.reset('current_password');
        currentPasswordInput.value.focus();
      }
    },
  });
};
</script>

<template>
  <section>
    <form @submit.prevent="updatePassword" class="mt-6 space-y-6">
      <div>
        <FormField
          label="Current Password"
          ref="currentPasswordInput"
          v-model="form.current_password"
          :error="form.errors.current_password"
          autocomplete="current-password"
          placeholder="Enter your current password"
          type="password"
        />
      </div>

      <div>
        <FormField
          label="New Password"
          ref="passwordInput"
          v-model="form.password"
          placeholder="Enter the new password"
          :error="form.errors.password"
          type="password"
        />
      </div>

      <div>
        <FormField
          label="Confirm Password"
          v-model="form.password_confirmation"
          placeholder="Confirm your new password"
          :error="form.errors.password_confirmation"
          type="password"
        />
      </div>

      <div class="flex items-center gap-4 justify-end">
        <Transition
          enter-active-class="transition ease-in-out"
          enter-from-class="opacity-0"
          leave-active-class="transition ease-in-out"
          leave-to-class="opacity-0">
          <p
            v-if="form.recentlySuccessful"
            class="text-sm text-gray-600 dark:text-gray-400">
            Saved.
          </p>
        </Transition>

        <Button
          size="lg"
          type="submit"
          :disabled="form.processing">
          Save
        </Button>
      </div>
    </form>
  </section>
</template>


