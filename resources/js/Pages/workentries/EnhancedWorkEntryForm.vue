<script setup lang="ts">
import { useForm, router } from '@inertiajs/vue3'
import { computed, onMounted, ref, watch } from 'vue'
import { toast } from 'vue-sonner'
import {
  Calendar,
  Clock,
  MapPin,
  Users,
  Building2,
  DollarSign,
  Star,
  Briefcase,
  Plus,
  X,
  Loader2,
  AlertCircle,
  CheckCircle2,
  Phone,
  Mail,
  Globe,
  User,
  ChevronDown,
  ChevronUp,
  Zap
} from 'lucide-vue-next'

import AppLayout from '@/layouts/AppLayout.vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Switch } from '@/components/ui/switch'
import { Badge } from '@/components/ui/badge'
import { Separator } from '@/components/ui/separator'
import DateTimePicker from '@/components/DateTimePicker.vue'
import DatePicker from '@/components/DatePicker.vue'
import { TagsInput, TagsInputInput, TagsInputItem, TagsInputItemDelete, TagsInputItemText } from '@/components/ui/tags-input'

interface WorkEntry {
  uuid?: string
  work_title?: string
  description?: string
  notes?: string
  start_date_time?: string
  end_date_time?: string
  status?: string
  priority?: string
  work_type?: string
  location?: string
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
  tags?: string[]
}

interface Props {
  workEntry: WorkEntry
  projects?: Array<{uuid: string, name: string}>
  availableTags?: string[]
}

const props = defineProps<Props>()

// Expanded/collapsed sections
const expandedSections = ref({
  basic: true,
  contacts: false,
  productivity: false,
  collaboration: false,
  followUp: false
})

const toggleSection = (section: keyof typeof expandedSections.value) => {
  expandedSections.value[section] = !expandedSections.value[section]
}

// Form setup with enhanced fields
const form = useForm({
  work_title: props.workEntry.work_title || '',
  description: props.workEntry.description || '',
  notes: props.workEntry.notes || '',
  start_date_time: props.workEntry.start_date_time || '',
  end_date_time: props.workEntry.end_date_time || '',
  status: props.workEntry.status || 'draft',
  priority: props.workEntry.priority || 'medium',
  work_type: props.workEntry.work_type || 'task',
  location: props.workEntry.location || '',
  contacts: props.workEntry.contacts || [],
  organizations: props.workEntry.organizations || [],
  value_generated: props.workEntry.value_generated || null,
  outcome: props.workEntry.outcome || null,
  mood: props.workEntry.mood || '',
  productivity_rating: props.workEntry.productivity_rating || null,
  tools_used: props.workEntry.tools_used || [],
  collaborators: props.workEntry.collaborators || [],
  requires_follow_up: props.workEntry.requires_follow_up || false,
  follow_up_date: props.workEntry.follow_up_date || '',
  weather_condition: props.workEntry.weather_condition || '',
  tags: props.workEntry.tags || []
})

// Computed properties
const hoursWorked = computed(() => {
  if (!form.start_date_time || !form.end_date_time) return 0
  const start = new Date(form.start_date_time)
  const end = new Date(form.end_date_time)
  if (end <= start) return 0
  return Math.round(((end.getTime() - start.getTime()) / (1000 * 60 * 60)) * 100) / 100
})

const priorityColor = computed(() => {
  const colors = {
    low: 'green',
    medium: 'yellow',
    high: 'orange',
    urgent: 'red'
  }
  return colors[form.priority as keyof typeof colors] || 'gray'
})

const workTypeIcon = computed(() => {
  const icons = {
    meeting: Users,
    call: Phone,
    email: Mail,
    travel: MapPin,
    research: Zap,
    presentation: Briefcase,
    task: CheckCircle2
  }
  return icons[form.work_type as keyof typeof icons] || CheckCircle2
})

// Contact management
const addContact = () => {
  form.contacts.push({
    name: '',
    email: '',
    phone: '',
    company: '',
    role: ''
  })
}

const removeContact = (index: number) => {
  form.contacts.splice(index, 1)
}

// Organization management
const addOrganization = () => {
  form.organizations.push({
    name: '',
    type: '',
    website: ''
  })
}

const removeOrganization = (index: number) => {
  form.organizations.splice(index, 1)
}

// Collaborator management
const addCollaborator = () => {
  form.collaborators.push({
    name: '',
    role: ''
  })
}

const removeCollaborator = (index: number) => {
  form.collaborators.splice(index, 1)
}

// Auto-expand sections based on data
watch(() => form, (newForm) => {
  if (newForm.contacts.length > 0 || newForm.organizations.length > 0) {
    expandedSections.value.contacts = true
  }
  if (newForm.mood || newForm.productivity_rating || newForm.tools_used.length > 0) {
    expandedSections.value.productivity = true
  }
  if (newForm.collaborators.length > 0) {
    expandedSections.value.collaboration = true
  }
  if (newForm.requires_follow_up || newForm.follow_up_date) {
    expandedSections.value.followUp = true
  }
}, { deep: true })

// Form submission
const submitForm = () => {
  const url = props.workEntry.uuid
    ? route('work-entries.update', props.workEntry.uuid)
    : route('work-entries.store')

  const method = props.workEntry.uuid ? 'put' : 'post'

  form[method](url, {
    onSuccess: () => {
      toast.success(props.workEntry.uuid ? 'Work entry updated!' : 'Work entry created!', {
        description: 'Your work log has been saved successfully.'
      })
      router.visit(route('work-entries.index'))
    },
    onError: (errors) => {
      console.error('Form errors:', errors)
      toast.error('Please fix the errors and try again')
    }
  })
}

const cancel = () => {
  router.visit(route('work-entries.index'))
}

// Initialize expanded sections based on existing data
onMounted(() => {
  if (props.workEntry.uuid) {
    // Auto-expand sections that have data
    if (form.contacts.length > 0 || form.organizations.length > 0) {
      expandedSections.value.contacts = true
    }
    if (form.mood || form.productivity_rating || form.tools_used.length > 0) {
      expandedSections.value.productivity = true
    }
    if (form.collaborators.length > 0) {
      expandedSections.value.collaboration = true
    }
    if (form.requires_follow_up || form.follow_up_date) {
      expandedSections.value.followUp = true
    }
  }
})
</script>

<template>
  <AppLayout>
    <div class="py-12">
      <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
          <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
            {{ workEntry.uuid ? 'Edit Work Entry' : 'Create Work Entry' }}
          </h1>
          <p class="text-gray-600 dark:text-gray-400 mt-2">
            {{ workEntry.uuid
              ? 'Update your work log with detailed information'
              : 'Log your work with comprehensive details for better tracking and analytics'
            }}
          </p>
        </div>

        <form @submit.prevent="submitForm" class="space-y-8">
          <!-- Basic Information -->
          <Card>
            <CardHeader>
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <component :is="workTypeIcon" class="w-5 h-5 text-blue-600" />
                  <CardTitle>Basic Information</CardTitle>
                  <Badge :variant="priorityColor === 'red' ? 'destructive' : 'secondary'" class="ml-2">
                    {{ form.priority?.toUpperCase() }}
                  </Badge>
                </div>
                <Button
                  type="button"
                  variant="ghost"
                  size="sm"
                  @click="toggleSection('basic')"
                >
                  <component :is="expandedSections.basic ? ChevronUp : ChevronDown" class="w-4 h-4" />
                </Button>
              </div>
            </CardHeader>

            <CardContent v-if="expandedSections.basic" class="space-y-6">
              <!-- Title -->
              <div class="space-y-2">
                <Label for="work_title">Work Title *</Label>
                <Input
                  id="work_title"
                  v-model="form.work_title"
                  placeholder="e.g., Client meeting for project proposal"
                  :class="{ 'border-red-500': form.errors.work_title }"
                />
                <p v-if="form.errors.work_title" class="text-sm text-red-600">
                  {{ form.errors.work_title }}
                </p>
              </div>

              <!-- Work Type and Priority -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="space-y-2">
                  <Label>Work Type *</Label>
                  <Select v-model="form.work_type">
                    <SelectTrigger>
                      <SelectValue placeholder="Select work type" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="task">Task</SelectItem>
                      <SelectItem value="meeting">Meeting</SelectItem>
                      <SelectItem value="call">Phone Call</SelectItem>
                      <SelectItem value="email">Email</SelectItem>
                      <SelectItem value="travel">Travel</SelectItem>
                      <SelectItem value="research">Research</SelectItem>
                      <SelectItem value="presentation">Presentation</SelectItem>
                      <SelectItem value="other">Other</SelectItem>
                    </SelectContent>
                  </Select>
                </div>

                <div class="space-y-2">
                  <Label>Priority *</Label>
                  <Select v-model="form.priority">
                    <SelectTrigger>
                      <SelectValue placeholder="Select priority" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="low">Low</SelectItem>
                      <SelectItem value="medium">Medium</SelectItem>
                      <SelectItem value="high">High</SelectItem>
                      <SelectItem value="urgent">Urgent</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
              </div>

              <!-- Date and Time -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="space-y-2">
                  <Label>Start Date & Time *</Label>
                  <DateTimePicker
                    v-model="form.start_date_time"
                    placeholder="Select start date and time"
                  />
                  <p v-if="form.errors.start_date_time" class="text-sm text-red-600">
                    {{ form.errors.start_date_time }}
                  </p>
                </div>

                <div class="space-y-2">
                  <Label>End Date & Time *</Label>
                  <DateTimePicker
                    v-model="form.end_date_time"
                    placeholder="Select end date and time"
                  />
                  <p v-if="form.errors.end_date_time" class="text-sm text-red-600">
                    {{ form.errors.end_date_time }}
                  </p>
                </div>
              </div>

              <!-- Duration Display -->
              <div v-if="hoursWorked > 0" class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
                <div class="flex items-center gap-2">
                  <Clock class="w-4 h-4 text-blue-600" />
                  <span class="text-sm font-medium">Duration: {{ hoursWorked }} hours</span>
                </div>
              </div>

              <!-- Status and Location -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="space-y-2">
                  <Label>Status *</Label>
                  <Select v-model="form.status">
                    <SelectTrigger>
                      <SelectValue placeholder="Select status" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="draft">Draft</SelectItem>
                      <SelectItem value="in_progress">In Progress</SelectItem>
                      <SelectItem value="completed">Completed</SelectItem>
                    </SelectContent>
                  </Select>
                </div>

                <div class="space-y-2">
                  <Label>Location</Label>
                  <Input
                    v-model="form.location"
                    placeholder="e.g., Office, Home, Client site"
                  />
                </div>
              </div>

              <!-- Description -->
              <div class="space-y-2">
                <Label for="description">Description *</Label>
                <Textarea
                  id="description"
                  v-model="form.description"
                  placeholder="Describe what you worked on, challenges faced, and outcomes achieved..."
                  rows="4"
                  :class="{ 'border-red-500': form.errors.description }"
                />
                <p v-if="form.errors.description" class="text-sm text-red-600">
                  {{ form.errors.description }}
                </p>
              </div>

              <!-- Additional Notes -->
              <div class="space-y-2">
                <Label for="notes">Additional Notes</Label>
                <Textarea
                  id="notes"
                  v-model="form.notes"
                  placeholder="Any additional notes, thoughts, or context..."
                  rows="3"
                />
              </div>

              <!-- Tags -->
              <div class="space-y-2">
                <Label>Tags</Label>
                <TagsInput v-model="form.tags" class="w-full">
                  <div class="flex gap-2 flex-wrap">
                    <TagsInputItem v-for="item in form.tags" :key="item" :value="item">
                      <TagsInputItemText />
                      <TagsInputItemDelete />
                    </TagsInputItem>
                  </div>
                  <TagsInputInput placeholder="Add tags..." />
                </TagsInput>
                <p class="text-sm text-gray-500">Add tags to categorize your work</p>
              </div>
            </CardContent>
          </Card>

          <!-- Contacts & Organizations -->
          <Card>
            <CardHeader>
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <Users class="w-5 h-5 text-green-600" />
                  <CardTitle>Contacts & Organizations</CardTitle>
                  <Badge variant="secondary">
                    {{ (form.contacts.length + form.organizations.length) }} items
                  </Badge>
                </div>
                <Button
                  type="button"
                  variant="ghost"
                  size="sm"
                  @click="toggleSection('contacts')"
                >
                  <component :is="expandedSections.contacts ? ChevronUp : ChevronDown" class="w-4 h-4" />
                </Button>
              </div>
              <CardDescription>Track people and organizations involved in your work</CardDescription>
            </CardHeader>

            <CardContent v-if="expandedSections.contacts" class="space-y-6">
              <!-- Contacts -->
              <div class="space-y-4">
                <div class="flex items-center justify-between">
                  <Label class="text-base font-semibold">Contacts</Label>
                  <Button type="button" variant="outline" size="sm" @click="addContact">
                    <Plus class="w-4 h-4 mr-2" />
                    Add Contact
                  </Button>
                </div>

                <div v-if="form.contacts.length === 0" class="text-center py-8 text-gray-500">
                  <User class="w-8 h-8 mx-auto mb-2" />
                  <p>No contacts added yet</p>
                </div>

                <div v-for="(contact, index) in form.contacts" :key="index" class="p-4 border rounded-lg space-y-4">
                  <div class="flex items-center justify-between">
                    <h4 class="font-medium">Contact {{ index + 1 }}</h4>
                    <Button type="button" variant="ghost" size="sm" @click="removeContact(index)">
                      <X class="w-4 h-4" />
                    </Button>
                  </div>

                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <Input
                      v-model="contact.name"
                      placeholder="Full name *"
                    />
                    <Input
                      v-model="contact.role"
                      placeholder="Role/Position"
                    />
                    <Input
                      v-model="contact.email"
                      type="email"
                      placeholder="Email address"
                    />
                    <Input
                      v-model="contact.phone"
                      placeholder="Phone number"
                    />
                    <Input
                      v-model="contact.company"
                      placeholder="Company"
                      class="md:col-span-2"
                    />
                  </div>
                </div>
              </div>

              <Separator />

              <!-- Organizations -->
              <div class="space-y-4">
                <div class="flex items-center justify-between">
                  <Label class="text-base font-semibold">Organizations</Label>
                  <Button type="button" variant="outline" size="sm" @click="addOrganization">
                    <Plus class="w-4 h-4 mr-2" />
                    Add Organization
                  </Button>
                </div>

                <div v-if="form.organizations.length === 0" class="text-center py-8 text-gray-500">
                  <Building2 class="w-8 h-8 mx-auto mb-2" />
                  <p>No organizations added yet</p>
                </div>

                <div v-for="(org, index) in form.organizations" :key="index" class="p-4 border rounded-lg space-y-4">
                  <div class="flex items-center justify-between">
                    <h4 class="font-medium">Organization {{ index + 1 }}</h4>
                    <Button type="button" variant="ghost" size="sm" @click="removeOrganization(index)">
                      <X class="w-4 h-4" />
                    </Button>
                  </div>

                  <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <Input
                      v-model="org.name"
                      placeholder="Organization name *"
                    />
                    <Input
                      v-model="org.type"
                      placeholder="Type (Client, Partner, etc.)"
                    />
                    <Input
                      v-model="org.website"
                      placeholder="Website URL"
                    />
                  </div>
                </div>
              </div>

              <!-- Value Generated and Outcome -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="space-y-2">
                  <Label>Value Generated ($)</Label>
                  <Input
                    v-model.number="form.value_generated"
                    type="number"
                    step="0.01"
                    min="0"
                    placeholder="0.00"
                  />
                  <p class="text-sm text-gray-500">Revenue or value generated from this work</p>
                </div>

                <div class="space-y-2">
                  <Label>Outcome</Label>
                  <Select v-model="form.outcome">
                    <SelectTrigger>
                      <SelectValue placeholder="Select outcome" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="successful">Successful</SelectItem>
                      <SelectItem value="partially_successful">Partially Successful</SelectItem>
                      <SelectItem value="unsuccessful">Unsuccessful</SelectItem>
                      <SelectItem value="pending">Pending</SelectItem>
                      <SelectItem value="follow_up_needed">Follow-up Needed</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
              </div>
            </CardContent>
          </Card>

          <!-- Productivity & Tools -->
          <Card>
            <CardHeader>
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <Zap class="w-5 h-5 text-yellow-600" />
                  <CardTitle>Productivity & Tools</CardTitle>
                  <Badge variant="secondary">
                    {{ form.mood || form.productivity_rating ? 'Tracked' : 'Not tracked' }}
                  </Badge>
                </div>
                <Button
                  type="button"
                  variant="ghost"
                  size="sm"
                  @click="toggleSection('productivity')"
                >
                  <component :is="expandedSections.productivity ? ChevronUp : ChevronDown" class="w-4 h-4" />
                </Button>
              </div>
              <CardDescription>Track your productivity and tools used</CardDescription>
            </CardHeader>

            <CardContent v-if="expandedSections.productivity" class="space-y-6">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Mood -->
                <div class="space-y-2">
                  <Label>Mood/Energy Level</Label>
                  <Select v-model="form.mood">
                    <SelectTrigger>
                      <SelectValue placeholder="How did you feel?" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="energetic">😄 Energetic</SelectItem>
                      <SelectItem value="focused">🎯 Focused</SelectItem>
                      <SelectItem value="motivated">💪 Motivated</SelectItem>
                      <SelectItem value="calm">😌 Calm</SelectItem>
                      <SelectItem value="tired">😴 Tired</SelectItem>
                      <SelectItem value="stressed">😰 Stressed</SelectItem>
                      <SelectItem value="frustrated">😤 Frustrated</SelectItem>
                    </SelectContent>
                  </Select>
                </div>

                <!-- Productivity Rating -->
                <div class="space-y-2">
                  <Label>Productivity Rating (1-5)</Label>
                  <Select v-model="form.productivity_rating">
                    <SelectTrigger>
                      <SelectValue placeholder="Rate your productivity" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem :value="1">⭐ 1 - Poor</SelectItem>
                      <SelectItem :value="2">⭐⭐ 2 - Below Average</SelectItem>
                      <SelectItem :value="3">⭐⭐⭐ 3 - Average</SelectItem>
                      <SelectItem :value="4">⭐⭐⭐⭐ 4 - Good</SelectItem>
                      <SelectItem :value="5">⭐⭐⭐⭐⭐ 5 - Excellent</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
              </div>

              <!-- Tools Used -->
              <div class="space-y-2">
                <Label>Tools/Software Used</Label>
                <TagsInput v-model="form.tools_used" class="w-full">
                  <div class="flex gap-2 flex-wrap">
                    <TagsInputItem v-for="item in form.tools_used" :key="item" :value="item">
                      <TagsInputItemText />
                      <TagsInputItemDelete />
                    </TagsInputItem>
                  </div>
                  <TagsInputInput placeholder="Add tools (e.g., VS Code, Figma, Excel)..." />
                </TagsInput>
                <p class="text-sm text-gray-500">Track which tools and software you used</p>
              </div>

              <!-- Weather Condition -->
              <div class="space-y-2">
                <Label>Weather Condition</Label>
                <Select v-model="form.weather_condition">
                  <SelectTrigger>
                    <SelectValue placeholder="Weather during work" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="sunny">☀️ Sunny</SelectItem>
                    <SelectItem value="cloudy">☁️ Cloudy</SelectItem>
                    <SelectItem value="rainy">🌧️ Rainy</SelectItem>
                    <SelectItem value="stormy">⛈️ Stormy</SelectItem>
                    <SelectItem value="snowy">❄️ Snowy</SelectItem>
                    <SelectItem value="windy">💨 Windy</SelectItem>
                  </SelectContent>
                </Select>
                <p class="text-sm text-gray-500">For field work or mood correlation</p>
              </div>
            </CardContent>
          </Card>

          <!-- Collaboration -->
          <Card>
            <CardHeader>
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <Users class="w-5 h-5 text-purple-600" />
                  <CardTitle>Collaboration</CardTitle>
                  <Badge variant="secondary">
                    {{ form.collaborators.length }} collaborators
                  </Badge>
                </div>
                <Button
                  type="button"
                  variant="ghost"
                  size="sm"
                  @click="toggleSection('collaboration')"
                >
                  <component :is="expandedSections.collaboration ? ChevronUp : ChevronDown" class="w-4 h-4" />
                </Button>
              </div>
              <CardDescription>Track team members and collaborators</CardDescription>
            </CardHeader>

            <CardContent v-if="expandedSections.collaboration" class="space-y-6">
              <div class="space-y-4">
                <div class="flex items-center justify-between">
                  <Label class="text-base font-semibold">Team Members</Label>
                  <Button type="button" variant="outline" size="sm" @click="addCollaborator">
                    <Plus class="w-4 h-4 mr-2" />
                    Add Collaborator
                  </Button>
                </div>

                <div v-if="form.collaborators.length === 0" class="text-center py-8 text-gray-500">
                  <Users class="w-8 h-8 mx-auto mb-2" />
                  <p>No collaborators added yet</p>
                </div>

                <div v-for="(collaborator, index) in form.collaborators" :key="index" class="p-4 border rounded-lg">
                  <div class="flex items-center justify-between mb-4">
                    <h4 class="font-medium">Collaborator {{ index + 1 }}</h4>
                    <Button type="button" variant="ghost" size="sm" @click="removeCollaborator(index)">
                      <X class="w-4 h-4" />
                    </Button>
                  </div>

                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <Input
                      v-model="collaborator.name"
                      placeholder="Name *"
                    />
                    <Input
                      v-model="collaborator.role"
                      placeholder="Role/Position"
                    />
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>

          <!-- Follow-up -->
          <Card>
            <CardHeader>
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <Calendar class="w-5 h-5 text-orange-600" />
                  <CardTitle>Follow-up</CardTitle>
                  <Badge :variant="form.requires_follow_up ? 'default' : 'secondary'">
                    {{ form.requires_follow_up ? 'Required' : 'Not required' }}
                  </Badge>
                </div>
                <Button
                  type="button"
                  variant="ghost"
                  size="sm"
                  @click="toggleSection('followUp')"
                >
                  <component :is="expandedSections.followUp ? ChevronUp : ChevronDown" class="w-4 h-4" />
                </Button>
              </div>
              <CardDescription>Set follow-up reminders and tasks</CardDescription>
            </CardHeader>

            <CardContent v-if="expandedSections.followUp" class="space-y-6">
              <div class="flex items-center space-x-2">
                <Switch
                  id="requires_follow_up"
                  v-model:checked="form.requires_follow_up"
                />
                <Label for="requires_follow_up">This work requires follow-up</Label>
              </div>

              <div v-if="form.requires_follow_up" class="space-y-2">
                <Label>Follow-up Date</Label>
                <DatePicker
                  v-model="form.follow_up_date"
                  placeholder="Select follow-up date"
                />
                <p class="text-sm text-gray-500">When should you follow up on this work?</p>
              </div>
            </CardContent>
          </Card>

          <!-- Submit Buttons -->
          <div class="flex justify-between items-center pt-6">
            <Button type="button" variant="outline" @click="cancel">
              Cancel
            </Button>

            <Button type="submit" :disabled="form.processing">
              <Loader2 v-if="form.processing" class="w-4 h-4 mr-2 animate-spin" />
              {{ workEntry.uuid ? 'Update Entry' : 'Create Entry' }}
            </Button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>
