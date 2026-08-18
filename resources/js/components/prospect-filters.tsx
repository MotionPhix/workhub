import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { ContactStatusBadge } from '@/components/status-badge';
import { TemperatureBadge } from '@/components/temperature-badge';
import type {
    Company, ContactStatus, LeadTemperature, Tag,
} from '@/types';

export interface ProspectFiltersValue {
    search: string | null;
    status: ContactStatus | null;
    temperature: LeadTemperature | null;
    company_id: string | null;
}

interface ProspectFiltersProps {
    value: ProspectFiltersValue;
    companies: Pick<Company, 'id' | 'name'>[];
    onChange: (value: ProspectFiltersValue) => void;
}

const STATUS_OPTIONS: ContactStatus[] = [
    'new', 'researching', 'ready_to_contact', 'contacted', 'replied',
    'interested', 'follow_up', 'proposal_requested', 'proposal_sent',
    'negotiating', 'won', 'lost', 'not_interested', 'do_not_contact',
];

const TEMPERATURE_OPTIONS: LeadTemperature[] = ['hot', 'warm', 'cold'];

export function ProspectFilters({ value, companies, onChange }: ProspectFiltersProps) {
    return (
        <div className="flex flex-wrap items-center gap-2">
            <Select
                value={value.status ?? 'all'}
                onValueChange={(next) =>
                    onChange({ ...value, status: next === 'all' ? null : (next as ContactStatus) })
                }
            >
                <SelectTrigger className="w-44">
                    <SelectValue placeholder="Status" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">All statuses</SelectItem>
                    {STATUS_OPTIONS.map((status) => (
                        <SelectItem key={status} value={status}>
                            <ContactStatusBadge status={status} />
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>

            <Select
                value={value.temperature ?? 'all'}
                onValueChange={(next) =>
                    onChange({ ...value, temperature: next === 'all' ? null : (next as LeadTemperature) })
                }
            >
                <SelectTrigger className="w-36">
                    <SelectValue placeholder="Temperature" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">All temperatures</SelectItem>
                    {TEMPERATURE_OPTIONS.map((temperature) => (
                        <SelectItem key={temperature} value={temperature}>
                            <TemperatureBadge temperature={temperature} />
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>

            <Select
                value={value.company_id ?? 'all'}
                onValueChange={(next) =>
                    onChange({ ...value, company_id: next === 'all' ? null : next })
                }
            >
                <SelectTrigger className="w-48">
                    <SelectValue placeholder="Company" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">All companies</SelectItem>
                    {companies.map((company) => (
                        <SelectItem key={company.id} value={company.id}>
                            {company.name}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
        </div>
    );
}
