export type * from './auth';
export type * from './navigation';
export type * from './ui';
export type * from './enums';
export type * from './models';

export interface Paginated<T> {
    data: T[];
    current_page: number;
    first_page_url: string;
    from: number | null;
    last_page: number;
    last_page_url: string;
    links: Array<{
        url: string | null;
        label: string;
        active: boolean;
    }>;
    next_page_url: string | null;
    path: string;
    per_page: number;
    prev_page_url: string | null;
    to: number | null;
    total: number;
}

export interface DashboardCycleStats {
    name: string;
    week_label: string;
    assigned: number;
    contacted: number;
    remaining: number;
}

export interface DashboardFollowUp {
    id: string;
    type: string;
    due_on: string;
    is_overdue: boolean;
    is_due_today: boolean;
    contact: {
        id: string;
        full_name: string;
        company: string | null;
    };
}

export interface DashboardPipelineStage {
    stage: string;
    count: number;
    value: number;
}

export interface DashboardMonthlyGoal {
    revenue_target: number;
    revenue_current: number;
    progress: number;
}
