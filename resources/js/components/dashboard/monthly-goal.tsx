import { Card } from '@/components/ui/card';
import { Target, TrendingUp } from 'lucide-react';
import type { DashboardMonthlyGoal } from '@/types';

interface MonthlyGoalProps {
    goal: DashboardMonthlyGoal | null;
}

function formatAmount(amount: number): string {
    return new Intl.NumberFormat('en-MW', {
        style: 'currency',
        currency: 'MWK',
        maximumFractionDigits: 0,
    }).format(amount);
}

export function MonthlyGoal({ goal }: MonthlyGoalProps) {
    if (!goal) {
        return (
            <Card className="p-4">
                <div className="flex items-center gap-2 text-sm text-muted-foreground">
                    <Target className="size-4" />
                    No revenue goal set for this month.
                </div>
            </Card>
        );
    }

    const pct = Math.min(goal.progress, 100);

    return (
        <Card className="overflow-hidden">
            <div className="p-4">
                <div className="flex items-center justify-between">
                    <span className="text-xs font-medium uppercase tracking-wider text-muted-foreground">
                        Monthly Goal
                    </span>
                    <span className="text-lg font-bold tabular-nums">
                        {goal.progress}%
                    </span>
                </div>

                <div className="mt-3 h-2 w-full overflow-hidden rounded-full bg-muted">
                    <div
                        className="h-full rounded-full bg-primary transition-all duration-500"
                        style={{ width: `${pct}%` }}
                    />
                </div>

                <div className="mt-3 flex items-baseline justify-between text-sm">
                    <span className="tabular-nums font-semibold">
                        {formatAmount(goal.revenue_current)}
                    </span>
                    <span className="text-muted-foreground">
                        of {formatAmount(goal.revenue_target)}
                    </span>
                </div>
            </div>
        </Card>
    );
}
