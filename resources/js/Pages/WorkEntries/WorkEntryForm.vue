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
import {computed, ref} from 'vue'
import InputError from "@/Components/InputError.vue";

const form = useForm({
  work_date: new Date(),
  description: '',
  hours_worked: 0,
  tags: [],
  status: 'draft',
});

const tagsDropdownOpen = ref(false)
const tagsSearchTerm = ref('')

const availableTags = [
  {id: 1, name: 'Urgent'},
  {id: 2, name: 'Bug Fix'},
  {id: 3, name: 'Meeting'},
  {id: 4, name: 'Documentation'},
];

const filteredTags = computed(() =>
  (availableTags || []).filter(tag => !form.tags.includes(tag.name))
);


const saveAsDraft = () => {
  form.post('/work-logs', {
    onError: (err) => {
      console.log(err)
    },

    onSuccess: () => {
      form.reset()
    },
  });
};

const submitForm = () => {
  form.put('/work-logs', {
    onError: (err) => {
      console.log(err)
    },
    onSuccess: () => {
      form.reset()
    },
  });
};
</script>

<template>
  <GlobalModal
    panel-classes="dark:bg-gray-800 rounded-xl"
    :close-explicitly="true">

    <h1 class="mb-4 text-xl font-semibold">Log Your Task</h1>

    <form @submit.prevent="submitForm" class="space-y-6">
      <!-- Work Date -->
      <div>
        <FormField
          type="date"
          :is-inline="true"
          label="Work Date"
          v-model="form.work_date"
          :error="form.errors.work_date"
          :class="{ 'border-red-500': form.errors.work_date }"
        />
      </div>

      <!-- Description -->
      <div>
        <FormField
          type="textarea"
          label="Description"
          v-model="form.description"
          :error="form.errors.description"
          placeholder="Describe your work, what have you worked on?"
          :class="{ 'border-red-500': form.errors.description }"
        />
      </div>

      <!-- Tags -->
      <div>
        <Label for="tags">Tags</Label>
        <TagsInput v-model="form.tags" class="w-full gap-0 px-0">
          <!-- Render existing tags -->
          <div class="flex flex-wrap items-center gap-2 px-3">
            <TagsInputItem v-for="tag in form.tags" :key="tag" :value="tag">
              <TagsInputItemText/>
              <TagsInputItemDelete/>
            </TagsInputItem>
          </div>

          <!-- Input for adding new tags -->
          <ComboboxRoot
            v-model="form.tags"
            v-model:open="tagsDropdownOpen"
            v-model:search-term="tagsSearchTerm"
            class="w-full">
            <ComboboxAnchor as-child>
              <ComboboxInput placeholder="Select or add tags..." as-child>
                <TagsInputInput
                  class="w-full px-3"
                  :class="form.tags.length > 0 ? 'mt-2' : ''"
                  @keydown.enter.prevent
                />
              </ComboboxInput>
            </ComboboxAnchor>
            <!-- Dropdown for available tags -->
            <ComboboxPortal>
              <ComboboxContent>
                <CommandList
                  position="popper"
                  class="w-[--radix-popper-anchor-width] rounded-md mt-2 border bg-popover text-popover-foreground shadow-md outline-none data-[state=open]:animate-in data-[state=closed]:animate-out"
                >
                  <CommandEmpty>No tags found.</CommandEmpty>
                  <CommandGroup heading="Available Tags">
                    <CommandItem
                      v-for="tag in filteredTags"
                      :key="tag.id"
                      :value="tag.name"
                      @select.prevent="(ev) => {
                        if (typeof ev.detail.value === 'string') {
                          tagsSearchTerm = ''
                          form.tags.push(ev.detail.value)
                        }
                      }">
                      {{ tag.name }}
                    </CommandItem>
                  </CommandGroup>
                </CommandList>
              </ComboboxContent>
            </ComboboxPortal>
          </ComboboxRoot>
        </TagsInput>

        <!-- Validation errors -->
        <InputError :message="form.errors.tags" />
      </div>

      <section class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <!-- Hours Worked -->
        <div>
          <FormField
            type="number"
            label="Hours Worked"
            v-model="form.hours_worked"
            :error="form.errors.hours_worked"
            :class="{ 'border-red-500': form.errors.hours_worked }"
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

      <!-- Actions -->
      <div class="flex justify-end space-x-4">
        <Button
          size="lg"
          type="button"
          variant="outline"
          @click="saveAsDraft">
          Save Draft
        </Button>

        <Button
          size="lg"
          type="submit">
          Mark Completed
        </Button>
      </div>
    </form>
  </GlobalModal>
</template>
