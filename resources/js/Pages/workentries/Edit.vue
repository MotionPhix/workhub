<script setup lang="ts">
import { ref, computed, onBeforeUnmount } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { Modal } from '@inertiaui/modal-vue'
import { toast } from 'vue-sonner'
import { EditorContent, useEditor } from '@tiptap/vue-3'
import {
  Calendar,
  Clock,
  Save,
  X,
  Bold,
  Italic,
  Underline,
  List,
  ListOrdered,
  Tag
} from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue
} from '@/components/ui/select'
import {
  TagsInput,
  TagsInputInput,
  TagsInputItem,
  TagsInputItemDelete,
  TagsInputItemText
} from '@/components/ui/tags-input'

// TipTap Extensions
import Document from '@tiptap/extension-document'
import Paragraph from '@tiptap/extension-paragraph'
import Text from '@tiptap/extension-text'
import BoldExtension from '@tiptap/extension-bold'
import ItalicExtension from '@tiptap/extension-italic'
import UnderlineExtension from '@tiptap/extension-underline'
import BulletList from '@tiptap/extension-bullet-list'
import OrderedList from '@tiptap/extension-ordered-list'
import ListItem from '@tiptap/extension-list-item'
import Placeholder from '@tiptap/extension-placeholder'

interface Props {
  workEntry: {
    uuid: string
    work_title: string
    work_date: string
    hours_worked: number
    description: string
    status: string
    project_uuid?: string
    tags: string[]
  }
  projects?: Array<{
    uuid: string
    name: string
  }>
}

const props = defineProps<Props>()

const form = useForm({
  work_title: props.workEntry.work_title,
  work_date: props.workEntry.work_date,
  hours_worked: props.workEntry.hours_worked,
  description: props.workEntry.description,
  status: props.workEntry.status,
  project_uuid: props.workEntry.project_uuid || '',
  tags: props.workEntry.tags || []
})

// Rich Text Editor Setup
const editor = useEditor({
  content: props.workEntry.description,
  extensions: [
    Document,
    Paragraph,
    Text,
    BoldExtension,
    ItalicExtension,
    UnderlineExtension,
    BulletList,
    OrderedList,
    ListItem,
    Placeholder.configure({
      placeholder: 'Describe your work activities, achievements, and notes...'
    })
  ],
  onUpdate: ({ editor }) => {
    form.description = editor.getHTML()
  }
})

onBeforeUnmount(() => {
  editor.value?.destroy()
})

const submitForm = () => {
  form.put(route('work-entries.update', props.workEntry.uuid), {
    onSuccess: () => {
      toast.success('Work entry updated successfully!')
    },
    onError: (errors) => {
      toast.error('Please fix the errors and try again.')
    }
  })
}

const toggleBold = () => editor.value?.chain().focus().toggleBold().run()
const toggleItalic = () => editor.value?.chain().focus().toggleItalic().run()
const toggleUnderline = () => editor.value?.chain().focus().toggleUnderline().run()
const toggleBulletList = () => editor.value?.chain().focus().toggleBulletList().run()
const toggleOrderedList = () => editor.value?.chain().focus().toggleOrderedList().run()
</script>

<template>
  <Modal max-width="4xl" :close-button="true">
    <div class="p-6">
      <!-- Header -->
      <div class="flex items-center justify-between mb-6">
        <div>
          <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
            Edit Work Entry
          </h2>
          <p class="text-gray-600 dark:text-gray-400 mt-1">
            Update your work log details
          </p>
        </div>
      </div>

      <!-- Form -->
      <form @submit.prevent="submitForm" class="space-y-6">
        <!-- Basic Information -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Work Title -->
          <div>
            <Label for="work_title" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              Work Title *
            </Label>
            <Input
              id="work_title"
              v-model="form.work_title"
              type="text"
              placeholder="Brief title for your work"
              required
              class="mt-1"
              :class="{ 'border-red-500': form.errors.work_title }"
            />
            <div v-if="form.errors.work_title" class="mt-1 text-sm text-red-600 dark:text-red-400">
              {{ form.errors.work_title }}
            </div>
          </div>

          <!-- Work Date -->
          <div>
            <Label for="work_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              <Calendar class="w-4 h-4 inline mr-1" />
              Work Date *
            </Label>
            <Input
              id="work_date"
              v-model="form.work_date"
              type="date"
              required
              class="mt-1"
              :class="{ 'border-red-500': form.errors.work_date }"
            />
            <div v-if="form.errors.work_date" class="mt-1 text-sm text-red-600 dark:text-red-400">
              {{ form.errors.work_date }}
            </div>
          </div>

          <!-- Hours Worked -->
          <div>
            <Label for="hours_worked" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              <Clock class="w-4 h-4 inline mr-1" />
              Hours Worked *
            </Label>
            <Input
              id="hours_worked"
              v-model="form.hours_worked"
              type="number"
              min="0.1"
              max="24"
              step="0.1"
              placeholder="8.0"
              required
              class="mt-1"
              :class="{ 'border-red-500': form.errors.hours_worked }"
            />
            <div v-if="form.errors.hours_worked" class="mt-1 text-sm text-red-600 dark:text-red-400">
              {{ form.errors.hours_worked }}
            </div>
          </div>

          <!-- Status -->
          <div>
            <Label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              Status
            </Label>
            <Select v-model="form.status">
              <SelectTrigger class="mt-1">
                <SelectValue placeholder="Select status" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="in_progress">In Progress</SelectItem>
                <SelectItem value="completed">Completed</SelectItem>
                <SelectItem value="draft">Draft</SelectItem>
              </SelectContent>
            </Select>
            <div v-if="form.errors.status" class="mt-1 text-sm text-red-600 dark:text-red-400">
              {{ form.errors.status }}
            </div>
          </div>
        </div>

        <!-- Project (if available) -->
        <div v-if="projects && projects.length > 0">
          <Label for="project" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            Project
          </Label>
          <Select v-model="form.project_uuid">
            <SelectTrigger class="mt-1">
              <SelectValue placeholder="Select a project (optional)" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="">No Project</SelectItem>
              <SelectItem v-for="project in projects" :key="project.uuid" :value="project.uuid">
                {{ project.name }}
              </SelectItem>
            </SelectContent>
          </Select>
          <div v-if="form.errors.project_uuid" class="mt-1 text-sm text-red-600 dark:text-red-400">
            {{ form.errors.project_uuid }}
          </div>
        </div>

        <!-- Tags -->
        <div>
          <Label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            <Tag class="w-4 h-4 inline mr-1" />
            Tags
          </Label>
          <TagsInput v-model="form.tags" class="gap-1">
            <TagsInputItem v-for="tag in form.tags" :key="tag" :value="tag">
              <TagsInputItemText />
              <TagsInputItemDelete />
            </TagsInputItem>

            <TagsInputInput placeholder="Add tags..." />
          </TagsInput>
          <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
            Press Enter to add tags (e.g., "meeting", "development", "research")
          </p>
          <div v-if="form.errors.tags" class="mt-1 text-sm text-red-600 dark:text-red-400">
            {{ form.errors.tags }}
          </div>
        </div>

        <!-- Description -->
        <div>
          <Label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Work Description
          </Label>

          <!-- Editor Toolbar -->
          <div v-if="editor" class="border border-gray-300 dark:border-gray-600 rounded-t-md bg-gray-50 dark:bg-gray-800 px-3 py-2 flex items-center gap-1">
            <Button
              type="button"
              variant="ghost"
              size="sm"
              @click="toggleBold"
              :class="{ 'bg-gray-200 dark:bg-gray-700': editor.isActive('bold') }"
            >
              <Bold class="w-4 h-4" />
            </Button>
            <Button
              type="button"
              variant="ghost"
              size="sm"
              @click="toggleItalic"
              :class="{ 'bg-gray-200 dark:bg-gray-700': editor.isActive('italic') }"
            >
              <Italic class="w-4 h-4" />
            </Button>
            <Button
              type="button"
              variant="ghost"
              size="sm"
              @click="toggleUnderline"
              :class="{ 'bg-gray-200 dark:bg-gray-700': editor.isActive('underline') }"
            >
              <Underline class="w-4 h-4" />
            </Button>
            <div class="w-px h-6 bg-gray-300 dark:bg-gray-600 mx-1"></div>
            <Button
              type="button"
              variant="ghost"
              size="sm"
              @click="toggleBulletList"
              :class="{ 'bg-gray-200 dark:bg-gray-700': editor.isActive('bulletList') }"
            >
              <List class="w-4 h-4" />
            </Button>
            <Button
              type="button"
              variant="ghost"
              size="sm"
              @click="toggleOrderedList"
              :class="{ 'bg-gray-200 dark:bg-gray-700': editor.isActive('orderedList') }"
            >
              <ListOrdered class="w-4 h-4" />
            </Button>
          </div>

          <!-- Editor Content -->
          <EditorContent
            :editor="editor"
            class="border border-gray-300 dark:border-gray-600 rounded-b-md min-h-[120px] p-3 prose prose-sm dark:prose-invert max-w-none focus-within:ring-2 focus-within:ring-blue-500 focus-within:border-transparent"
            :class="{ 'border-red-500': form.errors.description }"
          />
          <div v-if="form.errors.description" class="mt-1 text-sm text-red-600 dark:text-red-400">
            {{ form.errors.description }}
          </div>
        </div>

        <!-- Form Actions -->
        <div class="flex items-center justify-end gap-3 pt-6 border-t border-gray-200 dark:border-gray-700">
          <Button type="button" variant="outline" @click="$emit('close')">
            <X class="w-4 h-4 mr-2" />
            Cancel
          </Button>

          <Button type="submit" :disabled="form.processing">
            <Save class="w-4 h-4 mr-2" />
            {{ form.processing ? 'Updating...' : 'Update Entry' }}
          </Button>
        </div>
      </form>
    </div>
  </Modal>
</template>

<style>
.ProseMirror {
  outline: none;
}

.ProseMirror p {
  margin: 0.5rem 0;
}

.ProseMirror ul, .ProseMirror ol {
  padding-left: 1.5rem;
}

.ProseMirror li {
  margin: 0.25rem 0;
}

.ProseMirror-focused {
  outline: none;
}
</style>