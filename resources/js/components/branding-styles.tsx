import { usePage } from '@inertiajs/react';
import { useEffect } from 'react';

export default function BrandingStyles() {
    const { branding } = usePage().props;

    useEffect(() => {
        const root = document.documentElement;

        root.style.setProperty('--brand-primary', branding.primary_color);
        root.style.setProperty('--brand-light', branding.light_color);
        root.style.setProperty('--brand-dark', branding.dark_color);
    }, [branding.primary_color, branding.light_color, branding.dark_color]);

    useEffect(() => {
        document
            .querySelectorAll<HTMLLinkElement>('link[rel="icon"]')
            .forEach((link) => {
                link.href = branding.favicon_url;
            });
    }, [branding.favicon_url]);

    return null;
}
