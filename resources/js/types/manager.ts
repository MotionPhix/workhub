export interface TeamOverview {
  total_members: number
  active_members: number
  inactive_members: number
  activity_rate: number
  department_distribution: Record<string, number>
  role_distribution: Record<string, number>
  newest_member: {
    id: number
    name: string
    created_at: string
  } | null
}

export interface ReportMetrics {
  current_month: {
    total_reports: number
    approved_reports: number
    pending_reports: number
    draft_reports: number
    rejected_reports: number
    on_time_reports: number
    on_time_percentage: number
  }
  comparison: {
    reports_change: number
    reports_change_percent: number
  }
  by_type: Record<string, number>
  by_status: Record<string, number>
}

export interface IndividualPerformance {
  user_id: number
  name: string
  department: string | null
  reports_count: number
  approved_count: number
  on_time_count: number
  avg_completion_time: number | null
  approval_rate: number
}

export interface PerformanceAnalytics {
  individual_performance: IndividualPerformance[]
  team_averages: {
    avg_reports_per_member: number
    avg_approval_rate: number
    avg_completion_time: number | null
  }
  top_performers: IndividualPerformance[]
  needs_attention: IndividualPerformance[]
}

export interface ComplianceStatus {
  compliant_members: number
  non_compliant_members: number
  compliance_rate: number
  overdue_reports: number
  pending_approvals: number
  overdue_members: {
    id: number
    name: string
    department: string | null
    days_overdue: number | string
  }[]
}

export interface TrendingInsights {
  quarterly_data: {
    quarter: string
    total_reports: number
    avg_approval_time: number | null
    compliance_rate: number
  }[]
  trends: {
    reports_trend: 'up' | 'down' | 'stable'
    approval_trend: 'up' | 'down' | 'stable'
    compliance_trend: 'up' | 'down' | 'stable'
  }
  insights: string[]
}

export interface UpcomingReport {
  id: number
  user_id: number
  user_name: string
  department: string | null
  report_type: string
  due_date: string
  priority: 'high' | 'medium' | 'low'
  status: 'draft' | 'pending' | 'approved' | 'rejected'
}

export interface TeamRanking {
  department: string
  total_members: number
  avg_reports_per_member: number
  compliance_rate: number
  avg_approval_rate: number
  score: number
  rank: number
}

export interface ManagerDashboardData {
  team_overview: TeamOverview
  report_metrics: ReportMetrics
  performance_analytics: PerformanceAnalytics
  compliance_status: ComplianceStatus
  trending_insights: TrendingInsights
  upcoming_reports: UpcomingReport[]
  team_rankings: TeamRanking[]
}

// Page props interface
export interface ManagerDashboardProps {
  dashboardData: ManagerDashboardData
  currentUser: {
    id: number
    name: string
    email: string
  }
}
