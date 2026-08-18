import { Head } from '@inertiajs/react';
import companies from '@/routes/companies';
import Heading from '@/components/heading';
import { CompanyForm } from './company-form';

export default function CompanyCreate() {
    return (
        <>
            <Head title="New Company" />
            <div className="mx-auto max-w-3xl space-y-6">
                <Heading
                    title="New Company"
                    description="Add a new organization to your pipeline."
                />
                <CompanyForm
                    submitAction={companies.store.form().action}
                    submitMethod="post"
                    submitLabel="Create company"
                />
            </div>
        </>
    );
}

CompanyCreate.layout = {
    breadcrumbs: [
        { title: 'Companies', href: companies.index() },
        { title: 'New', href: companies.create() },
    ],
};
