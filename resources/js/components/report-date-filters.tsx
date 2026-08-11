import { router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export type ReportFilters = {
    from: string;
    to: string;
};

export function ReportDateFilters({
    filters,
    applyUrl,
    resetUrl,
}: {
    filters: ReportFilters;
    applyUrl: (query: ReportFilters) => string;
    resetUrl: string;
}) {
    const apply = (overrides: Partial<ReportFilters>) => {
        router.get(
            applyUrl({ ...filters, ...overrides }),
            {},
            { preserveState: true, replace: true },
        );
    };

    return (
        <div className="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-end">
            <div className="grid gap-2">
                <Label htmlFor="report-from">Desde</Label>
                <Input
                    id="report-from"
                    type="date"
                    value={filters.from}
                    onChange={(event) =>
                        apply({ from: event.target.value || filters.from })
                    }
                    className="w-full sm:w-auto"
                />
            </div>
            <div className="grid gap-2">
                <Label htmlFor="report-to">Hasta</Label>
                <Input
                    id="report-to"
                    type="date"
                    value={filters.to}
                    onChange={(event) =>
                        apply({ to: event.target.value || filters.to })
                    }
                    className="w-full sm:w-auto"
                />
            </div>
            <Button
                type="button"
                variant="ghost"
                size="sm"
                onClick={() =>
                    router.get(
                        resetUrl,
                        {},
                        { preserveState: true, replace: true },
                    )
                }
            >
                Últimos 30 días
            </Button>
        </div>
    );
}
