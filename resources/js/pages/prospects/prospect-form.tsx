import { useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card } from '@/components/ui/card';
import {
    Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/components/ui/select';
import { Checkbox } from '@/components/ui/checkbox';
import { cn } from '@/lib/utils';
import type { Company, Contact, Service, Tag } from '@/types';

interface ProspectFormProps {
    contact?: Contact;
    companies: Pick<Company, 'id' | 'name'>[];
    tags: Tag[];
    services: Service[];
    submitAction: string;
    submitMethod: 'post' | 'put';
    submitLabel: string;
}

interface FormState {
    first_name: string;
    last_name: string;
    job_title: string;
    email: string;
    phone: string;
    company_id: string;
    status: string;
    lead_source: string;
    primary_service_id: string;
    estimated_budget: string;
    notes: string;
    tag_ids: string[];
    service_ids: string[];
    [key: string]: string | string[];
}

const STATUS_OPTIONS = [
    'new', 'researching', 'ready_to_contact', 'contacted', 'replied',
    'interested', 'follow_up', 'proposal_requested', 'proposal_sent',
    'negotiating', 'won', 'lost', 'not_interested', 'do_not_contact',
];

const LEAD_SOURCES = [
    'referral', 'cold_outreach', 'website', 'social_media', 'search',
    'event', 'partner', 'existing_client', 'other',
];

export function ProspectForm({
    contact,
    companies,
    tags,
    services,
    submitAction,
    submitMethod,
    submitLabel,
}: ProspectFormProps) {
    const { data, setData, post, put, processing, errors, recentlySuccessful } = useForm<FormState>({
        first_name: contact?.first_name ?? '',
        last_name: contact?.last_name ?? '',
        job_title: contact?.job_title ?? '',
        email: contact?.email ?? '',
        phone: contact?.phone ?? '',
        company_id: contact?.company_id ?? '',
        status: contact?.status ?? 'new',
        lead_source: contact?.lead_source ?? '',
        primary_service_id: contact?.primary_service_id ?? '',
        estimated_budget: contact?.estimated_budget != null ? String(contact.estimated_budget) : '',
        notes: contact?.notes ?? '',
        tag_ids: contact?.tags?.map((t) => t.id) ?? [],
        service_ids: contact?.services?.map((s) => s.id) ?? [],
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();

        const payload = {
            ...data,
            estimated_budget: data.estimated_budget === '' ? null : Number(data.estimated_budget),
            company_id: data.company_id === '' ? null : data.company_id,
            primary_service_id: data.primary_service_id === '' ? null : data.primary_service_id,
        };

        if (submitMethod === 'put') {
            put(submitAction);
        } else {
            post(submitAction);
        }
    }

    return (
        <form onSubmit={handleSubmit} className="space-y-6">
            <Card className="space-y-4 p-5">
                <h2 className="text-base font-medium">Basics</h2>
                <div className="grid gap-4 sm:grid-cols-2">
                    <div className="space-y-2">
                        <Label htmlFor="first_name">First name</Label>
                        <Input
                            id="first_name"
                            value={data.first_name}
                            onChange={(e) => setData('first_name', e.target.value)}
                        />
                        {errors.first_name && (
                            <p className="text-xs text-red-600">{errors.first_name}</p>
                        )}
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="last_name">Last name</Label>
                        <Input
                            id="last_name"
                            value={data.last_name}
                            onChange={(e) => setData('last_name', e.target.value)}
                        />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="job_title">Job title</Label>
                        <Input
                            id="job_title"
                            value={data.job_title}
                            onChange={(e) => setData('job_title', e.target.value)}
                        />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="email">Email</Label>
                        <Input
                            id="email"
                            type="email"
                            value={data.email}
                            onChange={(e) => setData('email', e.target.value)}
                        />
                        {errors.email && (
                            <p className="text-xs text-red-600">{errors.email}</p>
                        )}
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="phone">Phone</Label>
                        <Input
                            id="phone"
                            value={data.phone}
                            onChange={(e) => setData('phone', e.target.value)}
                        />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="company_id">Company</Label>
                        <Select
                            value={data.company_id || 'none'}
                            onValueChange={(value) => setData('company_id', value === 'none' ? '' : value)}
                        >
                            <SelectTrigger id="company_id">
                                <SelectValue placeholder="No company" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="none">No company</SelectItem>
                                {companies.map((company) => (
                                    <SelectItem key={company.id} value={company.id}>
                                        {company.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                </div>
            </Card>

            <Card className="space-y-4 p-5">
                <h2 className="text-base font-medium">Pipeline</h2>
                <div className="grid gap-4 sm:grid-cols-2">
                    <div className="space-y-2">
                        <Label htmlFor="status">Status</Label>
                        <Select value={data.status} onValueChange={(value) => setData('status', value)}>
                            <SelectTrigger id="status">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {STATUS_OPTIONS.map((status) => (
                                    <SelectItem key={status} value={status} className="capitalize">
                                        {status.replace(/_/g, ' ')}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="lead_source">Lead source</Label>
                        <Select
                            value={data.lead_source || 'none'}
                            onValueChange={(value) => setData('lead_source', value === 'none' ? '' : value)}
                        >
                            <SelectTrigger id="lead_source">
                                <SelectValue placeholder="Unknown" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="none">Unknown</SelectItem>
                                {LEAD_SOURCES.map((source) => (
                                    <SelectItem key={source} value={source} className="capitalize">
                                        {source.replace(/_/g, ' ')}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="primary_service_id">Primary service</Label>
                        <Select
                            value={data.primary_service_id || 'none'}
                            onValueChange={(value) => setData('primary_service_id', value === 'none' ? '' : value)}
                        >
                            <SelectTrigger id="primary_service_id">
                                <SelectValue placeholder="None" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="none">None</SelectItem>
                                {services.map((service) => (
                                    <SelectItem key={service.id} value={service.id}>
                                        {service.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="estimated_budget">Estimated budget</Label>
                        <Input
                            id="estimated_budget"
                            type="number"
                            value={data.estimated_budget}
                            onChange={(e) => setData('estimated_budget', e.target.value)}
                        />
                    </div>
                </div>
            </Card>

            <Card className="space-y-4 p-5">
                <h2 className="text-base font-medium">Tags & services</h2>
                <div className="space-y-2">
                    <Label>Tags</Label>
                    <div className="flex flex-wrap gap-2">
                        {tags.map((tag) => {
                            const checked = data.tag_ids.includes(tag.id);
                            return (
                                <label
                                    key={tag.id}
                                    className={cn(
                                        'flex cursor-pointer items-center gap-2 rounded-full border px-3 py-1 text-sm',
                                        checked ? 'border-primary bg-primary/10' : 'border-border',
                                    )}
                                >
                                    <Checkbox
                                        checked={checked}
                                        onCheckedChange={(value) =>
                                            setData(
                                                'tag_ids',
                                                value
                                                    ? [...data.tag_ids, tag.id]
                                                    : data.tag_ids.filter((id) => id !== tag.id),
                                            )
                                        }
                                    />
                                    {tag.name}
                                </label>
                            );
                        })}
                    </div>
                </div>
                <div className="space-y-2">
                    <Label>Services</Label>
                    <div className="flex flex-wrap gap-2">
                        {services.map((service) => {
                            const checked = data.service_ids.includes(service.id);
                            return (
                                <label
                                    key={service.id}
                                    className={cn(
                                        'flex cursor-pointer items-center gap-2 rounded-full border px-3 py-1 text-sm',
                                        checked ? 'border-primary bg-primary/10' : 'border-border',
                                    )}
                                >
                                    <Checkbox
                                        checked={checked}
                                        onCheckedChange={(value) =>
                                            setData(
                                                'service_ids',
                                                value
                                                    ? [...data.service_ids, service.id]
                                                    : data.service_ids.filter((id) => id !== service.id),
                                            )
                                        }
                                    />
                                    {service.name}
                                </label>
                            );
                        })}
                    </div>
                </div>
            </Card>

            <Card className="space-y-2 p-5">
                <h2 className="text-base font-medium">Notes</h2>
                <textarea
                    className="min-h-28 w-full rounded-md border border-border bg-background px-3 py-2 text-sm text-foreground outline-none focus-visible:ring-1 focus-visible:ring-ring"
                    value={data.notes}
                    onChange={(e) => setData('notes', e.target.value)}
                />
            </Card>

            <div className="flex items-center gap-3">
                <Button type="submit" disabled={processing}>
                    {submitLabel}
                </Button>
                {recentlySuccessful && (
                    <span className="text-sm text-muted-foreground">Saved.</span>
                )}
            </div>
        </form>
    );
}
