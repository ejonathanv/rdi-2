import { Link } from '@inertiajs/react';
import { ChevronRight } from 'lucide-react';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/hooks/use-current-url';
import type { NavItem } from '@/types';

function itemIsActive(
    item: NavItem,
    isCurrentUrl: ReturnType<typeof useCurrentUrl>['isCurrentUrl'],
    isCurrentOrParentUrl: ReturnType<typeof useCurrentUrl>['isCurrentOrParentUrl'],
): boolean {
    if (item.href && isCurrentUrl(item.href)) {
        return true;
    }

    return (
        item.items?.some(
            (child) =>
                child.href &&
                (isCurrentUrl(child.href) || isCurrentOrParentUrl(child.href)),
        ) ?? false
    );
}

export function NavMain({ items = [] }: { items: NavItem[] }) {
    const { isCurrentUrl, isCurrentOrParentUrl } = useCurrentUrl();

    return (
        <SidebarGroup className="px-2 py-0">
            <SidebarGroupLabel>Menú</SidebarGroupLabel>
            <SidebarMenu>
                {items.map((item) => {
                    if (item.items && item.items.length > 0) {
                        const openByDefault = itemIsActive(
                            item,
                            isCurrentUrl,
                            isCurrentOrParentUrl,
                        );

                        return (
                            <Collapsible
                                key={item.title}
                                asChild
                                defaultOpen={openByDefault}
                                className="group/collapsible"
                            >
                                <SidebarMenuItem>
                                    <CollapsibleTrigger asChild>
                                        <SidebarMenuButton
                                            tooltip={{ children: item.title }}
                                            isActive={openByDefault}
                                        >
                                            {item.icon && <item.icon />}
                                            <span>{item.title}</span>
                                            <ChevronRight className="ml-auto transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90" />
                                        </SidebarMenuButton>
                                    </CollapsibleTrigger>
                                    <CollapsibleContent>
                                        <SidebarMenuSub>
                                            {item.items.map((child) => (
                                                <SidebarMenuSubItem key={child.title}>
                                                    <SidebarMenuSubButton
                                                        asChild
                                                        isActive={
                                                            child.href
                                                                ? isCurrentUrl(child.href) ||
                                                                  isCurrentOrParentUrl(
                                                                      child.href,
                                                                  )
                                                                : false
                                                        }
                                                    >
                                                        <Link href={child.href!} prefetch>
                                                            {child.icon && <child.icon />}
                                                            <span>{child.title}</span>
                                                        </Link>
                                                    </SidebarMenuSubButton>
                                                </SidebarMenuSubItem>
                                            ))}
                                        </SidebarMenuSub>
                                    </CollapsibleContent>
                                </SidebarMenuItem>
                            </Collapsible>
                        );
                    }

                    if (item.disabled || !item.href) {
                        return (
                            <SidebarMenuItem key={item.title}>
                                <SidebarMenuButton
                                    disabled
                                    tooltip={{ children: `${item.title} (próximamente)` }}
                                    className="opacity-60"
                                >
                                    {item.icon && <item.icon />}
                                    <span>{item.title}</span>
                                </SidebarMenuButton>
                            </SidebarMenuItem>
                        );
                    }

                    return (
                        <SidebarMenuItem key={item.title}>
                            <SidebarMenuButton
                                asChild
                                isActive={isCurrentUrl(item.href)}
                                tooltip={{ children: item.title }}
                            >
                                <Link href={item.href} prefetch>
                                    {item.icon && <item.icon />}
                                    <span>{item.title}</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    );
                })}
            </SidebarMenu>
        </SidebarGroup>
    );
}
