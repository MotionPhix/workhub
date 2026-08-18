import { Head } from '@inertiajs/react';
import companies from '@/routes/companies';
import Heading from '@/components/heading';
import { CompanyForm } from './company-form';
import type { Company } from '@/types';

interface CompanyEditProps {
    company: Company;
}

export default function CompanyEdit({ company }: CompanyEditProps) {
    return (
        <>
            <Head title={`Edit ${company.name}`} />
            <div className="mx-auto max-w-3xl space-y-6">
                <Heading title="Edit Company" description={company.name} />
                <CompanyForm
                    company={company}
                    submitAction={companies.update.form(company.id).action}
                    submitMethod="put"
                    submitLabel="Save changes"
                />
            </div>
        </>
    );
}

CompanyEdit.layout = {
    breadcrumbs: [{ title: 'Companies', href: companies.index() }],
};
