import { Link } from '@inertiajs/react';
import {
    Building2, CalendarClock, FileText, GitBranch, Handshake,
    LayoutGrid, LayoutTemplate, Send, Settings, UsersRound, BarChart3,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuBadge,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard, analytics } from '@/routes';
import profile from '@/routes/profile';
import prospects from '@/routes/prospects';
import companies from '@/routes/companies';
import outreach from '@/routes/outreach';
import followUps from '@/routes/follow-ups';
import proposals from '@/routes/proposals';
import pipeline from '@/routes/pipeline';
import clients from '@/routes/clients';
import templates from '@/routes/templates';
import type { NavGroup } from '@/types';

const navGroups: NavGroup[] = [
    {
        label: 'Reach',
        items: [
            { title: 'Dashboard', href: dashboard(), icon: LayoutGrid },
            { title: 'Prospects', href: prospects.index(), icon: UsersRound },
            { title: 'Companies', href: companies.index(), icon: Building2 },
            { title: 'Outreach', href: outreach.index(), icon: Send },
            { title: 'Follow-ups', href: followUps.index(), icon: CalendarClock, badge: null },
        ],
    },
    {
        label: 'Business',
        items: [
            { title: 'Proposals', href: proposals.index(), icon: FileText },
            { title: 'Pipeline', href: pipeline.index(), icon: GitBranch },
            { title: 'Clients', href: clients.index(), icon: Handshake },
            { title: 'Templates', href: templates.index(), icon: LayoutTemplate },
        ],
    },
    {
        label: 'Insight',
        items: [
            { title: 'Analytics', href: analytics(), icon: BarChart3 },
        ],
    },
];

const footerNavItems = [
    {
        title: 'Settings',
        href: profile.edit(),
        icon: Settings,
    },
];

export function AppSidebar() {
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
            </SidebarHeader>

            <SidebarContent>
                {navGroups.map((group) => (
                    <NavMain key={group.label} items={group.items} label={group.label} />
                ))}
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
