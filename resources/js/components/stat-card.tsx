import type { LucideIcon } from 'lucide-react';
import { Card } from '@/components/ui/card';

interface StatCardProps {
    label: string;
    value: string;
    icon?: LucideIcon;
    hint?: string;
    trend?: {
        direction: 'up' | 'down';
        value: string;
    };
}

export function StatCard({ label, value, icon: Icon, hint, trend }: StatCardProps) {
    return (
        <Card className="p-4">
            <div className="flex items-start justify-between">
                <div className="space-y-1">
                    <p className="text-sm text-muted-foreground">{label}</p>
                    <p className="text-2xl font-semibold tabular-nums tracking-tight">
                        {value}
                    </p>
                    {hint && (
                        <p className="text-xs text-muted-foreground">{hint}</p>
                    )}
                </div>
                {Icon && (
                    <div className="flex size-9 items-center justify-center rounded-lg bg-muted">
                        <Icon className="size-5 text-muted-foreground" />
                    </div>
                )}
            </div>
            {trend && (
                <p
                    className={
                        trend.direction === 'up'
                            ? 'mt-2 text-xs font-medium text-emerald-600 dark:text-emerald-400'
                            : 'mt-2 text-xs font-medium text-red-600 dark:text-red-400'
                    }
                >
                    {trend.direction === 'up' ? '↑' : '↓'} {trend.value}
                </p>
            )}
        </Card>
    );
}
