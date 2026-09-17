<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import {
    BedDouble,
    CalendarOff,
    ClipboardList,
    LayoutDashboard,
    LogOut,
    MessageSquare,
    Settings,
    Shield,
    Sparkles,
    Stethoscope,
    Tags,
    Users,
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

const navItems: NavItem[] = [
    { title: 'Dashboard', href: '/admin/dashboard', icon: LayoutDashboard },
    { title: 'Doctors', href: '/admin/doctors', icon: Stethoscope },
    { title: 'Patients', href: '/admin/patients', icon: Users },
    { title: 'Appointments', href: '/admin/appointments', icon: ClipboardList },
    { title: 'Bed Management', href: '/admin/beds', icon: BedDouble },
    { title: 'Revenue', href: '/admin/revenue', icon: Wallet },
    { title: 'Categories', href: '/admin/categories', icon: Tags },
    { title: 'Holidays', href: '/admin/holidays', icon: CalendarOff },
    {
        title: 'Feedback',
        href: '/admin/feedback',
        icon: MessageSquare,
        badge: hospital.value?.unreadNotifications || undefined,
    },
    { title: 'AI Insights', href: '/admin/ai-insights', icon: Sparkles },
    { title: 'Admin Accounts', href: '/admin/admins', icon: Shield },
    { title: 'Settings', href: '/admin/settings', icon: Settings },
];

function logout() {
    router.post('/logout/admin');
}
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link href="/admin/dashboard">
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
                                <Shield class="size-4" />
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
                                    >Admin Portal</span
                                >
                            </span>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <SidebarGroup class="px-2 py-0">
                <SidebarGroupLabel>Admin Portal</SidebarGroupLabel>
                <SidebarMenu>
                    <SidebarMenuItem v-for="item in navItems" :key="item.title">
                        <SidebarMenuButton
                            as-child
                            :is-active="isCurrentUrl(item.href)"
                            :tooltip="item.title"
                        >
                            <Link :href="item.href">
                                <component :is="item.icon" />
                                <span>{{ item.title }}</span>
                            </Link>
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
                        <Link href="/admin/profile">
                            <Avatar class="h-8 w-8 overflow-hidden rounded-lg">
                                <AvatarImage
                                    v-if="hospital?.user?.photo"
                                    :src="hospital.user.photo"
                                    :alt="hospital.user.name"
                                />
                                <AvatarFallback
                                    class="rounded-lg bg-brand-50 text-brand-700"
                                >
                                    {{ getInitials(hospital?.user?.name ?? 'A') }}
                                </AvatarFallback>
                            </Avatar>
                            <span
                                class="grid flex-1 text-left text-sm leading-tight"
                            >
                                <span class="truncate font-medium">{{
                                    hospital?.user?.name ?? 'Administrator'
                                }}</span>
                                <span
                                    class="truncate text-xs text-muted-foreground"
                                    >Administrator</span
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
