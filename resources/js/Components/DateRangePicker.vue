<script setup lang="ts">
import type { DateRange } from 'reka-ui'
import {
  DateFormatter,
  getLocalTimeZone,
} from '@internationalized/date'
import { CalendarIcon } from 'lucide-vue-next'
import { cn } from '@/lib/utils'
import { Button } from '@/components/ui/button'
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover'
import { RangeCalendar } from '@/components/ui/range-calendar'

defineProps<{
  placeholder?: string
}>()

const value = defineModel<DateRange>({ required: true })

/*watchEffect(() => {
  if (!value.value?.start || !value.value?.end) {
    const today = new Date()
    const start = new CalendarDate(today.getFullYear(), today.getMonth() + 1, today.getDate())
    const end = start.add({ days: 5 })
    value.value = { start, end }
  }
})*/

const df = new DateFormatter('en-MW', { dateStyle: 'medium' })
</script>

<template>
  <Popover>
    <PopoverTrigger as-child>
      <Button
        variant="outline"
        :class="cn(
          'w-full justify-start text-left font-normal',
          !value?.start && 'text-muted-foreground'
        )"
      >
        <CalendarIcon class="mr-2 h-4 w-4" />
        <template v-if="value?.start">
          <template v-if="value?.end">
            {{ df.format(value.start.toDate(getLocalTimeZone())) }} - {{ df.format(value.end.toDate(getLocalTimeZone())) }}
          </template>
          <template v-else>
            {{ df.format(value.start.toDate(getLocalTimeZone())) }}
          </template>
        </template>
        <template v-else>
          {{ placeholder ?? 'Pick a date' }}
        </template>
      </Button>
    </PopoverTrigger>

    <PopoverContent class="w-auto p-0">
      <RangeCalendar
        v-model="value"
        initial-focus
        :number-of-months="2"
      />
    </PopoverContent>
  </Popover>
</template>
