<script setup lang="ts">
import {Modal} from '@inertiaui/modal-vue'
import {ref} from "vue";

const modalRef = ref()

defineProps<{
  manualClose?: boolean
  placement?: 'center' | 'top' | 'bottom'
  hasCloseButton?: boolean
  maxWidth?: 'sm' | 'md' | 'lg' | 'xl' | '2xl'
  padding?: string
}>()

function onClose() {
  modalRef.value.close()
}

defineExpose({
  onClose,
});
</script>

<template>
  <Modal
    ref="modalRef"
    :max-width="maxWidth"
    :position="placement"
    :paddingClasses="padding"
    :close-explicitly="manualClose"
    :close-button="hasCloseButton"
    panel-classes="border-none dark:bg-gray-800 rounded-lg bg-gray-100 max-h-[70svh] overflow-y-auto scrollbar-none scroll-smooth">
    <slot/>
  </Modal>
</template>
