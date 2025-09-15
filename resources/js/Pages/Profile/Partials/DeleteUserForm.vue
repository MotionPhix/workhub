<script setup>
import { Button } from '@/components/ui/button';
import InputError from '@/components/InputError.vue';
import { Label } from '@/components/ui/label';
import { Modal, ModalLink } from '@inertiaui/modal-vue';
import { Input } from '@/components/ui/input';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const passwordInput = ref(null);

const form = useForm({
    password: '',
});

const deleteUser = () => {
    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: () => passwordInput.value?.focus(),
        onFinish: () => form.reset(),
    });
};
</script>

<template>
    <section class="space-y-6">
        <header>
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                Delete Account
            </h2>

            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Once your account is deleted, all of its resources and data will
                be permanently deleted. Before deleting your account, please
                download any data or information that you wish to retain.
            </p>
        </header>

        <ModalLink href="#confirm-delete-account" as="button" class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-hidden focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0 bg-destructive text-destructive-foreground shadow-xs hover:bg-destructive/90 h-9 px-4 py-2">
            Delete Account
        </ModalLink>

        <!-- Local Modal for Delete Confirmation -->
        <Modal name="confirm-delete-account" :close-explicitly="true" max-width="lg" v-slot="{ close }">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    Are you sure you want to delete your account?
                </h2>

                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Once your account is deleted, all of its resources and data
                    will be permanently deleted. Please enter your password to
                    confirm you would like to permanently delete your account.
                </p>

                <form @submit.prevent="deleteUser" class="mt-6">
                    <div>
                        <Label for="password" class="sr-only">
                            Password
                        </Label>

                        <Input
                            id="password"
                            ref="passwordInput"
                            v-model="form.password"
                            type="password"
                            class="mt-1 w-3/4"
                            placeholder="Password"
                            required
                            @keyup.enter="deleteUser"
                        />

                        <InputError :message="form.errors.password" class="mt-2" />
                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <Button variant="outline" type="button" @click="close">
                            Cancel
                        </Button>

                        <Button
                            variant="destructive"
                            type="submit"
                            :class="{ 'opacity-25': form.processing }"
                            :disabled="form.processing"
                        >
                            Delete Account
                        </Button>
                    </div>
                </form>
            </div>
        </Modal>
    </section>
</template>


