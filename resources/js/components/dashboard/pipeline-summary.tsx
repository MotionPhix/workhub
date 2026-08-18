import { Card } from '@/components/ui/card';
import { EmptyState } from '@/components/empty-state';
import { GitBranch } from 'lucide-react';
import type { DashboardPipelineStage } from '@/types';

interface PipelineSummaryProps {
    stages: DashboardPipelineStage[];
}

const STAGE_LABELS: Record<string, string> = {
    new: 'Lead',
    contacted: 'Contacted',
    interested: 'Interested',
    proposal: 'Proposal',
    negotiation: 'Negotiation',
    won: 'Won',
    lost: 'Lost',
};

const STAGE_COLORS: Record<string, string> = {
    new: 'bg-sky-500',
    contacted: 'bg-blue-500',
    interested: 'bg-violet-500',
    proposal: 'bg-amber-500',
    negotiation: 'bg-orange-500',
    won: 'bg-emerald-500',
    lost: 'bg-red-400',
};

function formatAmount(amount: number): string {
    return new Intl.NumberFormat('en-MW', {
        style: 'currency',
        currency: 'MWK',
        notation: 'compact',
        maximumFractionDigits: 1,
    }).format(amount);
}

export function PipelineSummary({ stages }: PipelineSummaryProps) {
    const active = stages.filter((stage) => stage.stage !== 'lost');

    if (active.length === 0) {
        return (
            <EmptyState
                icon={GitBranch}
                title="No pipeline yet"
                description="Opportunities you create will appear here."
            />
        );
    }

    const totalValue = active.reduce((sum, s) => sum + s.value, 0);

    return (
        <div>
            <div className="mb-3 flex items-center justify-between">
                <h3 className="text-sm font-semibold">Pipeline</h3>
                <span className="text-xs font-medium tabular-nums text-muted-foreground">
                    {formatAmount(totalValue)}
                </span>
            </div>

            <div className="space-y-1.5">
                {active.map((stage) => {
                    const pct = totalValue > 0 ? (stage.value / totalValue) * 100 : 0;

                    return (
                        <div key={stage.stage} className="group">
                            <div className="flex items-center justify-between py-1">
                                <div className="flex items-center gap-2">
                                    <span className={`size-1.5 rounded-full ${STAGE_COLORS[stage.stage] ?? 'bg-muted-foreground/40'}`} />
                                    <span className="text-xs text-muted-foreground group-hover:text-foreground transition-colors">
                                        {STAGE_LABELS[stage.stage] ?? stage.stage}
                                    </span>
                                </div>
                                <div className="flex items-center gap-2">
                                    <span className="text-xs tabular-nums text-muted-foreground">
                                        {stage.count}
                                    </span>
                                    <span className="w-16 text-right text-xs font-medium tabular-nums">
                                        {formatAmount(stage.value)}
                                    </span>
                                </div>
                            </div>
                            <div className="h-1 w-full overflow-hidden rounded-full bg-muted">
                                <div
                                    className={`h-full rounded-full transition-all ${STAGE_COLORS[stage.stage] ?? 'bg-muted-foreground/40'}`}
                                    style={{ width: `${pct}%` }}
                                />
                            </div>
                        </div>
                    );
                })}
            </div>
        </div>
    );
}
