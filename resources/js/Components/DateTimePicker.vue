<script setup lang="ts">
import {
  DateFormatter,
  getLocalTimeZone,
  CalendarDate,
  DateValue,
  parseDate,
  today,
  Time
} from '@internationalized/date'
import { CalendarIcon, Clock } from 'lucide-vue-next'
import { cn } from '@/lib/utils'
import { Button } from '@/components/ui/button'
import { Calendar } from '@/components/ui/calendar'
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { computed, ref } from 'vue'

interface Props {
  placeholder?: string
  disabled?: boolean
  min?: string
  max?: string
}

const props = defineProps<Props>()

const value = defineModel<string>({ required: true })

const isOpen = ref(false)

const df = new DateFormatter('en-MW', {
  dateStyle: 'medium',
})

// Parse datetime string into date and time components
const parsedDateTime = computed(() => {
  if (!value.value) return { date: null, time: '' }

  try {
    const dateObj = new Date(value.value)
    if (isNaN(dateObj.getTime())) return { date: null, time: '' }

    const date = new CalendarDate(
      dateObj.getFullYear(),
      dateObj.getMonth() + 1,
      dateObj.getDate()
    )

    const hours = String(dateObj.getHours()).padStart(2, '0')
    const minutes = String(dateObj.getMinutes()).padStart(2, '0')
    const time = `${hours}:${minutes}`

    return { date, time }
  } catch (error) {
    console.error('Error parsing datetime:', error)
    return { date: null, time: '' }
  }
})

// Selected date for calendar
const selectedDate = computed({
  get: () => parsedDateTime.value.date,
  set: (newDate: DateValue | null) => {
    if (!newDate) {
      value.value = ''
      return
    }

    const currentTime = parsedDateTime.value.time || '09:00'
    updateDateTime(newDate, currentTime)
  }
})

// Selected time
const selectedTime = ref('')

// Watch for changes in parsed time
const currentTime = computed({
  get: () => selectedTime.value || parsedDateTime.value.time || '09:00',
  set: (newTime: string) => {
    selectedTime.value = newTime
    const currentDate = parsedDateTime.value.date
    if (currentDate) {
      updateDateTime(currentDate, newTime)
    }
  }
})

// Update the datetime value
const updateDateTime = (date: DateValue, time: string) => {
  try {
    const [hours, minutes] = time.split(':').map(Number)
    if (isNaN(hours) || isNaN(minutes)) return

    const dateObj = date.toDate(getLocalTimeZone())
    dateObj.setHours(hours, minutes, 0, 0)

    value.value = dateObj.toISOString().slice(0, 16) // Format: YYYY-MM-DDTHH:mm
  } catch (error) {
    console.error('Error updating datetime:', error)
  }
}

// Display value
const displayValue = computed(() => {
  if (!value.value) return ''

  try {
    const date = new Date(value.value)
    if (isNaN(date.getTime())) return ''

    const dateStr = df.format(date)
    const timeStr = date.toLocaleTimeString('en-US', {
      hour: '2-digit',
      minute: '2-digit',
      hour12: true
    })

    return `${dateStr} at ${timeStr}`
  } catch {
    return ''
  }
})

// Default calendar value
const defaultCalendarValue = computed(() => {
  return selectedDate.value || today(getLocalTimeZone())
})

// Generate time options (every 15 minutes)
const timeOptions = computed(() => {
  const options = []
  for (let h = 0; h < 24; h++) {
    for (let m = 0; m < 60; m += 15) {
      const hours = String(h).padStart(2, '0')
      const minutes = String(m).padStart(2, '0')
      const time24 = `${hours}:${minutes}`

      // Convert to 12-hour format for display
      const hour12 = h === 0 ? 12 : h > 12 ? h - 12 : h
      const ampm = h < 12 ? 'AM' : 'PM'
      const display = `${hour12}:${minutes} ${ampm}`

      options.push({ value: time24, display })
    }
  }
  return options
})

// Quick time presets
const timePresets = [
  { label: '9:00 AM', value: '09:00' },
  { label: '12:00 PM', value: '12:00' },
  { label: '1:00 PM', value: '13:00' },
  { label: '5:00 PM', value: '17:00' },
  { label: 'Now', value: 'now' }
]

const selectTimePreset = (preset: string) => {
  if (preset === 'now') {
    const now = new Date()
    const hours = String(now.getHours()).padStart(2, '0')
    const minutes = String(now.getMinutes()).padStart(2, '0')
    currentTime.value = `${hours}:${minutes}`
  } else {
    currentTime.value = preset
  }
}

const closePopover = () => {
  isOpen.value = false
}
</script>

<template>
  <Popover v-model:open="isOpen">
    <PopoverTrigger as-child>
      <Button
        variant="outline"
        :disabled="disabled"
        :class="cn(
          'w-full justify-start text-left font-normal',
          !value && 'text-muted-foreground',
        )"
      >
        <CalendarIcon class="mr-2 h-4 w-4" />
        {{ displayValue || placeholder || "Pick date and time" }}
      </Button>
    </PopoverTrigger>

    <PopoverContent class="w-auto p-0" align="start">
      <div class="flex">
        <!-- Calendar Section -->
        <div class="border-r">
          <Calendar
            v-model="selectedDate"
            :default-value="defaultCalendarValue"
            :disabled="disabled"
            initial-focus
          />
        </div>

        <!-- Time Selection Section -->
        <div class="p-4 w-64">
          <div class="space-y-4">
            <div>
              <Label class="text-sm font-medium">Select Time</Label>
              <div class="mt-2">
                <select
                  v-model="currentTime"
                  class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                >
                  <option v-for="option in timeOptions" :key="option.value" :value="option.value">
                    {{ option.display }}
                  </option>
                </select>
              </div>
            </div>

            <!-- Time Input Alternative -->
            <div>
              <Label class="text-sm font-medium">Or type time (HH:MM)</Label>
              <div class="mt-2 flex items-center space-x-2">
                <Clock class="h-4 w-4 text-gray-500" />
                <Input
                  v-model="currentTime"
                  type="time"
                  class="flex-1"
                  placeholder="HH:MM"
                />
              </div>
            </div>

            <!-- Quick Presets -->
            <div>
              <Label class="text-sm font-medium mb-2 block">Quick Select</Label>
              <div class="flex flex-wrap gap-2">
                <Button
                  v-for="preset in timePresets"
                  :key="preset.label"
                  variant="outline"
                  size="sm"
                  @click="selectTimePreset(preset.value)"
                  class="text-xs"
                >
                  {{ preset.label }}
                </Button>
              </div>
            </div>

            <!-- Actions -->
            <div class="flex justify-end space-x-2 pt-4 border-t">
              <Button variant="outline" size="sm" @click="closePopover">
                Done
              </Button>
            </div>
          </div>
        </div>
      </div>
    </PopoverContent>
  </Popover>
</template>