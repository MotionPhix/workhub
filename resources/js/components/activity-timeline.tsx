import { cn } from '@/lib/utils';
import type { Activity, ActivityType } from '@/types';

const ACTIVITY_ICONS: Partial<Record<ActivityType, string>> = {
    email_sent: '✉️',
    email_opened: '👁️',
    email_replied: '💬',
    email_drafted: '📝',
    follow_up_completed: '☑️',
    contact_created: '➕',
    opportunity_stage_changed: '🔀',
    proposal_sent: '📄',
    proposal_accepted: '✅',
    proposal_rejected: '❌',
    client_converted: '🎉',
};

function formatOccurred(at: string): string {
    return new Date(at).toLocaleDateString(undefined, {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
}

interface ActivityTimelineProps {
    activities: Activity[];
}

export function ActivityTimeline({ activities }: ActivityTimelineProps) {
    if (activities.length === 0) {
        return (
            <p className="text-sm text-muted-foreground">No activity recorded yet.</p>
        );
    }

    return (
        <ol className="relative space-y-4 border-l border-border pl-6">
            {activities.map((activity) => (
                <li key={activity.id} className="relative">
                    <span
                        className={cn(
                            'absolute -left-[31px] flex size-6 items-center justify-center rounded-full border border-border bg-background text-xs',
                        )}
                        aria-hidden
                    >
                        {ACTIVITY_ICONS[activity.type] ?? '•'}
                    </span>
                    <div className="space-y-0.5">
                        <p className="text-sm font-medium capitalize">
                            {activity.type.replace(/_/g, ' ')}
                        </p>
                        {activity.description && (
                            <p className="text-sm text-muted-foreground">
                                {activity.description}
                            </p>
                        )}
                        <p className="text-xs text-muted-foreground">
                            {formatOccurred(activity.occurred_at)}
                        </p>
                    </div>
                </li>
            ))}
        </ol>
    );
}
