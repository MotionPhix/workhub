import type { ReactNode } from 'react';
import { Head } from '@inertiajs/react';
import type { BreadcrumbItem } from '@/types';

interface PageLayoutProps {
    title: string;
    description?: string;
    breadcrumbs?: BreadcrumbItem[];
    actions?: ReactNode;
    children: ReactNode;
}

export function PageLayout({
    title,
    description,
    breadcrumbs = [],
    actions,
    children,
}: PageLayoutProps) {
    return (
        <>
            <Head title={title} />
            <div className="flex flex-col gap-6 p-4 sm:p-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div className="space-y-1">
                        <h1 className="text-2xl font-semibold tracking-tight">
                            {title}
                        </h1>
                        {description && (
                            <p className="text-sm text-muted-foreground">
                                {description}
                            </p>
                        )}
                    </div>
                    {actions && (
                        <div className="flex shrink-0 items-center gap-2">
                            {actions}
                        </div>
                    )}
                </div>
                {children}
            </div>
        </>
    );
}
