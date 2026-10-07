import { usePage } from '@inertiajs/react';
import { cn } from '@/lib/utils';

export default function AppLogo() {
    const { branding } = usePage().props;

    return (
        <img
            src={branding.logo_url}
            alt="Logo"
            className={cn(
                'h-auto max-h-12 w-full object-contain object-left group-data-[collapsible=icon]:size-8',
                branding.has_custom_logo
                    ? 'group-data-[collapsible=icon]:object-center'
                    : 'group-data-[collapsible=icon]:object-cover group-data-[collapsible=icon]:object-[12%_center]',
            )}
        />
    );
}
