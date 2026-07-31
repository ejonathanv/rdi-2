import { Link, router, usePage } from '@inertiajs/react';
import {
    Building2,
    LayoutGrid,
    Users,
} from 'lucide-react';
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
import { index as usersIndex } from '@/routes/users';
import type { AreaSummary, NavItem } from '@/types';

type PageProps = {
    auth: {
        user: {
            is_super_admin?: boolean;
            can_manage_areas?: boolean;
            can_manage_users?: boolean;
        } | null;
    };
    currentArea: AreaSummary | null;
    availableAreas: AreaSummary[];
};

export function AppSidebar() {
    const { auth, currentArea, availableAreas } = usePage<PageProps>().props;

    const mainNavItems = useMemo(() => {
        const items: NavItem[] = [
            {
                title: 'Dashboard',
                href: dashboard(),
                icon: LayoutGrid,
            },
        ];

        if (auth.user?.is_super_admin || auth.user?.can_manage_areas) {
            if (auth.user?.is_super_admin) {
                items.push({
                    title: 'Areas',
                    href: areasIndex(),
                    icon: Building2,
                });
            }

            if (auth.user?.can_manage_users) {
                items.push({
                    title: 'Users',
                    href: usersIndex(),
                    icon: Users,
                });
            }
        }

        return items;
    }, [auth.user]);

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
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
                                <SelectValue placeholder="Select area" />
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
