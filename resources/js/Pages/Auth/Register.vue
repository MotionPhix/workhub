<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Register" />

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


          <div class="mt-4 text-center text-sm">
            Already have an account?
            <Link :href="route('login')" as="button" class="underline">
              Sign in
            </Link>
          </div>
        </CardContent>
    </GuestLayout>
</template>
