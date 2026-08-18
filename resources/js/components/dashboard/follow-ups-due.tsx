import { Card } from '@/components/ui/card';
import { EmptyState } from '@/components/empty-state';
import followUpsRoute from '@/routes/follow-ups';
import { Link } from '@inertiajs/react';
import { CalendarClock } from 'lucide-react';
import type { DashboardFollowUp } from '@/types';

interface FollowUpsDueProps {
    followUps: DashboardFollowUp[];
}

export function FollowUpsDue({ followUps }: FollowUpsDueProps) {
    if (followUps.length === 0) {
        return (
            <EmptyState
                icon={CalendarClock}
                title="All caught up"
                description="No follow-ups are due in the next 7 days."
            />
        );
    }

    const overdue = followUps.filter((item) => item.is_overdue).length;
    const dueToday = followUps.filter((item) => item.is_due_today).length;

    return (
        <div className="rounded-lg border border-border/60">
            {/* Summary strip */}
            <div className="flex items-center gap-4 border-b border-border/60 px-4 py-2.5 text-xs">
                {overdue > 0 && (
                    <span className="font-medium text-red-600 dark:text-red-400">
                        {overdue} overdue
                    </span>
                )}
                {dueToday > 0 && (
                    <span className="font-medium text-amber-600 dark:text-amber-400">
                        {dueToday} due today
                    </span>
                )}
                {!overdue && !dueToday && (
                    <span className="text-muted-foreground">All on track</span>
                )}
            </div>

            {/* List */}
            <ul className="divide-y divide-border/60">
                {followUps.slice(0, 6).map((item) => (
                    <li key={item.id}>
                        <Link
                            href={followUpsRoute.index()}
                            className="flex items-center gap-3 px-4 py-2.5 transition-colors hover:bg-muted/40"
                        >
                            <span
                                className={
                                    item.is_overdue
                                        ? 'size-1.5 shrink-0 rounded-full bg-red-500'
                                        : item.is_due_today
                                          ? 'size-1.5 shrink-0 rounded-full bg-amber-500'
                                          : 'size-1.5 shrink-0 rounded-full bg-muted-foreground/30'
                                }
                            />
                            <div className="min-w-0 flex-1">
                                <p className="truncate text-sm font-medium">
                                    {item.contact.full_name}
                                </p>
                                <p className="truncate text-xs capitalize text-muted-foreground">
                                    {item.type.replace('_', ' ')}
                                </p>
                            </div>
                            <span className="shrink-0 text-xs tabular-nums text-muted-foreground">
                                {item.due_on}
                            </span>
                        </Link>
                    </li>
                ))}
            </ul>
        </div>
    );
}
