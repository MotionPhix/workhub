import { Head } from '@inertiajs/react';
import prospects from '@/routes/prospects';
import Heading from '@/components/heading';
import { ProspectForm } from './prospect-form';
import type { Company, Service, Tag } from '@/types';

interface ProspectCreateProps {
    companies: Pick<Company, 'id' | 'name'>[];
    tags: Tag[];
    services: Service[];
}

export default function ProspectCreate({ companies, tags, services }: ProspectCreateProps) {
    return (
        <>
            <Head title="New Prospect" />
            <div className="mx-auto max-w-3xl space-y-6">
                <Heading
                    title="New Prospect"
                    description="Add a new contact to your outreach pipeline."
                />
                <ProspectForm
                    companies={companies}
                    tags={tags}
                    services={services}
                    submitAction={prospects.store.form().action}
                    submitMethod="post"
                    submitLabel="Create prospect"
                />
            </div>
        </>
    );
}

ProspectCreate.layout = {
    breadcrumbs: [
        { title: 'Prospects', href: prospects.index() },
        { title: 'New', href: prospects.create() },
    ],
};
