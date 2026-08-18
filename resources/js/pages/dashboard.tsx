import { Deferred, Head } from '@inertiajs/react';
import { dashboard } from '@/routes';
import { Card } from '@/components/ui/card';
import Heading from '@/components/heading';
import { EmptyState } from '@/components/empty-state';
import { CardListSkeleton, StatCardSkeleton } from '@/components/loading-skeleton';
import { AssignmentCard } from '@/components/dashboard/assignment-card';
import { NextBestProspects } from '@/components/dashboard/next-best-prospects';
import { PipelineSummary } from '@/components/dashboard/pipeline-summary';
import { MonthlyGoal } from '@/components/dashboard/monthly-goal';
import { FollowUpsDue } from '@/components/dashboard/follow-ups-due';
import { Send, Activity, Zap } from 'lucide-react';
import type {
    DashboardCycleStats, DashboardFollowUp, DashboardMonthlyGoal,
    DashboardPipelineStage, OutreachAssignment, Contact,
} from '@/types';

interface DashboardProps {
    todayOutreach: OutreachAssignment[];
    cycleStats: DashboardCycleStats | null;
    followUps: DashboardFollowUp[];
    recentActivity: number;
    nextBestProspects: Contact[];
    pipelineSummary: DashboardPipelineStage[];
    monthlyGoal: DashboardMonthlyGoal | null;
}

function greeting(): string {
    const hour = new Date().getHours();

    if (hour < 12) {
        return 'Good morning';
    }

    if (hour < 18) {
        return 'Good afternoon';
    }

    return 'Good evening';
}

export default function Dashboard({
    todayOutreach,
    cycleStats,
    followUps,
    recentActivity,
    nextBestProspects,
    pipelineSummary,
    monthlyGoal,
}: DashboardProps) {
    const firstName = 'Kingsley';

    return (
        <>
            <Head title="Dashboard" />

            <div className="space-y-8">
                {/* Greeting */}
                <header>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        {greeting()}, {firstName}
                    </h1>
                    {cycleStats && (
                        <p className="mt-1 text-sm text-muted-foreground">
                            Week {cycleStats.week_label} · {cycleStats.contacted}/{cycleStats.assigned} contacted
                        </p>
                    )}
                </header>

                <div className="grid gap-8 lg:grid-cols-[1fr_320px]">
                    {/* Main column */}
                    <div className="space-y-8">
                        {/* Outreach — the primary action area */}
                        <section>
                            <div className="mb-3 flex items-center justify-between">
                                <div className="flex items-center gap-2">
                                    <div className="flex size-7 items-center justify-center rounded-md bg-primary/10">
                                        <Send className="size-3.5 text-primary" />
                                    </div>
                                    <h2 className="text-sm font-semibold">Today's Outreach</h2>
                                </div>
                                {cycleStats && cycleStats.remaining > 0 && (
                                    <span className="rounded-full bg-primary/10 px-2.5 py-0.5 text-xs font-medium text-primary">
                                        {cycleStats.remaining} remaining
                                    </span>
                                )}
                            </div>
                            {todayOutreach.length === 0 ? (
                                <EmptyState
                                    icon={Send}
                                    title="No outreach waiting"
                                    description="When an active cycle is running, today's ranked assignments appear here."
                                />
                            ) : (
                                <div className="space-y-3">
                                    {todayOutreach.map((assignment) => (
                                        <AssignmentCard
                                            key={assignment.id}
                                            assignment={assignment}
                                        />
                                    ))}
                                </div>
                            )}
                        </section>

                        {/* Follow-ups */}
                        <section>
                            <div className="mb-3 flex items-center gap-2">
                                <h2 className="text-sm font-semibold">Follow-ups</h2>
                            </div>
                            <FollowUpsDue followUps={followUps} />
                        </section>
                    </div>

                    {/* Right rail — info density, not cards */}
                    <div className="space-y-6">
                        <Deferred
                            data="monthlyGoal"
                            fallback={<StatCardSkeleton />}
                        >
                            <MonthlyGoal goal={monthlyGoal} />
                        </Deferred>

                        <Deferred
                            data="pipelineSummary"
                            fallback={
                                <Card className="p-4">
                                    <StatCardSkeleton />
                                </Card>
                            }
                        >
                            <PipelineSummary stages={pipelineSummary} />
                        </Deferred>

                        <Deferred
                            data="nextBestProspects"
                            fallback={<CardListSkeleton count={5} />}
                        >
                            <NextBestProspects prospects={nextBestProspects} />
                        </Deferred>

                        {/* Activity indicator — minimal */}
                        <div className="flex items-center gap-2 rounded-lg border border-border/60 px-3 py-2.5 text-sm">
                            <Zap className="size-3.5 text-muted-foreground" />
                            <span className="text-muted-foreground">
                                {recentActivity} activities this week
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};
