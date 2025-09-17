<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount, watch, nextTick } from 'vue'
import { EditorContent, useEditor } from '@tiptap/vue-3'
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
import Link from '@tiptap/extension-link'
import Image from '@tiptap/extension-image'
import Placeholder from '@tiptap/extension-placeholder'
import {
  UnderlineIcon,
  ItalicIcon,
  BoldIcon,
  ListIcon,
  ListOrderedIcon,
  MinusIcon,
  Heading1Icon,
  Heading2Icon,
  Heading3Icon,
  Link2Icon,
  ImageIcon
} from 'lucide-vue-next'

interface Props {
  modelValue?: string
  placeholder?: string
  disabled?: boolean
  showToolbar?: boolean
  minHeight?: string
  maxHeight?: string
  error?: string
  readonly?: boolean
}

interface Emits {
  (event: 'update:modelValue', value: string): void
  (event: 'change', value: string): void
  (event: 'focus'): void
  (event: 'blur'): void
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: '',
  placeholder: 'Start typing...',
  disabled: false,
  showToolbar: true,
  minHeight: '150px',
  maxHeight: '400px',
  error: '',
  readonly: false
})

const emit = defineEmits<Emits>()

// Editor instance
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
      placeholder: props.placeholder,
      emptyEditorClass: 'is-empty'
    }),
    Link.configure({
      openOnClick: false,
      HTMLAttributes: {
        class: 'text-blue-600 dark:text-blue-400 underline hover:text-blue-800 dark:hover:text-blue-300'
      }
    }),
    HorizontalRule.configure({
      HTMLAttributes: {
        class: 'my-4 border-gray-300 dark:border-gray-600'
      }
    }),
    Bold,
    Heading.configure({
      levels: [1, 2, 3],
      HTMLAttributes: {
        class: 'font-bold text-gray-900 dark:text-gray-100'
      }
    }),
    Italic,
    Image.configure({
      HTMLAttributes: {
        class: 'max-w-full h-auto rounded-lg'
      }
    }),
    Underline
  ],
  content: props.modelValue,
  editable: !props.disabled && !props.readonly,
  onUpdate: ({ editor }) => {
    const newContent = editor.getHTML()
    if (newContent !== props.modelValue) {
      emit('update:modelValue', newContent)
      emit('change', newContent)
    }
  },
  onFocus: () => {
    emit('focus')
  },
  onBlur: () => {
    emit('blur')
  }
})

// Watch for external value changes
watch(() => props.modelValue, (newValue) => {
  if (editor.value && editor.value.getHTML() !== newValue) {
    nextTick(() => {
      editor.value?.commands.setContent(newValue || '', false)
    })
  }
}, { immediate: false })

// Watch for disabled state changes
watch(() => props.disabled, (disabled) => {
  if (editor.value) {
    editor.value.setEditable(!disabled && !props.readonly)
  }
})

// Watch for readonly state changes
watch(() => props.readonly, (readonly) => {
  if (editor.value) {
    editor.value.setEditable(!props.disabled && !readonly)
  }
})

// Editor toolbar configuration
const toolbarGroups = [
  // Text formatting
  {
    name: 'formatting',
    tools: [
      {
        icon: BoldIcon,
        title: 'Bold (Ctrl+B)',
        action: 'toggleBold',
        isActive: () => editor.value?.isActive('bold') || false
      },
      {
        icon: ItalicIcon,
        title: 'Italic (Ctrl+I)',
        action: 'toggleItalic',
        isActive: () => editor.value?.isActive('italic') || false
      },
      {
        icon: UnderlineIcon,
        title: 'Underline (Ctrl+U)',
        action: 'toggleUnderline',
        isActive: () => editor.value?.isActive('underline') || false
      }
    ]
  },
  // Headings
  {
    name: 'headings',
    tools: [
      {
        icon: Heading1Icon,
        title: 'Heading 1',
        action: () => editor.value?.chain().focus().toggleHeading({ level: 1 }).run(),
        isActive: () => editor.value?.isActive('heading', { level: 1 }) || false
      },
      {
        icon: Heading2Icon,
        title: 'Heading 2',
        action: () => editor.value?.chain().focus().toggleHeading({ level: 2 }).run(),
        isActive: () => editor.value?.isActive('heading', { level: 2 }) || false
      },
      {
        icon: Heading3Icon,
        title: 'Heading 3',
        action: () => editor.value?.chain().focus().toggleHeading({ level: 3 }).run(),
        isActive: () => editor.value?.isActive('heading', { level: 3 }) || false
      }
    ]
  },
  // Lists
  {
    name: 'lists',
    tools: [
      {
        icon: ListIcon,
        title: 'Bullet List',
        action: 'toggleBulletList',
        isActive: () => editor.value?.isActive('bulletList') || false
      },
      {
        icon: ListOrderedIcon,
        title: 'Ordered List',
        action: 'toggleOrderedList',
        isActive: () => editor.value?.isActive('orderedList') || false
      }
    ]
  },
  // Other tools
  {
    name: 'other',
    tools: [
      {
        icon: MinusIcon,
        title: 'Horizontal Rule',
        action: 'setHorizontalRule',
        isActive: () => false
      }
    ]
  }
]

// Execute editor command
const executeCommand = (action: string | Function) => {
  if (!editor.value) return

  if (typeof action === 'function') {
    action()
  } else {
    editor.value.chain().focus()[action]().run()
  }
}

// Cleanup
onBeforeUnmount(() => {
  editor.value?.destroy()
})
</script>

<template>
  <div class="rich-text-editor">
    <!-- Toolbar -->
    <div
      v-if="showToolbar && !readonly"
      class="flex items-center space-x-1 p-2 border-b bg-gray-50 dark:bg-gray-800 rounded-t-lg border border-gray-300 dark:border-gray-600"
    >
      <template v-for="(group, groupIndex) in toolbarGroups" :key="group.name">
        <div class="flex items-center space-x-1">
          <button
            v-for="(tool, toolIndex) in group.tools"
            :key="toolIndex"
            type="button"
            :title="tool.title"
            :class="[
              'p-1.5 rounded text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors',
              {
                'bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-gray-100': tool.isActive(),
                'opacity-50 cursor-not-allowed': disabled || readonly
              }
            ]"
            :disabled="disabled || readonly"
            @click="executeCommand(tool.action)"
          >
            <component :is="tool.icon" class="w-4 h-4" />
          </button>
        </div>

        <!-- Separator -->
        <div
          v-if="groupIndex < toolbarGroups.length - 1"
          class="h-4 w-px bg-gray-300 dark:bg-gray-600"
        ></div>
      </template>
    </div>

    <!-- Editor Content -->
    <div
      :class="[
        'border rounded-lg',
        showToolbar && !readonly ? 'rounded-t-none border-t-0' : '',
        error ? 'border-red-500' : 'border-gray-300 dark:border-gray-600',
        readonly ? 'bg-gray-50 dark:bg-gray-800' : 'bg-white dark:bg-gray-900'
      ]"
    >
      <div
        :class="[
          'p-3 overflow-y-auto',
          readonly ? 'cursor-default' : ''
        ]"
        :style="{
          minHeight: minHeight,
          maxHeight: maxHeight
        }"
      >
        <EditorContent
          :editor="editor"
          :class="[
            'prose prose-sm dark:prose-invert max-w-none focus:outline-none',
            readonly ? 'pointer-events-none' : ''
          ]"
        />
      </div>
    </div>

    <!-- Error Message -->
    <div v-if="error" class="mt-2 text-sm text-red-600">
      {{ error }}
    </div>

    <!-- Character/Word Count (Optional) -->
    <div
      v-if="editor && !readonly"
      class="mt-2 text-xs text-gray-500 dark:text-gray-400 text-right"
    >
      {{ editor.storage.characterCount?.characters() || 0 }} characters
    </div>
  </div>
</template>

<style lang="scss">
.rich-text-editor {
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

    ul, ol {
      padding: 0 1rem;
    }

    h1, h2, h3, h4, h5, h6 {
      line-height: 1.1;
      margin-top: 1.5rem;
      margin-bottom: 0.5rem;

      &:first-child {
        margin-top: 0;
      }
    }

    h1 {
      font-size: 1.75rem;
      font-weight: 700;
    }

    h2 {
      font-size: 1.5rem;
      font-weight: 600;
    }

    h3 {
      font-size: 1.25rem;
      font-weight: 600;
    }

    code {
      background-color: rgba(97, 97, 97, 0.1);
      color: #616161;
      font-size: 0.9rem;
      padding: 0.25rem 0.5rem;
      border-radius: 0.25rem;
    }

    pre {
      background: #0d0d0d;
      color: #fff;
      font-family: 'JetBrainsMono', monospace;
      padding: 0.75rem 1rem;
      border-radius: 0.5rem;
      overflow-x: auto;

      code {
        color: inherit;
        padding: 0;
        background: none;
        font-size: 0.8rem;
      }
    }

    mark {
      background-color: #faf594;
      padding: 0.125rem 0.25rem;
      border-radius: 0.25rem;
    }

    img {
      max-width: 100%;
      height: auto;
      border-radius: 0.5rem;
      margin: 1rem 0;
    }

    hr {
      border: none;
      border-top: 2px solid rgba(13, 13, 13, 0.1);
      margin: 2rem 0;
    }

    blockquote {
      padding-left: 1rem;
      border-left: 2px solid rgba(13, 13, 13, 0.1);
      margin-left: 0;
      margin-right: 0;
      font-style: italic;
    }

    ol, ul {
      li {
        p {
          margin: 0 !important;
        }
      }
    }

    a {
      color: theme('colors.blue.600');
      text-decoration: underline;

      &:hover {
        color: theme('colors.blue.800');
      }
    }

    .dark & {
      a {
        color: theme('colors.blue.400');

        &:hover {
          color: theme('colors.blue.300');
        }
      }
    }
  }
}
</style>
