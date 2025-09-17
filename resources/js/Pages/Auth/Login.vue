<script setup lang="ts">
import GuestLayout from '@/layouts/GuestLayout.vue';
import { Label } from '@/components/ui/label';
import { Head, Link, useForm } from '@inertiajs/vue3';
import FormField from "@/components/forms/FormField.vue";
import { LockIcon } from "lucide-vue-next";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';

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

    <Card>
      <CardHeader>
        <CardTitle class="text-2xl">
          Login
        </CardTitle>

        <CardDescription>
          <span v-if="status"
                class="text-green-600">
            {{ status }}
          </span>

          <span v-else>Enter your email below to login to your account</span>
        </CardDescription>
      </CardHeader>

      <CardContent>
        <form @submit.prevent="submit">
          <div>
            <FormField type="email"
                       label="Email"
                       class="make-large"
                       v-model="form.email"
                       placeholder="Enter your email address"
                       :error="form.errors.email"
                       autofocus
                       required />
          </div>

          <div class="grid gap-2 mt-4">
            <div class="flex items-center">
              <Label for="password">Password</Label>
              <Link as="button"
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="ml-auto inline-block text-sm underline">
              Forgot your password?
              </Link>
            </div>

            <FormField v-model="form.password"
                       placeholder="Enter your password"
                       class="make-large"
                       type="password"
                       :suffix="LockIcon"
                       :error="form.errors.password"
                       required />
          </div>

          <div class="mt-4 block">
            <Label class="flex items-center">
              <Checkbox name="remember"
                        class="h-5 w-5"
                        v-model:checked="form.remember" />

              <span class="ms-2 text-sm text-gray-600 dark:text-gray-400">
                Remember me
              </span>
            </Label>
          </div>

          <div class="mt-4 flex items-center justify-end">

            <Button size="lg"
                    type="submit"
                    class="w-full"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing">
              Log in
            </Button>

          </div>
        </form>
      </CardContent>
    </Card>
  </GuestLayout>
</template>
