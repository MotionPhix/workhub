<script setup>
import { useForm } from '@inertiajs/vue3'
import {Card, CardContent, CardHeader, CardTitle} from "@/Components/ui/card";
import {FormLabel, Form, FormField} from "@/Components/ui/form";
import {Button} from "@/Components/ui/button";
import {Textarea} from "@/Components/ui/textarea";

const form = useForm({
  work_date: '',
  description: '',
  hours_worked: null,
  status: 'draft'
})

const submitWorkEntry = () => {
  form.post(route('work-entries.store'))
}
</script>

<template>
  <div class="container mx-auto p-6">
    <Card>

      <CardHeader>

        <CardTitle>Work Entries</CardTitle>

      </CardHeader>

      <CardContent>

        <Form @submit="submitWorkEntry">

          <FormField>
            <FormLabel>Work Date</FormLabel>
            <Input type="date" v-model="form.work_date" />
          </FormField>

          <FormField>
            <FormLabel>Description</FormLabel>
            <Textarea v-model="form.description" />
          </FormField>

          <Button type="submit">Save Work Entry</Button>

        </Form>

      </CardContent>

    </Card>
  </div>
</template>
