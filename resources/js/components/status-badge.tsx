import { Badge, badgeVariants } from '@/components/ui/badge';
import { type VariantProps } from 'class-variance-authority';
import type { ContactStatus } from '@/types';

const CONTACT_STATUS_STYLES: Record<ContactStatus, VariantProps<typeof badgeVariants>['variant']> = {
    new: 'secondary',
    researching: 'secondary',
    ready_to_contact: 'outline',
    contacted: 'outline',
    replied: 'secondary',
    interested: 'default',
    follow_up: 'outline',
    proposal_requested: 'default',
    proposal_sent: 'default',
    negotiating: 'default',
    won: 'default',
    lost: 'destructive',
    not_interested: 'destructive',
    do_not_contact: 'destructive',
};

const CONTACT_STATUS_LABELS: Record<ContactStatus, string> = {
    new: 'New',
    researching: 'Researching',
    ready_to_contact: 'Ready to contact',
    contacted: 'Contacted',
    replied: 'Replied',
    interested: 'Interested',
    follow_up: 'Follow-up',
    proposal_requested: 'Proposal requested',
    proposal_sent: 'Proposal sent',
    negotiating: 'Negotiating',
    won: 'Won',
    lost: 'Lost',
    not_interested: 'Not interested',
    do_not_contact: 'Do not contact',
};

export function ContactStatusBadge({ status }: { status: ContactStatus }) {
    return (
        <Badge variant={CONTACT_STATUS_STYLES[status]}>
            {CONTACT_STATUS_LABELS[status]}
        </Badge>
    );
}
