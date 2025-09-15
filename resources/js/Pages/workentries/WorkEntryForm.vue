<script setup lang="ts">
import {useForm, router} from '@inertiajs/vue3';
import {
  CommandEmpty,
  CommandGroup,
  CommandItem,
  CommandList
} from '@/components/ui/command'
import {
  TagsInput,
  TagsInputInput,
  TagsInputItem,
  TagsInputItemDelete,
  TagsInputItemText
} from '@/components/ui/tags-input'
import {
  ComboboxAnchor,
  ComboboxContent,
  ComboboxInput,
  ComboboxPortal,
  ComboboxRoot
} from 'radix-vue'
import {computed, onBeforeUnmount, ref, onMounted, nextTick} from 'vue'
import {
  UnderlineIcon,
  ItalicIcon,
  BoldIcon,
  ListIcon,
  ListOrderedIcon,
  MinusIcon,
  ChevronLeft,
  ChevronRight,
  X,
  Loader2,
  Users,
  Building2,
  DollarSign,
  Star,
  Phone,
  Mail,
  User,
  Zap
} from "lucide-vue-next";
import InputError from "@/components/InputError.vue";
import {toast} from "vue-sonner";
import {EditorContent, useEditor} from '@tiptap/vue-3';
import Document from '@tiptap/extension-document'
import ListItem from '@tiptap/extension-list-item'
import Bold from '@tiptap/extension-bold'
import OrderedList from '@tiptap/extension-ordered-list'
import BulletList from '@tiptap/extension-bullet-list'
import Italic from '@tiptap/extension-italic'
import Heading from '@tiptap/extension-heading'
import Paragraph from '@tiptap/extension-paragraph'
import HorizontalRule from '@tiptap/extension-horizontal-rule'
import Text from '@tiptap/extension-text'
import Underline from '@tiptap/extension-underline'
import Link from '@tiptap/extension-link';
import Image from '@tiptap/extension-image';
import Placeholder from '@tiptap/extension-placeholder';
import {subDays, format, isAfter, isBefore, startOfDay} from "date-fns";
import {CalendarDate, parseDate, today, getLocalTimeZone} from '@internationalized/date';
import {
  // CalendarRoot,
  CalendarHeader,
  CalendarHeading,
  CalendarGrid,
  CalendarCell,
  CalendarPrevButton,
  CalendarNextButton
} from '@/components/ui/calendar';
// import {Project} from '@/types'
import EditorToolbarButton from "@/pages/workentries/components/EditorToolbarButton.vue";
import AppLayout from '@/layouts/AppLayout.vue'
import FormField from "@/components/forms/FormField.vue";
import {Label} from "@/components/ui/label";
import {Button} from "@/components/ui/button";
import {Card, CardContent, CardDescription, CardHeader, CardTitle} from "@/components/ui/card";
import DateTimePicker from "@/components/DateTimePicker.vue";

interface WorkLog {
  id?: number
  uuid?: string
  work_title?: string
  description?: string
  start_date_time?: string
  end_date_time?: string
  status?: 'draft' | 'completed' | 'in_progress'
  project_uuid?: string
  priority?: string
  work_type?: string
  location?: string
  notes?: string
  tags?: string[]
  // Enhanced fields
  contacts?: Array<{name: string, email?: string, phone?: string, company?: string, role?: string}>
  organizations?: Array<{name: string, type?: string, website?: string}>
  value_generated?: number
  outcome?: string
  mood?: string
  productivity_rating?: number
  tools_used?: string[]
  collaborators?: Array<{name: string, role?: string}>
  requires_follow_up?: boolean
  follow_up_date?: string
  weather_condition?: string
}

interface Props {
  workLog: WorkLog
  tags?: Array<{
    id: number
    name: string
  }>
  projects?: Array<{
    uuid: string
    name: string
    completionPercentage?: number
    dueDate?: string
  }>
}

// Component props
const props = defineProps<Props>()

// Helper function to format datetime for datetime-local input
const formatDateTimeLocal = (dateString: string): string => {
  if (!dateString) return ''
  const date = new Date(dateString)
  return format(date, "yyyy-MM-dd'T'HH:mm")
}


// Step management for workflow with validation enforcement
const currentStep = ref(1)
const totalSteps = 3
const completedSteps = ref<Set<number>>(new Set([]))
const stepErrors = ref<Record<number, string[]>>({})

// Step validation rules (industry standard: prevent skipping, ensure data quality)
const getStepValidationErrors = (step: number): string[] => {
  const errors: string[] = []

  if (step === 1) {
    // Step 1: Core Work Information - All required
    if (!form.work_title?.trim()) {
      errors.push('Work title is required and cannot be empty')
    } else if (form.work_title.trim().length < 3) {
      errors.push('Work title must be at least 3 characters long')
    }

    if (!form.start_date_time) {
      errors.push('Start date and time is required')
    }

    if (!form.end_date_time) {
      errors.push('End date and time is required')
    }

    if (form.start_date_time && form.end_date_time) {
      const start = new Date(form.start_date_time)
      const end = new Date(form.end_date_time)

      if (end <= start) {
        errors.push('End date/time must be after start date/time')
      }

      // Check if start time is not in the future
      const now = new Date()
      if (start > now) {
        errors.push('Start time cannot be in the future')
      }

      // Reasonable limit - task can't be longer than 30 days
      const maxDuration = 30 * 24 * 60 * 60 * 1000 // 30 days in milliseconds
      if (end.getTime() - start.getTime() > maxDuration) {
        errors.push('Task duration cannot exceed 30 days')
      }
    }

    if (!form.status) {
      errors.push('Work status is required')
    }
  }

  if (step === 2) {
    // Step 2: Description - Required for professional work logging
    if (!form.description?.trim()) {
      errors.push('Work description is required')
    } else if (form.description.trim().length < 10) {
      errors.push('Description must be at least 10 characters long for proper work documentation')
    }
  }

  // Step 3 is optional (project and tags)

  return errors
}

const validateCurrentStep = (): boolean => {
  const errors = getStepValidationErrors(currentStep.value)
  stepErrors.value[currentStep.value] = errors

  if (errors.length === 0) {
    completedSteps.value.add(currentStep.value)
    return true
  }

  // Show validation errors to user
  errors.forEach(error => {
    toast.error(error, {
      description: `Please fix this issue in Step ${currentStep.value} before proceeding.`,
      duration: 5000
    })
  })

  return false
}

const canAccessStep = (step: number): boolean => {
  // Always allow step 1
  if (step === 1) return true

  // For subsequent steps, ensure all previous steps are completed
  for (let i = 1; i < step; i++) {
    if (!completedSteps.value.has(i)) {
      return false
    }
  }

  return true
}

// Step navigation functions with validation
// Helper functions for managing array fields
const addContact = () => {
  form.contacts.push({
    name: '',
    email: '',
    phone: '',
    company: '',
    role: ''
  })
}

const removeContact = (index) => {
  form.contacts.splice(index, 1)
}

const addOrganization = () => {
  form.organizations.push({
    name: '',
    type: '',
    website: ''
  })
}

const removeOrganization = (index) => {
  form.organizations.splice(index, 1)
}

const addCollaborator = () => {
  form.collaborators.push({
    name: '',
    role: ''
  })
}

const removeCollaborator = (index) => {
  form.collaborators.splice(index, 1)
}

const nextStep = () => {
  if (currentStep.value < totalSteps && validateCurrentStep()) {
    currentStep.value++
  }
}

const prevStep = () => {
  if (currentStep.value > 1) {
    currentStep.value--
  }
}

const goToStep = (step: number) => {
  if (step >= 1 && step <= totalSteps && canAccessStep(step)) {
    currentStep.value = step
  } else if (!canAccessStep(step)) {
    toast.warning(`Complete Step ${step - 1} first`, {
      description: 'You must complete each step in order before proceeding.',
      duration: 4000
    })
  }
}

// Form initialization with default values
const form = useForm({
  work_title: props.workLog.work_title || '',
  description: props.workLog.description || '',
  start_date_time: props.workLog.start_date_time || '',
  end_date_time: props.workLog.end_date_time || '',
  status: props.workLog.status || 'draft',
  priority: props.workLog.priority || 'medium',
  work_type: props.workLog.work_type || 'task',
  location: props.workLog.location || '',
  notes: props.workLog.notes || '',
  tags: props.workLog.tags || [],
  project_uuid: props.workLog.project_uuid || '',
  // Enhanced fields
  contacts: props.workLog.contacts || [],
  organizations: props.workLog.organizations || [],
  value_generated: props.workLog.value_generated || null,
  outcome: props.workLog.outcome || null,
  mood: props.workLog.mood || '',
  productivity_rating: props.workLog.productivity_rating || null,
  tools_used: props.workLog.tools_used || [],
  collaborators: props.workLog.collaborators || [],
  requires_follow_up: props.workLog.requires_follow_up || false,
  follow_up_date: props.workLog.follow_up_date || '',
  weather_condition: props.workLog.weather_condition || '',
})

// Computed property to calculate hours worked from start and end datetime
const hoursWorked = computed(() => {
  if (!form.start_date_time || !form.end_date_time) return 0

  const start = new Date(form.start_date_time)
  const end = new Date(form.end_date_time)

  if (end <= start) return 0

  return Math.round(((end.getTime() - start.getTime()) / (1000 * 60 * 60)) * 100) / 100
})

// Computed property to get the task duration in a readable format
const taskDuration = computed(() => {
  if (!form.start_date_time || !form.end_date_time) return ''

  const start = new Date(form.start_date_time)
  const end = new Date(form.end_date_time)

  if (end <= start) return 'Invalid duration'

  const diffMs = end.getTime() - start.getTime()
  const hours = Math.floor(diffMs / (1000 * 60 * 60))
  const minutes = Math.floor((diffMs % (1000 * 60 * 60)) / (1000 * 60))

  if (hours === 0) return `${minutes} minutes`
  if (minutes === 0) return `${hours} hour${hours !== 1 ? 's' : ''}`

  return `${hours}h ${minutes}m`
})

// Tags management with autocomplete and validation
const availableTags = computed(() => {
  return props.tags?.map(tag => tag.name) || []
})

const tagInputValue = ref('')
const filteredTags = computed(() => {
  if (!tagInputValue.value.trim()) return availableTags.value

  const searchTerm = tagInputValue.value.toLowerCase()
  return availableTags.value.filter(tag =>
    tag.toLowerCase().includes(searchTerm) &&
    !form.tags.some(existingTag => existingTag.toLowerCase() === tag.toLowerCase())
  )
})

const addTag = (tagName: string) => {
  const trimmedTag = tagName.trim()

  if (!trimmedTag) return

  // Check for duplicates (case-insensitive)
  if (form.tags.some(tag => tag.toLowerCase() === trimmedTag.toLowerCase())) {
    toast.warning('Tag already added', {
      description: `"${trimmedTag}" is already in your tag list.`,
      duration: 3000
    })
    return
  }

  // Validate tag length
  if (trimmedTag.length < 2) {
    toast.error('Tag too short', {
      description: 'Tags must be at least 2 characters long.',
      duration: 3000
    })
    return
  }

  if (trimmedTag.length > 30) {
    toast.error('Tag too long', {
      description: 'Tags cannot exceed 30 characters.',
      duration: 3000
    })
    return
  }

  // Limit number of tags
  if (form.tags.length >= 10) {
    toast.warning('Too many tags', {
      description: 'You can only add up to 10 tags per work entry.',
      duration: 3000
    })
    return
  }

  form.tags.push(trimmedTag)
  tagInputValue.value = ''

  toast.success(`Tag "${trimmedTag}" added`)
}

const removeTag = (tagToRemove: string) => {
  form.tags = form.tags.filter(tag => tag !== tagToRemove)
  toast.success(`Tag "${tagToRemove}" removed`)
}


// Project options computed property
const projectOptions = computed(() => {
  return props.projects?.map(project => ({
    value: project.uuid,
    label: project.name
  })) || []
})

// Selected project computed property
const selectedProject = computed(() => {
  return props.projects?.find(project => project.uuid === form.project_uuid)
})

// Initialize Tiptap editor with configurations
const editor = useEditor({
  extensions: [
    Document,
    Paragraph,
    Text,
    BulletList.configure({
      HTMLAttributes: {
        class: 'list-disc list-inside ml-6 space-y-2 text-gray-800 dark:text-gray-200'
      }
    }),
    OrderedList.configure({
      HTMLAttributes: {
        class: 'list-decimal list-inside ml-6 space-y-2 text-gray-800 dark:text-gray-200'
      }
    }),
    ListItem.configure({
      HTMLAttributes: {
        class: 'text-gray-700 dark:text-gray-300'
      }
    }),
    Placeholder.configure({
      placeholder: 'Describe what you worked on, challenges faced, and outcomes achieved...',
      emptyEditorClass: 'is-empty'
    }),
    Link,
    HorizontalRule,
    Bold,
    Heading,
    Italic,
    Image,
    Underline
  ],
  content: form.description,
  onUpdate: ({editor}) => {
    const newContent = editor.getHTML()
    if (form.description !== newContent) {
      form.description = newContent
    }
  }
})

// Auto-save functionality (fail-safe mechanism)
const autoSaveTimer = ref<NodeJS.Timeout | null>(null)
const lastSaved = ref<Date | null>(null)

const autoSave = () => {
  if (!form.work_title && !form.description) return

  // Save to localStorage as backup
  const backup = {
    ...form.data(),
    timestamp: new Date().toISOString()
  }

  localStorage.setItem(`worklog_backup_${props.workLog.uuid || 'new'}`, JSON.stringify(backup))
  lastSaved.value = new Date()
}

// Auto-save every 30 seconds
const startAutoSave = () => {
  if (autoSaveTimer.value) {
    clearInterval(autoSaveTimer.value)
  }

  autoSaveTimer.value = setInterval(autoSave, 30000)
}

// Restore from backup if available
const restoreFromBackup = () => {
  const backupKey = `worklog_backup_${props.workLog.uuid || 'new'}`
  const backup = localStorage.getItem(backupKey)

  if (backup) {
    try {
      const data = JSON.parse(backup)
      const backupDate = new Date(data.timestamp)
      const fifteenMinutesAgo = new Date(Date.now() - 15 * 60 * 1000)

      if (backupDate > fifteenMinutesAgo && data.work_title) {
        toast('Backup found', {
          description: 'Would you like to restore your previous work?',
          action: {
            label: 'Restore',
            onClick: () => {
              // Use nextTick to prevent reactive loops
              nextTick(() => {
                try {
                  form.work_title = data.work_title
                  form.description = data.description
                  form.start_date_time = data.start_date_time || ''
                  form.end_date_time = data.end_date_time || ''
                  form.status = data.status
                  form.tags = data.tags || []
                  form.project_uuid = data.project_uuid

                  if (editor.value) {
                    editor.value.commands.setContent(data.description || '')
                  }

                  // Use a timeout to prevent toast conflicts
                  setTimeout(() => {
                    toast.success('Backup restored successfully')
                  }, 100)
                } catch (error) {
                  console.error('Error restoring backup:', error)
                  toast.error('Failed to restore backup')
                }
              })
            }
          },
          duration: 10000
        })
      }
    } catch (error) {
      console.warn('Failed to parse backup data:', error)
    }
  }
}

// Comprehensive form submission with validation
const submitForm = async () => {
  // Final validation before submission
  const allErrors: string[] = []

  for (let step = 1; step <= totalSteps; step++) {
    const stepErrors = getStepValidationErrors(step)
    allErrors.push(...stepErrors)
  }

  if (allErrors.length > 0) {
    toast.error('Form validation failed', {
      description: 'Please fix all errors before submitting.',
      duration: 5000
    })

    allErrors.forEach((error, index) => {
      setTimeout(() => {
        toast.error(error, {duration: 3000})
      }, index * 100)
    })

    return
  }

  // Prepare submission data
  const submissionData = {
    work_title: form.work_title.trim(),
    description: form.description.trim(),
    start_date_time: form.start_date_time,
    end_date_time: form.end_date_time,
    hours_worked: hoursWorked.value,
    status: form.status,
    project_uuid: form.project_uuid || null,
    tags: form.tags
  }

  const handleSuccess = () => {
    // Clear backup on successful submission
    localStorage.removeItem(`worklog_backup_${props.workLog.uuid || 'new'}`)

    toast.success('Work log saved successfully', {
      description: props.workLog.uuid
        ? 'Your work entry has been updated.'
        : 'Your work entry has been created.',
      duration: 4000
    })

    onClose()
  }

  const handleError = (errors: any) => {
    console.error('Form submission error:', errors)

    if (errors && typeof errors === 'object') {
      Object.keys(errors).forEach(field => {
        const fieldErrors = Array.isArray(errors[field]) ? errors[field] : [errors[field]]
        fieldErrors.forEach((error: string) => {
          toast.error(`${field}: ${error}`, {duration: 4000})
        })
      })
    } else {
      toast.error('Submission failed', {
        description: 'Please check your connection and try again.',
        duration: 4000
      })
    }
  }

  // Submit form
  try {
    if (props.workLog.uuid) {
      await form
        .transform(() => submissionData)
        .put(route('work-entries.update', props.workLog.uuid), {
          onSuccess: handleSuccess,
          onError: handleError
        })
    } else {
      await form
        .transform(() => submissionData)
        .post(route('work-entries.store'), {
          onSuccess: handleSuccess,
          onError: handleError
        })
    }
  } catch (error) {
    handleError(error)
  }
}

const onClose = () => {
  if (autoSaveTimer.value) {
    clearInterval(autoSaveTimer.value)
  }

  // Clear any unsaved backup if user cancels
  localStorage.removeItem(`worklog_backup_${props.workLog.uuid || 'new'}`)

  // Navigate back to work logs index using Inertia
  router.visit('/work-logs', {
    method: 'get',
    preserveScroll: true
  })
}

// Initialize on mount
onMounted(() => {
  startAutoSave()
  restoreFromBackup()
})

// Cleanup on unmount
onBeforeUnmount(() => {
  if (autoSaveTimer.value) {
    clearInterval(autoSaveTimer.value)
  }
  editor.value?.destroy()
})

// Editor tools configuration
const editorTools = [
  {
    icon: BoldIcon,
    title: 'Bold',
    action: 'toggleBold'
  },
  {
    icon: ItalicIcon,
    title: 'Italic',
    action: 'toggleItalic'
  },
  {
    icon: UnderlineIcon,
    title: 'Underline',
    action: 'toggleUnderline'
  },
  {
    icon: ListIcon,
    title: 'Bullet List',
    action: 'toggleBulletList'
  },
  {
    icon: ListOrderedIcon,
    title: 'Ordered List',
    action: 'toggleOrderedList'
  },
  {
    icon: MinusIcon,
    title: 'Horizontal Rule',
    action: 'horizontalRule'
  }
]

// Cleanup
onBeforeUnmount(() => {
  editor.value?.destroy()
})
</script>

<template>
  <AppLayout>
    <div class="py-12">
      <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
        <!-- Page Header -->
        <Card>
          <CardHeader>
            <CardTitle>
              {{ workLog.uuid ? `Edit ${workLog.work_title || 'Work Entry'}` : 'Create Work Entry' }}
            </CardTitle>

            <CardDescription>
              {{
                workLog.uuid ? 'Update your work log details' : 'Log your daily work activities with detailed information'
              }}
            </CardDescription>
          </CardHeader>
        </Card>

        <Card>
          <CardContent>
            <form class="space-y-6">
              <!-- Progress Steps with Validation Status -->
              <div class="flex items-center justify-center mb-8 space-x-4">
                <div class="flex items-center space-x-2">
                  <button
                    type="button"
                    @click="goToStep(1)"
                    :class="[
              'w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium transition-all cursor-pointer',
              currentStep >= 1 ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-600',
              completedSteps.has(1) ? 'ring-2 ring-green-300' : '',
              stepErrors[1]?.length > 0 ? 'ring-2 ring-red-300' : ''
            ]"
                    :title="canAccessStep(1) ? 'Go to Step 1' : 'Complete previous steps first'"
                  >
                    <span v-if="completedSteps.has(1)" class="text-xs">✓</span>
                    <span v-else>1</span>
                  </button>
                  <span :class="[
            'transition-all',
            currentStep >= 1 ? 'text-blue-600 font-medium' : 'text-gray-500',
            completedSteps.has(1) ? 'text-green-600' : '',
            stepErrors[1]?.length > 0 ? 'text-red-600' : ''
          ]">
            Work Details
            <span v-if="stepErrors[1]?.length > 0" class="text-xs block">{{ stepErrors[1].length }} error(s)</span>
          </span>
                </div>
                <div class="w-12 h-0.5 bg-gray-200 relative overflow-hidden">
                  <div :class="[
            'h-full transition-all duration-300',
            completedSteps.has(1) ? 'bg-green-500' : currentStep >= 2 ? 'bg-blue-600' : 'bg-gray-200',
            { 'w-full': currentStep >= 2 || completedSteps.has(1), 'w-0': currentStep < 2 && !completedSteps.has(1) }
          ]"></div>
                </div>
                <div class="flex items-center space-x-2">
                  <button
                    type="button"
                    @click="goToStep(2)"
                    :class="[
              'w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium transition-all',
              canAccessStep(2) ? 'cursor-pointer' : 'cursor-not-allowed opacity-50',
              currentStep >= 2 ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-600',
              completedSteps.has(2) ? 'ring-2 ring-green-300' : '',
              stepErrors[2]?.length > 0 ? 'ring-2 ring-red-300' : ''
            ]"
                    :disabled="!canAccessStep(2)"
                    :title="canAccessStep(2) ? 'Go to Step 2' : 'Complete Step 1 first'"
                  >
                    <span v-if="completedSteps.has(2)" class="text-xs">✓</span>
                    <span v-else>2</span>
                  </button>
                  <span :class="[
            'transition-all',
            canAccessStep(2) ? (currentStep >= 2 ? 'text-blue-600 font-medium' : 'text-gray-500') : 'text-gray-400',
            completedSteps.has(2) ? 'text-green-600' : '',
            stepErrors[2]?.length > 0 ? 'text-red-600' : ''
          ]">
            Description
            <span v-if="stepErrors[2]?.length > 0" class="text-xs block">{{ stepErrors[2].length }} error(s)</span>
          </span>
                </div>
                <div class="w-12 h-0.5 bg-gray-200 relative overflow-hidden">
                  <div :class="[
            'h-full transition-all duration-300',
            completedSteps.has(2) ? 'bg-green-500' : currentStep >= 3 ? 'bg-blue-600' : 'bg-gray-200',
            { 'w-full': currentStep >= 3 || completedSteps.has(2), 'w-0': currentStep < 3 && !completedSteps.has(2) }
          ]"></div>
                </div>
                <div class="flex items-center space-x-2">
                  <button
                    type="button"
                    @click="goToStep(3)"
                    :class="[
              'w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium transition-all',
              canAccessStep(3) ? 'cursor-pointer' : 'cursor-not-allowed opacity-50',
              currentStep >= 3 ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-600',
              completedSteps.has(3) ? 'ring-2 ring-green-300' : ''
            ]"
                    :disabled="!canAccessStep(3)"
                    :title="canAccessStep(3) ? 'Go to Step 3' : 'Complete previous steps first'"
                  >
                    <span v-if="completedSteps.has(3)" class="text-xs">✓</span>
                    <span v-else>3</span>
                  </button>
                  <span :class="[
            'transition-all',
            canAccessStep(3) ? (currentStep >= 3 ? 'text-blue-600 font-medium' : 'text-gray-500') : 'text-gray-400',
            completedSteps.has(3) ? 'text-green-600' : ''
          ]">
            Organization
          </span>
                </div>
              </div>

              <!-- Step Content -->
              <div class="min-h-[400px]">
                <!-- Step 1: Core Work Information -->
                <div v-show="currentStep === 1" class="space-y-6">
                  <div class="text-center mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                      📝 What task did you work on?
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                      Let's start with the basics - what you worked on and when
                    </p>
                  </div>

                  <!-- Task Title -->
                  <FormField
                    v-model="form.work_title"
                    type="text"
                    label="Task Title"
                    placeholder="e.g., Fixed user authentication bug, Updated dashboard UI..."
                    :error="form.errors.work_title"
                    hint="A clear, brief description of what you worked on"
                    required
                  />

                  <!-- Start Date & Time -->
                  <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                      Start Date & Time <span class="text-red-500 ml-1">*</span>
                    </label>
                    <DateTimePicker
                      v-model="form.start_date_time"
                      placeholder="Select start date and time"
                    />
                    <div v-if="form.errors.start_date_time" class="mt-2 text-sm text-red-600">
                      {{ Array.isArray(form.errors.start_date_time) ? form.errors.start_date_time.join(', ') : form.errors.start_date_time }}
                    </div>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                      When did you start this task?
                    </p>
                  </div>

                  <!-- End Date & Time -->
                  <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                      End Date & Time <span class="text-red-500 ml-1">*</span>
                    </label>
                    <DateTimePicker
                      v-model="form.end_date_time"
                      placeholder="Select end date and time"
                    />
                    <div v-if="form.errors.end_date_time" class="mt-2 text-sm text-red-600">
                      {{ Array.isArray(form.errors.end_date_time) ? form.errors.end_date_time.join(', ') : form.errors.end_date_time }}
                    </div>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                      When did you finish this task?
                    </p>
                  </div>

                  <!-- Task Duration (Calculated) -->
                  <div v-if="taskDuration" class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg border border-blue-200 dark:border-blue-800">
                    <div class="flex items-center justify-between">
                      <span class="text-sm font-medium text-blue-900 dark:text-blue-100">Task Duration:</span>
                      <span class="text-lg font-semibold text-blue-800 dark:text-blue-200">{{ taskDuration }}</span>
                    </div>
                    <div class="text-xs text-blue-700 dark:text-blue-300 mt-1">
                      Total hours: {{ hoursWorked }}
                    </div>
                  </div>

                  <!-- Status -->
                  <FormField
                    v-model="form.status"
                    type="radio"
                    label="Status"
                    :options="[
                      { value: 'in_progress', label: 'In Progress' },
                      { value: 'completed', label: 'Completed' },
                      { value: 'draft', label: 'Draft' }
                    ]"
                    orientation="horizontal"
                    :error="form.errors.status"
                    hint="What's the current state of this work?"
                  />
                </div>

                <!-- Step 2: Work Description -->
                <div v-show="currentStep === 2" class="space-y-6">
                  <div class="text-center mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                      📋 Tell us more about your task
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                      Provide detailed information about what you accomplished
                    </p>
                  </div>

                  <!-- Rich Text Editor -->
                  <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                      Description <span class="text-red-500 ml-1">*</span>
                    </label>
                    <div class="border rounded-lg">
                      <!-- Editor Toolbar -->
                      <div class="flex items-center space-x-1 p-2 border-b bg-gray-50 dark:bg-gray-800 rounded-t-lg">
                        <EditorToolbarButton
                          v-for="(tool, index) in editorTools"
                          :key="index"
                          :editor="editor"
                          :tool="tool"
                        />
                      </div>

                      <!-- Editor Content -->
                      <div class="p-3">
                        <EditorContent
                          :editor="editor"
                          class="prose prose-sm dark:prose-invert max-w-none min-h-[150px] focus:outline-none"
                        />
                      </div>
                    </div>
                    <div v-if="form.errors.description" class="mt-2 text-sm text-red-600">
                      {{ Array.isArray(form.errors.description) ? form.errors.description.join(', ') : form.errors.description }}
                    </div>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                      Describe the task you worked on, challenges faced, and outcomes achieved
                    </p>
                  </div>

                  <!-- Enhanced Fields for Step 2 -->
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Work Type -->
                    <div>
                      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Work Type
                      </label>
                      <select
                        v-model="form.work_type"
                        class="w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm px-3 py-2 bg-white dark:bg-gray-800 focus:ring-blue-500 focus:border-blue-500"
                      >
                        <option value="task">Task</option>
                        <option value="meeting">Meeting</option>
                        <option value="call">Phone Call</option>
                        <option value="email">Email</option>
                        <option value="travel">Travel</option>
                        <option value="research">Research</option>
                        <option value="presentation">Presentation</option>
                        <option value="other">Other</option>
                      </select>
                    </div>

                    <!-- Priority -->
                    <div>
                      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Priority
                      </label>
                      <select
                        v-model="form.priority"
                        class="w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm px-3 py-2 bg-white dark:bg-gray-800 focus:ring-blue-500 focus:border-blue-500"
                      >
                        <option value="low">Low</option>
                        <option value="medium">Medium</option>
                        <option value="high">High</option>
                        <option value="urgent">Urgent</option>
                      </select>
                    </div>
                  </div>

                  <!-- Location and Notes -->
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Location
                      </label>
                      <input
                        v-model="form.location"
                        type="text"
                        placeholder="e.g., Office, Home, Client site"
                        class="w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm px-3 py-2 bg-white dark:bg-gray-800 focus:ring-blue-500 focus:border-blue-500"
                      />
                    </div>

                    <div>
                      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Outcome
                      </label>
                      <select
                        v-model="form.outcome"
                        class="w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm px-3 py-2 bg-white dark:bg-gray-800 focus:ring-blue-500 focus:border-blue-500"
                      >
                        <option value="">Select outcome</option>
                        <option value="successful">Successful</option>
                        <option value="partially_successful">Partially Successful</option>
                        <option value="unsuccessful">Unsuccessful</option>
                        <option value="pending">Pending</option>
                        <option value="follow_up_needed">Follow-up Needed</option>
                      </select>
                    </div>
                  </div>

                  <!-- Additional Notes -->
                  <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                      Additional Notes
                    </label>
                    <textarea
                      v-model="form.notes"
                      rows="3"
                      placeholder="Any additional notes, thoughts, or context..."
                      class="w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm px-3 py-2 bg-white dark:bg-gray-800 focus:ring-blue-500 focus:border-blue-500"
                    ></textarea>
                  </div>
                </div>

                <!-- Step 3: Organization (Optional) -->
                <div v-show="currentStep === 3" class="space-y-6">
                  <div class="text-center mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                      🎯 Project & Tags (Optional)
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                      Help us track project progress and organize your work
                    </p>
                  </div>

                  <!-- Project Selection -->
                  <FormField
                    v-model="form.project_uuid"
                    type="select"
                    label="Project"
                    placeholder="Select a project (optional)..."
                    :options="projectOptions"
                    :error="form.errors.project_uuid"
                    hint="Associate this task with a specific project for progress tracking"
                  />

                  <!-- Tags -->
                  <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                      Tags
                    </label>
                    <div class="space-y-2">
                      <!-- Selected Tags -->
                      <div v-if="form.tags.length > 0" class="flex flex-wrap gap-2">
                <span
                  v-for="tag in form.tags"
                  :key="tag"
                  class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200"
                >
                  {{ tag }}
                  <button
                    type="button"
                    @click="removeTag(tag)"
                    class="ml-1.5 h-4 w-4 flex items-center justify-center rounded-full hover:bg-blue-200 dark:hover:bg-blue-800 transition-colors"
                    :aria-label="`Remove ${tag} tag`"
                  >
                    <X class="h-2 w-2"/>
                  </button>
                </span>
                      </div>

                      <!-- Tag Input with Autocomplete -->
                      <div class="relative">
                        <ComboboxRoot v-model:search-term="tagInputValue">
                          <ComboboxAnchor>
                            <ComboboxInput
                              v-model="tagInputValue"
                              placeholder="Type to search or add new tags..."
                              class="flex h-10 w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-sm ring-offset-white file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-slate-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-950 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 dark:border-slate-800 dark:bg-slate-950 dark:ring-offset-slate-950 dark:placeholder:text-slate-400 dark:focus-visible:ring-slate-300"
                              @keydown.enter.prevent="addTag(tagInputValue)"
                            />
                          </ComboboxAnchor>

                          <ComboboxPortal>
                            <ComboboxContent
                              v-if="filteredTags.length > 0 || tagInputValue.trim()"
                              class="relative z-50 max-h-96 min-w-[8rem] overflow-hidden rounded-md border border-slate-200 bg-white text-slate-950 shadow-md dark:border-slate-800 dark:bg-slate-950 dark:text-slate-50"
                            >
                              <!-- Existing tags -->
                              <CommandGroup v-if="filteredTags.length > 0" heading="Existing Tags">
                                <CommandItem
                                  v-for="tag in filteredTags"
                                  :key="tag"
                                  :value="tag"
                                  @select="addTag(tag)"
                                  class="relative flex cursor-default select-none items-center rounded-sm px-2 py-1.5 text-sm outline-none hover:bg-slate-100 hover:text-slate-900 dark:hover:bg-slate-800 dark:hover:text-slate-50"
                                >
                                  <span>{{ tag }}</span>
                                </CommandItem>
                              </CommandGroup>

                              <!-- Add new tag option -->
                              <CommandGroup
                                v-if="tagInputValue.trim() && !availableTags.some(t => t.toLowerCase() === tagInputValue.toLowerCase())">
                                <CommandItem
                                  :value="tagInputValue"
                                  @select="addTag(tagInputValue)"
                                  class="relative flex cursor-default select-none items-center rounded-sm px-2 py-1.5 text-sm outline-none hover:bg-slate-100 hover:text-slate-900 dark:hover:bg-slate-800 dark:hover:text-slate-50"
                                >
                                  <span class="text-green-600 dark:text-green-400">+ Add "{{ tagInputValue }}"</span>
                                </CommandItem>
                              </CommandGroup>

                              <CommandEmpty>No matching tags found.</CommandEmpty>
                            </ComboboxContent>
                          </ComboboxPortal>
                        </ComboboxRoot>
                      </div>

                      <!-- Tag count indicator -->
                      <div class="text-xs text-gray-500 dark:text-gray-400">
                        {{ form.tags.length }}/10 tags used
                      </div>
                    </div>
                    <div v-if="form.errors.tags" class="mt-2 text-sm text-red-600">
                      {{ Array.isArray(form.errors.tags) ? form.errors.tags.join(', ') : form.errors.tags }}
                    </div>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                      Add tags to categorize and filter your task entries (up to 10 tags)
                    </p>
                  </div>

                  <!-- Project Context (if project selected) -->
                  <div v-if="selectedProject"
                       class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg border border-blue-200 dark:border-blue-800">
                    <h4 class="font-medium text-blue-900 dark:text-blue-100 mb-2">
                      📊 {{ selectedProject.name }} Progress
                    </h4>
                    <div class="space-y-2 text-sm text-blue-800 dark:text-blue-200">
                      <div class="flex justify-between">
                        <span>Completion:</span>
                        <span class="font-medium">{{ selectedProject.completionPercentage }}%</span>
                      </div>
                      <div class="flex justify-between">
                        <span>Due Date:</span>
                        <span class="font-medium">{{
                            selectedProject.dueDate ? format(new Date(selectedProject.dueDate), 'MMM dd, yyyy') : 'Not set'
                          }}</span>
                      </div>
                    </div>
                  </div>

                  <!-- Enhanced Sections -->

                  <!-- Contacts Section -->
                  <div class="space-y-4">
                    <div class="flex items-center justify-between">
                      <h4 class="text-md font-medium text-gray-900 dark:text-gray-100 flex items-center gap-2">
                        <Users class="w-4 h-4" />
                        Contacts
                      </h4>
                      <button
                        type="button"
                        @click="addContact"
                        class="inline-flex items-center px-3 py-1 border border-transparent text-sm leading-4 font-medium rounded-md text-blue-700 bg-blue-100 hover:bg-blue-200 dark:bg-blue-900 dark:text-blue-100 dark:hover:bg-blue-800"
                      >
                        <Plus class="w-4 h-4 mr-1" />
                        Add Contact
                      </button>
                    </div>

                    <div v-if="form.contacts.length === 0" class="text-center py-4 text-gray-500 dark:text-gray-400">
                      No contacts added yet
                    </div>

                    <div v-for="(contact, index) in form.contacts" :key="index" class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg space-y-3">
                      <div class="flex items-center justify-between">
                        <h5 class="text-sm font-medium">Contact {{ index + 1 }}</h5>
                        <button
                          type="button"
                          @click="removeContact(index)"
                          class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-200"
                        >
                          <X class="w-4 h-4" />
                        </button>
                      </div>
                      <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <input
                          v-model="contact.name"
                          type="text"
                          placeholder="Full name"
                          class="w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm px-3 py-2 text-sm bg-white dark:bg-gray-700"
                        />
                        <input
                          v-model="contact.role"
                          type="text"
                          placeholder="Role/Position"
                          class="w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm px-3 py-2 text-sm bg-white dark:bg-gray-700"
                        />
                        <input
                          v-model="contact.email"
                          type="email"
                          placeholder="Email address"
                          class="w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm px-3 py-2 text-sm bg-white dark:bg-gray-700"
                        />
                        <input
                          v-model="contact.phone"
                          type="text"
                          placeholder="Phone number"
                          class="w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm px-3 py-2 text-sm bg-white dark:bg-gray-700"
                        />
                        <input
                          v-model="contact.company"
                          type="text"
                          placeholder="Company"
                          class="w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm px-3 py-2 text-sm bg-white dark:bg-gray-700 md:col-span-2"
                        />
                      </div>
                    </div>
                  </div>

                  <!-- Organizations Section -->
                  <div class="space-y-4">
                    <div class="flex items-center justify-between">
                      <h4 class="text-md font-medium text-gray-900 dark:text-gray-100 flex items-center gap-2">
                        <Building2 class="w-4 h-4" />
                        Organizations
                      </h4>
                      <button
                        type="button"
                        @click="addOrganization"
                        class="inline-flex items-center px-3 py-1 border border-transparent text-sm leading-4 font-medium rounded-md text-green-700 bg-green-100 hover:bg-green-200 dark:bg-green-900 dark:text-green-100 dark:hover:bg-green-800"
                      >
                        <Plus class="w-4 h-4 mr-1" />
                        Add Organization
                      </button>
                    </div>

                    <div v-if="form.organizations.length === 0" class="text-center py-4 text-gray-500 dark:text-gray-400">
                      No organizations added yet
                    </div>

                    <div v-for="(org, index) in form.organizations" :key="index" class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg space-y-3">
                      <div class="flex items-center justify-between">
                        <h5 class="text-sm font-medium">Organization {{ index + 1 }}</h5>
                        <button
                          type="button"
                          @click="removeOrganization(index)"
                          class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-200"
                        >
                          <X class="w-4 h-4" />
                        </button>
                      </div>
                      <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <input
                          v-model="org.name"
                          type="text"
                          placeholder="Organization name"
                          class="w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm px-3 py-2 text-sm bg-white dark:bg-gray-700"
                        />
                        <input
                          v-model="org.type"
                          type="text"
                          placeholder="Type (Client, Partner, etc.)"
                          class="w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm px-3 py-2 text-sm bg-white dark:bg-gray-700"
                        />
                        <input
                          v-model="org.website"
                          type="url"
                          placeholder="Website URL"
                          class="w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm px-3 py-2 text-sm bg-white dark:bg-gray-700"
                        />
                      </div>
                    </div>
                  </div>

                  <!-- Value & Productivity Section -->
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Value Generated -->
                    <div>
                      <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 flex items-center gap-2">
                        <DollarSign class="w-4 h-4" />
                        Value Generated ($)
                      </label>
                      <input
                        v-model.number="form.value_generated"
                        type="number"
                        step="0.01"
                        min="0"
                        placeholder="0.00"
                        class="w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm px-3 py-2 bg-white dark:bg-gray-800"
                      />
                      <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Revenue or value generated from this work</p>
                    </div>

                    <!-- Productivity Rating -->
                    <div>
                      <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 flex items-center gap-2">
                        <Star class="w-4 h-4" />
                        Productivity Rating
                      </label>
                      <select
                        v-model="form.productivity_rating"
                        class="w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm px-3 py-2 bg-white dark:bg-gray-800"
                      >
                        <option value="">Rate your productivity</option>
                        <option :value="1">⭐ 1 - Poor</option>
                        <option :value="2">⭐⭐ 2 - Below Average</option>
                        <option :value="3">⭐⭐⭐ 3 - Average</option>
                        <option :value="4">⭐⭐⭐⭐ 4 - Good</option>
                        <option :value="5">⭐⭐⭐⭐⭐ 5 - Excellent</option>
                      </select>
                    </div>
                  </div>

                  <!-- Mood and Weather -->
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Mood/Energy Level
                      </label>
                      <select
                        v-model="form.mood"
                        class="w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm px-3 py-2 bg-white dark:bg-gray-800"
                      >
                        <option value="">How did you feel?</option>
                        <option value="energetic">😄 Energetic</option>
                        <option value="focused">🎯 Focused</option>
                        <option value="motivated">💪 Motivated</option>
                        <option value="calm">😌 Calm</option>
                        <option value="tired">😴 Tired</option>
                        <option value="stressed">😰 Stressed</option>
                        <option value="frustrated">😤 Frustrated</option>
                      </select>
                    </div>

                    <div>
                      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Weather Condition
                      </label>
                      <select
                        v-model="form.weather_condition"
                        class="w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm px-3 py-2 bg-white dark:bg-gray-800"
                      >
                        <option value="">Weather during work</option>
                        <option value="sunny">☀️ Sunny</option>
                        <option value="cloudy">☁️ Cloudy</option>
                        <option value="rainy">🌧️ Rainy</option>
                        <option value="stormy">⛈️ Stormy</option>
                        <option value="snowy">❄️ Snowy</option>
                        <option value="windy">💨 Windy</option>
                      </select>
                    </div>
                  </div>

                  <!-- Tools and Collaborators -->
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Tools Used -->
                    <div>
                      <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 flex items-center gap-2">
                        <Zap class="w-4 h-4" />
                        Tools/Software Used
                      </label>
                      <div class="space-y-2">
                        <div v-if="form.tools_used.length > 0" class="flex flex-wrap gap-2">
                          <span
                            v-for="tool in form.tools_used"
                            :key="tool"
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200"
                          >
                            {{ tool }}
                            <button
                              type="button"
                              @click="form.tools_used = form.tools_used.filter(t => t !== tool)"
                              class="ml-1.5 h-4 w-4 flex items-center justify-center rounded-full hover:bg-yellow-200 dark:hover:bg-yellow-800"
                            >
                              <X class="w-3 h-3" />
                            </button>
                          </span>
                        </div>
                        <input
                          @keydown.enter.prevent="(e) => {
                            const target = e.target as HTMLInputElement
                            const value = target.value.trim()
                            if (value && !form.tools_used.includes(value)) {
                              form.tools_used.push(value)
                              target.value = ''
                            }
                          }"
                          type="text"
                          placeholder="Type tool name and press Enter"
                          class="w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm px-3 py-2 text-sm bg-white dark:bg-gray-700"
                        />
                      </div>
                    </div>

                    <!-- Collaborators -->
                    <div>
                      <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 flex items-center gap-2">
                        <User class="w-4 h-4" />
                        Collaborators
                      </label>
                      <div class="space-y-2">
                        <div v-for="(collaborator, index) in form.collaborators" :key="index" class="flex gap-2">
                          <input
                            v-model="collaborator.name"
                            type="text"
                            placeholder="Name"
                            class="flex-1 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm px-3 py-2 text-sm bg-white dark:bg-gray-700"
                          />
                          <input
                            v-model="collaborator.role"
                            type="text"
                            placeholder="Role"
                            class="flex-1 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm px-3 py-2 text-sm bg-white dark:bg-gray-700"
                          />
                          <button
                            type="button"
                            @click="removeCollaborator(index)"
                            class="text-red-600 hover:text-red-800 dark:text-red-400 p-2"
                          >
                            <X class="w-4 h-4" />
                          </button>
                        </div>
                        <button
                          type="button"
                          @click="addCollaborator"
                          class="w-full py-2 border border-dashed border-gray-300 dark:border-gray-600 rounded-md text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                        >
                          + Add Collaborator
                        </button>
                      </div>
                    </div>
                  </div>

                  <!-- Follow-up Section -->
                  <div class="space-y-4">
                    <div class="flex items-center space-x-2">
                      <input
                        id="requires_follow_up"
                        v-model="form.requires_follow_up"
                        type="checkbox"
                        class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                      />
                      <label for="requires_follow_up" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        This work requires follow-up
                      </label>
                    </div>

                    <div v-if="form.requires_follow_up" class="ml-6">
                      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Follow-up Date
                      </label>
                      <input
                        v-model="form.follow_up_date"
                        type="date"
                        class="w-full md:w-1/2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm px-3 py-2 bg-white dark:bg-gray-800"
                      />
                    </div>
                  </div>
                </div>
              </div>

              <!-- Navigation and Action Buttons -->
              <div class="flex justify-between items-center pt-6 border-t">
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
            </form>
          </CardContent>
        </Card>
      </div>
    </div>
  </AppLayout>
</template>

<style lang="scss">
.tiptap {
  :first-child {
    margin-top: 0;
  }

  :last-child {
    margin-bottom: 0;
  }

  p.is-editor-empty:first-child::before {
    color: theme('colors.slate.400');
    content: attr(data-placeholder);
    float: left;
    height: 0;
    pointer-events: none;
  }

  ul,
  ol {
    padding: 0 1rem;
  }

  h1,
  h2,
  h3,
  h4,
  h5,
  h6 {
    line-height: 1.1;
  }

  code {
    background-color: rgba(97, 97, 97, 0.1);
    color: #616161;
  }

  pre {
    background: #0d0d0d;
    color: #fff;
    font-family: 'JetBrainsMono', monospace;
    padding: 0.75rem 1rem;
    border-radius: 0.5rem;

    code {
      color: inherit;
      padding: 0;
      background: none;
      font-size: 0.8rem;
    }
  }

  mark {
    background-color: #faf594;
  }

  img {
    max-width: 100%;
    height: auto;
  }

  hr {
    border: none;
    border-top: 2px solid rgba(13, 13, 13, 0.1);
    margin: 2rem 0;
  }

  blockquote {
    padding-left: 1rem;
    border-left: 2px solid rgba(13, 13, 13, 0.1);
  }

  ol, ul {
    li {
      p {
        margin: 0 !important;
      }
    }
  }
}
</style>


