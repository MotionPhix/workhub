<template>
    <Head title="Team Members" />

    <ManagerLayout>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <Card>
                    <CardHeader>
                        <div class="flex items-center justify-between">
                            <div>
                                <CardTitle class="flex items-center gap-2">
                                    <Users class="h-5 w-5" />
                                    Team Members
                                </CardTitle>
                                <CardDescription>
                                    Manage and monitor your team members' performance and status
                                </CardDescription>
                            </div>
                            <div class="flex items-center gap-4">
                                <Select v-model="filters.department_id" @update:model-value="applyFilters">
                                    <SelectTrigger class="w-48">
                                        <SelectValue placeholder="All Departments" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">All Departments</SelectItem>
                                        <SelectItem
                                            v-for="dept in departments"
                                            :key="dept.id"
                                            :value="dept.id.toString()"
                                        >
                                            {{ dept.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <Select v-model="filters.status" @update:model-value="applyFilters">
                                    <SelectTrigger class="w-40">
                                        <SelectValue placeholder="All Statuses" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">All Statuses</SelectItem>
                                        <SelectItem value="active">Active</SelectItem>
                                        <SelectItem value="inactive">Inactive</SelectItem>
                                        <SelectItem value="on_leave">On Leave</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>
                    </CardHeader>

                    <CardContent>
                        <!-- Search -->
                        <div class="mb-6">
                            <div class="relative">
                                <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 text-muted-foreground h-4 w-4" />
                                <Input
                                    v-model="filters.search"
                                    @input="debounceSearch"
                                    placeholder="Search team members..."
                                    class="pl-10"
                                />
                            </div>
                        </div>

                        <!-- Team Members Grid -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <Card
                                v-for="member in teamMembers.data"
                                :key="member.id"
                                class="hover:shadow-lg transition-shadow"
                            >
                                <CardContent class="p-6">
                                    <div class="flex items-center justify-between mb-4">
                                        <div class="flex items-center gap-3">
                                            <Avatar>
                                                <AvatarFallback class="bg-primary text-primary-foreground">
                                                    {{ member.name.charAt(0).toUpperCase() }}
                                                </AvatarFallback>
                                            </Avatar>
                                            <div>
                                                <h3 class="font-semibold">{{ member.name }}</h3>
                                                <p class="text-sm text-muted-foreground">{{ member.email }}</p>
                                            </div>
                                        </div>
                                        <Badge
                                            :variant="
                                                member.status === 'active' ? 'default' :
                                                member.status === 'inactive' ? 'destructive' :
                                                member.status === 'on_leave' ? 'secondary' :
                                                'outline-solid'
                                            "
                                        >
                                            {{ member.status ? member.status.replace('_', ' ') : 'active' }}
                                        </Badge>
                                    </div>

                                    <div class="space-y-3">
                                        <div v-if="member.department">
                                            <span class="text-xs text-muted-foreground">Department:</span>
                                            <p class="text-sm font-medium">{{ member.department.name }}</p>
                                        </div>

                                        <!-- Performance Stats -->
                                        <div v-if="member.performance_stats" class="space-y-2">
                                            <div class="flex justify-between text-sm">
                                                <span class="text-muted-foreground flex items-center gap-1">
                                                    <Clock class="h-3 w-3" />
                                                    Hours this month:
                                                </span>
                                                <span class="font-medium">{{ member.performance_stats.total_hours || 0 }}h</span>
                                            </div>
                                            <div class="flex justify-between text-sm">
                                                <span class="text-muted-foreground flex items-center gap-1">
                                                    <FileText class="h-3 w-3" />
                                                    Work entries:
                                                </span>
                                                <span class="font-medium">{{ member.performance_stats.total_entries || 0 }}</span>
                                            </div>
                                            <div class="flex justify-between text-sm">
                                                <span class="text-muted-foreground flex items-center gap-1">
                                                    <CheckCircle class="h-3 w-3" />
                                                    Reports submitted:
                                                </span>
                                                <span class="font-medium">{{ member.performance_stats.reports_submitted || 0 }}</span>
                                            </div>
                                            <div class="flex justify-between text-sm">
                                                <span class="text-muted-foreground flex items-center gap-1">
                                                    <AlertCircle class="h-3 w-3" />
                                                    Pending reports:
                                                </span>
                                                <Badge variant="secondary" class="h-5 text-xs">
                                                    {{ member.performance_stats.reports_pending || 0 }}
                                                </Badge>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Action Buttons -->
                                    <div class="flex gap-2 pt-4 border-t mt-4">
                                        <Button asChild class="flex-1" size="sm">
                                            <Link :href="route('manager.team.show', member.id)">
                                                <Eye class="h-4 w-4 mr-1" />
                                                View Details
                                            </Link>
                                        </Button>
                                        <ModalLink
                                            href="#edit-member-modal"
                                            @click="prepareEditModal(member)"
                                        >
                                            <Button
                                                variant="outline"
                                                size="sm"
                                            >
                                                <Edit class="h-4 w-4" />
                                            </Button>
                                        </ModalLink>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>

                        <!-- Empty State -->
                        <div v-if="!teamMembers.data || teamMembers.data.length === 0" class="text-center py-12">
                            <div class="text-muted-foreground mb-4">
                                <Users class="w-16 h-16 mx-auto" />
                            </div>
                            <p class="text-muted-foreground">No team members found</p>
                        </div>

                        <!-- Pagination -->
                        <div v-if="teamMembers.links && teamMembers.links.length > 3" class="mt-6 flex justify-center">
                            <div class="flex gap-1">
                                <template v-for="link in teamMembers.links" :key="link.label">
                                    <Button
                                        v-if="link.url"
                                        :variant="link.active ? 'default' : 'outline-solid'"
                                        size="sm"
                                        asChild
                                    >
                                        <Link :href="link.url" v-html="link.label" />
                                    </Button>
                                    <Button
                                        v-else
                                        variant="outline"
                                        size="sm"
                                        disabled
                                        v-html="link.label"
                                    />
                                </template>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>

        <!-- Edit Member Modal -->
        <Modal name="edit-member-modal" max-width="md" #default="{ close }">
            <div class="p-6">
                <div class="flex items-center gap-2 mb-4">
                    <UserCog class="h-5 w-5" />
                    <h3 class="text-lg font-semibold">Edit Team Member</h3>
                </div>

                <form @submit.prevent="updateMember(close)" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label for="name">Name</Label>
                            <Input
                                id="name"
                                v-model="editForm.name"
                                type="text"
                                placeholder="Enter full name"
                            />
                        </div>
                        <div class="space-y-2">
                            <Label for="email">Email</Label>
                            <Input
                                id="email"
                                v-model="editForm.email"
                                type="email"
                                placeholder="Enter email address"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label for="department">Department</Label>
                            <Select v-model="editForm.department_id">
                                <SelectTrigger>
                                    <SelectValue placeholder="Select Department" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="none">Select Department</SelectItem>
                                    <SelectItem
                                        v-for="dept in departments"
                                        :key="dept.id"
                                        :value="dept.id.toString()"
                                    >
                                        {{ dept.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="space-y-2">
                            <Label for="status">Status</Label>
                            <Select v-model="editForm.status">
                                <SelectTrigger>
                                    <SelectValue placeholder="Select Status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="active">Active</SelectItem>
                                    <SelectItem value="inactive">Inactive</SelectItem>
                                    <SelectItem value="on_leave">On Leave</SelectItem>
                                    <SelectItem value="terminated">Terminated</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label for="notes">Manager Notes</Label>
                        <Textarea
                            id="notes"
                            v-model="editForm.notes"
                            rows="3"
                            placeholder="Add any manager notes..."
                        />
                    </div>

                    <div class="flex gap-3 pt-4">
                        <Button
                            type="submit"
                            :disabled="processing"
                            class="flex-1"
                        >
                            <Loader2 v-if="processing" class="h-4 w-4 mr-2 animate-spin" />
                            {{ processing ? 'Updating...' : 'Update Member' }}
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            @click="close"
                            class="flex-1"
                        >
                            Cancel
                        </Button>
                    </div>
                </form>
            </div>
        </Modal>
    </ManagerLayout>
</template>

<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import ManagerLayout from '@/layouts/ManagerLayout.vue'
import { ref, reactive } from 'vue'

// Shadcn Components
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Badge } from '@/components/ui/badge'
import { Avatar, AvatarFallback } from '@/components/ui/avatar'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Textarea } from '@/components/ui/textarea'

// InertiaUI Modal
import { Modal, ModalLink } from '@inertiaui/modal-vue'

// Lucide Icons
import {
    Users,
    Search,
    Clock,
    FileText,
    CheckCircle,
    AlertCircle,
    Eye,
    Edit,
    UserCog,
    Loader2
} from 'lucide-vue-next'

const props = defineProps({
    teamMembers: {
        type: Object,
        required: true
    },
    filters: {
        type: Object,
        default: () => ({})
    },
    departments: {
        type: Array,
        default: () => []
    }
})

// Reactive filter state
const filters = reactive({
    search: props.filters.search || '',
    department_id: props.filters.department_id || 'all',
    status: props.filters.status || 'all'
})

// Edit modal state
const editingMember = ref(null)
const processing = ref(false)
const editForm = reactive({
    name: '',
    email: '',
    department_id: 'none',
    status: 'active',
    notes: ''
})

// Search debounce
let searchTimeout = null

function debounceSearch() {
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => {
        applyFilters()
    }, 300)
}

function applyFilters() {
    const filterData = {
        search: filters.search,
        department_id: filters.department_id === 'all' ? '' : filters.department_id,
        status: filters.status === 'all' ? '' : filters.status
    }

    router.get(route('manager.team.index'), filterData, {
        preserveState: true,
        replace: true
    })
}

function prepareEditModal(member) {
    editingMember.value = member
    editForm.name = member.name
    editForm.email = member.email
    editForm.department_id = member.department_id ? member.department_id.toString() : 'none'
    editForm.status = member.status || 'active'
    editForm.notes = member.notes || ''
}

function closeEditModal() {
    editingMember.value = null
    editForm.name = ''
    editForm.email = ''
    editForm.department_id = 'none'
    editForm.status = 'active'
    editForm.notes = ''
}

function updateMember(close) {
    if (!editingMember.value) return

    processing.value = true

    const formData = {
        ...editForm,
        department_id: editForm.department_id === 'none' ? null : editForm.department_id
    }

    router.put(route('manager.team.update', editingMember.value.id), formData, {
        onSuccess: () => {
            closeEditModal()
            close()
        },
        onFinish: () => {
            processing.value = false
        }
    })
}
</script>
