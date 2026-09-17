<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import {
    Bell,
    Calendar,
    ClipboardList,
    FileEdit,
    LayoutDashboard,
    LogOut,
    MessagesSquare,
    Share2,
    Stethoscope,
    User,
    Wallet,
} from '@lucide/vue';
import { computed } from 'vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupLabel,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuBadge,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { useInitials } from '@/composables/useInitials';
import type { NavItem } from '@/types';

const page = usePage();
const hospital = computed(() => page.props.hospital);
const { getInitials } = useInitials();
const { isCurrentUrl } = useCurrentUrl();

// NOTE: this sidebar deliberately uses plain <a> tags (not the shared
// NavMain component, which always renders Inertia <Link>) because most of
// these routes still serve old Blade pages while the doctor panel redesign
// is in progress — an Inertia <Link> to a non-Inertia response throws
// "must receive a valid Inertia response". As each page below is migrated
// to Inertia, its entry here can move to <Link> (matching PatientSidebar).
const navItems: (NavItem & { converted?: boolean })[] = [
    {
        title: 'Dashboard',
        href: '/doctor/dashboard',
        icon: LayoutDashboard,
        converted: true,
    },
    { title: 'Appointments', href: '/doctor/appointments', icon: ClipboardList, converted: true },
    { title: 'Messages', href: '/doctor/messages', icon: MessagesSquare, converted: true },
    { title: 'Calendar', href: '/doctor/calendar', icon: Calendar, converted: true },
    { title: 'Templates', href: '/doctor/templates', icon: FileEdit, converted: true },
    {
        title: 'Notifications',
        href: '/doctor/notifications',
        icon: Bell,
        badge: hospital.value?.unreadNotifications || undefined,
        converted: true,
    },
    { title: 'Referrals', href: '/doctor/referrals', icon: Share2, converted: true },
    { title: 'Earnings', href: '/doctor/earnings', icon: Wallet, converted: true },
];

function logout() {
    router.post('/logout/doctor');
}
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link href="/doctor/dashboard">
                            <span
                                v-if="hospital?.logo"
                                class="flex aspect-square size-8 items-center justify-center overflow-hidden rounded-md bg-white ring-1 ring-ink-200"
                            >
                                <img :src="hospital.logo" :alt="hospital.name" class="h-full w-full object-contain" />
                            </span>
                            <span
                                v-else
                                class="flex aspect-square size-8 items-center justify-center rounded-md bg-gradient-to-br from-brand-500 to-sky-600 text-white"
                            >
                                <Stethoscope class="size-4" />
                            </span>
                            <span class="ml-1 grid flex-1 text-left text-sm">
                                <span
                                    class="mb-0.5 truncate leading-tight font-semibold"
                                    >{{
                                        hospital?.name ?? 'MediCare Hospital'
                                    }}</span
                                >
                                <span
                                    class="truncate text-xs text-muted-foreground"
                                    >Doctor Portal</span
                                >
                            </span>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <SidebarGroup class="px-2 py-0">
                <SidebarGroupLabel>Doctor Portal</SidebarGroupLabel>
                <SidebarMenu>
                    <SidebarMenuItem v-for="item in navItems" :key="item.title">
                        <SidebarMenuButton
                            as-child
                            :is-active="isCurrentUrl(item.href)"
                            :tooltip="item.title"
                        >
                            <Link v-if="item.converted" :href="item.href">
                                <component :is="item.icon" />
                                <span>{{ item.title }}</span>
                            </Link>
                            <a v-else :href="item.href as string">
                                <component :is="item.icon" />
                                <span>{{ item.title }}</span>
                            </a>
                        </SidebarMenuButton>
                        <SidebarMenuBadge v-if="item.badge">{{
                            item.badge
                        }}</SidebarMenuBadge>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarGroup>
        </SidebarContent>

        <SidebarFooter>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton as-child size="lg">
                        <Link href="/doctor/profile">
                            <Avatar class="h-8 w-8 overflow-hidden rounded-lg">
                                <AvatarImage
                                    v-if="hospital?.user?.photo"
                                    :src="hospital.user.photo"
                                    :alt="hospital.user.name"
                                />
                                <AvatarFallback
                                    class="rounded-lg bg-brand-50 text-brand-700"
                                >
                                    {{
                                        getInitials(hospital?.user?.name ?? 'D')
                                    }}
                                </AvatarFallback>
                            </Avatar>
                            <span
                                class="grid flex-1 text-left text-sm leading-tight"
                            >
                                <span class="truncate font-medium"
                                    >Dr.
                                    {{ hospital?.user?.name }}</span
                                >
                                <span
                                    class="flex items-center gap-1 truncate text-xs text-muted-foreground"
                                    ><User class="size-3" /> My Profile</span
                                >
                            </span>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
                <SidebarMenuItem>
                    <SidebarMenuButton @click="logout">
                        <LogOut /> <span>Logout</span>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
