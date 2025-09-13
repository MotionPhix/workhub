<template>
    <Head title="Admin Dashboard" />

    <AdminLayout>
        <div class="py-8">
            <div class="space-y-8">
                <!-- Header with Quick Stats -->
                <div>
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Admin Dashboard</h1>
                            <p class="text-gray-600 dark:text-gray-400">Organization overview and key metrics</p>
                        </div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            Last updated: {{ new Date().toLocaleString() }}
                        </div>
                    </div>

                    <!-- Key Performance Indicators -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                        <!-- Total Users with Growth -->
                        <div class="bg-gradient-to-r from-blue-500 to-blue-600 p-6 rounded-xl text-white shadow-lg">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-sm font-medium opacity-90">Total Users</h3>
                                    <p class="text-3xl font-bold" v-text="stats?.total_users || '0'"></p>
                                    <div class="flex items-center mt-2">
                                        <TrendingUp v-if="stats?.user_growth > 0" class="w-4 h-4 mr-1" />
                                        <TrendingDown v-else class="w-4 h-4 mr-1" />
                                        <span class="text-sm">{{ Math.abs(stats?.user_growth || 0) }}% this month</span>
                                    </div>
                                </div>
                                <Users class="w-12 h-12 opacity-80" />
                            </div>
                        </div>

                        <!-- Active Users -->
                        <div class="bg-gradient-to-r from-green-500 to-green-600 p-6 rounded-xl text-white shadow-lg">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-sm font-medium opacity-90">Active Users (7d)</h3>
                                    <p class="text-3xl font-bold" v-text="stats?.active_users || '0'"></p>
                                    <div class="text-sm mt-2">
                                        {{ analytics?.user_engagement?.engagement_rate || 0 }}% engagement rate
                                    </div>
                                </div>
                                <Activity class="w-12 h-12 opacity-80" />
                            </div>
                        </div>

                        <!-- Work Hours This Month -->
                        <div class="bg-gradient-to-r from-purple-500 to-purple-600 p-6 rounded-xl text-white shadow-lg">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-sm font-medium opacity-90">Hours This Month</h3>
                                    <p class="text-3xl font-bold" v-text="stats?.total_work_hours_month || '0'"></p>
                                    <div class="flex items-center mt-2">
                                        <span class="text-sm">
                                            {{ charts?.monthly_hours_comparison?.growth_percentage || 0 }}% vs last month
                                        </span>
                                    </div>
                                </div>
                                <Clock class="w-12 h-12 opacity-80" />
                            </div>
                        </div>

                        <!-- Productivity Score -->
                        <div class="bg-gradient-to-r from-orange-500 to-orange-600 p-6 rounded-xl text-white shadow-lg">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-sm font-medium opacity-90">Productivity Score</h3>
                                    <p class="text-3xl font-bold">{{ stats?.avg_productivity_score || '0' }}%</p>
                                    <div class="text-sm mt-2">
                                        System Health: {{ stats?.system_health?.status || 'Unknown' }}
                                    </div>
                                </div>
                                <Target class="w-12 h-12 opacity-80" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts and Analytics -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Productivity Trends Chart -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-lg">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Productivity Trends (30 Days)</h3>
                            <BarChart3 class="w-5 h-5 text-gray-400" />
                        </div>
                        <div class="h-80">
                            <VueApexCharts
                                type="area"
                                height="320"
                                :options="productivityChartOptions"
                                :series="productivitySeries"
                            />
                        </div>
                    </div>

                    <!-- User Registration Trend -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-lg">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">User Registration Trend</h3>
                            <UserPlus class="w-5 h-5 text-gray-400" />
                        </div>
                        <div class="h-80">
                            <VueApexCharts
                                type="line"
                                height="320"
                                :options="registrationChartOptions"
                                :series="registrationSeries"
                            />
                        </div>
                    </div>
                </div>

                <!-- Department Performance & User Distribution -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Department Performance -->
                    <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-xl p-6 shadow-lg">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Department Performance</h3>
                        <div class="space-y-4">
                            <div v-for="dept in analytics?.department_performance" :key="dept.name"
                                 class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <div>
                                    <h4 class="font-medium text-gray-900 dark:text-white">{{ dept.name }}</h4>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ dept.users_count }} users</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ dept.total_hours }}h</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ dept.avg_hours_per_user }}h avg</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- User Role Distribution -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-lg">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">User Distribution</h3>
                        <div class="h-64">
                            <VueApexCharts
                                type="donut"
                                height="256"
                                :options="distributionChartOptions"
                                :series="distributionSeries"
                            />
                        </div>
                    </div>
                </div>

                <!-- Recent Activities and Invitations -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Recent Activities -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-lg">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Recent Work Entries</h3>
                            <Link :href="route('admin.reports.index')" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 text-sm font-medium">
                                View All
                            </Link>
                        </div>
                        <div class="space-y-3">
                            <div v-for="entry in activities?.recent_work_entries?.slice(0, 5)" :key="entry.id"
                                 class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <div>
                                    <p class="font-medium text-sm text-gray-900 dark:text-white">{{ entry.work_title }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">by {{ entry.user?.name }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ entry.hours_worked }}h</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ formatDate(entry.created_at) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Invitation Management -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-lg">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Invitation Status</h3>
                            <Link :href="route('admin.invitations.index')" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 text-sm font-medium">
                                Manage
                            </Link>
                        </div>

                        <!-- Invitation Stats -->
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div class="text-center p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
                                <p class="text-2xl font-bold text-green-600 dark:text-green-400">
                                    {{ analytics?.invitation_conversion?.accepted || 0 }}
                                </p>
                                <p class="text-xs text-green-700 dark:text-green-300">Accepted</p>
                            </div>
                            <div class="text-center p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
                                <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">
                                    {{ analytics?.invitation_conversion?.pending || 0 }}
                                </p>
                                <p class="text-xs text-yellow-700 dark:text-yellow-300">Pending</p>
                            </div>
                        </div>

                        <div class="text-center p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                            <p class="text-sm font-medium text-blue-900 dark:text-blue-100">Conversion Rate</p>
                            <p class="text-xl font-bold text-blue-600 dark:text-blue-400">
                                {{ analytics?.invitation_conversion?.conversion_rate || 0 }}%
                            </p>
                        </div>

                        <!-- Recent Pending Invitations -->
                        <div class="mt-4 space-y-2">
                            <div v-for="invitation in activities?.pending_invitations_list?.slice(0, 3)" :key="invitation.email"
                                 class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-700 rounded">
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ invitation.email }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ invitation.role }}</p>
                                </div>
                                <span class="text-xs bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300 px-2 py-1 rounded">
                                    Pending
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Business Insights and Recommendations -->
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-lg">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Business Insights & Recommendations</h3>

                    <!-- KPIs Row -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                        <div class="text-center p-4 bg-gradient-to-r from-emerald-50 to-emerald-100 dark:from-emerald-900/20 dark:to-emerald-800/20 rounded-lg">
                            <p class="text-sm text-emerald-700 dark:text-emerald-300">User Satisfaction</p>
                            <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ insights?.kpi_summary?.user_satisfaction }}%</p>
                        </div>
                        <div class="text-center p-4 bg-gradient-to-r from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 rounded-lg">
                            <p class="text-sm text-blue-700 dark:text-blue-300">System Uptime</p>
                            <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ insights?.kpi_summary?.system_uptime }}%</p>
                        </div>
                        <div class="text-center p-4 bg-gradient-to-r from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20 rounded-lg">
                            <p class="text-sm text-purple-700 dark:text-purple-300">Data Accuracy</p>
                            <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ insights?.kpi_summary?.data_accuracy }}%</p>
                        </div>
                        <div class="text-center p-4 bg-gradient-to-r from-orange-50 to-orange-100 dark:from-orange-900/20 dark:to-orange-800/20 rounded-lg">
                            <p class="text-sm text-orange-700 dark:text-orange-300">Response Time</p>
                            <p class="text-2xl font-bold text-orange-600 dark:text-orange-400">{{ insights?.kpi_summary?.response_time }}</p>
                        </div>
                    </div>

                    <!-- Recommendations -->
                    <div v-if="insights?.recommendations?.length" class="space-y-3">
                        <h4 class="font-medium text-gray-900 dark:text-white">Recommendations:</h4>
                        <div v-for="(recommendation, index) in insights.recommendations" :key="index"
                             class="flex items-start space-x-3 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                            <Lightbulb class="w-5 h-5 text-blue-600 dark:text-blue-400 mt-0.5 flex-shrink-0" />
                            <p class="text-sm text-blue-900 dark:text-blue-100">{{ recommendation }}</p>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <Link :href="route('admin.invitations.create')"
                          class="flex items-center justify-center p-6 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white rounded-xl shadow-lg transition-all duration-200 transform hover:scale-105">
                        <UserPlus class="w-8 h-8 mr-3" />
                        <div>
                            <h3 class="text-lg font-semibold">Send Invitations</h3>
                            <p class="text-sm opacity-90">Invite new team members</p>
                        </div>
                    </Link>

                    <Link :href="route('admin.users.index')"
                          class="flex items-center justify-center p-6 bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white rounded-xl shadow-lg transition-all duration-200 transform hover:scale-105">
                        <Users class="w-8 h-8 mr-3" />
                        <div>
                            <h3 class="text-lg font-semibold">Manage Users</h3>
                            <p class="text-sm opacity-90">View and edit user accounts</p>
                        </div>
                    </Link>

                    <Link :href="route('admin.reports.index')"
                          class="flex items-center justify-center p-6 bg-gradient-to-r from-purple-500 to-purple-600 hover:from-purple-600 hover:to-purple-700 text-white rounded-xl shadow-lg transition-all duration-200 transform hover:scale-105">
                        <BarChart3 class="w-8 h-8 mr-3" />
                        <div>
                            <h3 class="text-lg font-semibold">View Reports</h3>
                            <p class="text-sm opacity-90">Detailed analytics</p>
                        </div>
                    </Link>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AdminLayout from '@/layouts/AdminLayout.vue'
import VueApexCharts from 'vue3-apexcharts'
import {
    Users,
    Activity,
    Clock,
    Target,
    BarChart3,
    UserPlus,
    TrendingUp,
    TrendingDown,
    Lightbulb,
    Calendar,
    Mail,
    Server
} from 'lucide-vue-next'
import { computed, ref } from 'vue'

const props = defineProps({
    stats: {
        type: Object,
        default: () => ({})
    },
    analytics: {
        type: Object,
        default: () => ({})
    },
    charts: {
        type: Object,
        default: () => ({})
    },
    activities: {
        type: Object,
        default: () => ({})
    },
    insights: {
        type: Object,
        default: () => ({})
    }
})

// Chart configurations
const productivityChartOptions = computed(() => ({
    chart: {
        type: 'area',
        toolbar: {
            show: false
        },
        background: 'transparent',
        foreColor: document.documentElement.classList.contains('dark') ? '#9CA3AF' : '#374151'
    },
    theme: {
        mode: document.documentElement.classList.contains('dark') ? 'dark' : 'light'
    },
    dataLabels: {
        enabled: false
    },
    stroke: {
        curve: 'smooth',
        width: 2
    },
    fill: {
        type: 'gradient',
        gradient: {
            shadeIntensity: 1,
            inverseColors: false,
            opacityFrom: 0.5,
            opacityTo: 0,
            stops: [0, 90, 100]
        }
    },
    colors: ['#8B5CF6'],
    xaxis: {
        categories: props.charts?.productivity_trend?.map(item => item.date) || [],
        axisBorder: {
            show: false
        },
        axisTicks: {
            show: false
        }
    },
    yaxis: {
        title: {
            text: 'Hours Worked'
        }
    },
    tooltip: {
        x: {
            format: 'dd MMM'
        }
    },
    grid: {
        borderColor: document.documentElement.classList.contains('dark') ? '#374151' : '#E5E7EB',
        strokeDashArray: 5
    }
}))

const productivitySeries = computed(() => [{
    name: 'Hours Worked',
    data: props.charts?.productivity_trend?.map(item => item.total_hours) || []
}])

const registrationChartOptions = computed(() => ({
    chart: {
        type: 'line',
        toolbar: {
            show: false
        },
        background: 'transparent',
        foreColor: document.documentElement.classList.contains('dark') ? '#9CA3AF' : '#374151'
    },
    theme: {
        mode: document.documentElement.classList.contains('dark') ? 'dark' : 'light'
    },
    dataLabels: {
        enabled: false
    },
    stroke: {
        curve: 'smooth',
        width: 3
    },
    colors: ['#10B981'],
    xaxis: {
        categories: props.charts?.registration_trend?.map(item => item.date) || [],
        axisBorder: {
            show: false
        },
        axisTicks: {
            show: false
        }
    },
    yaxis: {
        title: {
            text: 'New Users'
        }
    },
    tooltip: {
        x: {
            format: 'dd MMM'
        }
    },
    grid: {
        borderColor: document.documentElement.classList.contains('dark') ? '#374151' : '#E5E7EB',
        strokeDashArray: 5
    }
}))

const registrationSeries = computed(() => [{
    name: 'New Users',
    data: props.charts?.registration_trend?.map(item => item.count) || []
}])

const distributionChartOptions = computed(() => ({
    chart: {
        type: 'donut',
        background: 'transparent'
    },
    theme: {
        mode: document.documentElement.classList.contains('dark') ? 'dark' : 'light'
    },
    colors: ['#3B82F6', '#10B981', '#F59E0B'],
    labels: props.analytics?.user_distribution?.map(item => item.role) || [],
    dataLabels: {
        enabled: true,
        formatter: function (val) {
            return Math.round(val) + '%'
        }
    },
    responsive: [{
        breakpoint: 480,
        options: {
            chart: {
                width: 200
            },
            legend: {
                position: 'bottom'
            }
        }
    }],
    legend: {
        position: 'bottom',
        fontSize: '14px'
    }
}))

const distributionSeries = computed(() =>
    props.analytics?.user_distribution?.map(item => item.count) || []
)

// Utility functions
const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}
</script>
