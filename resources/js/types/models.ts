import type {
    ActivityType, AssignmentStatus, ClientStatus, CompanySize, ContactStatus,
    CycleStatus, EmailDirection, EmailStatus, FollowUpStatus, FollowUpType,
    LeadSource, LeadTemperature, OpportunityStage, OrganizationType, Priority,
    ProposalStatus, ProjectStatus, QualityRating, RelationshipStage,
    TemplateCategory, TemplateStatus, OutreachStrategy,
} from './enums';

export interface Service {
    id: string;
    name: string;
    slug: string;
    description: string | null;
    sort_order: number;
}

export interface Tag {
    id: string;
    name: string;
    slug: string;
    color: string | null;
}

export interface Company {
    id: string;
    name: string;
    slug: string;
    email: string | null;
    phone: string | null;
    website: string | null;
    industry: string | null;
    location: string | null;
    country: string | null;
    size: CompanySize | null;
    organization_type: OrganizationType | null;
    lead_source: LeadSource | null;
    linkedin: string | null;
    facebook: string | null;
    twitter: string | null;
    instagram: string | null;
    estimated_budget: number | null;
    services_offered: string | null;
    notes: string | null;
    description: string | null;
    what_they_do: string | null;
    website_quality: QualityRating | null;
    branding_quality: QualityRating | null;
    social_presence: string | null;
    potential_problems: string | null;
    suggested_pitch: string | null;
    researched_at: string | null;
    is_client: boolean;
    created_at: string;
    updated_at: string;
    contacts?: Contact[];
    opportunities?: Opportunity[];
}

export interface Contact {
    id: string;
    company_id: string | null;
    first_name: string;
    last_name: string | null;
    job_title: string | null;
    email: string | null;
    phone: string | null;
    website: string | null;
    linkedin: string | null;
    facebook: string | null;
    twitter: string | null;
    instagram: string | null;
    location: string | null;
    industry: string | null;
    status: ContactStatus;
    priority: Priority;
    lead_score: number;
    temperature: LeadTemperature;
    full_name: string;
    relationship_stage: RelationshipStage;
    lead_source: LeadSource | null;
    primary_service_id: string | null;
    potential_needs: string | null;
    estimated_budget: number | null;
    notes: string | null;
    is_primary_contact: boolean;
    first_contacted_at: string | null;
    last_contacted_at: string | null;
    responded_at: string | null;
    outreach_count: number;
    last_outcome: string | null;
    cooldown_until: string | null;
    created_at: string;
    updated_at: string;
    company?: Company;
    services?: Service[];
    tags?: Tag[];
    primary_service?: Service;
    latest_email_message?: EmailMessage;
}

export interface OutreachCycle {
    id: string;
    user_id: number;
    name: string | null;
    starts_on: string;
    ends_on: string;
    status: CycleStatus;
    assignment_count: number;
    contacted_count: number;
    created_at: string;
    updated_at: string;
}

export interface OutreachAssignment {
    id: string;
    user_id: number;
    outreach_cycle_id: string;
    contact_id: string;
    status: AssignmentStatus;
    rank: number;
    score: number;
    reasons: string[] | null;
    strategy: OutreachStrategy;
    suggested_service_id: string | null;
    suggested_angle: string | null;
    assigned_on: string;
    contacted_at: string | null;
    skipped_at: string | null;
    snoozed_until: string | null;
    notes: string | null;
    created_at: string;
    updated_at: string;
    contact?: Contact;
    suggested_service?: Service;
    cycle?: OutreachCycle;
}

export interface EmailMessage {
    id: string;
    user_id: number;
    contact_id: string;
    company_id: string | null;
    email_template_id: string | null;
    outreach_cycle_id: string | null;
    outreach_assignment_id: string | null;
    parent_id: string | null;
    direction: EmailDirection;
    strategy: OutreachStrategy;
    status: EmailStatus;
    subject: string;
    body: string;
    variables: Record<string, string> | null;
    to_email: string;
    to_name: string | null;
    from_email: string;
    from_name: string | null;
    reply_to: string | null;
    queued_at: string | null;
    sent_at: string | null;
    delivered_at: string | null;
    opened_at: string | null;
    replied_at: string | null;
    failed_at: string | null;
    failure_reason: string | null;
    message_id: string | null;
    tracking_token: string | null;
    opens: number;
    created_at: string;
    updated_at: string;
    contact?: Contact;
    company?: Company;
}

export interface FollowUp {
    id: string;
    user_id: number;
    contact_id: string;
    email_message_id: string | null;
    type: FollowUpType;
    status: FollowUpStatus;
    due_on: string;
    completed_at: string | null;
    notes: string | null;
    created_at: string;
    updated_at: string;
    contact?: Contact;
    email_message?: EmailMessage;
}

export interface ProposalVersion {
    id: string;
    proposal_id: string;
    version: number;
    status: ProposalStatus;
    sections: Array<{
        key: string;
        title: string;
        body: string;
        enabled: boolean;
    }>;
    line_items: Array<{
        description: string;
        quantity: number;
        unit_price: number;
        total: number;
    }> | null;
    subtotal: number;
    discount: number;
    tax_rate: number;
    total_amount: number;
    currency: string;
    timeline: string | null;
    terms: string | null;
    change_summary: string | null;
    valid_until: string | null;
    sent_at: string | null;
    created_at: string;
    updated_at: string;
}

export interface Proposal {
    id: string;
    user_id: number;
    contact_id: string;
    company_id: string | null;
    opportunity_id: string | null;
    proposal_template_id: string | null;
    title: string;
    status: ProposalStatus;
    total_amount: number | null;
    currency: string;
    sent_at: string | null;
    viewed_at: string | null;
    view_count: number;
    accepted_at: string | null;
    rejected_at: string | null;
    expires_at: string | null;
    created_at: string;
    updated_at: string;
    contact?: Contact;
    company?: Company;
    opportunity?: Opportunity;
    latest_version?: ProposalVersion;
}

export interface Opportunity {
    id: string;
    user_id: number;
    contact_id: string;
    company_id: string | null;
    service_id: string | null;
    title: string;
    description: string | null;
    stage: OpportunityStage;
    value: number;
    probability: number | null;
    expected_close_on: string | null;
    won_at: string | null;
    lost_at: string | null;
    created_at: string;
    updated_at: string;
    contact?: Contact;
    company?: Company;
    service?: Service;
    proposal?: Proposal;
}

export interface Client {
    id: string;
    user_id: number;
    company_id: string | null;
    contact_id: string | null;
    opportunity_id: string | null;
    name: string;
    email: string | null;
    phone: string | null;
    status: ClientStatus;
    is_retainer: boolean;
    on_retainer_since: string | null;
    monthly_value: number | null;
    notes: string | null;
    created_at: string;
    updated_at: string;
    company?: Company;
    contact?: Contact;
    projects?: Project[];
}

export interface Project {
    id: string;
    user_id: number;
    client_id: string;
    opportunity_id: string | null;
    proposal_id: string | null;
    service_id: string | null;
    name: string;
    description: string | null;
    status: ProjectStatus;
    value: number | null;
    currency: string;
    started_on: string | null;
    completed_on: string | null;
    created_at: string;
    updated_at: string;
    client?: Client;
    service?: Service;
}

export interface EmailTemplate {
    id: string;
    user_id: number;
    name: string;
    category: TemplateCategory;
    subject: string;
    body: string;
    variables: string[] | null;
    status: TemplateStatus;
    is_default: boolean;
    times_used: number;
    last_used_at: string | null;
    created_at: string;
    updated_at: string;
}

export interface ProposalTemplate {
    id: string;
    user_id: number;
    name: string;
    description: string | null;
    sections: Array<{
        key: string;
        title: string;
        body: string;
        enabled: boolean;
    }>;
    terms: string | null;
    is_default: boolean;
    times_used: number;
    created_at: string;
    updated_at: string;
}

export interface Activity {
    id: string;
    user_id: number;
    contact_id: string;
    company_id: string | null;
    type: ActivityType;
    subject_type: string | null;
    subject_id: string | null;
    description: string | null;
    occurred_at: string;
    created_at: string;
    updated_at: string;
    contact?: Contact;
    company?: Company;
}

export interface Goal {
    id: string;
    user_id: number;
    month: string;
    revenue_target: number;
    new_clients_target: number;
    proposals_target: number;
    outreach_target: number;
    currency: string;
    created_at: string;
    updated_at: string;
}
