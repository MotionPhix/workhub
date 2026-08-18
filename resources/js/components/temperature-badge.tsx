import { Badge } from '@/components/ui/badge';
import type { LeadTemperature } from '@/types';

const TEMPERATURE_CLASSES: Record<LeadTemperature, string> = {
    hot: 'border-transparent bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300',
    warm: 'border-transparent bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300',
    cold: 'border-transparent bg-sky-100 text-sky-700 dark:bg-sky-950 dark:text-sky-300',
};

const TEMPERATURE_LABELS: Record<LeadTemperature, string> = {
    hot: 'Hot',
    warm: 'Warm',
    cold: 'Cold',
};

export function TemperatureBadge({ temperature }: { temperature: LeadTemperature }) {
    return (
        <Badge className={TEMPERATURE_CLASSES[temperature]} variant="secondary">
            {TEMPERATURE_LABELS[temperature]}
        </Badge>
    );
}
