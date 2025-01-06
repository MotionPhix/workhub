<script setup>
import { ref, reactive } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { format } from 'date-fns'
import {
  CalendarIcon,
} from 'lucide-vue-next'
import {Label} from "@/Components/ui/label";
import {Button} from "@/Components/ui/button";
import {
  Dialog,
  DialogHeader,
  DialogContent,
  DialogTrigger,
  DialogFooter,
  DialogTitle
} from "@/Components/ui/dialog/index.js";
import {Popover} from "@/Components/ui/popover";
import {Calendar} from "@/Components/ui/calendar";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectValue,
  SelectTrigger,
  SelectGroup,
  SelectLabel
} from "@/Components/ui/select";

const props = defineProps({
  projects: {
    type: Array,
    default: () => []
  }
})

const form = useForm({
  work_date: new Date(),
  project: null,
  hours_worked: 0,
  description: '',
  tags: []
})

const isOpen = ref(false)
const isSubmitting = ref(false)

const availableTags = [
  'Development',
  'Design',
  'Meeting',
  'Research',
  'Documentation'
]

const submitWorkEntry = async () => {
  isSubmitting.value = true

  try {
    await form.post(route('work-entries.store'), {
      onSuccess: () => {
        isOpen.value = false
        form.reset()
      },
      onError: (errors) => {
        // Handle validation errors
        console.error(errors)
      }
    })
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <Dialog v-model:open="isOpen">
    <DialogTrigger as-child>
      <Button variant="outline">Add Work Entry</Button>
    </DialogTrigger>
    <DialogContent class="sm:max-w-[600px]">
      <DialogHeader>
        <DialogTitle>Create New Work Entry</DialogTitle>
        <DialogDescription>
          Log your daily work and track your productivity
        </DialogDescription>
      </DialogHeader>

      <form @submit.prevent="submitWorkEntry" class="space-y-4">
        <div>
          <Label>Work Date</Label>
          <Popover>
            <PopoverTrigger as-child>
              <Button
                variant="outline"
                :class="cn(
                  'w-full justify-start text-left font-normal',
                  !form.work_date && 'text-muted-foreground'
                )"
              >
                <CalendarIcon class="mr-2 h-4 w-4" />
                {{ form.work_date ? format(form.work_date, 'PPP') : 'Pick a date' }}
              </Button>
            </PopoverTrigger>

            <PopoverContent class="w-auto p-0">
              <Calendar
                v-model="form.work_date"
                mode="single"
                :disabled="(date) => date > new Date()"
              />
            </PopoverContent>
          </Popover>
        </div>

        <div>
          <Label>Project</Label>
          <Select v-model="form.project">
            <SelectTrigger>
              <SelectValue placeholder="Select a project" />
            </SelectTrigger>
            <SelectContent>
              <SelectGroup>
                <SelectLabel>Your Projects</SelectLabel>
                <SelectItem
                  v-for="project in projects"
                  :key="project.id"
                  :value="project.id"
                >
                  {{ project.name }}
                </SelectItem>
              </SelectGroup>
            </SelectContent>
          </Select>
        </div>

        <div>
          <Label>Hours Worked</Label>
          <Input
            type="number"
            v-model="form.hours_worked"
            min="0"
            max="24"
            step="0.5"
          />
        </div>

        <div>
          <Label>Description</Label>
          <Textarea
            v-model="form.description"
            placeholder="Describe your work today"
          />
        </div>

        <div>
          <Label>Tags</Label>
          <MultiSelect
            v-model="form.tags"
            :options="availableTags"
            placeholder="Select tags"
          />
        </div>

        <DialogFooter>
          <Button type="submit" :disabled="isSubmitting">
            {{ isSubmitting ? 'Saving...' : 'Save Work Entry' }}
          </Button>
        </DialogFooter>
      </form>
    </DialogContent>
  </Dialog>
</template>
