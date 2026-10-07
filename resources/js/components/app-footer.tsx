import { cn } from '@/lib/utils';

export function AppFooter({ className }: { className?: string }) {
    return (
        <footer
            className={cn(
                'mt-auto px-4 py-4 text-center text-xs text-muted-foreground',
                className,
            )}
        >
            {new Date().getFullYear()}, RDI - Reporte de Incidentes -
            Desarrollo de IP Media River.
        </footer>
    );
}
