<script setup lang="ts">
import {useForm} from '@inertiajs/vue3';
import {
  CommandEmpty,
  CommandGroup,
  CommandItem,
  CommandList
} from '@/Components/ui/command'
import {
  TagsInput,
  TagsInputInput,
  TagsInputItem,
  TagsInputItemDelete,
  TagsInputItemText
} from '@/Components/ui/tags-input'
import {
  ComboboxAnchor,
  ComboboxContent,
  ComboboxInput,
  ComboboxPortal,
  ComboboxRoot
} from 'radix-vue'
import {computed, onBeforeUnmount, ref} from 'vue'
import {UnderlineIcon, ItalicIcon, BoldIcon, ListIcon, ListOrderedIcon, MinusIcon} from "lucide-vue-next";
import InputError from "@/Components/InputError.vue";
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
import {subDays, format} from "date-fns";
import { Project } from '@/types'
import EditorToolbarButton from "@/Pages/WorkEntries/Components/EditorToolbarButton.vue";

interface WorkLog {
  id?: number
  uuid?: string
  work_date?: Date | string
  work_title?: string
  description?: string
  hours_worked?: number
  status?: 'draft' | 'completed' | 'in_progress'
  project_uuid?: string
}

interface Props {
  workLog: WorkLog
  tags?: Array<{
    id: number
    name: string
  }>
  projects?: Project[]
}

// Component props
const props = defineProps<Props>()

// Form initialization with default values
const form = useForm({
  work_date: props.workLog.work_date || new Date().toISOString().split('T')[0],
  work_title: props.workLog.work_title || '',
  description: props.workLog.description || '',
  hours_worked: props.workLog.hours_worked || 0,
  project_uuid: props.workLog.project_uuid || '',
  tags: props.tags || [],
  status: props.workLog.status || 'draft'
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
      placeholder: 'Type your content here...',
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
  onUpdate: ({ editor }) => {
    form.description = editor.getHTML()
  }
})

// State management
const now = new Date()
const tagInputValue = ref('')
const workLogRef = ref()

// Computed properties
const filteredTags = computed(() => {
  return props.tags?.filter(tag => !form.tags.includes(tag)) || []
})

const selectedProject = computed(() =>
  props.projects?.find(p => p.uuid === form.project_uuid)
)

const projectOptions = computed(() =>
  props.projects?.map(project => ({
    value: project.uuid,
    label: project.name
  })) || []
)

// Methods
const addTag = (tag: string) => {
  const normalizedTag = tag.trim().toLowerCase()

  if (form.tags.some(t => t.toLowerCase() === normalizedTag)) {
    toast.error('Duplicate Entry', {
      description: `${tag.toUpperCase()} already exists!`
    })
    return
  }

  if (!form.tags.includes(tag)) {
    form.tags.push(tag)
  }

  tagInputValue.value = ''
}

const onClose = () => {
  workLogRef.value.onClose()
}

const onSubmitForm = () => {
  const transformedData = {
    ...form,
    work_date: format(new Date(form.work_date), 'yyyy-MM-dd')
  }

  const handleError = (err: any) => {
    console.error('Form submission error:', err)
    toast.error('Error', {
      description: 'Failed to save work entry. Please try again.'
    })
  }

  const handleSuccess = () => {
    toast.success('Success', {
      description: props.workLog.uuid
        ? 'Work entry updated successfully'
        : 'Work entry created successfully'
    })
    if (!props.workLog.uuid) {
      form.reset()
    }
    workLogRef.value.onClose()
  }

  if (props.workLog.uuid) {
    form
      .transform(() => transformedData)
      .put(route('work-entries.update', props.workLog.uuid), {
        onError: handleError,
        onSuccess: handleSuccess
      })
  } else {
    form
      .transform(() => transformedData)
      .post(route('work-entries.store'), {
        onError: handleError,
        onSuccess: handleSuccess
      })
  }
}

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
  <GlobalModal
    ref="workLogRef"
    padding="px-4 pb-4 sm:px-5 sm:pb-5"
    :manual-close="true"
    :has-close-button="false"
  >
    <ModalHeader :heading="workLog.uuid ? `Edit ${workLog.work_title} work log` : 'Log your work'">
      <template #action>
        <Button @click="onClose" variant="outline" type="button">
          Cancel
        </Button>
        <Button @click="onSubmitForm" type="button">
          Save
        </Button>
      </template>
    </ModalHeader>

    <form class="space-y-6">
      <!-- Project Selection -->
      <FormField
        label="Project"
        type="select"
        v-model="form.project_uuid"
        :error="form.errors.project_uuid"
        :options="projectOptions"
        placeholder="Select a project"
      />

      <!-- Work Date -->
      <FormField
        type="date"
        :is-inline="true"
        label="Work Date"
        v-model="form.work_date"
        :min-date="format(subDays(now, 2), 'yyyy-MM-dd')"
        :error="form.errors.work_date"
        :max-date="now"
      />

      <!-- Work Title -->
      <FormField
        label="Work Title"
        v-model="form.work_title"
        :error="form.errors.work_title"
        :placeholder="
          selectedProject
            ? `What are you working on in ${selectedProject.name}?`
            : 'Write what you are working on in fewer words'
        "
      />

      <!-- Rich Text Editor -->
      <div>
        <Label for="description">Description</Label>
        <div class="my-2 flex space-x-2">
          <EditorToolbarButton
            v-for="(tool, index) in editorTools"
            :key="index"
            :editor="editor"
            :tool="tool"
          />
        </div>

        <div class="prose prose-lg dark:prose-invert max-w-none">
          <EditorContent
            :editor="editor"
            class="prose prose-lg border dark:prose-invert max-w-none p-3 rounded-lg dark:bg-gray-800 dark:text-white my-1"
          />
        </div>
        <InputError :message="form.errors.description" />
      </div>

      <!-- Tags -->
      <div>
        <Label for="tags">Tags</Label>
        <TagsInput v-model="form.tags" class="w-full gap-0 p-0 !border-none mt-1">
          <div
            class="flex flex-wrap items-center gap-2 px-2"
            :class="{ 'py-1': form.tags.length }"
          >
            <TagsInputItem v-for="tag in form.tags" :key="tag" :value="tag">
              <TagsInputItemText />
              <TagsInputItemDelete @click="form.tags = form.tags.filter(t => t !== tag)" />
            </TagsInputItem>
          </div>

          <ComboboxRoot v-model="tagInputValue" v-model:search-term="tagInputValue" class="w-full">
            <ComboboxAnchor as-child>
              <ComboboxInput placeholder="Add or select tags..." as-child>
                <TagsInputInput
                  class="w-full px-3 rounded-md make-large !border-none focus:ring-0"
                  @keydown.enter.prevent="addTag(tagInputValue)"
                />
              </ComboboxInput>
            </ComboboxAnchor>

            <ComboboxPortal>
              <ComboboxContent>
                <CommandList
                  position="popper"
                  class="w-[--radix-popper-anchor-width] rounded-md mt-2 bg-popover text-popover-foreground shadow-md"
                >
                  <CommandEmpty>No tags found.</CommandEmpty>
                  <CommandGroup heading="Available Tags">
                    <CommandItem
                      v-for="tag in filteredTags"
                      :key="tag"
                      :value="tag"
                      @select.prevent="addTag(tag)"
                    >
                      {{ tag }}
                    </CommandItem>
                  </CommandGroup>
                </CommandList>
              </ComboboxContent>
            </ComboboxPortal>
          </ComboboxRoot>
        </TagsInput>
        <InputError :message="form.errors.tags" />
      </div>

      <!-- Hours and Status -->
      <section class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <FormField
          type="number"
          label="Hours Worked"
          v-model="form.hours_worked"
          :error="form.errors.hours_worked"
          :step="0.01"
        />

        <FormField
          label="Status"
          type="select"
          v-model="form.status"
          :options="[
            { value: 'draft', label: 'Draft' },
            { value: 'completed', label: 'Completed' },
            { value: 'in_progress', label: 'In Progress' }
          ]"
        />
      </section>
    </form>
  </GlobalModal>
</template>

<style lang="scss">
.tiptap {
  :first-child {
    margin-top: 0;
  }

  h1,
  h2,
  h3,
  h4,
  h5,
  h6 {
    line-height: 0.5;
    margin-top: 2.5rem;
  }

  h1,
  h2 {
    margin-bottom: 1.5rem;
  }

  h1 {
    font-size: 1.4rem;
  }

  h2 {
    font-size: 1.2rem;
  }

  h3 {
    font-size: 1.1rem;
  }

  h4,
  h5,
  h6 {
    font-size: 1rem;
  }

  ul,
  ol {
    padding: 0 1rem;
    margin: 1.25rem 1rem 1.25rem 0.4rem;

    li p {
      margin-top: 0.25em;
      margin-bottom: 0.25em;
    }
  }

  .is-empty::before {
    content: attr(data-placeholder);
    color: #6b7280;
    font-style: italic;
    pointer-events: none;
    position: absolute;
  }

  .dark .is-empty::before {
    color: #9ca3af;
  }
}
</style>
