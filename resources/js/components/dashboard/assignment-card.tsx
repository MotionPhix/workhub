import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { ContactStatusBadge } from '@/components/status-badge';
import { TemperatureBadge } from '@/components/temperature-badge';
import outreach from '@/routes/outreach';
import { router } from '@inertiajs/react';
import { toast } from 'sonner';
import { Mail, Clock, SkipForward, CheckCircle2 } from 'lucide-react';
import type { OutreachAssignment } from '@/types';

interface AssignmentCardProps {
    assignment: OutreachAssignment;
}

function initials(name: string | null | undefined): string {
    if (!name) {
        return '?';
    }

    return name
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase())
        .join('');
}

function daysAgo(date: string | null): string {
    if (!date) {
        return 'Never contacted';
    }

    const diff = Math.floor(
        (Date.now() - new Date(date).getTime()) / 86_400_000,
    );

    if (diff <= 0) {
        return 'Contacted today';
    }

    if (diff === 1) {
        return '1 day ago';
    }

    return `${diff} days ago`;
}

export function AssignmentCard({ assignment }: AssignmentCardProps) {
    const contact = assignment.contact;
    const company = contact?.company;
    const isSnoozed = assignment.status === 'snoozed';

    // Only show "Mark Contacted" for contacts that haven't been contacted yet
    const needsContact = contact && ['new', 'researching', 'ready_to_contact'].includes(contact.status);

    const markContacted = () => {
        router.post(
            outreach.markContacted(assignment.id),
            {},
            {
                preserveScroll: true,
                onSuccess: () => {
                    toast.success(`${contact?.full_name ?? 'Contact'} marked as contacted`);
                },
            },
        );
    };

    const skip = () => {
        router.post(outreach.skip(assignment.id), {}, {
            preserveScroll: true,
            onSuccess: () => {
                toast.success(`Skipped ${contact?.full_name ?? 'contact'} for this cycle`);
            },
        });
    };

    return (
        <Card className="overflow-hidden">
            <div className="flex items-stretch">
                {/* Left accent stripe based on temperature */}
                <div
                    className={
                        assignment.contact?.temperature === 'hot'
                            ? 'w-1 shrink-0 bg-orange-500'
                            : assignment.contact?.temperature === 'warm'
                              ? 'w-1 shrink-0 bg-amber-400'
                              : assignment.contact?.temperature === 'cold'
                                ? 'w-1 shrink-0 bg-blue-400'
                                : 'w-1 shrink-0 bg-muted-foreground/20'
                    }
                />

                <div className="flex flex-1 items-start gap-3 p-4">
                    <Avatar className="mt-0.5 size-10">
                        <AvatarFallback>
                            {initials(company?.name ?? contact?.full_name)}
                        </AvatarFallback>
                    </Avatar>

                    <div className="min-w-0 flex-1">
                        <div className="flex items-center gap-2">
                            <p className="truncate font-medium">
                                {company?.name ?? contact?.full_name ?? 'Prospect'}
                            </p>
                            {contact && <ContactStatusBadge status={contact.status} />}
                        </div>

                        <p className="text-sm text-muted-foreground">
                            {contact?.full_name ?? 'Unknown contact'}
                            {contact?.job_title ? ` · ${contact.job_title}` : ''}
                        </p>

                        <div className="mt-1.5 flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
                            {contact && <TemperatureBadge temperature={contact.temperature} />}
                            <span>{daysAgo(contact?.last_contacted_at ?? null)}</span>
                        </div>

                        {assignment.suggested_service && (
                            <p className="mt-2 text-sm">
                                <span className="font-medium">
                                    {assignment.suggested_service.name}
                                </span>
                            </p>
                        )}

                        {assignment.suggested_angle && (
                            <p className="mt-1 text-sm text-muted-foreground italic">
                                {assignment.suggested_angle}
                            </p>
                        )}

                        {assignment.reasons && assignment.reasons.length > 0 && (
                            <ul className="mt-2 space-y-0.5">
                                {assignment.reasons.slice(0, 2).map((reason, i) => (
                                    <li
                                        key={i}
                                        className="flex items-start gap-1.5 text-xs text-muted-foreground"
                                    >
                                        <span className="mt-1 size-1 shrink-0 rounded-full bg-muted-foreground/40" />
                                        {reason}
                                    </li>
                                ))}
                            </ul>
                        )}

                        <div className="mt-3 flex items-center gap-2">
                            {needsContact && (
                                <Button size="sm" onClick={markContacted}>
                                    <CheckCircle2 />
                                    Mark Contacted
                                </Button>
                            )}
                            <Button
                                size="sm"
                                variant="ghost"
                                onClick={skip}
                            >
                                {isSnoozed ? (
                                    <>
                                        <Clock />
                                        Snoozed
                                    </>
                                ) : (
                                    <>
                                        <SkipForward />
                                        Skip
                                    </>
                                )}
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </Card>
    );
}
