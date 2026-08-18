import { Head, Link } from '@inertiajs/react';
import companies from '@/routes/companies';
import prospects from '@/routes/prospects';
import { Card } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import Heading from '@/components/heading';
import { EmptyState } from '@/components/empty-state';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { ContactStatusBadge } from '@/components/status-badge';
import { TemperatureBadge } from '@/components/temperature-badge';
import { ArrowLeft, Briefcase, Globe, MapPin, Users } from 'lucide-react';
import type { Company, Contact, Opportunity } from '@/types';

interface CompanyShowProps {
    company: Company;
}

const currency = new Intl.NumberFormat('en-MW', {
    style: 'currency',
    currency: 'MWK',
    maximumFractionDigits: 0,
});

function initials(name: string): string {
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

export default function CompanyShow({ company }: CompanyShowProps) {
    const contacts = company.contacts ?? [];
    const opportunities = company.opportunities ?? [];

    return (
        <>
            <Head title={company.name} />

            <div className="space-y-6">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div className="flex items-center gap-3">
                        <Button asChild variant="ghost" size="icon">
                            <Link href={companies.index()}>
                                <ArrowLeft />
                            </Link>
                        </Button>
                        <Avatar className="size-12">
                            <AvatarFallback className="text-base">
                                {initials(company.name)}
                            </AvatarFallback>
                        </Avatar>
                        <div>
                            <h1 className="text-2xl font-semibold tracking-tight">
                                {company.name}
                            </h1>
                            <p className="text-sm text-muted-foreground">
                                {company.industry ?? 'Unknown industry'}
                                {company.location && ` · ${company.location}`}
                            </p>
                        </div>
                    </div>
                    <Button asChild variant="outline">
                        <Link href={companies.edit(company.id)}>Edit</Link>
                    </Button>
                </div>

                <div className="grid gap-4 lg:grid-cols-3">
                    <Card className="p-5 lg:col-span-2">
                        <Heading title="Details" variant="small" />
                        <div className="divide-y divide-border">
                            <DetailRow label="Website">
                                {company.website ? (
                                    <a
                                        href={company.website}
                                        target="_blank"
                                        rel="noreferrer"
                                        className="inline-flex items-center gap-1 text-primary hover:underline"
                                    >
                                        <Globe className="size-3.5" />
                                        {company.website}
                                    </a>
                                ) : '—'}
                            </DetailRow>
                            <DetailRow label="Email">{company.email ?? '—'}</DetailRow>
                            <DetailRow label="Phone">{company.phone ?? '—'}</DetailRow>
                            <DetailRow label="Location">
                                {company.location ? (
                                    <span className="inline-flex items-center gap-1">
                                        <MapPin className="size-3.5" />
                                        {company.location}
                                    </span>
                                ) : '—'}
                            </DetailRow>
                            <DetailRow label="Type">
                                {company.organization_type
                                    ? <span className="capitalize">{company.organization_type.replace(/_/g, ' ')}</span>
                                    : '—'}
                            </DetailRow>
                            <DetailRow label="Size">
                                {company.size ? <span className="capitalize">{company.size}</span> : '—'}
                            </DetailRow>
                            <DetailRow label="Lead source">
                                {company.lead_source
                                    ? <span className="capitalize">{company.lead_source.replace(/_/g, ' ')}</span>
                                    : '—'}
                            </DetailRow>
                            <DetailRow label="Estimated budget">
                                {company.estimated_budget
                                    ? currency.format(company.estimated_budget)
                                    : '—'}
                            </DetailRow>
                        </div>
                    </Card>

                    <div className="space-y-4">
                        {company.description && (
                            <Card className="p-5">
                                <Heading title="About" variant="small" />
                                <p className="whitespace-pre-wrap text-sm text-muted-foreground">
                                    {company.description}
                                </p>
                            </Card>
                        )}
                        {company.what_they_do && (
                            <Card className="p-5">
                                <Heading title="What they do" variant="small" />
                                <p className="whitespace-pre-wrap text-sm text-muted-foreground">
                                    {company.what_they_do}
                                </p>
                            </Card>
                        )}
                        {company.notes && (
                            <Card className="p-5">
                                <Heading title="Notes" variant="small" />
                                <p className="whitespace-pre-wrap text-sm text-muted-foreground">
                                    {company.notes}
                                </p>
                            </Card>
                        )}
                    </div>
                </div>

                <section className="space-y-3">
                    <Heading title="Contacts" variant="small" />
                    {contacts.length === 0 ? (
                        <EmptyState
                            icon={Users}
                            title="No contacts"
                            description="Add a contact to start outreach with this company."
                        />
                    ) : (
                        <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            {contacts.map((contact: Contact) => (
                                <Link key={contact.id} href={prospects.show(contact.id)} className="block">
                                    <Card className="flex items-center gap-3 p-4 transition-colors hover:bg-muted/50">
                                        <Avatar className="size-9">
                                            <AvatarFallback>
                                                {initials(contact.full_name)}
                                            </AvatarFallback>
                                        </Avatar>
                                        <div className="min-w-0 flex-1">
                                            <p className="truncate text-sm font-medium">
                                                {contact.full_name}
                                            </p>
                                            <p className="truncate text-xs text-muted-foreground">
                                                {contact.job_title ?? contact.email ?? '—'}
                                            </p>
                                        </div>
                                        <div className="flex shrink-0 flex-col items-end gap-1">
                                            <ContactStatusBadge status={contact.status} />
                                            <TemperatureBadge temperature={contact.temperature} />
                                        </div>
                                    </Card>
                                </Link>
                            ))}
                        </div>
                    )}
                </section>

                <section className="space-y-3">
                    <Heading title="Opportunities" variant="small" />
                    {opportunities.length === 0 ? (
                        <EmptyState
                            icon={Briefcase}
                            title="No opportunities"
                            description="Opportunities for this company will appear here."
                        />
                    ) : (
                        <div className="space-y-2">
                            {opportunities.map((opp: Opportunity) => (
                                <Card key={opp.id} className="flex items-center justify-between p-4">
                                    <div>
                                        <p className="font-medium">{opp.title}</p>
                                        <p className="text-xs capitalize text-muted-foreground">
                                            {opp.stage.replace(/_/g, ' ')}
                                        </p>
                                    </div>
                                    {opp.value != null && (
                                        <span className="text-sm font-medium tabular-nums">
                                            {currency.format(opp.value)}
                                        </span>
                                    )}
                                </Card>
                            ))}
                        </div>
                    )}
                </section>
            </div>
        </>
    );
}

CompanyShow.layout = {
    breadcrumbs: [{ title: 'Companies', href: companies.index() }],
};
