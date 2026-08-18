import { Head, Link, router } from '@inertiajs/react';
import prospects from '@/routes/prospects';
import { Button } from '@/components/ui/button';
import Heading from '@/components/heading';
import { EmptyState } from '@/components/empty-state';
import { ContactStatusBadge } from '@/components/status-badge';
import { TemperatureBadge } from '@/components/temperature-badge';
import { DataTable, type ColumnDef } from '@/components/data-table';
import { ProspectFilters, type ProspectFiltersValue } from '@/components/prospect-filters';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Plus, UsersRound } from 'lucide-react';
import { useDebouncedFilter } from '@/hooks/use-debounced-filter';
import type { Company, Contact, ContactStatus, LeadTemperature, Paginated, Tag } from '@/types';

interface ProspectsIndexProps {
    contacts: Paginated<Contact>;
    filters: {
        search: string | null;
        status: ContactStatus | null;
        temperature: LeadTemperature | null;
        company_id: string | null;
    };
    companies: Pick<Company, 'id' | 'name'>[];
    tags: Tag[];
    services: { id: string; name: string }[];
}

function initials(name: string | null | undefined): string {
    if (!name) {
        return '?';
    }

    return name.split(' ').filter(Boolean).slice(0, 2).map((p) => p[0]?.toUpperCase()).join('');
}

export default function ProspectsIndex({
    contacts,
    filters,
    companies,
}: ProspectsIndexProps) {
    const current: ProspectFiltersValue = {
        search: filters.search,
        status: filters.status,
        temperature: filters.temperature,
        company_id: filters.company_id,
    };

    const applyFilters = useDebouncedFilter(prospects.index().url, 250);

    const columns: ColumnDef<Contact>[] = [
        {
            key: 'name',
            header: 'Name',
            cell: (contact) => (
                <div className="flex items-center gap-3">
                    <Avatar className="size-8">
                        <AvatarFallback>
                            {initials(contact.company?.name ?? contact.full_name)}
                        </AvatarFallback>
                    </Avatar>
                    <div className="min-w-0">
                        <p className="truncate font-medium">{contact.full_name}</p>
                        {contact.job_title && (
                            <p className="truncate text-xs text-muted-foreground">
                                {contact.job_title}
                            </p>
                        )}
                    </div>
                </div>
            ),
        },
        {
            key: 'company',
            header: 'Company',
            cell: (contact) => contact.company?.name ?? '—',
            hideOnMobile: true,
        },
        {
            key: 'status',
            header: 'Status',
            cell: (contact) => <ContactStatusBadge status={contact.status} />,
        },
        {
            key: 'temperature',
            header: 'Temp',
            cell: (contact) => <TemperatureBadge temperature={contact.temperature} />,
            hideOnMobile: true,
        },
        {
            key: 'score',
            header: 'Score',
            cell: (contact) => (
                <span className="font-semibold tabular-nums">{contact.lead_score}</span>
            ),
            hideOnMobile: true,
        },
        {
            key: 'last_contacted',
            header: 'Last contact',
            cell: (contact) =>
                contact.last_contacted_at
                    ? new Date(contact.last_contacted_at).toLocaleDateString()
                    : 'Never',
            hideOnMobile: true,
        },
    ];

    return (
        <>
            <Head title="Prospects" />

            <div className="space-y-6">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <Heading title="Prospects" description={`${contacts.total} contacts`} />
                    <div className="flex items-center gap-2">
                        <ProspectFilters
                            value={current}
                            companies={companies}
                            onChange={(value) => applyFilters({ ...value })}
                        />
                        <Button asChild>
                            <Link href={prospects.create()}>
                                <Plus />
                                New Prospect
                            </Link>
                        </Button>
                    </div>
                </div>

                <DataTable
                    columns={columns}
                    rows={contacts.data}
                    rowKey={(contact) => contact.id}
                    onRowClick={(contact) => router.visit(prospects.show(contact.id))}
                    empty={
                        <EmptyState
                            icon={UsersRound}
                            title="No prospects found"
                            description="Adjust your filters or add a new prospect to get started."
                        />
                    }
                />

                {contacts.last_page > 1 && (
                    <div className="flex items-center justify-center gap-2 text-sm text-muted-foreground">
                        Page {contacts.current_page} of {contacts.last_page}
                    </div>
                )}
            </div>
        </>
    );
}

ProspectsIndex.layout = {
    breadcrumbs: [{ title: 'Prospects', href: prospects.index() }],
};

