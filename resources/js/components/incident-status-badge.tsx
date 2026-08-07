import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';

const statusClasses: Record<string, string> = {
    nueva: 'border-transparent bg-amber-100 text-amber-950 dark:bg-amber-950/40 dark:text-amber-100',
    en_atencion:
        'border-transparent bg-primary text-primary-foreground dark:bg-primary dark:text-primary-foreground',
    resuelta:
        'border-transparent bg-emerald-100 text-emerald-950 dark:bg-emerald-950/40 dark:text-emerald-100',
    descartada:
        'border-transparent bg-slate-200 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
};

export function IncidentStatusBadge({
    status,
    label,
    className,
}: {
    status: string;
    label: string;
    className?: string;
}) {
    return (
        <Badge
            variant="secondary"
            className={cn(statusClasses[status] ?? '', className)}
        >
            {label}
        </Badge>
    );
}
