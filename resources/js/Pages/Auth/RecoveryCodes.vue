<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from "@/layouts/AppLayout.vue";
import {
  Card,
  CardContent,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle
} from "@/components/ui/card";
import {Button} from "@/components/ui/button";
import CodeBlock from "@/components/CodeBlock.vue";

const props = defineProps({
  recoveryCodes: {
    type: Array,
    required: true
  }
})

const regenerateCodes = () => {
  router.post(route('two-factor.recovery-codes.regenerate'), {}, {
    onSuccess: () => {
      // Refresh the page to show new codes
      router.reload()
    }
  })
}
</script>

<template>
  <AppLayout>
    <div class="container mx-auto p-6">
      <Card>
        <CardHeader>
          <CardTitle>Two-Factor Authentication Recovery Codes</CardTitle>
          <CardDescription>
            Store these recovery codes in a safe place. They can be used to regain access to your account.
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div class="grid grid-cols-2 gap-4">
            <CodeBlock
              v-for="(code, index) in recoveryCodes"
              :key="index"
              :code="code"
            />
          </div>
        </CardContent>
        <CardFooter>
          <Button @click="regenerateCodes" variant="destructive">
            Regenerate Recovery Codes
          </Button>
        </CardFooter>
      </Card>
    </div>
  </AppLayout>
</template>


