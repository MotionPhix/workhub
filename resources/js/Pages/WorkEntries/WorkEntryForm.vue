<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { CommandEmpty, CommandGroup, CommandItem, CommandList } from '@/Components/ui/command'
import { TagsInput, TagsInputInput, TagsInputItem, TagsInputItemDelete, TagsInputItemText } from '@/Components/ui/tags-input'
import { ComboboxAnchor, ComboboxContent, ComboboxInput, ComboboxPortal, ComboboxRoot } from 'radix-vue'
import { computed, ref } from 'vue'

const form = useForm({
  work_date: '',
  description: '',
  hours_worked: '',
  tags: [],
  status: 'draft',
});

const tagsDropdownOpen = ref(false)
const tagsSearchTerm = ref('')

const errors = ref({});
const availableTags = [
  { id: 1, name: 'Urgent' },
  { id: 2, name: 'Bug Fix' },
  { id: 3, name: 'Meeting' },
  { id: 4, name: 'Documentation' },
];

const filteredTags = computed(() =>
  (availableTags || []).filter(tag => !form.tags.includes(tag.name))
);


const saveAsDraft = () => {
  form.post('/work-entries', {
    onError: (err) => {
      errors.value = err;
    },
    onSuccess: () => {
      errors.value = {};
    },
  });
};

const submitForm = () => {
  form.put('/work-entries', {
    onError: (err) => {
      errors.value = err;
    },
    onSuccess: () => {
      errors.value = {};
    },
  });
};
</script>

<template>
  <div class="container p-6 mx-auto">
    <h1 class="mb-4 text-xl font-semibold">Log Your Task</h1>
    <form @submit.prevent="submitForm" class="space-y-6">
      <!-- Work Date -->
      <div>
        <label for="work_date" class="block text-sm font-medium text-gray-700">Work Date</label>
        <input
          type="date"
          id="work_date"
          v-model="form.work_date"
          class="w-full input input-bordered"
          :class="{ 'border-red-500': errors.work_date }"
        />
        <p v-if="errors.work_date" class="text-sm text-red-500">{{ errors.work_date }}</p>
      </div>

      <!-- Description -->
      <div>
        <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
        <textarea
          id="description"
          v-model="form.description"
          rows="4"
          class="w-full textarea textarea-bordered"
          :class="{ 'border-red-500': errors.description }"
        ></textarea>
        <p v-if="errors.description" class="text-sm text-red-500">{{ errors.description }}</p>
      </div>

      <!-- Hours Worked -->
      <div>
        <label for="hours_worked" class="block text-sm font-medium text-gray-700">Hours Worked</label>
        <input
          type="number"
          id="hours_worked"
          v-model="form.hours_worked"
          class="w-full input input-bordered"
          :class="{ 'border-red-500': errors.hours_worked }"
        />
        <p v-if="errors.hours_worked" class="text-sm text-red-500">{{ errors.hours_worked }}</p>
      </div>

      <!-- Tags -->
<div>
  <label for="tags" class="block text-sm font-medium text-gray-700">Tags</label>
  <TagsInput v-model="form.tags" class="w-full gap-0 px-0">
    <!-- Render existing tags -->
    <div class="flex flex-wrap items-center gap-2 px-3">
      <TagsInputItem v-for="tag in form.tags" :key="tag" :value="tag">
        <TagsInputItemText />
        <TagsInputItemDelete />
      </TagsInputItem>
    </div>
    <!-- Input for adding new tags -->
    <ComboboxRoot
      v-model="form.tags"
      v-model:open="tagsDropdownOpen"
      v-model:search-term="tagsSearchTerm"
      class="w-full"
    >
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
                }"
              >
                {{ tag.name }}
              </CommandItem>
            </CommandGroup>
          </CommandList>
        </ComboboxContent>
      </ComboboxPortal>
    </ComboboxRoot>
  </TagsInput>
  <!-- Validation errors -->
  <p v-if="errors.tags" class="text-sm text-red-500">{{ errors.tags }}</p>
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

      <!-- Actions -->
      <div class="flex justify-end space-x-4">
        <Button variant="outline" @click="saveAsDraft">
          Save as Draft
        </Button>
        <Button type="submit" variant="primary">
          Mark as Completed
        </Button>
      </div>
    </form>
  </div>
</template>
