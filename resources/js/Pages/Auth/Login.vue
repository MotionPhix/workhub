<script setup>
<<<<<<< HEAD
import {useForm} from '@inertiajs/vue3'
import {Link} from '@inertiajs/vue3'
import {Checkbox} from "@/Components/ui/checkbox";
import {Button} from "@/Components/ui/button";
import {Label} from "@/Components/ui/label";
import {Input} from "@/Components/ui/input"
import InputError from "@/Components/InputError.vue";
=======
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
>>>>>>> e9156be882d0fca8dcd710285a1bea1b93423b5d

const form = useForm({
  email: '',
  password: '',
<<<<<<< HEAD
  remember: false
})
=======
  remember: false,
});
>>>>>>> e9156be882d0fca8dcd710285a1bea1b93423b5d

const submit = () => {
  form.post(route('login'), {
    onFinish: () => form.reset('password'),
<<<<<<< HEAD
  })
}

const socialLogin = (provider) => {
  window.location.href = route('socialite.redirect', {provider})
}
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-100 px-4 py-8">
    <div class="w-full max-w-md">
      <div class="bg-white shadow-md rounded-xl p-8">
        <h2 class="text-2xl font-bold text-center mb-6">
          Sign in to your account
        </h2>

        <form @submit.prevent="submit" class="space-y-6">
          <!-- Email Input -->
          <div>
            <Label for="email">Email address</Label>
            <Input
              id="email"
              type="email"
              v-model="form.email"
            />

            <InputError class="mt-1" :message="form.errors.email" />
          </div>

          <!-- Password Input -->
          <div>
            <div class="flex justify-between items-center">
              <Label for="password">Password</Label>
              <Link
                :href="route('password.request')"
                class="text-sm text-blue-600 hover:underline">
                Forgot password?
              </Link>
            </div>

            <Input
              id="password"
              type="password"
              v-model="form.password"
            />

            <InputError class="mt-1" :message="form.errors.password" />
          </div>

          <!-- Remember Me Checkbox -->
          <div class="flex items-center gap-2">
            <Checkbox
              id="remember"
              @update:checked="form.remember = !form.remember"
              :checked="form.remember" />

            <Label for="remember">
              Keep me signed in
            </Label>
          </div>

          <!-- Submit Button -->
          <Button
            type="submit"
            class="w-full"
            :disabled="form.processing">
            {{ form.processing ? 'Signing in...' : 'Sign in' }}
          </Button>
        </form>

        <!-- Divider -->
        <div class="my-6 relative">
          <div class="absolute inset-0 flex items-center">
            <div class="w-full border-t border-gray-300"></div>
          </div>

          <div class="relative flex justify-center text-sm">
            <span class="px-2 bg-white text-gray-500">
              Or continue with
            </span>
          </div>
        </div>

        <!-- Social Login -->
        <div class="grid grid-cols-2 gap-4">
          <Button
            variant="outline"
            @click="socialLogin('google')">
            <Icon name="fa-google" class="mr-2" />
            Google
          </Button>

          <Button
            variant="outline"
            @click="socialLogin('facebook')">
            <Icon name="fa-facebook-square" class="mr-2" />
            Facebook
          </Button>
        </div>

        <!-- Registration Link -->
        <div class="mt-6 text-center">
          <p class="text-sm text-gray-600">
            Don't have an account?
            <Link
              :href="route('register')"
              class="font-medium text-blue-600 hover:underline">
              Sign up
            </Link>
          </p>
        </div>
      </div>
    </div>
  </div>
=======
  });
};
</script>

<template>
  <GuestLayout>

    <Head title="Log in" />

    <div v-if="status"
         class="mb-4 text-sm font-medium text-green-600">
      {{ status }}
    </div>

    <CardHeader>
      <CardTitle class="text-2xl">
        Login
      </CardTitle>

      <CardDescription>
        Enter your email below to login to your account
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
>>>>>>> e9156be882d0fca8dcd710285a1bea1b93423b5d
</template>
