<template>
    <Head title="Team Reports" />

    <ManagerLayout>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xs sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-6">
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Team Reports</h1>
                            <div class="flex items-center space-x-4">
                                <select
                                    v-model="filters.status"
                                    @change="applyFilters"
                                    class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 text-sm"
                                >
                                    <option value="">All Statuses</option>
                                    <option value="draft">Draft</option>
                                    <option value="pending">Pending</option>
                                    <option value="approved">Approved</option>
                                    <option value="rejected">Rejected</option>
                                    <option value="sent">Sent</option>
                                </select>
                                <select
                                    v-model="filters.user_id"
                                    @change="applyFilters"
                                    class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 text-sm"
                                >
                                    <option value="">All Team Members</option>
                                    <option
                                        v-for="member in teamMembers"
                                        :key="member.id"
                                        :value="member.id"
                                    >
                                        {{ member.name }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <!-- Stats Cards -->
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                            <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
                                <h3 class="text-sm font-medium text-blue-900 dark:text-blue-100">Total Reports</h3>
                                <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ stats.total_reports || 0 }}</p>
                            </div>
                            <div class="bg-yellow-50 dark:bg-yellow-900/20 p-4 rounded-lg">
                                <h3 class="text-sm font-medium text-yellow-900 dark:text-yellow-100">Pending</h3>
                                <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ stats.pending_reports || 0 }}</p>
                            </div>
                            <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg">
                                <h3 class="text-sm font-medium text-green-900 dark:text-green-100">Approved</h3>
                                <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ stats.approved_reports || 0 }}</p>
                            </div>
                            <div class="bg-purple-50 dark:bg-purple-900/20 p-4 rounded-lg">
                                <h3 class="text-sm font-medium text-purple-900 dark:text-purple-100">Approval Rate</h3>
                                <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ stats.approval_rate || 0 }}%</p>
                            </div>
                        </div>

                        <!-- Search -->
                        <div class="mb-6">
                            <input
                                v-model="filters.search"
                                @input="debounceSearch"
                                type="text"
                                placeholder="Search reports..."
                                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 px-4 py-2"
                            />
                        </div>

                        <!-- Reports Table -->
                        <div class="bg-white dark:bg-gray-800 shadow-xs rounded-lg overflow-hidden">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Report
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Employee
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Type
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Status
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Created
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr
                                        v-for="report in reports.data"
                                        :key="report.id"
                                        class="hover:bg-gray-50 dark:hover:bg-gray-700"
                                    >
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div>
                                                <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                    {{ report.title }}
                                                </div>
                                                <div class="text-sm text-gray-500 dark:text-gray-400 truncate max-w-xs">
                                                    {{ report.description }}
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900 dark:text-gray-100">{{ report.user.name }}</div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">{{ report.user.email }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900 dark:text-gray-100">{{ report.report_type || 'General' }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span
                                                :class="[
                                                    'px-2 inline-flex text-xs leading-5 font-semibold rounded-full',
                                                    report.status === 'approved' ? 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-200' :
                                                    report.status === 'rejected' ? 'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-200' :
                                                    report.status === 'pending' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-200' :
                                                    report.status === 'draft' ? 'bg-gray-100 text-gray-800 dark:bg-gray-600 dark:text-gray-200' :
                                                    'bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-200'
                                                ]"
                                            >
                                                {{ report.status || 'draft' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            {{ formatDate(report.created_at) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex space-x-2">
                                                <Link
                                                    :href="route('manager.team-reports.show', report.id)"
                                                    class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-200"
                                                >
                                                    View
                                                </Link>
                                                <button
                                                    v-if="report.status === 'pending'"
                                                    @click="quickApprove(report.id)"
                                                    class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-200"
                                                >
                                                    Approve
                                                </button>
                                                <button
                                                    v-if="report.status === 'pending'"
                                                    @click="openRejectModal(report)"
                                                    class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-200"
                                                >
                                                    Reject
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>

                            <!-- Empty State -->
                            <div v-if="!reports.data || reports.data.length === 0" class="text-center py-12">
                                <div class="text-gray-400 dark:text-gray-600 mb-4">
                                    <svg class="w-16 h-16 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 0v12h8V4H6z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                                <p class="text-gray-500 dark:text-gray-400">No reports found</p>
                            </div>
                        </div>

                        <!-- Pagination -->
                        <div v-if="reports.links && reports.links.length > 3" class="mt-6 flex justify-center">
                            <div class="flex space-x-1">
                                <Link
                                    v-for="link in reports.links"
                                    :key="link.label"
                                    :href="link.url"
                                    v-html="link.label"
                                    :class="[
                                        'px-3 py-2 text-sm rounded-md',
                                        link.active
                                            ? 'bg-blue-600 text-white'
                                            : link.url
                                                ? 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600'
                                                : 'bg-gray-100 text-gray-400 cursor-not-allowed dark:bg-gray-800 dark:text-gray-500',
                                        { 'cursor-not-allowed': !link.url }
                                    ]"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reject Modal -->
        <div
            v-if="showRejectModal"
            class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
            @click="closeRejectModal"
        >
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800" @click.stop>
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Reject Report</h3>

                    <form @submit.prevent="rejectReport">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Reason for rejection
                            </label>
                            <textarea
                                v-model="rejectForm.reason"
                                rows="4"
                                required
                                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300"
                                placeholder="Please provide a reason for rejecting this report..."
                            ></textarea>
                        </div>

                        <div class="flex space-x-3">
                            <button
                                type="submit"
                                :disabled="processing"
                                class="flex-1 bg-red-600 text-white py-2 px-4 rounded-md hover:bg-red-700 disabled:opacity-50"
                            >
                                {{ processing ? 'Rejecting...' : 'Reject Report' }}
                            </button>
                            <button
                                type="button"
                                @click="closeRejectModal"
                                class="flex-1 bg-gray-300 text-gray-700 py-2 px-4 rounded-md hover:bg-gray-400 dark:bg-gray-600 dark:text-gray-300 dark:hover:bg-gray-500"
                            >
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </ManagerLayout>
</template>

<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import ManagerLayout from '@/layouts/ManagerLayout.vue'
import { ref, reactive } from 'vue'
import { format } from 'date-fns'

const props = defineProps({
    reports: {
        type: Object,
        required: true
    },
    teamMembers: {
        type: Array,
        default: () => []
    },
    filters: {
        type: Object,
        default: () => ({})
    },
    stats: {
        type: Object,
        default: () => ({})
    }
})

// Reactive filter state
const filters = reactive({
    search: props.filters.search || '',
    status: props.filters.status || '',
    user_id: props.filters.user_id || ''
})

// Reject modal state
const showRejectModal = ref(false)
const rejectingReport = ref(null)
const processing = ref(false)
const rejectForm = reactive({
    reason: ''
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
    router.get(route('manager.team-reports.index'), filters, {
        preserveState: true,
        replace: true
    })
}

function formatDate(dateString) {
    try {
        return format(new Date(dateString), 'MMM d, yyyy')
    } catch {
        return dateString
    }
}

function quickApprove(reportId) {
    router.post(route('manager.team-reports.approve', reportId), {}, {
        onSuccess: () => {
            router.reload()
        }
    })
}

function openRejectModal(report) {
    rejectingReport.value = report
    rejectForm.reason = ''
    showRejectModal.value = true
}

function closeRejectModal() {
    showRejectModal.value = false
    rejectingReport.value = null
    rejectForm.reason = ''
}

function rejectReport() {
    if (!rejectingReport.value) return

    processing.value = true

    router.post(route('manager.team-reports.reject', rejectingReport.value.id), rejectForm, {
        onSuccess: () => {
            closeRejectModal()
            router.reload()
        },
        onFinish: () => {
            processing.value = false
        }
    })
}
</script>
