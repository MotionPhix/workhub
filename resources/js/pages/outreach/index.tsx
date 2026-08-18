import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import outreach from '@/routes/outreach';
import { Card } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import Heading from '@/components/heading';
import { EmptyState } from '@/components/empty-state';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { ContactStatusBadge } from '@/components/status-badge';
import { TemperatureBadge } from '@/components/temperature-badge';
import { Progress } from '@/components/ui/progress';
import { DraftEmailDrawer } from '@/components/outreach/draft-email-drawer';
import { toast } from 'sonner';
import {
    Send, CheckCircle2, SkipForward, Clock, Mail, Zap,
} from 'lucide-react';
import type { OutreachAssignment } from '@/types';

interface OutreachIndexProps {
    cycle: {
        id: string;
        name: string;
        starts_on: string;
        status: string;
        assigned: number;
        contacted: number;
        completion: number;
    } | null;
    assignments: OutreachAssignment[];
    templates: Array<{
        id: string;
        name: string;
        category: string;
        subject: string;
    }>;
    today: string;
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

export default function OutreachIndex({
    cycle,
    assignments,
    templates,
}: OutreachIndexProps) {
    const [draftingAssignment, setDraftingAssignment] = useState<OutreachAssignment | null>(null);

    const pending = assignments.filter((a) => a.status === 'pending');
    const snoozed = assignments.filter((a) => a.status === 'snoozed');
    const completed = assignments.filter((a) => ['contacted', 'skipped'].includes(a.status));

    const markContacted = (assignment: OutreachAssignment) => {
        router.post(
            outreach.markContacted(assignment.id),
            {},
            {
                preserveScroll: true,
                onSuccess: () => {
                    toast.success(`${assignment.contact?.full_name ?? 'Contact'} marked as contacted`);
                },
            },
        );
    };

    const skip = (assignment: OutreachAssignment) => {
        router.post(
            outreach.skip(assignment.id),
            {},
            {
                preserveScroll: true,
                onSuccess: () => {
                    toast.success(`Skipped ${assignment.contact?.full_name ?? 'contact'}`);
                },
            },
        );
    };

    const openDraft = (assignment: OutreachAssignment) => {
        setDraftingAssignment(assignment);
    };

    return (
        <>
            <Head title="Outreach Queue" />

            <div className="space-y-6">
                <header>
                    <Heading
                        title="Outreach Queue"
                        description={
                            cycle
                                ? `${cycle.name} • ${cycle.contacted}/${cycle.assigned} contacted`
                                : 'Your ranked outreach assignments'
                        }
                    />
                </header>

                {cycle && (
                    <Card className="p-4">
                        <div className="flex items-center justify-between">
                            <div className="flex items-center gap-3">
                                <div className="flex size-10 items-center justify-center rounded-lg bg-primary/10">
                                    <Zap className="size-5 text-primary" />
                                </div>
                                <div>
                                    <p className="font-semibold">{cycle.name}</p>
                                    <p className="text-sm text-muted-foreground">
                                        {cycle.contacted} of {cycle.assigned} contacted
                                    </p>
                                </div>
                            </div>
                            <div className="text-right">
                                <p className="text-2xl font-bold tabular-nums">{cycle.completion}%</p>
                                <p className="text-xs text-muted-foreground">complete</p>
                            </div>
                        </div>
                        <Progress value={cycle.completion} className="mt-3" />
                    </Card>
                )}

                {!cycle && assignments.length === 0 && (
                    <EmptyState
                        icon={Send}
                        title="No active outreach cycle"
                        description="Create an outreach cycle to start reaching out to prospects."
                    />
                )}

                {pending.length > 0 && (
                    <section>
                        <div className="mb-3 flex items-center justify-between">
                            <h2 className="text-sm font-semibold">Pending ({pending.length})</h2>
                        </div>
                        <div className="space-y-3">
                            {pending.map((assignment) => {
                                const contact = assignment.contact;
                                const company = contact?.company;
                                const needsContact = contact && ['new', 'researching', 'ready_to_contact'].includes(contact.status);

                                return (
                                    <Card key={assignment.id} className="overflow-hidden">
                                        <div className="flex items-stretch">
                                            <div
                                                className={
                                                    contact?.temperature === 'hot'
                                                        ? 'w-1 shrink-0 bg-orange-500'
                                                        : contact?.temperature === 'warm'
                                                          ? 'w-1 shrink-0 bg-amber-400'
                                                          : contact?.temperature === 'cold'
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
                                                        <p className="mt-1 text-sm italic text-muted-foreground">
                                                            {assignment.suggested_angle}
                                                        </p>
                                                    )}

                                                    {assignment.reasons && assignment.reasons.length > 0 && (
                                                        <ul className="mt-2 space-y-0.5">
                                                            {assignment.reasons.slice(0, 3).map((reason, i) => (
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
                                                        <Button size="sm" onClick={() => openDraft(assignment)}>
                                                            <Mail />
                                                            Draft Email
                                                        </Button>
                                                        {needsContact && (
                                                            <Button
                                                                size="sm"
                                                                variant="outline"
                                                                onClick={() => markContacted(assignment)}
                                                            >
                                                                <CheckCircle2 />
                                                                Mark Contacted
                                                            </Button>
                                                        )}
                                                        <Button
                                                            size="sm"
                                                            variant="ghost"
                                                            onClick={() => skip(assignment)}
                                                        >
                                                            <SkipForward />
                                                            Skip
                                                        </Button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </Card>
                                );
                            })}
                        </div>
                    </section>
                )}

                {snoozed.length > 0 && (
                    <section>
                        <div className="mb-3 flex items-center gap-2">
                            <Clock className="size-4 text-muted-foreground" />
                            <h2 className="text-sm font-semibold">Snoozed ({snoozed.length})</h2>
                        </div>
                        <div className="space-y-2">
                            {snoozed.map((assignment) => {
                                const contact = assignment.contact;

                                return (
                                    <Card key={assignment.id} className="flex items-center gap-3 p-3 opacity-60">
                                        <Avatar className="size-8">
                                            <AvatarFallback className="text-xs">
                                                {initials(contact?.full_name)}
                                            </AvatarFallback>
                                        </Avatar>
                                        <div className="min-w-0 flex-1">
                                            <p className="truncate text-sm font-medium">
                                                {contact?.full_name ?? 'Contact'}
                                            </p>
                                            <p className="truncate text-xs text-muted-foreground">
                                                Snoozed until {assignment.snoozed_until}
                                            </p>
                                        </div>
                                    </Card>
                                );
                            })}
                        </div>
                    </section>
                )}

                {completed.length > 0 && (
                    <section>
                        <div className="mb-3 flex items-center gap-2">
                            <CheckCircle2 className="size-4 text-emerald-600" />
                            <h2 className="text-sm font-semibold">Completed ({completed.length})</h2>
                        </div>
                        <div className="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                            {completed.slice(0, 12).map((assignment) => {
                                const contact = assignment.contact;

                                return (
                                    <Card key={assignment.id} className="flex items-center gap-2 p-2 opacity-50">
                                        <Avatar className="size-7">
                                            <AvatarFallback className="text-xs">
                                                {initials(contact?.full_name)}
                                            </AvatarFallback>
                                        </Avatar>
                                        <p className="min-w-0 flex-1 truncate text-xs font-medium">
                                            {contact?.full_name ?? 'Contact'}
                                        </p>
                                    </Card>
                                );
                            })}
                        </div>
                    </section>
                )}
            </div>

            {draftingAssignment && (
                <DraftEmailDrawer
                    assignment={draftingAssignment}
                    templates={templates}
                    open={!!draftingAssignment}
                    onClose={() => setDraftingAssignment(null)}
                />
            )}
        </>
    );
}

OutreachIndex.layout = {
    breadcrumbs: [
        { title: 'Outreach', href: outreach.index() },
    ],
};
