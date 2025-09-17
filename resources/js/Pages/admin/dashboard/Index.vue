<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { Modal, visitModal } from '@inertiaui/modal-vue'
import AdminLayout from '@/layouts/AdminLayout.vue'
import VueApexCharts from 'vue3-apexcharts'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
  Card,
  CardContent,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle,
} from '@/components/ui/card'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
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
    Server,
    Plus,
    Eye,
    Edit,
    MoreHorizontal,
    Archive,
    Filter
} from 'lucide-vue-next'
import { computed, ref, watch } from 'vue'

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
    },
    projects: {
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

// Project action methods
const openProjectFilters = () => {
    visitModal('#project-filters-slideover')
}

const viewProject = (project) => {
    router.visit(route('admin.projects.show', { project: project.uuid }))
}

const editProject = (project) => {
    router.visit(route('admin.projects.edit', { project: project.uuid }))
}

const manageTeam = (project) => {
    // Placeholder: open future team management modal / page
    alert('Team management coming soon for: ' + project.name)
}

const archiveProject = (project) => {
    if (confirm(`Are you sure you want to archive "${project.name}"? This action can be undone.`)) {
    router.post(route('admin.projects.archive', { project: project.uuid }), {}, {
            preserveScroll: true,
            onSuccess: () => {
                // Optionally toast success when toast system integrated
            }
        })
    }
}

// Filter state management
const searchQuery = ref(props.filters?.search || '')
const filterProjectType = ref(props.filters?.project_type || 'all')
const filterStatus = ref(props.filters?.status || 'all')
const filterPriority = ref(props.filters?.priority || 'all')
const sortBy = ref(props.filters?.sort_by || 'name')
const sortDirection = ref(props.filters?.sort_direction || 'asc')

// Filter methods
const applyFilters = () => {
    const params = {}

    if (searchQuery.value) params.search = searchQuery.value
    if (filterProjectType.value !== 'all') params.project_type = filterProjectType.value
    if (filterStatus.value !== 'all') params.status = filterStatus.value
    if (filterPriority.value !== 'all') params.priority = filterPriority.value
    if (sortBy.value !== 'name') params.sort_by = sortBy.value
    if (sortDirection.value !== 'asc') params.sort_direction = sortDirection.value

    router.get(route('admin.dashboard'), params, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            // Close the modal after successful filter application
            visitModal('#close')
        }
    })
}

const resetFilters = () => {
    searchQuery.value = ''
    filterProjectType.value = 'all'
    filterStatus.value = 'all'
    filterPriority.value = 'all'
    sortBy.value = 'name'
    sortDirection.value = 'asc'

    router.get(route('admin.dashboard'), {}, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            // Close the modal after successful reset
            visitModal('#close')
        }
    })
}

// Real-time data updates with debounced search
let searchTimeout = null

// Watch for search query changes with debounce
watch(searchQuery, (newValue) => {
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => {
        updateFilters()
    }, 500) // 500ms debounce for search
})

// Watch for immediate filter changes
watch([filterProjectType, filterStatus, filterPriority, sortBy, sortDirection], () => {
    updateFilters()
})

// Helper function to update filters without closing modal
const updateFilters = () => {
    const params = {}

    if (searchQuery.value) params.search = searchQuery.value
    if (filterProjectType.value !== 'all') params.project_type = filterProjectType.value
    if (filterStatus.value !== 'all') params.status = filterStatus.value
    if (filterPriority.value !== 'all') params.priority = filterPriority.value
    if (sortBy.value !== 'name') params.sort_by = sortBy.value
    if (sortDirection.value !== 'asc') params.sort_direction = sortDirection.value

    router.get(route('admin.dashboard'), params, {
        preserveState: true,
        preserveScroll: true,
        replace: true, // Use replace to avoid adding to history
    })
}

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
                        <div class="bg-linear-to-r from-blue-500 to-blue-600 p-6 rounded-xl text-white shadow-lg">
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
                        <div class="bg-linear-to-r from-green-500 to-green-600 p-6 rounded-xl text-white shadow-lg">
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
                        <div class="bg-linear-to-r from-purple-500 to-purple-600 p-6 rounded-xl text-white shadow-lg">
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
                        <div class="bg-linear-to-r from-orange-500 to-orange-600 p-6 rounded-xl text-white shadow-lg">
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

                <!-- Project Management Overview -->
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-lg">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Project Management</h2>
                            <p class="text-gray-600 dark:text-gray-400">Organization-wide project oversight and performance</p>
                        </div>
                        <div class="flex space-x-2">
                            <Button class="bg-blue-600 hover:bg-blue-700">
                                <Plus class="w-4 h-4 mr-2" />
                                New Project
                            </Button>
                        </div>
                    </div>

                    <!-- Project Overview Stats -->
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                        <div class="bg-gradient-to-r from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 p-4 rounded-lg">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-blue-700 dark:text-blue-300">Total Projects</p>
                                    <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ projects?.project_overview?.total_projects || 0 }}</p>
                                </div>
                                <div class="text-blue-500">
                                    <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <div class="bg-gradient-to-r from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 p-4 rounded-lg">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-green-700 dark:text-green-300">Active Projects</p>
                                    <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ projects?.project_overview?.active_projects || 0 }}</p>
                                </div>
                                <Activity class="w-8 h-8 text-green-500" />
                            </div>
                        </div>

                        <div class="bg-gradient-to-r from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20 p-4 rounded-lg">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-purple-700 dark:text-purple-300">Client Projects</p>
                                    <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ projects?.project_overview?.client_projects || 0 }}</p>
                                </div>
                                <Users class="w-8 h-8 text-purple-500" />
                            </div>
                        </div>

                        <div class="bg-gradient-to-r from-orange-50 to-orange-100 dark:from-orange-900/20 dark:to-orange-800/20 p-4 rounded-lg">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-orange-700 dark:text-orange-300">Completion Rate</p>
                                    <p class="text-2xl font-bold text-orange-600 dark:text-orange-400">{{ projects?.project_overview?.completion_rate || 0 }}%</p>
                                </div>
                                <Target class="w-8 h-8 text-orange-500" />
                            </div>
                        </div>
                    </div>

                    <!-- Project Boards (Monday.com style) -->
                    <div class="mb-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Project Boards</h3>
                            <div class="flex space-x-2">
                                <Button variant="outline" size="sm" @click="openProjectFilters">
                                    <Filter class="w-4 h-4 mr-1" />
                                    Filters & Search
                                </Button>
                            </div>
                        </div>

                        <!-- Project Board Table -->
                        <div class="overflow-x-auto">
                            <table class="w-full border-collapse">
                                <thead>
                                    <tr class="bg-gray-50 dark:bg-gray-700">
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Project</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Type</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Priority</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Progress</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Manager</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Due Date</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Team</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr v-for="project in projects?.project_boards?.active_projects?.slice(0, 8)" :key="project.uuid"
                                        class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                        <td class="px-4 py-4">
                                            <div>
                                                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ project.name }}</div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400 truncate max-w-48">{{ project.description }}</div>
                                                <div v-if="project.client_name" class="text-xs text-blue-600 dark:text-blue-400 mt-1">
                                                    {{ project.client_name }}
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-4">
                                            <span :class="{
                                                'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300': project.project_type === 'client',
                                                'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300': project.project_type === 'internal'
                                            }" class="inline-flex px-2 py-1 text-xs font-semibold rounded-full">
                                                {{ project.project_type === 'client' ? 'Client' : 'Internal' }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-4">
                                            <span :class="{
                                                'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300': project.status === 'active',
                                                'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300': project.status === 'on_hold',
                                                'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300': project.status === 'completed',
                                                'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300': project.status === 'draft'
                                            }" class="inline-flex px-2 py-1 text-xs font-semibold rounded-full">
                                                {{ project.status.replace('_', ' ').toLowerCase() }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-4">
                                            <span :class="{
                                                'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300': project.priority === 'urgent',
                                                'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300': project.priority === 'high',
                                                'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300': project.priority === 'medium',
                                                'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300': project.priority === 'low'
                                            }" class="inline-flex px-2 py-1 text-xs font-semibold rounded-full">
                                                {{ project.priority }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-4">
                                            <div class="flex items-center">
                                                <div class="flex-1">
                                                    <div class="bg-gray-200 dark:bg-gray-600 rounded-full h-2">
                                                        <div :class="{
                                                            'bg-red-500': project.completion_percentage < 25,
                                                            'bg-yellow-500': project.completion_percentage >= 25 && project.completion_percentage < 75,
                                                            'bg-green-500': project.completion_percentage >= 75
                                                        }" :style="`width: ${project.completion_percentage}%`" class="h-2 rounded-full transition-all"></div>
                                                    </div>
                                                </div>
                                                <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">{{ project.completion_percentage }}%</span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-4">
                                            <div class="text-sm text-gray-900 dark:text-white">{{ project.manager?.name || 'Unassigned' }}</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ project.department?.name }}</div>
                                        </td>
                                        <td class="px-4 py-4">
                                            <div :class="{
                                                'text-red-600 dark:text-red-400': project.is_overdue,
                                                'text-yellow-600 dark:text-yellow-400': !project.is_overdue && new Date(project.due_date) < new Date(Date.now() + 7 * 24 * 60 * 60 * 1000),
                                                'text-gray-900 dark:text-white': !project.is_overdue && new Date(project.due_date) >= new Date(Date.now() + 7 * 24 * 60 * 60 * 1000)
                                            }" class="text-sm">
                                                {{ project.due_date ? new Date(project.due_date).toLocaleDateString() : 'No deadline' }}
                                            </div>
                                            <div v-if="project.is_overdue" class="text-xs text-red-500">Overdue</div>
                                        </td>
                                        <td class="px-4 py-4">
                                            <div class="flex items-center">
                                                <span class="text-sm text-gray-900 dark:text-white">{{ project.team_members_count || 0 }}</span>
                                                <Users class="w-4 h-4 ml-1 text-gray-400" />
                                            </div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ project.work_entries_count || 0 }} tasks
                                            </div>
                                        </td>
                                        <td class="px-4 py-4">
                                            <div class="flex items-center space-x-2">
                                                <Button variant="outline" size="sm" @click="viewProject(project)">
                                                    <Eye class="w-3 h-3 mr-1" />
                                                    View
                                                </Button>
                                                <DropdownMenu>
                                                    <DropdownMenuTrigger as-child>
                                                        <Button variant="ghost" size="sm">
                                                            <MoreHorizontal class="w-4 h-4" />
                                                        </Button>
                                                    </DropdownMenuTrigger>
                                                    <DropdownMenuContent align="end">
                                                        <DropdownMenuItem @click="editProject(project)">
                                                            <Edit class="w-4 h-4 mr-2" />
                                                            Edit
                                                        </DropdownMenuItem>
                                                        <DropdownMenuItem @click="manageTeam(project)">
                                                            <Users class="w-4 h-4 mr-2" />
                                                            Manage Team
                                                        </DropdownMenuItem>
                                                        <DropdownMenuItem class="text-red-600" @click="archiveProject(project)">
                                                            <Archive class="w-4 h-4 mr-2" />
                                                            Archive
                                                        </DropdownMenuItem>
                                                    </DropdownMenuContent>
                                                </DropdownMenu>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div v-if="!projects?.project_boards?.active_projects?.length" class="text-center py-8">
                            <div class="text-gray-400 dark:text-gray-500 mb-2">
                                <svg class="w-12 h-12 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"></path>
                                </svg>
                            </div>
                            <p class="text-gray-500 dark:text-gray-400">No active projects found</p>
                            <p class="text-sm text-gray-400 dark:text-gray-500">Create a new project to get started</p>
                        </div>
                    </div>
                </div>

                <!-- Workload Distribution -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Team Workload Analysis -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-lg">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Team Workload Distribution</h3>

                        <div class="space-y-4">
                            <div v-for="dept in projects?.workload_distribution?.by_department" :key="dept.department" class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                <div class="flex items-center justify-between mb-2">
                                    <h4 class="font-medium text-gray-900 dark:text-white">{{ dept.department }}</h4>
                                    <span :class="{
                                        'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300': dept.workload_status === 'overloaded',
                                        'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300': dept.workload_status === 'high',
                                        'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300': dept.workload_status === 'medium',
                                        'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300': dept.workload_status === 'low'
                                    }" class="px-2 py-1 text-xs font-semibold rounded-full">
                                        {{ dept.workload_status }}
                                    </span>
                                </div>
                                <div class="grid grid-cols-3 gap-4 text-sm">
                                    <div>
                                        <p class="text-gray-500 dark:text-gray-400">Users</p>
                                        <p class="font-semibold text-gray-900 dark:text-white">{{ dept.users_count }}</p>
                                    </div>
                                    <div>
                                        <p class="text-gray-500 dark:text-gray-400">Active Projects</p>
                                        <p class="font-semibold text-gray-900 dark:text-white">{{ dept.active_projects }}</p>
                                    </div>
                                    <div>
                                        <p class="text-gray-500 dark:text-gray-400">Avg Completion</p>
                                        <p class="font-semibold text-gray-900 dark:text-white">{{ dept.avg_completion_rate }}%</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Project Health Overview -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-lg">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Project Health Overview</h3>

                        <!-- Health Score -->
                        <div class="text-center mb-6">
                            <div class="text-3xl font-bold mb-2" :class="{
                                'text-red-600': projects?.project_health?.health_score < 50,
                                'text-yellow-600': projects?.project_health?.health_score >= 50 && projects?.project_health?.health_score < 75,
                                'text-green-600': projects?.project_health?.health_score >= 75
                            }">
                                {{ projects?.project_health?.health_score || 0 }}%
                            </div>
                            <p class="text-gray-600 dark:text-gray-400">Overall Project Health</p>
                        </div>

                        <!-- Health Metrics -->
                        <div class="grid grid-cols-3 gap-4 mb-6">
                            <div class="text-center p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
                                <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ projects?.project_health?.healthy_projects || 0 }}</p>
                                <p class="text-xs text-green-700 dark:text-green-300">Healthy</p>
                            </div>
                            <div class="text-center p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
                                <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ projects?.project_health?.at_risk_projects || 0 }}</p>
                                <p class="text-xs text-yellow-700 dark:text-yellow-300">At Risk</p>
                            </div>
                            <div class="text-center p-3 bg-red-50 dark:bg-red-900/20 rounded-lg">
                                <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ projects?.project_health?.blocked_projects || 0 }}</p>
                                <p class="text-xs text-red-700 dark:text-red-300">Blocked</p>
                            </div>
                        </div>

                        <!-- Common Issues -->
                        <div v-if="projects?.project_health?.common_issues?.length">
                            <h4 class="font-medium text-gray-900 dark:text-white mb-3">Common Issues:</h4>
                            <div class="space-y-2">
                                <div v-for="issue in projects.project_health.common_issues" :key="issue"
                                     class="flex items-start space-x-2 p-2 bg-yellow-50 dark:bg-yellow-900/20 rounded">
                                    <svg class="w-4 h-4 text-yellow-600 mt-0.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                    </svg>
                                    <p class="text-sm text-yellow-900 dark:text-yellow-100">{{ issue }}</p>
                                </div>
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
                        <div class="text-center p-4 bg-linear-to-r from-emerald-50 to-emerald-100 dark:from-emerald-900/20 dark:to-emerald-800/20 rounded-lg">
                            <p class="text-sm text-emerald-700 dark:text-emerald-300">User Satisfaction</p>
                            <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ insights?.kpi_summary?.user_satisfaction }}%</p>
                        </div>
                        <div class="text-center p-4 bg-linear-to-r from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 rounded-lg">
                            <p class="text-sm text-blue-700 dark:text-blue-300">System Uptime</p>
                            <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ insights?.kpi_summary?.system_uptime }}%</p>
                        </div>
                        <div class="text-center p-4 bg-linear-to-r from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20 rounded-lg">
                            <p class="text-sm text-purple-700 dark:text-purple-300">Data Accuracy</p>
                            <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ insights?.kpi_summary?.data_accuracy }}%</p>
                        </div>
                        <div class="text-center p-4 bg-linear-to-r from-orange-50 to-orange-100 dark:from-orange-900/20 dark:to-orange-800/20 rounded-lg">
                            <p class="text-sm text-orange-700 dark:text-orange-300">Response Time</p>
                            <p class="text-2xl font-bold text-orange-600 dark:text-orange-400">{{ insights?.kpi_summary?.response_time }}</p>
                        </div>
                    </div>

                    <!-- Recommendations -->
                    <div v-if="insights?.recommendations?.length" class="space-y-3">
                        <h4 class="font-medium text-gray-900 dark:text-white">Recommendations:</h4>
                        <div v-for="(recommendation, index) in insights.recommendations" :key="index"
                             class="flex items-start space-x-3 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                            <Lightbulb class="w-5 h-5 text-blue-600 dark:text-blue-400 mt-0.5 shrink-0" />
                            <p class="text-sm text-blue-900 dark:text-blue-100">{{ recommendation }}</p>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <Link :href="route('admin.invitations.create')"
                          class="flex items-center justify-center p-6 bg-linear-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white rounded-xl shadow-lg transition-all duration-200 transform hover:scale-105">
                        <UserPlus class="w-8 h-8 mr-3" />
                        <div>
                            <h3 class="text-lg font-semibold">Send Invitations</h3>
                            <p class="text-sm opacity-90">Invite new team members</p>
                        </div>
                    </Link>

                    <Link :href="route('admin.users.index')"
                          class="flex items-center justify-center p-6 bg-linear-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white rounded-xl shadow-lg transition-all duration-200 transform hover:scale-105">
                        <Users class="w-8 h-8 mr-3" />
                        <div>
                            <h3 class="text-lg font-semibold">Manage Users</h3>
                            <p class="text-sm opacity-90">View and edit user accounts</p>
                        </div>
                    </Link>

                    <Link :href="route('admin.reports.index')"
                          class="flex items-center justify-center p-6 bg-linear-to-r from-purple-500 to-purple-600 hover:from-purple-600 hover:to-purple-700 text-white rounded-xl shadow-lg transition-all duration-200 transform hover:scale-105">
                        <BarChart3 class="w-8 h-8 mr-3" />
                        <div>
                            <h3 class="text-lg font-semibold">View Reports</h3>
                            <p class="text-sm opacity-90">Detailed analytics</p>
                        </div>
                    </Link>
                </div>
            </div>
        </div>

        <!-- Project Filters Slideover -->
        <Modal
            name="project-filters-slideover"
            max-width="lg"
            :close-button="true"
        >
            <Card>
                <CardHeader>
                    <CardTitle>Project Filters & Search</CardTitle>
                    <CardDescription>
                        Filter and search projects by various criteria
                    </CardDescription>
                </CardHeader>

                <CardContent class="space-y-6">
                    <!-- Search Input -->
                    <div class="space-y-2">
                        <Label for="search-input">Search Projects</Label>
                        <Input
                            id="search-input"
                            v-model="searchQuery"
                            type="text"
                            placeholder="Search by name, description, or client..."
                        />
                    </div>

                    <!-- Project Type Filter -->
                    <div class="space-y-2">
                        <Label>Project Type</Label>
                        <Select v-model="filterProjectType">
                            <SelectTrigger>
                                <SelectValue placeholder="Select project type" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All Types</SelectItem>
                                <SelectItem value="client">Client Projects</SelectItem>
                                <SelectItem value="internal">Internal Projects</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- Status Filter -->
                    <div class="space-y-2">
                        <Label>Status</Label>
                        <Select v-model="filterStatus">
                            <SelectTrigger>
                                <SelectValue placeholder="Select status" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All Status</SelectItem>
                                <SelectItem value="active">Active</SelectItem>
                                <SelectItem value="completed">Completed</SelectItem>
                                <SelectItem value="on_hold">On Hold</SelectItem>
                                <SelectItem value="draft">Draft</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- Priority Filter -->
                    <div class="space-y-2">
                        <Label>Priority</Label>
                        <Select v-model="filterPriority">
                            <SelectTrigger>
                                <SelectValue placeholder="Select priority" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All Priorities</SelectItem>
                                <SelectItem value="urgent">Urgent</SelectItem>
                                <SelectItem value="high">High</SelectItem>
                                <SelectItem value="medium">Medium</SelectItem>
                                <SelectItem value="low">Low</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- Sort Options -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label>Sort By</Label>
                            <Select v-model="sortBy">
                                <SelectTrigger>
                                    <SelectValue placeholder="Sort by" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="name">Name</SelectItem>
                                    <SelectItem value="due_date">Due Date</SelectItem>
                                    <SelectItem value="priority">Priority</SelectItem>
                                    <SelectItem value="completion">Completion</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="space-y-2">
                            <Label>Direction</Label>
                            <Select v-model="sortDirection">
                                <SelectTrigger>
                                    <SelectValue placeholder="Direction" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="asc">Ascending</SelectItem>
                                    <SelectItem value="desc">Descending</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                </CardContent>

                <CardFooter class="flex gap-3">
                    <Button @click="applyFilters" class="flex-1">
                        Apply Filters
                    </Button>
                    <Button variant="outline" @click="resetFilters" class="flex-1">
                        Reset All
                    </Button>
                </CardFooter>
            </Card>
        </Modal>
    </AdminLayout>
</template>
