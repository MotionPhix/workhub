<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'
import { Button } from '@/components/ui/button'
import { Label } from '@/components/ui/label'
import { Card, CardContent } from '@/components/ui/card'
import { computed, watch } from 'vue'
import { toast } from 'vue-sonner'

interface ProjectFormProps {
  mode: 'create' | 'edit'
  action: string
  method?: 'post' | 'put'
  project?: any
  departments: Array<{ uuid: string; name: string }>
  managers: Array<{ id: number; name: string; email: string }>
  tags?: string[]
}

const props = defineProps<ProjectFormProps>()
const emit = defineEmits(['submitted'])

const form = useForm({
  name: props.project?.name || '',
  description: props.project?.description || '',
  department_uuid: props.project?.department_uuid || '',
  manager_id: props.project?.manager_id || '',
  start_date: props.project?.start_date || '',
  due_date: props.project?.due_date || '',
  status: props.project?.status || 'planning',
  priority: props.project?.priority || 'medium',
  project_type: props.project?.project_type || 'internal',
  client_name: props.project?.client_name || '',
  client_contact: props.project?.client_contact || '',
  estimated_hours: props.project?.estimated_hours || '',
  is_shared: props.project?.is_shared || false,
  tags: props.project?.tags?.map((t:any)=> t.name) || [],
})

// Alias to prevent deep TS inference loops in template
const f: any = form

const isClient = computed(() => form.project_type === 'client')

function submit() {
  const options:any = {
    onSuccess: () => {
      toast.success(props.mode === 'create' ? 'Project created' : 'Project updated')
      emit('submitted')
      if (props.mode === 'create') {
        // Use alias to minimize TS deep inference
        f.reset && f.reset()
      }
    }
  }
  if (props.method === 'put') {
    form.put(props.action, options)
  } else {
    form.post(props.action, options)
  }
}

watch(() => form.project_type, (val) => {
  if (val === 'internal') {
    form.client_name = ''
    form.client_contact = ''
  }
})
</script>

<template>
  <form @submit.prevent="submit" class="space-y-6">
    <div class="grid gap-6 md:grid-cols-2">
      <div class="space-y-1 md:col-span-2">
        <Label for="name">Name</Label>
        <Input id="name" v-model="form.name" />
  <p v-if="f.errors.name" class="text-xs text-red-500">{{ f.errors.name }}</p>
      </div>

      <div class="space-y-1 md:col-span-2">
        <Label for="description">Description</Label>
        <Textarea id="description" v-model="form.description" rows="4" />
  <p v-if="f.errors.description" class="text-xs text-red-500">{{ f.errors.description }}</p>
      </div>

      <div class="space-y-1">
        <Label for="department_uuid">Department</Label>
        <select id="department_uuid" v-model="form.department_uuid" class="w-full rounded border bg-background px-3 py-2 text-sm">
          <option value="" disabled>Select department</option>
          <option v-for="d in departments" :key="d.uuid" :value="d.uuid">{{ d.name }}</option>
        </select>
  <p v-if="f.errors.department_uuid" class="text-xs text-red-500">{{ f.errors.department_uuid }}</p>
      </div>

      <div class="space-y-1">
        <Label for="manager_id">Manager</Label>
        <select id="manager_id" v-model="form.manager_id" class="w-full rounded border bg-background px-3 py-2 text-sm">
          <option value="" disabled>Select manager</option>
            <option v-for="m in managers" :key="m.id" :value="m.id">{{ m.name }}</option>
        </select>
  <p v-if="f.errors.manager_id" class="text-xs text-red-500">{{ f.errors.manager_id }}</p>
      </div>

      <div class="space-y-1">
        <Label for="start_date">Start Date</Label>
        <Input id="start_date" type="date" v-model="form.start_date" />
  <p v-if="f.errors.start_date" class="text-xs text-red-500">{{ f.errors.start_date }}</p>
      </div>

      <div class="space-y-1">
        <Label for="due_date">Due Date</Label>
        <Input id="due_date" type="date" v-model="form.due_date" />
  <p v-if="f.errors.due_date" class="text-xs text-red-500">{{ f.errors.due_date }}</p>
      </div>

      <div class="space-y-1">
        <Label for="status">Status</Label>
        <select id="status" v-model="form.status" class="w-full rounded border bg-background px-3 py-2 text-sm">
          <option value="planning">Planning</option>
          <option value="active">Active</option>
          <option value="on_hold">On Hold</option>
          <option value="completed">Completed</option>
          <option value="cancelled">Cancelled</option>
        </select>
  <p v-if="f.errors.status" class="text-xs text-red-500">{{ f.errors.status }}</p>
      </div>

      <div class="space-y-1">
        <Label for="priority">Priority</Label>
        <select id="priority" v-model="form.priority" class="w-full rounded border bg-background px-3 py-2 text-sm">
          <option value="low">Low</option>
          <option value="medium">Medium</option>
          <option value="high">High</option>
          <option value="urgent">Urgent</option>
        </select>
  <p v-if="f.errors.priority" class="text-xs text-red-500">{{ f.errors.priority }}</p>
      </div>

      <div class="space-y-1">
        <Label for="project_type">Project Type</Label>
        <select id="project_type" v-model="form.project_type" class="w-full rounded border bg-background px-3 py-2 text-sm">
          <option value="internal">Internal</option>
          <option value="client">Client</option>
        </select>
  <p v-if="f.errors.project_type" class="text-xs text-red-500">{{ f.errors.project_type }}</p>
      </div>

      <div v-if="isClient" class="space-y-1">
        <Label for="client_name">Client Name</Label>
        <Input id="client_name" v-model="form.client_name" />
  <p v-if="f.errors.client_name" class="text-xs text-red-500">{{ f.errors.client_name }}</p>
      </div>

      <div v-if="isClient" class="space-y-1">
        <Label for="client_contact">Client Contact</Label>
        <Input id="client_contact" v-model="form.client_contact" />
  <p v-if="f.errors.client_contact" class="text-xs text-red-500">{{ f.errors.client_contact }}</p>
      </div>

      <div class="space-y-1">
        <Label for="estimated_hours">Estimated Hours</Label>
        <Input id="estimated_hours" type="number" step="0.25" v-model="form.estimated_hours" />
  <p v-if="f.errors.estimated_hours" class="text-xs text-red-500">{{ f.errors.estimated_hours }}</p>
      </div>

      <div class="space-y-1">
        <Label class="flex items-center gap-2">
          <input type="checkbox" v-model="form.is_shared" class="h-4 w-4" />
          <span>Shared Project</span>
        </Label>
  <p v-if="f.errors.is_shared" class="text-xs text-red-500">{{ f.errors.is_shared }}</p>
      </div>

      <div class="space-y-1 md:col-span-2">
        <Label for="tags">Tags (comma separated)</Label>
        <Input id="tags" :value="form.tags.join(', ')" @change="e => form.tags = (e.target as HTMLInputElement).value.split(',').map(t=>t.trim()).filter(Boolean)" />
      </div>
    </div>

    <div class="flex gap-3">
      <Button type="submit" :disabled="form.processing">
        {{ form.processing ? (mode === 'create' ? 'Creating...' : 'Saving...') : (mode === 'create' ? 'Create Project' : 'Save Changes') }}
      </Button>
      <slot name="secondary-actions" />
    </div>
  </form>
</template>
