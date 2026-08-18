import { useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card } from '@/components/ui/card';
import {
    Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/components/ui/select';
import type { Company, CompanySize, LeadSource, OrganizationType } from '@/types';

interface CompanyFormProps {
    company?: Company;
    submitAction: string;
    submitMethod: 'post' | 'put';
    submitLabel: string;
}

interface FormState {
    name: string;
    website: string;
    industry: string;
    location: string;
    country: string;
    size: string;
    organization_type: string;
    lead_source: string;
    description: string;
    what_they_do: string;
    estimated_budget: string;
    notes: string;
    [key: string]: string;
}

const SIZES: CompanySize[] = ['solo', 'micro', 'small', 'medium', 'large', 'enterprise'];

const ORGANIZATION_TYPES: OrganizationType[] = [
    'ngo', 'international_ngo', 'business', 'startup', 'agency', 'government',
    'parastatal', 'education', 'health', 'financial', 'religious', 'cooperative', 'other',
];

const LEAD_SOURCES: LeadSource[] = [
    'referral', 'website', 'linkedin', 'facebook', 'instagram', 'directory',
    'event', 'cold_research', 'word_of_mouth', 'existing_client', 'other',
];

export function CompanyForm({
    company,
    submitAction,
    submitMethod,
    submitLabel,
}: CompanyFormProps) {
    const { data, setData, post, put, processing, errors, recentlySuccessful } = useForm<FormState>({
        name: company?.name ?? '',
        website: company?.website ?? '',
        industry: company?.industry ?? '',
        location: company?.location ?? '',
        country: company?.country ?? '',
        size: company?.size ?? '',
        organization_type: company?.organization_type ?? '',
        lead_source: company?.lead_source ?? '',
        description: company?.description ?? '',
        what_they_do: company?.what_they_do ?? '',
        estimated_budget: company?.estimated_budget != null ? String(company.estimated_budget) : '',
        notes: company?.notes ?? '',
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();

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
                        <Label htmlFor="name">Name</Label>
                        <Input
                            id="name"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                        />
                        {errors.name && (
                            <p className="text-xs text-red-600">{errors.name}</p>
                        )}
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="website">Website</Label>
                        <Input
                            id="website"
                            value={data.website}
                            onChange={(e) => setData('website', e.target.value)}
                        />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="industry">Industry</Label>
                        <Input
                            id="industry"
                            value={data.industry}
                            onChange={(e) => setData('industry', e.target.value)}
                        />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="location">Location</Label>
                        <Input
                            id="location"
                            value={data.location}
                            onChange={(e) => setData('location', e.target.value)}
                        />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="country">Country</Label>
                        <Input
                            id="country"
                            value={data.country}
                            onChange={(e) => setData('country', e.target.value)}
                        />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="estimated_budget">Estimated budget</Label>
                        <Input
                            id="estimated_budget"
                            type="number"
                            value={data.estimated_budget}
                            onChange={(e) => setData('estimated_budget', e.target.value)}
                        />
                        {errors.estimated_budget && (
                            <p className="text-xs text-red-600">{errors.estimated_budget}</p>
                        )}
                    </div>
                </div>
            </Card>

            <Card className="space-y-4 p-5">
                <h2 className="text-base font-medium">Classification</h2>
                <div className="grid gap-4 sm:grid-cols-3">
                    <div className="space-y-2">
                        <Label htmlFor="size">Size</Label>
                        <Select
                            value={data.size || 'none'}
                            onValueChange={(value) => setData('size', value === 'none' ? '' : value)}
                        >
                            <SelectTrigger id="size">
                                <SelectValue placeholder="Unknown" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="none">Unknown</SelectItem>
                                {SIZES.map((size) => (
                                    <SelectItem key={size} value={size} className="capitalize">
                                        {size}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="organization_type">Organization type</Label>
                        <Select
                            value={data.organization_type || 'none'}
                            onValueChange={(value) => setData('organization_type', value === 'none' ? '' : value)}
                        >
                            <SelectTrigger id="organization_type">
                                <SelectValue placeholder="Unknown" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="none">Unknown</SelectItem>
                                {ORGANIZATION_TYPES.map((type) => (
                                    <SelectItem key={type} value={type} className="capitalize">
                                        {type.replace(/_/g, ' ')}
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
                </div>
            </Card>

            <Card className="space-y-4 p-5">
                <h2 className="text-base font-medium">Research</h2>
                <div className="space-y-2">
                    <Label htmlFor="description">Description</Label>
                    <textarea
                        id="description"
                        className="min-h-20 w-full rounded-md border border-border bg-background px-3 py-2 text-sm text-foreground outline-none focus-visible:ring-1 focus-visible:ring-ring"
                        value={data.description}
                        onChange={(e) => setData('description', e.target.value)}
                    />
                </div>
                <div className="space-y-2">
                    <Label htmlFor="what_they_do">What they do</Label>
                    <textarea
                        id="what_they_do"
                        className="min-h-20 w-full rounded-md border border-border bg-background px-3 py-2 text-sm text-foreground outline-none focus-visible:ring-1 focus-visible:ring-ring"
                        value={data.what_they_do}
                        onChange={(e) => setData('what_they_do', e.target.value)}
                    />
                </div>
                <div className="space-y-2">
                    <Label htmlFor="notes">Notes</Label>
                    <textarea
                        id="notes"
                        className="min-h-20 w-full rounded-md border border-border bg-background px-3 py-2 text-sm text-foreground outline-none focus-visible:ring-1 focus-visible:ring-ring"
                        value={data.notes}
                        onChange={(e) => setData('notes', e.target.value)}
                    />
                </div>
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
