<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue'
import { Head, useForm, Link } from '@inertiajs/vue3'
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card'
import ProjectForm from '@/components/projects/ProjectForm.vue'

interface Manager { id: number; name: string; email: string }
interface Department { uuid: string; name: string }
const props = defineProps<{ departments: Department[]; managers: Manager[] }>()
</script>

<template>
  <Head title="Create Project" />
  <AdminLayout>
    <Card class="max-w-2xl">
      <CardHeader class="pb-2">
        <CardTitle class="text-base">Create Project</CardTitle>
      </CardHeader>
      <CardContent>
        <ProjectForm
          mode="create"
          :departments="props.departments"
          :managers="props.managers"
          :action="route('admin.projects.store')"
          method="post"
        >
          <template #secondary-actions>
            <Link :href="route('admin.projects.index')" class="text-sm text-muted-foreground">Cancel</Link>
          </template>
        </ProjectForm>
      </CardContent>
    </Card>
  </AdminLayout>
</template>
