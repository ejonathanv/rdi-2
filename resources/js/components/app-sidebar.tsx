import { Link, router, usePage } from '@inertiajs/react';
import { Building2, ClipboardList, LayoutGrid, Route, Users } from 'lucide-react';
import { useMemo } from 'react';
import AppLogo from '@/components/app-logo';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { update as updateCurrentArea } from '@/routes/current-area';
import { dashboard } from '@/routes';
import { index as areasIndex } from '@/routes/areas';
import { home as guardHome } from '@/routes/guard';
import { index as rondinesIndex } from '@/routes/rondines';
import { index as roundsIndex } from '@/routes/rounds';
import { index as usersIndex } from '@/routes/users';
import type { AreaSummary, NavItem } from '@/types';

type PageProps = {
    auth: {
        user: {
            is_super_admin?: boolean;
            is_guard_only?: boolean;
            home_path?: string;
            can_manage_areas?: boolean;
            can_manage_users?: boolean;
        } | null;
    };
    currentArea: AreaSummary | null;
    availableAreas: AreaSummary[];
};

export function AppSidebar() {
    const { auth, currentArea, availableAreas } = usePage<PageProps>().props;

    const panelHref = auth.user?.is_guard_only
        ? guardHome()
        : (auth.user?.home_path ?? dashboard());

    const mainNavItems = useMemo(() => {
        const items: NavItem[] = [
            {
                title: 'Panel',
                href: panelHref,
                icon: LayoutGrid,
            },
        ];

        if (auth.user?.is_super_admin || auth.user?.can_manage_areas) {
            if (auth.user?.is_super_admin) {
                items.push({
                    title: 'Áreas',
                    href: areasIndex(),
                    icon: Building2,
                });
            }

            if (auth.user?.can_manage_users) {
                items.push({
                    title: 'Usuarios',
                    href: usersIndex(),
                    icon: Users,
                });
            }

            items.push({
                title: 'Recorridos',
                href: roundsIndex(),
                icon: Route,
            });

            items.push({
                title: 'Rondines',
                href: rondinesIndex(),
                icon: ClipboardList,
            });
        }

        return items;
    }, [auth.user, panelHref]);

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={panelHref} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>

                {availableAreas.length > 0 && (
                    <div className="px-2 pt-2 group-data-[collapsible=icon]:hidden">
                        <Select
                            value={
                                currentArea
                                    ? String(currentArea.id)
                                    : undefined
                            }
                            onValueChange={(value) => {
                                router.put(updateCurrentArea.url(), {
                                    area_id: Number(value),
                                });
                            }}
                        >
                            <SelectTrigger className="w-full">
                                <SelectValue placeholder="Seleccionar área" />
                            </SelectTrigger>
                            <SelectContent>
                                {availableAreas.map((area) => (
                                    <SelectItem
                                        key={area.id}
                                        value={String(area.id)}
                                    >
                                        {area.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                )}
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
