<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import {Button} from '@/Components/ui/button';
import {Input} from '@/Components/ui/input';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
  canResetPassword: {
    type: Boolean,
  },
  status: {
    type: String,
  },
});

const form = useForm({
  email: '',
  password: '',
  remember: false,
});

const submit = () => {
  form.post(route('login'), {
    onFinish: () => form.reset('password'),
  });
};
</script>

<template>
  <GuestLayout>

    <Head title="Log in" />

    <CardHeader>
      <CardTitle class="text-2xl">
        Login
      </CardTitle>

      <CardDescription>
        <span
          v-if="status"
          class="text-green-600">
          {{ status }}
        </span>

        <span v-else>Enter your email below to login to your account</span>
      </CardDescription>
    </CardHeader>

    <CardContent>
      <form @submit.prevent="submit">
        <div>
          <InputLabel
            for="email"
            value="Email" />

          <Input
            id="email"
            type="email"
            class="mt-1 block w-full"
            v-model="form.email"
            required
            autofocus
            make-large
            placeholder="Enter your email address" />

          <InputError class="mt-2"
                      :message="form.errors.email" />
        </div>

        <div class="grid gap-2 mt-4">
          <div class="flex items-center">
            <InputLabel for="password">Password</InputLabel>
            <Link
              as="button"
              v-if="canResetPassword"
              :href="route('password.request')"
              class="ml-auto inline-block text-sm underline">
              Forgot your password?
            </Link>
          </div>

          <Input
            id="password"
            v-model="form.password"
            placeholder="Enter your password"
            type="password"
            make-large
            required />
        </div>

        <div class="mt-4 block">
          <label class="flex items-center">
            <Checkbox name="remember"
                      v-model:checked="form.remember" />
            <span class="ms-2 text-sm text-gray-600 dark:text-gray-400">Remember me</span>
          </label>
        </div>

        <div class="mt-4 flex items-center justify-end">

          <Button
            type="submit"
            class="w-full"
            size="lg"
            :class="{ 'opacity-25': form.processing }"
            :disabled="form.processing">
            Log in
          </Button>
        </div>

        <div class="mt-4 text-center text-sm">
          Don't have an account?
          <Link as="button" :href="route('register')" class="underline">
            Sign up
          </Link>
        </div>
      </form>
    </CardContent>
  </GuestLayout>
</template>
