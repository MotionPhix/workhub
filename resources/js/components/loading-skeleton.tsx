import { Skeleton } from '@/components/ui/skeleton';
import { Card } from '@/components/ui/card';

export function StatCardSkeleton() {
    return (
        <Card className="p-4">
            <div className="space-y-2">
                <Skeleton className="h-4 w-24" />
                <Skeleton className="h-8 w-16" />
                <Skeleton className="h-3 w-20" />
            </div>
        </Card>
    );
}

export function CardListSkeleton({ count = 4 }: { count?: number }) {
    return (
        <div className="space-y-3">
            {Array.from({ length: count }).map((_, i) => (
                <Card key={i} className="p-4">
                    <div className="flex items-center gap-4">
                        <Skeleton className="size-10 rounded-full" />
                        <div className="flex-1 space-y-2">
                            <Skeleton className="h-4 w-48" />
                            <Skeleton className="h-3 w-32" />
                        </div>
                        <Skeleton className="h-6 w-16" />
                    </div>
                </Card>
            ))}
        </div>
    );
}

export function TableSkeleton({
    rows = 8,
    cols = 4,
}: {
    rows?: number;
    cols?: number;
}) {
    return (
        <div className="space-y-2">
            {Array.from({ length: rows }).map((_, r) => (
                <div key={r} className="flex gap-4">
                    {Array.from({ length: cols }).map((_, c) => (
                        <Skeleton
                            key={c}
                            className="h-4 flex-1"
                            style={{ maxWidth: `${100 / cols}%` }}
                        />
                    ))}
                </div>
            ))}
        </div>
    );
}
