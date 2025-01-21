<script setup>
import {useForm} from '@inertiajs/vue3'
import {Link} from '@inertiajs/vue3'
import {Checkbox} from "@/Components/ui/checkbox";
import {Button} from "@/Components/ui/button";
import {Label} from "@/Components/ui/label";
import {Input} from "@/Components/ui/input"
import InputError from "@/Components/InputError.vue";

const form = useForm({
  email: '',
  password: '',
  remember: false
})

const submit = () => {
  form.post(route('login'), {
    onFinish: () => form.reset('password'),
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
</template>
