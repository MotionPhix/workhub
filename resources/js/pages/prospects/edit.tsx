import { Head } from '@inertiajs/react';
import prospects from '@/routes/prospects';
import Heading from '@/components/heading';
import { ProspectForm } from './prospect-form';
import type { Company, Contact, Service, Tag } from '@/types';

interface ProspectEditProps {
    contact: Contact;
    companies: Pick<Company, 'id' | 'name'>[];
    tags: Tag[];
    services: Service[];
}

export default function ProspectEdit({ contact, companies, tags, services }: ProspectEditProps) {
    return (
        <>
            <Head title={`Edit ${contact.full_name}`} />
            <div className="mx-auto max-w-3xl space-y-6">
                <Heading title="Edit Prospect" description={contact.full_name} />
                <ProspectForm
                    contact={contact}
                    companies={companies}
                    tags={tags}
                    services={services}
                    submitAction={prospects.update.form(contact.id).action}
                    submitMethod="put"
                    submitLabel="Save changes"
                />
            </div>
        </>
    );
}

ProspectEdit.layout = {
    breadcrumbs: [
        { title: 'Prospects', href: prospects.index() },
    ],
};
