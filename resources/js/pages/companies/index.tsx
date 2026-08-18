import { Head, Link, router } from '@inertiajs/react';
import companies from '@/routes/companies';
import { Button } from '@/components/ui/button';
import Heading from '@/components/heading';
import { EmptyState } from '@/components/empty-state';
import { SearchInput } from '@/components/search-input';
import { DataTable, type ColumnDef } from '@/components/data-table';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import {
    Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/components/ui/select';
import { Building2, Plus } from 'lucide-react';
import { useDebouncedFilter } from '@/hooks/use-debounced-filter';
import type { Company, OrganizationType, Paginated } from '@/types';

interface CompaniesIndexProps {
    companies: Paginated<Company>;
    filters: {
        search: string | null;
        organization_type: OrganizationType | null;
    };
}

const ORGANIZATION_TYPES: OrganizationType[] = [
    'ngo', 'international_ngo', 'business', 'startup', 'agency', 'government',
    'parastatal', 'education', 'health', 'financial', 'religious', 'cooperative', 'other',
];

function initials(name: string): string {
    return name.split(' ').filter(Boolean).slice(0, 2).map((p) => p[0]?.toUpperCase()).join('');
}

export default function CompaniesIndex({ companies: companiesData, filters }: CompaniesIndexProps) {
    const applyFilters = useDebouncedFilter(companies.index().url, 250);

    const columns: ColumnDef<Company>[] = [
        {
            key: 'name',
            header: 'Company',
            cell: (company) => (
                <div className="flex items-center gap-3">
                    <Avatar className="size-8">
                        <AvatarFallback>{initials(company.name)}</AvatarFallback>
                    </Avatar>
                    <div className="min-w-0">
                        <p className="truncate font-medium">{company.name}</p>
                        {company.website && (
                            <p className="truncate text-xs text-muted-foreground">
                                {company.website}
                            </p>
                        )}
                    </div>
                </div>
            ),
        },
        {
            key: 'industry',
            header: 'Industry',
            cell: (company) => company.industry ?? '—',
            hideOnMobile: true,
        },
        {
            key: 'location',
            header: 'Location',
            cell: (company) => company.location ?? '—',
            hideOnMobile: true,
        },
        {
            key: 'organization_type',
            header: 'Type',
            cell: (company) =>
                company.organization_type
                    ? <span className="capitalize">{company.organization_type.replace(/_/g, ' ')}</span>
                    : '—',
            hideOnMobile: true,
        },
        {
            key: 'contacts',
            header: 'Contacts',
            cell: (company) => (
                <span className="tabular-nums">
                    {(company as Company & { contacts_count?: number }).contacts_count ?? 0}
                </span>
            ),
        },
    ];

    return (
        <>
            <Head title="Companies" />

            <div className="space-y-6">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <Heading title="Companies" description={`${companiesData.total} organizations`} />
                    <div className="flex items-center gap-2">
                        <Select
                            value={filters.organization_type ?? 'all'}
                            onValueChange={(value) =>
                                applyFilters({
                                    ...filters,
                                    organization_type: value === 'all' ? null : value,
                                })
                            }
                        >
                            <SelectTrigger className="w-48">
                                <SelectValue placeholder="Organization type" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All types</SelectItem>
                                {ORGANIZATION_TYPES.map((type) => (
                                    <SelectItem key={type} value={type} className="capitalize">
                                        {type.replace(/_/g, ' ')}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <Button asChild>
                            <Link href={companies.create()}>
                                <Plus />
                                New Company
                            </Link>
                        </Button>
                    </div>
                </div>

                <DataTable
                    columns={columns}
                    rows={companiesData.data}
                    rowKey={(company) => company.id}
                    onRowClick={(company) => router.visit(companies.show(company.id))}
                    empty={
                        <EmptyState
                            icon={Building2}
                            title="No companies found"
                            description="Adjust your filters or add a new company to get started."
                        />
                    }
                />

                {companiesData.last_page > 1 && (
                    <div className="flex items-center justify-center gap-2 text-sm text-muted-foreground">
                        Page {companiesData.current_page} of {companiesData.last_page}
                    </div>
                )}
            </div>
        </>
    );
}

CompaniesIndex.layout = {
    breadcrumbs: [{ title: 'Companies', href: companies.index() }],
};
