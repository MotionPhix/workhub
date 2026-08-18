import { EmptyState } from '@/components/empty-state';
import { TemperatureBadge } from '@/components/temperature-badge';
import prospectsRoute from '@/routes/prospects';
import { Link } from '@inertiajs/react';
import { UsersRound } from 'lucide-react';
import type { Contact } from '@/types';

interface NextBestProspectsProps {
    prospects: Contact[];
}

function budget(value: number | null): string | null {
    if (value === null) {
        return null;
    }

    return new Intl.NumberFormat('en-MW', {
        style: 'currency',
        currency: 'MWK',
        maximumFractionDigits: 0,
    }).format(value);
}

export function NextBestProspects({ prospects }: NextBestProspectsProps) {
    if (prospects.length === 0) {
        return (
            <EmptyState
                icon={UsersRound}
                title="No ranked prospects yet"
                description="Contacts become ranked once they're researchable."
            />
        );
    }

    return (
        <div>
            <div className="mb-2 flex items-center justify-between">
                <h3 className="text-sm font-semibold">Next Best Prospects</h3>
                <span className="text-xs text-muted-foreground">by lead score</span>
            </div>

            <div className="rounded-lg border border-border/60 divide-y divide-border/60">
                {prospects.map((contact, index) => (
                    <Link
                        key={contact.id}
                        href={prospectsRoute.show(contact.id)}
                        className="flex items-center gap-3 px-3 py-2.5 transition-colors hover:bg-muted/40"
                    >
                        <span className="w-4 shrink-0 text-center text-xs font-semibold tabular-nums text-muted-foreground">
                            {index + 1}
                        </span>
                        <div className="min-w-0 flex-1">
                            <div className="flex items-center justify-between gap-2">
                                <p className="truncate text-sm font-medium">
                                    {contact.full_name}
                                </p>
                                <TemperatureBadge temperature={contact.temperature} />
                            </div>
                            <p className="truncate text-xs text-muted-foreground">
                                {contact.company?.name ?? contact.job_title ?? 'No company'}
                                {budget(contact.estimated_budget) ? (
                                    <> · {budget(contact.estimated_budget)}</>
                                ) : null}
                            </p>
                        </div>
                        <span className="shrink-0 text-sm font-bold tabular-nums text-primary">
                            {contact.lead_score}
                        </span>
                    </Link>
                ))}
            </div>
        </div>
    );
}
