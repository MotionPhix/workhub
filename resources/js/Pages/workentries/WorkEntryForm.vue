<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { useForm, router, Head } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import FormField from '@/components/forms/FormField.vue'
import RichTextEditor from '@/components/RichTextEditor.vue'
import CustomCard from '@/components/CustomCard.vue'
import {
  Briefcase,
  FileText,
  Target,
  Info,
  Check,
  Loader2
} from 'lucide-vue-next'

interface WorkLog {
  uuid?: string
  project_id?: string | number
  work_date?: string
  duration_hours?: number
  status?: string
  priority?: string
  category?: string
  title?: string
  summary?: string
  description?: string
  deliverables?: string
  technologies?: string
  challenges?: string
  solutions?: string
  learnings?: string
  next_steps?: string
  notes?: string
}

interface Project {
  id: number
  name: string
}

interface Props {
  workLog?: WorkLog
  projects?: Project[]
}

// Props
const props = withDefaults(defineProps<Props>(), {
  workLog: () => ({}),
  projects: () => []
})

// Form state
const form = useForm({
  // Basic Information
  project_id: props.workLog?.project_id || '',
  work_date: props.workLog?.work_date || new Date().toISOString().split('T')[0],
  duration_hours: props.workLog?.duration_hours || 0,
  status: props.workLog?.status || 'in-progress',
  priority: props.workLog?.priority || 'medium',
  category: props.workLog?.category || '',

  // Work Details
  title: props.workLog?.title || '',
  summary: props.workLog?.summary || '',
  description: props.workLog?.description || '',
  deliverables: props.workLog?.deliverables || '',
  technologies: props.workLog?.technologies || '',

  // Additional Information
  challenges: props.workLog?.challenges || '',
  solutions: props.workLog?.solutions || '',
  learnings: props.workLog?.learnings || '',
  next_steps: props.workLog?.next_steps || '',
  notes: props.workLog?.notes || ''
})

// Multi-step navigation
const currentStep = ref(1)
const totalSteps = 3

const nextStep = () => {
  if (validateCurrentStep()) {
    if (currentStep.value < totalSteps) {
      currentStep.value++
    }
  }
}

const prevStep = () => {
  if (currentStep.value > 1) {
    currentStep.value--
  }
}

const validateCurrentStep = () => {
  let isValid = true

  if (currentStep.value === 1) {
    // Validate required fields for step 1
    if (!form.project_id) {
      form.setError('project_id', 'Please select a project')
      isValid = false
    }
    if (!form.work_date) {
      form.setError('work_date', 'Please select a work date')
      isValid = false
    }
    if (!form.duration_hours) {
      form.setError('duration_hours', 'Please enter the duration')
      isValid = false
    }
    if (!form.status) {
      form.setError('status', 'Please select a status')
      isValid = false
    }
  } else if (currentStep.value === 2) {
    // Validate required fields for step 2
    if (!form.title) {
      form.setError('title', 'Please enter a title')
      isValid = false
    }
    if (!form.description) {
      form.setError('description', 'Please provide a detailed description')
      isValid = false
    }
  }

  return isValid
}

// Submit form
const submitForm = () => {
  if (!validateCurrentStep()) {
    return
  }

  const method = props.workLog.uuid ? 'put' : 'post'
  const url = props.workLog.uuid
    ? route('worklogs.update', props.workLog.uuid)
    : route('worklogs.store')

  form[method](url, {
    onSuccess: () => {
      onClose()
    }
  })
}

// Handle closing
const onClose = () => {
  router.visit(route('worklogs.index'))
}

// Keyboard navigation
const handleKeydown = (event: KeyboardEvent) => {
  // Allow Ctrl+Enter to submit from any step
  if ((event.ctrlKey || event.metaKey) && event.key === 'Enter') {
    if (currentStep.value === totalSteps) {
      submitForm()
    } else {
      nextStep()
    }
  }
}

onMounted(() => {
  window.addEventListener('keydown', handleKeydown)
})

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeydown)
})
</script>

<template>
  <Head :title="workLog.uuid ? 'Edit Work Entry' : 'New Work Entry'" />
  <AppLayout>
    <div class="py-8">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <CustomCard
          :title="workLog.uuid ? 'Edit Work Entry' : 'New Work Entry'"
          :description="workLog.uuid ? 'Update your work entry details' : 'Log your work progress and deliverables'"
          :icon="Briefcase"
          class="shadow-lg"
        >
          <template #header>
            <!-- Progress Indicator -->
            <div class="hidden lg:flex items-center space-x-3">
              <div
                v-for="step in totalSteps"
                :key="step"
                class="flex items-center"
              >
                <div
                  :class="[
                    'w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium border-2',
                    step < currentStep
                      ? 'bg-blue-600 text-white border-blue-600'
                      : step === currentStep
                      ? 'bg-blue-600 text-white border-blue-600'
                      : 'bg-gray-100 text-gray-400 border-gray-300 dark:bg-gray-700 dark:text-gray-500 dark:border-gray-600'
                  ]"
                >
                  <Check v-if="step < currentStep" class="w-4 h-4" />
                  <span v-else>{{ step }}</span>
                </div>
                <div
                  v-if="step < totalSteps"
                  :class="[
                    'w-12 h-0.5 ml-2',
                    step < currentStep ? 'bg-blue-600' : 'bg-gray-300 dark:bg-gray-600'
                  ]"
                />
              </div>
            </div>
          </template>
            <form @submit.prevent="submitForm" class="space-y-8">
              <!-- Step 1: Basic Information -->
              <div v-if="currentStep === 1" class="space-y-6">
                <CustomCard
                  title="Basic Information"
                  :icon="FileText"
                >

                  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Left Column -->
                    <div class="space-y-6">
                      <!-- Project Selection -->
                      <div>
                        <FormField
                          id="project_id"
                          v-model="form.project_id"
                          label="Project"
                          type="select"
                          :error="form.errors.project_id"
                          hint="Select the project this work is associated with"
                          required
                        >
                          <option value="">Select a project...</option>
                          <option
                            v-for="project in projects"
                            :key="project.id"
                            :value="project.id"
                          >
                            {{ project.name }}
                          </option>
                        </FormField>
                      </div>

                      <!-- Work Date -->
                      <div>
                        <FormField
                          id="work_date"
                          v-model="form.work_date"
                          label="Work Date"
                          type="date"
                          :error="form.errors.work_date"
                          hint="When was this work completed?"
                          required
                        />
                      </div>

                      <!-- Duration -->
                      <div>
                        <FormField
                          id="duration_hours"
                          v-model="form.duration_hours"
                          label="Duration (hours)"
                          type="number"
                          :step="0.25"
                          :min="0"
                          :max="24"
                          :error="form.errors.duration_hours"
                          hint="Time spent on this work (in hours)"
                          required
                        />
                      </div>
                    </div>

                    <!-- Right Column -->
                    <div class="space-y-6">
                      <!-- Status -->
                      <div>
                        <FormField
                          id="status"
                          v-model="form.status"
                          label="Status"
                          type="select"
                          :error="form.errors.status"
                          required
                        >
                          <option value="">Select status...</option>
                          <option value="in-progress">In Progress</option>
                          <option value="completed">Completed</option>
                          <option value="blocked">Blocked</option>
                          <option value="review">Under Review</option>
                        </FormField>
                      </div>

                      <!-- Priority -->
                      <div>
                        <FormField
                          id="priority"
                          v-model="form.priority"
                          label="Priority"
                          type="select"
                          :error="form.errors.priority"
                        >
                          <option value="">Select priority...</option>
                          <option value="low">Low</option>
                          <option value="medium">Medium</option>
                          <option value="high">High</option>
                          <option value="urgent">Urgent</option>
                        </FormField>
                      </div>

                      <!-- Category -->
                      <div>
                        <FormField
                          id="category"
                          v-model="form.category"
                          label="Category"
                          type="select"
                          :error="form.errors.category"
                          hint="What type of work was this?"
                        >
                          <option value="">Select category...</option>
                          <option value="development">Development</option>
                          <option value="design">Design</option>
                          <option value="testing">Testing</option>
                          <option value="documentation">Documentation</option>
                          <option value="meeting">Meeting</option>
                          <option value="research">Research</option>
                          <option value="admin">Administrative</option>
                          <option value="other">Other</option>
                        </FormField>
                      </div>
                    </div>
                  </div>
                </CustomCard>
              </div>

              <!-- Step 2: Work Details -->
              <div v-if="currentStep === 2" class="space-y-6">
                <CustomCard
                  title="Work Details"
                  :icon="Target"
                >

                  <div class="space-y-6">
                    <!-- Title -->
                    <div>
                      <FormField
                        id="title"
                        v-model="form.title"
                        label="Title"
                        type="text"
                        :error="form.errors.title"
                        placeholder="Brief summary of the work completed..."
                        hint="A clear, concise title for this work entry"
                        required
                      />
                    </div>

                    <!-- Summary -->
                    <div>
                      <FormField
                        id="summary"
                        v-model="form.summary"
                        label="Summary"
                        placeholder="What did you accomplish? What are the key outcomes?"
                        :error="form.errors.summary"
                        hint="High-level overview of what was accomplished"
                        rows="3"
                      />
                    </div>

                    <!-- Detailed Description -->
                    <div>
                      <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 block">
                        Detailed Description <span class="text-red-500">*</span>
                      </label>
                      <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">
                        Provide a comprehensive description of the work completed
                      </p>
                      <RichTextEditor
                        v-model="form.description"
                        placeholder="Describe the work in detail..."
                        :error="form.errors.description"
                        class="min-h-[300px]"
                      />
                    </div>

                    <!-- Key Deliverables -->
                    <div>
                      <FormField
                        id="deliverables"
                        v-model="form.deliverables"
                        label="Key Deliverables"
                        placeholder="• Feature X implemented&#10;• Bug Y fixed&#10;• Document Z updated"
                        :error="form.errors.deliverables"
                        hint="Bullet points of specific outcomes and deliverables"
                        rows="4"
                      />
                    </div>

                    <!-- Technologies Used -->
                    <div>
                      <FormField
                        id="technologies"
                        v-model="form.technologies"
                        label="Technologies Used"
                        placeholder="React, Laravel, MySQL, Docker..."
                        :error="form.errors.technologies"
                        hint="Comma-separated list of technologies, tools, or frameworks"
                      />
                    </div>
                  </div>
                </CustomCard>
              </div>

              <!-- Step 3: Additional Information -->
              <div v-if="currentStep === 3" class="space-y-6">
                <CustomCard
                  title="Additional Information"
                  :icon="Info"
                >

                  <div class="space-y-6">
                    <!-- Challenges -->
                    <div>
                      <FormField
                        id="challenges"
                        v-model="form.challenges"
                        label="Challenges Faced"
                        placeholder="Describe any obstacles, blockers, or difficult problems encountered..."
                        :error="form.errors.challenges"
                        hint="Document any issues for future reference and learning"
                        rows="3"
                      />
                    </div>

                    <!-- Solutions -->
                    <div>
                      <FormField
                        id="solutions"
                        v-model="form.solutions"
                        label="Solutions & Approaches"
                        placeholder="How were challenges addressed? What approaches were used?"
                        :error="form.errors.solutions"
                        hint="Document solutions for knowledge sharing and future reference"
                        rows="3"
                      />
                    </div>

                    <!-- Learning Notes -->
                    <div>
                      <FormField
                        id="learnings"
                        v-model="form.learnings"
                        label="Key Learnings"
                        placeholder="What did you learn? New skills, insights, or knowledge gained?"
                        :error="form.errors.learnings"
                        hint="Capture insights and knowledge for professional development"
                        rows="3"
                      />
                    </div>

                    <!-- Next Steps -->
                    <div>
                      <FormField
                        id="next_steps"
                        v-model="form.next_steps"
                        label="Next Steps"
                        placeholder="What needs to happen next? Follow-up actions?"
                        :error="form.errors.next_steps"
                        hint="Outline future actions or dependencies"
                        rows="3"
                      />
                    </div>

                    <!-- Additional Notes -->
                    <div>
                      <FormField
                        id="notes"
                        v-model="form.notes"
                        label="Additional Notes"
                        placeholder="Any blockers, insights, or context for the team..."
                        :error="form.errors.notes"
                        hint="Share any important context or blockers with your team"
                        rows="3"
                      />
                    </div>
                  </div>
                </CustomCard>
              </div>

            </form>

          <template #footer>
            <!-- Navigation and Action Buttons -->
            <div class="flex justify-between items-center">
              <div class="flex space-x-2">
                <Button
                  v-if="currentStep > 1"
                  @click="prevStep"
                  variant="outline"
                  type="button"
                  class="flex items-center space-x-2"
                >
                  <span>← Back</span>
                </Button>
              </div>

              <div class="flex space-x-2">
                <Button @click="onClose" variant="outline" type="button">
                  Cancel
                </Button>

                <Button
                  v-if="currentStep < totalSteps"
                  @click="nextStep"
                  type="button"
                  class="flex items-center space-x-2"
                >
                  <span>Next →</span>
                </Button>

                <Button
                  v-if="currentStep === totalSteps"
                  @click="submitForm"
                  type="button"
                  :disabled="form.processing"
                  class="flex items-center space-x-2"
                >
                  <span v-if="form.processing">
                    <Loader2 class="animate-spin -ml-1 mr-2 h-4 w-4"/>
                    {{ workLog.uuid ? 'Updating...' : 'Creating...' }}
                  </span>
                  <span v-else>
                    {{ workLog.uuid ? 'Update Entry' : 'Create Entry' }}
                  </span>
                </Button>
              </div>
            </div>
          </template>
        </CustomCard>
      </div>
    </div>
  </AppLayout>
</template>
