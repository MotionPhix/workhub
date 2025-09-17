<script setup lang="ts">
import { computed } from 'vue'
import { useForm, Link } from '@inertiajs/vue3'
import {Label} from "@/components/ui/label"
import {Input} from "@/components/ui/input"
import Checkbox from "@/components/Checkbox.vue";
import PasswordStrengthIndicator from "@/pages/auth/PasswordStrengthIndicator.vue";
import InputError from "@/components/InputError.vue";
import GuestLayout from "@/layouts/GuestLayout.vue"

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
  <guest-layout>

        <CardHeader>
          <CardTitle class="text-xl">
            Sign Up
          </CardTitle>

          <CardDescription>
            Enter your information to create an account
          </CardDescription>
        </CardHeader>

        <CardContent>
          <form @submit.prevent="submit" class="grid gap-2">
            <section class="grid gap-2 grid-col-1 sm:grid-cols-2">
              <div>
                <FormField
                  label="Full Name"
                  type="text"
                  placeholder="Enter your full name"
                  v-model="form.name"
                  required
                  autofocus
                  :error="form.errors.name"
                />
              </div>

              <div>

                <FormField
                  label="Email"
                  type="email"
                  placeholder="Type your email"
                  v-model="form.email"
                  required
                  :error="form.errors.email"
                />

              </div>

            </section>

            <div>

              <FormField
                label="Password"
                type="password"
                placeholder="Type your password"
                v-model="form.password"
                required
                :error="form.errors.password"
              />

            </div>

            <div>

              <FormField
                label="Confirm your password"
                type="password"
                placeholder="Confirm the password you entered above"
                v-model="form.password_confirmation"
                required
              />
            </div>

            <div>

              <Button
                size="lg"
                type="submit"
                class="w-full"
                :class="{ 'opacity-25': form.processing }"
                :disabled="form.processing">
                <Loader :is-loading="form.processing" />
                Register
              </Button>

            </div>
          </form>


          <div class="mt-4 text-sm text-center">
            Already have an account?
            <Link :href="route('login')" as="button" class="underline">
              Sign in
            </Link>
          </div>
        </CardContent>
    </guest-layout>
</template>


