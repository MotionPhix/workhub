<script setup lang="ts">
import {type Component, computed, ref} from 'vue'
import {v4 as uuidv4} from 'uuid'
import InputError from "@/Components/InputError.vue";
import {Textarea} from "@/Components/ui/textarea";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectLabel,
  SelectValue,
  SelectGroup, SelectSeparator
} from "@/Components/ui/select";
import {RadioGroup, RadioGroupItem} from "@/Components/ui/radio-group";
import { Popover, PopoverContent, PopoverTrigger } from '@/Components/ui/popover'
import {Checkbox} from "@/Components/ui/checkbox";
import {
  NumberField,
  NumberFieldContent,
  NumberFieldDecrement,
  NumberFieldIncrement,
  NumberFieldInput,
} from '@/Components/ui/number-field'
import {usePage} from "@inertiajs/vue3";
import { cn } from '@/lib/utils'
import { format } from 'date-fns'
import {Calendar as CalendarIcon, LockOpenIcon} from 'lucide-vue-next'

type Option = {
  value: string | number;
  label: string;
};

type OptionGroup = {
  label: string;
  items: Array<Option>;
};

const props = withDefaults(defineProps<{
  label?: string
  type?: string
  formatStyle?: string
  placeholder?: string
  required?: boolean
  disabled?: boolean
  error?: string | string[]
  hint?: string
  icon?: Component
  variant?: 'default' | 'outlined' | 'underlined'
  options?: Array<Option> | Array<OptionGroup>  // For select input
  prefix?: string | Component
  suffix?: string | Component
  hasGroups?: boolean
  containerClass?: string
  orientation?: string
  autocomplete?: string
  min?: number
  max?: number
  step?: number
  isInline?: boolean
}>(), {
  type: 'text',
  required: false,
  disabled: false,
  variant: 'default',
  orientation: 'vertical',
  hasGroups: false,
  min: 0,
  max: 100
})

const model = defineModel()
const page = usePage()
const id = uuidv4()
const suffixIcon = ref(props.suffix)
const tempType = ref<Component | string>(props.type)

const currency = page.props?.currency ?? 'MWK'

// Computed classes
const inputClasses = computed(() => {
  const baseClasses = '!min-h-10 block w-full rounded-md shadow-sm focus:ring-2 focus:ring-opacity-50'

  const variantClasses = {
    default: 'border-gray-300 dark:border-gray-600 focus:border-primary-500 focus:ring-primary-500',
    outlined: 'border-2 border-gray-300 dark:border-gray-600 focus:border-primary-500',
    underlined: 'border-0 border-b-2 border-gray-300 dark:border-gray-600 focus:border-primary-500'
  }

  const stateClasses = {
    error: 'border-red-500 focus:border-red-500 focus:ring-red-500',
    disabled: 'bg-gray-100 cursor-not-allowed opacity-50'
  }

  return [
    baseClasses,
    variantClasses[props.variant],
    props.error && stateClasses.error,
    props.disabled && stateClasses.disabled
  ].filter(Boolean).join(' ')
})

const containerClasses = computed(() => props.containerClass || 'mb-4')

function onToggle() {
  tempType.value = tempType.value === 'password' ? 'text' : 'password';

  if (tempType.value === 'text') {
    suffixIcon.value = LockOpenIcon
  } else {
    suffixIcon.value = props.suffix
  }
}
</script>

<template>
  <div :class="containerClasses">
    <slot name="label">
      <Label v-if="label" :for="id">
        {{ label }} <span v-if="required" class="text-red-500 ml-1">*</span>
      </Label>
    </slot>

    <div class="relative" :class="{ 'mt-1': label }">
      <slot name="prefix">
        <span v-if="prefix" class="absolute inset-y-0 left-3 flex items-center text-gray-500">
          <component :is="prefix" v-if="typeof prefix === 'object'"/>
          <span v-else>{{ prefix }}</span>
        </span>
      </slot>

      <slot>
        <template v-if="type === 'select'">
          <Select
            :id="id"
            v-model="model"
            :required="required"
            :disabled="disabled">
            <SelectTrigger class="make-large dark:bg-primary-foreground">
              <SelectValue :placeholder="placeholder"/>
            </SelectTrigger>

            <SelectContent>
              <template v-if="hasGroups">
                <SelectGroup
                  v-for="(group, idx) in options as OptionGroup[]"
                  :key="group.label">

                  <SelectSeparator v-if="idx !== 0" class="dark:bg-amber-200 bg-gray-300"/>

                  <SelectLabel>{{ group.label }}</SelectLabel>

                  <SelectItem
                    v-for="item in group.items"
                    :key="item.value"
                    :value="item.value">
                    {{ item.label }}
                  </SelectItem>

                </SelectGroup>
              </template>

              <template v-else>
                <SelectItem
                  v-for="item in options as Option[]"
                  :key="item.value"
                  :value="item.value">
                  {{ item.label }}
                </SelectItem>
              </template>
            </SelectContent>
          </Select>
        </template>

        <template v-else-if="type === 'textarea'">
          <Textarea
            :id="id"
            class="overflow-hidden min-h-9 h-16 dark:bg-primary-foreground"
            @input="(e) => {
              const target = e.target as HTMLTextAreaElement;
              target.style.height = '0px';
              target.style.height = target.scrollHeight + 'px';
            }"
            v-model="model"
            :placeholder="placeholder"
            :required="required"
            :disabled="disabled"
            :class="inputClasses"
          />
        </template>

        <template v-else-if="type === 'radio'">
          <RadioGroup
            v-model="model"
            :orientation="orientation"
            :class="{ 'flex items-center gap-4': orientation === 'horizontal' }">
            <div
              class="flex items-center space-x-2"
              v-for="option in options" :key="option.label">
              <RadioGroupItem :id="option.label" :value="option.value"/>
              <Label :for="option.label">{{ option.label }}</Label>
            </div>
          </RadioGroup>
        </template>

        <template v-else-if="type === 'checkbox'">
          <div class="items-center flex gap-x-2">
            <Checkbox
              :checked="model"
              class="h-5 w-5"
              @update:checked="model = !model"
              :id="id"
            />

            <label
              :for="id"
              class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
              {{ placeholder }}
            </label>
          </div>
        </template>

        <template v-else-if="type === 'number'">
          <NumberField
            v-if="formatStyle"
            v-model="model" :step="step"
            :id="id" :max="max" :min="min"
            :format-options="{
              style: formatStyle,
              currency: currency,
              currencySign: 'accounting',
            }">
            <NumberFieldContent>
              <NumberFieldDecrement/>
              <NumberFieldInput class="dark:bg-primary-foreground make-large"/>
              <NumberFieldIncrement/>
            </NumberFieldContent>
          </NumberField>

          <NumberField
            v-else
            v-model="model" :step="step"
            :id="id" :max="max" :min="min">
            <NumberFieldContent>
              <NumberFieldDecrement/>
              <NumberFieldInput class="dark:bg-primary-foreground make-large"/>
              <NumberFieldIncrement/>
            </NumberFieldContent>
          </NumberField>
        </template>

        <template v-else-if="type === 'date'">
          <template v-if="isInline">
            <!-- Inline Calendar -->
            <Calendar />
          </template>

          <template v-else>
            <!-- Popover Calendar -->
            <Popover>
              <PopoverTrigger as-child>
                <Button
                  :variant="'outline'"
                  :class="cn(
                    'justify-start text-left font-normal',
                    !model && 'text-muted-foreground',
                  )">
                  <CalendarIcon class="mr-2 h-4 w-4" />
                  <span>{{ model ? format(model as Date, "PPP") : "Pick a date" }}</span>
                </Button>
              </PopoverTrigger>

              <PopoverContent class="w-auto p-0">
                <Calendar v-model="model" />
              </PopoverContent>
            </Popover>
          </template>
        </template>

        <template v-else>
          <Input
            :id="id"
            :type="tempType"
            v-model="model"
            :placeholder="placeholder"
            :required="required"
            :disabled="disabled"
            :autocomplete="autocomplete"
            class="dark:bg-primary-foreground"
            :min="min"
            :max="max"
            :step="step"
            :class="inputClasses"
            ref="input_ref"
          />
        </template>
      </slot>

      <slot name="suffix">
        <span v-if="suffix" class="absolute inset-y-0 right-3 flex items-center text-gray-500">
          <component
            :is="suffixIcon"
            v-if="typeof suffixIcon === 'function'"
            @click="onToggle()"
          />

          <span v-else>{{ suffix }}</span>
        </span>
      </slot>

      <div v-if="icon" class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
        <component :is="icon" class="h-5 w-5 text-gray-400"/>
      </div>

      <slot name="error">
        <InputError v-if="error" :message="Array.isArray(error) ? error.join(', ') : error" class="mt-2"/>
      </slot>
    </div>

    <p v-if="hint" class="mt-1 text-sm text-gray-500 dark:text-gray-400">
      {{ hint }}
    </p>
  </div>
</template>
