<script setup>
import { computed } from 'vue'
import { useForm, Link } from '@inertiajs/vue3'
import {Label} from "@/Components/ui/label"
import {Input} from "@/Components/ui/input"
import Checkbox from "@/Components/Checkbox.vue";
import {Button} from "@/Components/ui/button"
import PasswordStrengthIndicator from "@/Pages/Auth/PasswordStrengthIndicator.vue";
import InputError from "@/Components/InputError.vue";

const form = useForm({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  // terms: false
})

// Password strength and form validation
const isFormValid = computed(() => {
  console.log(form.data())
  return form.name &&
    form.email &&
    form.password &&
    form.password_confirmation === form.password &&
    // form.terms &&
    form.password.length >= 8
})

// Submit handler
const submit = () => {
  form.post(route('register'), {
    onFinish: () => {
      // Reset sensitive fields
      form.reset('password', 'password_confirmation')
    },
    onError: (errors) => {
      // Handle specific error scenarios
      console.log(errors)
    }
  })
}

// Social login handler
const socialLogin = (provider) => {
  window.location.href = route('socialite.redirect', { provider })
}
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-100 px-4 py-8">
    <div class="w-full max-w-md">
      <div class="bg-white shadow-md rounded-xl p-8">
        <h2 class="text-2xl font-bold text-center mb-6">
          Create your account
        </h2>

        <form @submit.prevent="submit" class="space-y-6">
          <!-- Name Input -->
          <div>
            <Label for="name">Full Name</Label>
            <Input
              id="name"
              type="text"
              v-model="form.name"
              required
              autocomplete="name"
            />

            <InputError :message="form.errors.name" />
          </div>

          <!-- Email Input -->
          <div>
            <Label for="email">Email address</Label>
            <Input
              id="email"
              type="email"
              v-model="form.email"
              required
              autocomplete="email"
            />

            <InputError :message="form.errors.email" />
          </div>

          <!-- Password Input -->
          <div>
            <Label for="password">Password</Label>
            <Input
              id="password"
              type="password"
              v-model="form.password"
              required
            />

            <PasswordStrengthIndicator
              :password="form.password"
              class="mt-2"
            />

            <InputError :message="form.errors.password" />
          </div>

          <!-- Confirm Password -->
          <div>
            <Label for="password_confirmation">
              Confirm Password
            </Label>

            <Input
              id="password_confirmation"
              type="password"
              v-model="form.password_confirmation"
            />
          </div>

          <!-- Terms Checkbox -->
<!--          <div>-->
<!--            <Checkbox-->
<!--              id="terms"-->
<!--              :checked="form.terms">-->
<!--              I agree to the-->
<!--              <Link-->
<!--                :href="route('terms')"-->
<!--                class="text-blue-600 hover:underline"-->
<!--                target="_blank">-->
<!--                Terms of Service-->
<!--              </Link>-->
<!--            </Checkbox>-->

<!--            <InputError :message="form.errors.terms" />-->
<!--          </div>-->

          <!-- Submit Button -->
          <Button
            type="submit"
            class="w-full"
            :disabled="!isFormValid || form.processing">
            {{ form.processing ? 'Creating account...' : 'Create Account' }}
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

        <!-- Social Registration -->
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
            <Icon name="fa-facebook-square" :scale="3" class="mr-2" />
            Facebook
          </Button>
        </div>

        <!-- Login Link -->
        <div class="mt-6 text-center">
          <p class="text-sm text-gray-600">
            Already have an account?
            <Link
              as="button"
              :href="route('login')"
              class="font-medium text-blue-600 hover:underline">
              Sign in
            </Link>
          </p>
        </div>
      </div>
    </div>
  </div>
</template>
