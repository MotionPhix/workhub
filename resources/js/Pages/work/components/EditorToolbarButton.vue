<script setup lang="ts">
import { Editor } from '@tiptap/vue-3'
import {type Component, computed} from 'vue'

interface EditorTool {
  icon: Component
  title: string
  action: string
  isActive?: boolean
}

interface Props {
  editor: Editor | null
  tool: EditorTool
}

const props = defineProps<Props>()

const onClick = () => {
  if (!props.editor) return

  switch (props.tool.action) {
    case 'toggleBold':
      props.editor.chain().focus().toggleBold().run()
      break
    case 'toggleItalic':
      props.editor.chain().focus().toggleItalic().run()
      break
    case 'toggleUnderline':
      props.editor.chain().focus().toggleUnderline().run()
      break
    case 'toggleBulletList':
      props.editor.chain().focus().toggleBulletList().run()
      break
    case 'toggleOrderedList':
      props.editor.chain().focus().toggleOrderedList().run()
      break
    case 'horizontalRule':
      props.editor.chain().focus().setHorizontalRule().run()
      break
    // Add more actions as needed
  }
}

const isActive = computed(() => {
  if (!props.editor) return false

  switch (props.tool.action) {
    case 'toggleBold':
      return props.editor.isActive('bold')
    case 'toggleItalic':
      return props.editor.isActive('italic')
    case 'toggleUnderline':
      return props.editor.isActive('underline')
    case 'toggleBulletList':
      return props.editor.isActive('bulletList')
    case 'toggleOrderedList':
      return props.editor.isActive('orderedList')
    default:
      return false
  }
})
</script>

<template>
  <button
    type="button"
    @click="onClick"
    :title="tool.title"
    class="p-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
    :class="{ 'bg-gray-100 dark:bg-gray-700': isActive }"
  >
    <component :is="tool.icon" class="w-5 h-5" />
  </button>
</template>


