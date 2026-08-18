import { Head, Link } from '@inertiajs/react';
import prospects from '@/routes/prospects';
import { Card } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import Heading from '@/components/heading';
import { EmptyState } from '@/components/empty-state';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { ContactStatusBadge } from '@/components/status-badge';
import { TemperatureBadge } from '@/components/temperature-badge';
import { ActivityTimeline } from '@/components/activity-timeline';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import {
    ArrowLeft, Mail, Building2, Phone, Globe, Linkedin,
    CalendarClock, Briefcase,
} from 'lucide-react';
import type { Contact, EmailMessage, Opportunity, Activity } from '@/types';

interface ProspectShowProps {
    contact: Contact;
    tags: { id: string; name: string; color: string | null }[];
    services: { id: string; name: string }[];
}

const currency = new Intl.NumberFormat('en-MW', {
    style: 'currency',
    currency: 'MWK',
    maximumFractionDigits: 0,
});

function initials(name: string | null | undefined): string {
    if (!name) {
        return '?';
    }

    return name.split(' ').filter(Boolean).slice(0, 2).map((p) => p[0]?.toUpperCase()).join('');
}

function DetailRow({ label, children }: { label: string; children: React.ReactNode }) {
    return (
        <div className="flex items-center justify-between gap-4 py-2 text-sm">
            <span className="text-muted-foreground">{label}</span>
            <span className="text-right font-medium">{children}</span>
        </div>
    );
}

export default function ProspectShow({ contact, tags, services }: ProspectShowProps) {
    const emails = contact.latest_email_message ? [contact.latest_email_message] : [];
    const opportunities = (contact as Contact & { opportunities?: Opportunity[] }).opportunities ?? [];
    const activities = (contact as Contact & { activities?: Activity[] }).activities ?? [];

    return (
        <>
            <Head title={contact.full_name} />

            <div className="space-y-6">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div className="flex items-center gap-3">
                        <Button asChild variant="ghost" size="icon">
                            <Link href={prospects.index()}>
                                <ArrowLeft />
                            </Link>
                        </Button>
                        <Avatar className="size-12">
                            <AvatarFallback className="text-base">
                                {initials(contact.company?.name ?? contact.full_name)}
                            </AvatarFallback>
                        </Avatar>
                        <div>
                            <h1 className="text-2xl font-semibold tracking-tight">
                                {contact.full_name}
                            </h1>
                            <p className="text-sm text-muted-foreground">
                                {contact.job_title ?? 'No title'}
                                {contact.company && ` · ${contact.company.name}`}
                            </p>
                        </div>
                    </div>
                    <div className="flex items-center gap-2">
                        <ContactStatusBadge status={contact.status} />
                        <TemperatureBadge temperature={contact.temperature} />
                    </div>
                </div>

                <Tabs defaultValue="overview">
                    <TabsList>
                        <TabsTrigger value="overview">Overview</TabsTrigger>
                        <TabsTrigger value="emails">Emails</TabsTrigger>
                        <TabsTrigger value="activities">Activity</TabsTrigger>
                        <TabsTrigger value="proposals">Proposals</TabsTrigger>
                    </TabsList>

                    <TabsContent value="overview" className="space-y-4">
                        <div className="grid gap-4 lg:grid-cols-3">
                            <Card className="p-5 lg:col-span-2">
                                <Heading title="Details" variant="small" />
                                <div className="divide-y divide-border">
                                    <DetailRow label="Email">
                                        {contact.email ?? '—'}
                                    </DetailRow>
                                    <DetailRow label="Phone">
                                        {contact.phone ?? '—'}
                                    </DetailRow>
                                    <DetailRow label="Company">
                                        {contact.company?.name ?? '—'}
                                    </DetailRow>
                                    <DetailRow label="Lead source">
                                        {contact.lead_source ?? '—'}
                                    </DetailRow>
                                    <DetailRow label="Relationship">
                                        {contact.relationship_stage.replace(/_/g, ' ')}
                                    </DetailRow>
                                    <DetailRow label="Lead score">
                                        {contact.lead_score}
                                    </DetailRow>
                                    <DetailRow label="Estimated budget">
                                        {contact.estimated_budget
                                            ? currency.format(contact.estimated_budget)
                                            : '—'}
                                    </DetailRow>
                                    <DetailRow label="Outreach count">
                                        {contact.outreach_count}
                                    </DetailRow>
                                </div>
                            </Card>

                            <div className="space-y-4">
                                <Card className="p-5">
                                    <Heading title="Tags" variant="small" />
                                    {tags.length === 0 ? (
                                        <p className="text-sm text-muted-foreground">
                                            No tags
                                        </p>
                                    ) : (
                                        <div className="flex flex-wrap gap-2">
                                            {tags.map((tag) => (
                                                <span
                                                    key={tag.id}
                                                    className="rounded-full border border-border px-2.5 py-0.5 text-xs font-medium"
                                                >
                                                    {tag.name}
                                                </span>
                                            ))}
                                        </div>
                                    )}
                                </Card>

                                <Card className="p-5">
                                    <Heading title="Services" variant="small" />
                                    {contact.services && contact.services.length > 0 ? (
                                        <ul className="space-y-1 text-sm">
                                            {contact.services.map((service) => (
                                                <li key={service.id} className="flex items-center gap-2">
                                                    <Briefcase className="size-3.5 text-muted-foreground" />
                                                    {service.name}
                                                </li>
                                            ))}
                                        </ul>
                                    ) : (
                                        <p className="text-sm text-muted-foreground">
                                            No services linked
                                        </p>
                                    )}
                                </Card>
                            </div>
                        </div>

                        {contact.notes && (
                            <Card className="p-5">
                                <Heading title="Notes" variant="small" />
                                <p className="whitespace-pre-wrap text-sm text-muted-foreground">
                                    {contact.notes}
                                </p>
                            </Card>
                        )}
                    </TabsContent>

                    <TabsContent value="emails" className="space-y-3">
                        {emails.length === 0 ? (
                            <EmptyState
                                icon={Mail}
                                title="No emails yet"
                                description="Draft your first outreach email to this prospect."
                            />
                        ) : (
                            emails.map((email: EmailMessage) => (
                                <Card key={email.id} className="p-5">
                                    <div className="flex items-center justify-between">
                                        <p className="font-medium">{email.subject}</p>
                                        <span className="text-xs capitalize text-muted-foreground">
                                            {email.direction} · {email.status}
                                        </span>
                                    </div>
                                    <p className="mt-2 whitespace-pre-wrap text-sm text-muted-foreground">
                                        {email.body}
                                    </p>
                                </Card>
                            ))
                        )}
                    </TabsContent>

                    <TabsContent value="activities">
                        <Card className="p-5">
                            <ActivityTimeline activities={activities} />
                        </Card>
                    </TabsContent>

                    <TabsContent value="proposals" className="space-y-3">
                        {opportunities.length === 0 ? (
                            <EmptyState
                                icon={Building2}
                                title="No opportunities"
                                description="Opportunities and proposals for this prospect will appear here."
                            />
                        ) : (
                            opportunities.map((opp) => (
                                <Card key={opp.id} className="p-5">
                                    <div className="flex items-center justify-between">
                                        <p className="font-medium">{opp.title}</p>
                                        <span className="text-xs capitalize text-muted-foreground">
                                            {opp.stage.replace(/_/g, ' ')}
                                        </span>
                                    </div>
                                    {opp.value != null && (
                                        <p className="mt-1 text-sm text-muted-foreground">
                                            {currency.format(opp.value)}
                                        </p>
                                    )}
                                </Card>
                            ))
                        )}
                    </TabsContent>
                </Tabs>
            </div>
        </>
    );
}

ProspectShow.layout = {
    breadcrumbs: [
        { title: 'Prospects', href: prospects.index() },
    ],
};
