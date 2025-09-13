<template>
    <Head title="Manager Dashboard" />

    <ManagerLayout>
        <div class="py-8">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Dashboard Header -->
                <div class="mb-8">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Manager Dashboard</h1>
                            <p class="text-gray-600 dark:text-gray-400 mt-1">Welcome back, {{ $page.props.auth.user.name }}</p>
                        </div>
                        <div class="flex items-center space-x-4">
                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                Last updated: {{ new Date().toLocaleDateString() }}
                            </div>
                            <button
                                @click="refreshData"
                                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors flex items-center"
                            >
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                                Refresh
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Key Metrics Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <!-- Team Size -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 hover:shadow-md transition-shadow">
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <div class="flex items-center">
                                    <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                                        <Users class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                                    </div>
                                    <div class="ml-4">
                                        <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400">Team Members</h3>
                                        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100" v-text="dashboardData?.team_overview?.total_members || '0'"></p>
                                    </div>
                                </div>
                                <div class="mt-4 flex items-center text-sm">
                                    <span class="text-green-600 dark:text-green-400 font-medium">{{ dashboardData?.team_overview?.active_members || 0 }} active</span>
                                    <span class="text-gray-600 dark:text-gray-400 ml-1">this week</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Current Month Reports -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 hover:shadow-md transition-shadow">
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <div class="flex items-center">
                                    <div class="p-2 bg-green-100 dark:bg-green-900/30 rounded-lg">
                                        <Clock class="w-6 h-6 text-green-600 dark:text-green-400" />
                                    </div>
                                    <div class="ml-4">
                                        <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400">Reports This Month</h3>
                                        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100" v-text="dashboardData?.report_metrics?.current_month?.total_reports || '0'"></p>
                                    </div>
                                </div>
                                <div class="mt-4 flex items-center text-sm">
                                    <span
                                        :class="[
                                            (dashboardData?.report_metrics?.comparison?.reports_change_percent || 0) >= 0
                                                ? 'text-green-600 dark:text-green-400'
                                                : 'text-red-600 dark:text-red-400',
                                            'font-medium'
                                        ]"
                                    >
                                        {{ (dashboardData?.report_metrics?.comparison?.reports_change_percent || 0) >= 0 ? '+' : '' }}{{ dashboardData?.report_metrics?.comparison?.reports_change_percent || 0 }}%
                                    </span>
                                    <span class="text-gray-600 dark:text-gray-400 ml-1">vs last month</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pending Reports -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 hover:shadow-md transition-shadow">
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <div class="flex items-center">
                                    <div class="p-2 bg-amber-100 dark:bg-amber-900/30 rounded-lg">
                                        <AlertCircle class="w-6 h-6 text-amber-600 dark:text-amber-400" />
                                    </div>
                                    <div class="ml-4">
                                        <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400">Pending Approvals</h3>
                                        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100" v-text="dashboardData?.report_metrics?.current_month?.pending_reports || '0'"></p>
                                    </div>
                                </div>
                                <div class="mt-4 flex items-center text-sm">
                                    <span class="text-amber-600 dark:text-amber-400 font-medium">{{ dashboardData?.report_metrics?.current_month?.rejected_reports || 0 }} rejected</span>
                                    <span class="text-gray-600 dark:text-gray-400 ml-1">this month</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Team Performance -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 hover:shadow-md transition-shadow">
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <div class="flex items-center">
                                    <div class="p-2 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
                                        <TrendingUp class="w-6 h-6 text-purple-600 dark:text-purple-400" />
                                    </div>
                                    <div class="ml-4">
                                        <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400">On-time Reports</h3>
                                        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                                            {{ dashboardData?.report_metrics?.current_month?.on_time_percentage || 0 }}%
                                        </p>
                                    </div>
                                </div>
                                <div class="mt-4 flex items-center text-sm">
                                    <span class="text-purple-600 dark:text-purple-400 font-medium">{{ dashboardData?.report_metrics?.current_month?.on_time_reports || 0 }} reports</span>
                                    <span class="text-gray-600 dark:text-gray-400 ml-1">submitted on time</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Content Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Reports Awaiting Review - Takes 2/3 width -->
                    <div class="lg:col-span-2">
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Reports Awaiting Review</h3>
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Manage and approve team submissions</p>
                                    </div>
                                    <Link
                                        :href="route('manager.reports.index')"
                                        class="inline-flex items-center px-4 py-2 bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 text-sm font-medium rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/30 transition-colors"
                                    >
                                        View All
                                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                        </svg>
                                    </Link>
                                </div>
                            </div>

                            <div class="p-6">
                                <div v-if="dashboardData?.upcoming_reports?.length" class="space-y-4">
                                    <div
                                        v-for="report in dashboardData.upcoming_reports.slice(0, 5)"
                                        :key="report.id"
                                        class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl border border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500 transition-colors"
                                    >
                                        <div class="flex-1">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0">
                                                    <div
                                                        :class="[
                                                            report.status === 'pending' ? 'bg-amber-500' :
                                                            report.status === 'draft' ? 'bg-gray-500' :
                                                            report.status === 'rejected' ? 'bg-red-500' : 'bg-blue-500',
                                                            'w-2 h-2 rounded-full'
                                                        ]"
                                                    ></div>
                                                </div>
                                                <div class="ml-3">
                                                    <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ report.title || report.report_type || 'Report' }}</h4>
                                                    <div class="flex items-center mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                        <span>{{ report.user?.name }}</span>
                                                        <span class="mx-2">•</span>
                                                        <span>{{ formatDate(report.created_at || report.due_at) }}</span>
                                                        <span v-if="report.status === 'pending'" class="ml-2 px-2 py-0.5 bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 rounded-full text-xs font-medium">
                                                            Pending
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <button
                                                v-if="report.status === 'pending'"
                                                @click="quickApprove(report.id)"
                                                class="px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white text-xs font-medium rounded-lg transition-colors"
                                            >
                                                Approve
                                            </button>
                                            <Link
                                                :href="route('manager.reports.index')"
                                                class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded-lg transition-colors"
                                            >
                                                Review
                                            </Link>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="text-center py-12">
                                    <div class="w-12 h-12 mx-auto bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mb-4">
                                        <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </div>
                                    <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-1">All caught up!</h4>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">No reports pending review at the moment</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Sidebar -->
                    <div class="space-y-6">
                        <!-- Team Performance Snapshot -->
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Team Performance</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Current metrics overview</p>
                            </div>
                            <div class="p-6 space-y-4">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Team Approval Rate</span>
                                    <div class="flex items-center">
                                        <div class="w-20 bg-gray-200 dark:bg-gray-600 rounded-full h-2 mr-3">
                                            <div
                                                class="bg-blue-600 h-2 rounded-full transition-all duration-300"
                                                :style="{ width: (dashboardData?.performance_analytics?.team_averages?.avg_approval_rate || 0) + '%' }"
                                            ></div>
                                        </div>
                                        <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ Math.round(dashboardData?.performance_analytics?.team_averages?.avg_approval_rate || 0) }}%</span>
                                    </div>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Average Reports/Member</span>
                                    <div class="flex items-center">
                                        <div class="w-20 bg-gray-200 dark:bg-gray-600 rounded-full h-2 mr-3">
                                            <div
                                                class="bg-green-600 h-2 rounded-full transition-all duration-300"
                                                :style="{ width: Math.min((dashboardData?.performance_analytics?.team_averages?.avg_reports_per_member || 0) * 10, 100) + '%' }"
                                            ></div>
                                        </div>
                                        <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ Math.round(dashboardData?.performance_analytics?.team_averages?.avg_reports_per_member || 0) }}</span>
                                    </div>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Compliance Rate</span>
                                    <div class="flex items-center">
                                        <div class="w-20 bg-gray-200 dark:bg-gray-600 rounded-full h-2 mr-3">
                                            <div
                                                class="bg-purple-600 h-2 rounded-full transition-all duration-300"
                                                :style="{ width: (dashboardData?.compliance_status?.compliance_rate || 0) + '%' }"
                                            ></div>
                                        </div>
                                        <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ Math.round(dashboardData?.compliance_status?.compliance_rate || 0) }}%</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Actions -->
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Quick Actions</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Common management tasks</p>
                            </div>
                            <div class="p-6 grid grid-cols-1 gap-3">
                                <Link
                                    :href="route('manager.team.index')"
                                    class="flex items-center p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-700 hover:bg-blue-100 dark:hover:bg-blue-900/30 transition-colors group"
                                >
                                    <div class="flex-shrink-0">
                                        <div class="p-2 bg-blue-100 dark:bg-blue-900/50 rounded-lg group-hover:bg-blue-200 dark:group-hover:bg-blue-900/70 transition-colors">
                                            <Users class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                                        </div>
                                    </div>
                                    <div class="ml-4 flex-1">
                                        <h4 class="text-sm font-medium text-blue-900 dark:text-blue-100">Manage Team</h4>
                                        <p class="text-xs text-blue-700 dark:text-blue-300 mt-1">View team members and assignments</p>
                                    </div>
                                    <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                </Link>

                                <Link
                                    :href="route('manager.insights.index')"
                                    class="flex items-center p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg border border-purple-200 dark:border-purple-700 hover:bg-purple-100 dark:hover:bg-purple-900/30 transition-colors group"
                                >
                                    <div class="flex-shrink-0">
                                        <div class="p-2 bg-purple-100 dark:bg-purple-900/50 rounded-lg group-hover:bg-purple-200 dark:group-hover:bg-purple-900/70 transition-colors">
                                            <BarChart3 class="w-5 h-5 text-purple-600 dark:text-purple-400" />
                                        </div>
                                    </div>
                                    <div class="ml-4 flex-1">
                                        <h4 class="text-sm font-medium text-purple-900 dark:text-purple-100">Team Insights</h4>
                                        <p class="text-xs text-purple-700 dark:text-purple-300 mt-1">Analytics and detailed reports</p>
                                    </div>
                                    <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                </Link>

                                <button
                                    @click="generateReport"
                                    class="flex items-center p-4 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-700 hover:bg-green-100 dark:hover:bg-green-900/30 transition-colors group"
                                >
                                    <div class="flex-shrink-0">
                                        <div class="p-2 bg-green-100 dark:bg-green-900/50 rounded-lg group-hover:bg-green-200 dark:group-hover:bg-green-900/70 transition-colors">
                                            <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="ml-4 flex-1 text-left">
                                        <h4 class="text-sm font-medium text-green-900 dark:text-green-100">Generate Report</h4>
                                        <p class="text-xs text-green-700 dark:text-green-300 mt-1">Create team performance summary</p>
                                    </div>
                                </button>
                            </div>
                        </div>

                        <!-- Top Performers -->
                        <div v-if="dashboardData?.performance_analytics?.top_performers?.length" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Top Performers</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">This month's standout team members</p>
                            </div>
                            <div class="p-6 space-y-3">
                                <div
                                    v-for="(performer, index) in dashboardData.performance_analytics.top_performers"
                                    :key="performer.user_id"
                                    class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg"
                                >
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 w-8 h-8 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full flex items-center justify-center text-white text-sm font-bold">
                                            {{ index + 1 }}
                                        </div>
                                        <div class="ml-3">
                                            <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ performer.name }}</h4>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ performer.department }}</p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ performer.approval_rate }}%</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ performer.reports_count }} reports</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </ManagerLayout>
</template>

<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import ManagerLayout from '@/layouts/ManagerLayout.vue'
import { format } from 'date-fns'
import { Users, Clock, AlertCircle, TrendingUp, BarChart3 } from 'lucide-vue-next'

defineProps({
    dashboardData: {
        type: Object,
        default: () => ({})
    },
    currentUser: {
        type: Object,
        default: () => ({})
    }
})

function formatDate(dateString) {
    try {
        return format(new Date(dateString), 'MMM d, h:mm a')
    } catch {
        return dateString
    }
}

function quickApprove(reportId) {
    // TODO: Implement report approval functionality
    // router.post(route('manager.reports.approve', reportId), {}, {
    //     onSuccess: () => {
    //         router.reload()
    //     }
    // })
    alert('Report approval functionality not implemented yet')
}

function refreshData() {
    router.reload({ only: ['dashboardData'] })
}

function generateReport() {
    // TODO: Implement team performance report generation
    alert('Team performance report generation coming soon!')
}
</script>
