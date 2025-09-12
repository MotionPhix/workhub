<script setup lang="ts">
import { ref, computed } from 'vue'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog'
import { Progress } from '@/components/ui/progress'
import { router } from '@inertiajs/vue3'
import { UserCog, CheckCircle2 } from 'lucide-vue-next'
import {toast} from "vue-sonner";

interface OnboardingStep {
  id: number
  title: string
  description: string
  completed: boolean
  action?: string
  route?: string
}

interface UserOnboarding {
  user_id: number
  completed_steps: number[]
  current_step: number
  completion_percentage: number
}

interface Props {
  userOnboarding: UserOnboarding
}

const props = defineProps<Props>()

// Onboarding steps configuration
const onboardingSteps = ref<OnboardingStep[]>([
  {
    id: 1,
    title: 'Complete Profile',
    description: 'Fill in your personal and professional information',
    completed: false,
    action: 'Complete Profile',
    route: 'profile.edit'
  },
  {
    id: 2,
    title: 'Department Assignment',
    description: 'Confirm your department assignment and role',
    completed: false,
    action: 'Review Assignment',
    route: 'department.assignment'
  },
  {
    id: 3,
    title: 'System Access',
    description: 'Set up your system credentials and permissions',
    completed: false,
    action: 'Setup Access',
    route: 'system.access'
  },
  {
    id: 4,
    title: 'Training Materials',
    description: 'Review required training materials and documentation',
    completed: false,
    action: 'Start Training',
    route: 'training.materials'
  }
])

// Update steps completion status based on user data
const updateStepsStatus = () => {
  onboardingSteps.value = onboardingSteps.value.map(step => ({
    ...step,
    completed: props.userOnboarding.completed_steps.includes(step.id)
  }))
}

// Computed properties
const currentStep = computed(() =>
  onboardingSteps.value.find(step => !step.completed) ?? onboardingSteps.value[0]
)

const completionPercentage = computed(() =>
  props.userOnboarding.completion_percentage
)

const isOnboardingComplete = computed(() =>
  completionPercentage.value === 100
)

// Dialog state
const isDialogOpen = ref(false)

// Handle step completion
const completeStep = async (stepId: number) => {
  try {
    await router.post(route('onboarding.complete-step'), {
      step_id: stepId,
      user_id: props.userOnboarding.user_id
    }, {
      preserveScroll: true,
      onSuccess: () => {
        toast.success('Step Completed', {
          description: 'Onboarding step has been marked as complete.',
        })

        updateStepsStatus()
      },

      onError: () => {
        toast.error('Error', {
          description: 'Failed to complete the step. Please try again.',
        })
      }
    })
  } catch (error) {
    console.error('Error completing step:', error)
  }
}

// Navigate to step action
const navigateToStep = (route: string) => {
  router.visit(route)
}
</script>

<template>
  <Dialog v-model:open="isDialogOpen">
    <DialogTrigger asChild>
      <Button variant="outline" class="w-full">
        <UserCog class="mr-2 h-4 w-4" />
        {{ isOnboardingComplete ? 'View Onboarding Status' : 'Continue Onboarding' }}
      </Button>
    </DialogTrigger>

    <DialogContent class="sm:max-w-[425px]">
      <DialogHeader>
        <DialogTitle>User Onboarding</DialogTitle>
        <DialogDescription>
          Complete the following steps to finish your onboarding process.
        </DialogDescription>
      </DialogHeader>

      <div class="mt-4 space-y-4">
        <!-- Progress indicator -->
        <div class="space-y-2">
          <div class="flex justify-between text-sm">
            <span>Overall Progress</span>
            <span>{{ completionPercentage }}%</span>
          </div>
          <Progress :value="completionPercentage" class="h-2" />
        </div>

        <!-- Steps list -->
        <div class="space-y-4">
          <div
            v-for="step in onboardingSteps"
            :key="step.id"
            class="flex items-start space-x-4 p-4 rounded-lg border"
            :class="{
              'bg-muted': step.completed,
              'border-primary': currentStep?.id === step.id
            }"
          >
            <div class="flex-shrink-0">
              <component
                :is="step.completed ? CheckCircle2 : UserCog"
                class="h-5 w-5"
                :class="step.completed ? 'text-primary' : 'text-muted-foreground'"
              />
            </div>

            <div class="flex-grow space-y-1">
              <h4 class="text-sm font-medium">{{ step.title }}</h4>
              <p class="text-sm text-muted-foreground">
                {{ step.description }}
              </p>

              <Button
                v-if="step.action && !step.completed"
                variant="outline"
                size="sm"
                class="mt-2"
                @click="step.route && navigateToStep(step.route)"
              >
                {{ step.action }}
              </Button>
            </div>
          </div>
        </div>
      </div>

      <DialogFooter>
        <Button
          v-if="currentStep && !isOnboardingComplete"
          @click="completeStep(currentStep.id)"
        >
          Complete Current Step
        </Button>
        <Button
          v-else
          variant="outline"
          @click="isDialogOpen = false"
        >
          Close
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>


