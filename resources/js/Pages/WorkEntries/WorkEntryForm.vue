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
import {subDays, format, formatDate} from "date-fns";

const props = defineProps<{
  workLog: {
    id?: number
    uuid?: string
    work_date?: Date | string
    work_title?: string
    description?: string
    hours_worked?: number
    status?: 'draft' | 'completed' | 'in_progress'
  },
  tags?: Array<{
    id: number
    name: string
  }>
}>()

const form = useForm({
  work_date: props.workLog.work_date || new Date().toISOString().split('T')[0],
  work_title: props.workLog.work_title || '',
  description: props.workLog.description || '',
  hours_worked: props.workLog.hours_worked || 0,
  tags: props.tags || [],
  status: props.workLog.status || 'draft',
});

// Initialize Tiptap editor
const editor = useEditor({
  extensions: [
    Document,
    Paragraph,
    Text,
    BulletList.configure({
      HTMLAttributes: {
        class: 'list-disc list-inside ml-6 space-y-2 text-gray-800 dark:text-gray-200'
      },
    }),
    OrderedList.configure({
      HTMLAttributes: {
        class: 'list-decimal list-inside ml-6 space-y-2 text-gray-800 dark:text-gray-200',
      },
    }),
    ListItem.configure({
      HTMLAttributes: {
        class: 'text-gray-700 dark:text-gray-300',
      },
    }),
    Placeholder.configure({
      placeholder: 'Type your content here...', // Placeholder text
      emptyEditorClass: 'is-empty', // Optional: Add a class when the editor is empty
    }),
    Link,
    HorizontalRule,
    Bold,
    Heading,
    Italic,
    Image,
    Underline,
  ],
  content: form.description, // Bind to the form description
  onUpdate: ({editor}) => {
    form.description = editor.getHTML(); // Update form when content changes
  },
});

const now = new Date();
const tagInputValue = ref('');
const workLogRef = ref()

const filteredTags = computed(() => {
  return props.tags.filter(tag => !form.tags.includes(tag)); // Filter out already selected tags
});

const addTag = (tag) => {
  const normalizedTag = tag.trim().toLowerCase();

  // Check for case-insensitive uniqueness
  if (form.tags.some(tag => tag.toLowerCase() === normalizedTag)) {
    toast.error('Duplicate Entry', {
      description: `${tag.toUpperCase()} already exists!`
    })

    return;
  }

  if (!form.tags.includes(tag)) {
    form.tags.push(tag);
  }

  tagInputValue.value = ''; // Clear input after adding
};

const onClose = () => {
  workLogRef.value.onClose()
}

const onSubmitForm = () => {
  if (props.workLog.uuid) {
    form
      .transform(data => {
        return {
          ...data,
          work_date: format(data.work_date, 'yyyy-MM-dd'),
        }
      })
      .put(route('work-entries.update', props.workLog.uuid), {
        onError: (err) => {
          console.log(err)
        },
        onSuccess: () => {
          workLogRef.value.onClose()
        },
      })
  } else {
    form
      .transform(data => {
        return {
          ...data,
          work_date: format(data.work_date, 'yyyy-MM-dd'),
        }
      })
      .post(route('work-entries.store'), {
        onError: (err) => {
          console.log(err)
        },
        onSuccess: () => {
          form.reset()
          workLogRef.value.onClose()
        },
      });
  }
};

// Clean up editor instance when the component unmounts
onBeforeUnmount(() => {
  editor.value.destroy();
});
</script>

<template>
  <GlobalModal
    ref="workLogRef"
    padding="px-4 pb-4 sm:px-5 sm:pb-5"
    :manual-close="true"
    :has-close-button="false">

    <ModalHeader :heading="workLog.uuid ? `Edit ${workLog.work_title} work log` : 'Log your work'">
      <!-- Actions -->
      <template #action>
        <Button
          @click="onClose"
          variant="outline"
          type="button">
          Cancel
        </Button>

        <Button
          @click="onSubmitForm"
          type="button">
          Save
        </Button>
      </template>
    </ModalHeader>

    <form class="space-y-6">
      <div>
        <FormField
          type="date"
          :is-inline="true"
          label="Work Date"
          v-model="form.work_date"
          :min-date="format(subDays(now, 2), 'yyyy-MM-dd')"
          :error="form.errors.work_date"
          :max-date="now"
        />
      </div>

      <div>
        <FormField
          label="Work Title"
          v-model="form.work_title"
          :error="form.errors.work_title"
          placeholder="Write what you are working on in fewer words"
        />
      </div>

      <!-- Description -->
      <div>
        <Label for="description">Description</Label>

        <!-- Rich Text Editor -->
        <!-- Toolbar -->
        <div class="my-2 flex space-x-2">
          <Button
            size="icon"
            type="button"
            @click="editor.chain().focus().toggleBold().run()"
            :variant="editor.isActive('bold') ? 'default' : 'secondary'">
            <BoldIcon/>
          </Button>

          <Button
            size="icon"
            type="button"
            :variant="editor.isActive('italic') ? 'default' : 'secondary'"
            @click="editor.chain().focus().toggleItalic().run()">
            <ItalicIcon/>
          </Button>

          <Button
            size="icon"
            type="button"
            :variant="editor.isActive('underline') ? 'default' : 'secondary'"
            @click="editor.chain().focus().toggleUnderline?.().run()">
            <UnderlineIcon/>
          </Button>

          <!-- Unordered List -->
          <Button
            size="icon"
            type="button"
            :variant="editor.isActive('bulletList') ? 'default' : 'secondary'"
            @click="editor.chain().focus().toggleBulletList().run()">
            <ListIcon/>
          </Button>

          <!-- Ordered List -->
          <Button
            size="icon"
            type="button"
            :variant="editor.isActive('orderedList') ? 'default' : 'secondary'"
            @click="editor.chain().focus().toggleOrderedList().run()">
            <ListOrderedIcon/>
          </Button>

          <Button
            size="icon"
            type="button"
            :variant="editor.isActive('orderedList') ? 'default' : 'secondary'"
            @click="editor.chain().focus().setHorizontalRule().run()">
            <MinusIcon/>
          </Button>
        </div>

        <div class="prose prose-lg dark:prose-invert max-w-none">
          <EditorContent
            :editor="editor"
            class="prose prose-lg border dark:prose-invert max-w-none p-3 rounded-lg dark:bg-gray-800 dark:text-white my-1"
          />
        </div>

        <InputError :message="form.errors.description"/>
      </div>

      <!-- Tags -->
      <div>
        <Label for="tags">Tags</Label>
        <TagsInput v-model="form.tags" class="w-full gap-0 p-0 !border-none mt-1">
          <!-- Existing Tags -->
          <div
            class="flex flex-wrap items-center gap-2 px-2"
            :class="{ 'py-1': form.tags.length }">
            <TagsInputItem v-for="tag in form.tags" :key="tag" :value="tag">
              <TagsInputItemText/>
              <TagsInputItemDelete @click="form.tags = form.tags.filter(t => t !== tag)"/>
            </TagsInputItem>
          </div>

          <!-- Input for Adding Tags -->
          <ComboboxRoot
            v-model="tagInputValue"
            v-model:search-term="tagInputValue"
            class="w-full">
            <ComboboxAnchor as-child>
              <ComboboxInput
                placeholder="Add or select tags..."
                as-child>
                <TagsInputInput
                  class="w-full px-3 rounded-md make-large !border-none focus:ring-0"
                  @keydown.enter.prevent="addTag(tagInputValue)"/>
              </ComboboxInput>
            </ComboboxAnchor>

            <!-- Dropdown for Available Tags -->
            <ComboboxPortal>
              <ComboboxContent>
                <CommandList
                  position="popper"
                  class="w-[--radix-popper-anchor-width] rounded-md mt-2 bg-popover text-popover-foreground shadow-md">
                  <CommandEmpty>No tags found.</CommandEmpty>
                  <CommandGroup heading="Available Tags">
                    <CommandItem
                      v-for="tag in filteredTags"
                      :key="tag"
                      :value="tag"
                      @select.prevent="addTag(tag)">
                      {{ tag }}
                    </CommandItem>
                  </CommandGroup>
                </CommandList>
              </ComboboxContent>
            </ComboboxPortal>
          </ComboboxRoot>
        </TagsInput>

        <InputError :message="form.errors.tags"/>
      </div>

      <section class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <!-- Hours Worked -->
        <div>
          <FormField
            type="number"
            label="Hours Worked"
            v-model="form.hours_worked"
            :error="form.errors.hours_worked"
            :step="0.01"
          />
        </div>

        <!-- Status -->
        <div>
          <FormField
            label="Status"
            type="select"
            v-model="form.status"
            :options="[
              { value: 'draft', label: 'Draft' },
              { value: 'completed', label: 'Completed' },
              { value: 'in_progress', label: 'In Progress' },
            ]"
          />
        </div>
      </section>
    </form>
  </GlobalModal>
</template>

<style lang="scss">
/* Basic editor styles */
.tiptap {
  :first-child {
    margin-top: 0;
  }

  /* Heading styles */
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

  /* List styles */
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
    color: #6b7280; /* Gray-500 */
    font-style: italic;
    pointer-events: none;
    position: absolute;
  }

  /* Dark mode support */
  .dark .is-empty::before {
    color: #9ca3af; /* Gray-400 for dark mode */
  }

}
</style>
