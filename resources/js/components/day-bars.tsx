import { Link } from '@inertiajs/react';
import { cn } from '@/lib/utils';

export type DayCount = {
    date: string;
    label: string;
    count: number;
};

export function DayBars({
    days,
    hrefForDay,
    countNoun,
    layout = 'grid',
}: {
    days: DayCount[];
    hrefForDay: (day: DayCount) => string;
    countNoun: string;
    layout?: 'grid' | 'scroll';
}) {
    const maxCount = Math.max(1, ...days.map((day) => day.count));
    const compact = layout === 'scroll';

    return (
        <div
            className={
                compact
                    ? 'flex gap-1 overflow-x-auto pb-2'
                    : 'grid grid-cols-7 gap-2'
            }
        >
            {days.map((day) => (
                <Link
                    key={day.date}
                    href={hrefForDay(day)}
                    prefetch
                    aria-label={`Ver ${day.count} ${countNoun} del ${day.label}`}
                    title={`${day.label}: ${day.count} ${countNoun}`}
                    className={cn(
                        'group flex flex-col items-center gap-2 rounded-lg p-1 outline-none transition-colors',
                        'hover:bg-muted/70 focus-visible:ring-2 focus-visible:ring-ring',
                        compact ? 'w-8 shrink-0' : 'w-full',
                    )}
                >
                    <div className="flex h-28 w-full items-end justify-center rounded-lg bg-muted/40 px-1 py-2">
                        <div
                            className={cn(
                                'rounded-t bg-primary transition-colors group-hover:bg-primary/80',
                                compact ? 'w-full' : 'w-full max-w-8',
                            )}
                            style={{
                                height: `${Math.max(
                                    day.count === 0 ? 0 : 12,
                                    (day.count / maxCount) * 100,
                                )}%`,
                            }}
                        />
                    </div>
                    <div className="text-center">
                        <div
                            className={cn(
                                'text-muted-foreground',
                                compact ? 'text-[10px]' : 'text-xs',
                            )}
                        >
                            {day.label}
                        </div>
                        {!compact && (
                            <div className="text-sm font-medium">
                                {day.count}
                            </div>
                        )}
                    </div>
                </Link>
            ))}
        </div>
    );
}
