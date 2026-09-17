<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import {
    Bell,
    Bot,
    Calendar,
    ClipboardList,
    FileText,
    Heart,
    LayoutDashboard,
    LogOut,
    MessageCircle,
    Pill,
    Receipt,
    Search,
    Share2,
    Shield,
    Sprout,
    User,
} from '@lucide/vue';
import { computed } from 'vue';
import NavMain from '@/components/NavMain.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useInitials } from '@/composables/useInitials';
import type { NavItem } from '@/types';

const page = usePage();
const hospital = computed(() => page.props.hospital);
const { getInitials } = useInitials();

const navItems = computed<NavItem[]>(() => [
    { title: 'Dashboard', href: '/patient/dashboard', icon: LayoutDashboard },
    { title: 'Book Appointment', href: '/patient/book', icon: Calendar },
    {
        title: 'My Appointments',
        href: '/patient/appointments',
        icon: ClipboardList,
    },
    { title: 'Find a Doctor', href: '/patient/search', icon: Search },
    { title: 'Favourites', href: '/patient/favourites', icon: Heart },
    {
        title: 'Notifications',
        href: '/patient/notifications',
        icon: Bell,
        badge: hospital.value?.unreadNotifications || undefined,
    },
    { title: 'Messages', href: '/patient/appointments', icon: MessageCircle },
    { title: 'Medical History', href: '/patient/history', icon: FileText },
    { title: 'Prescriptions', href: '/patient/prescriptions', icon: Pill },
    { title: 'Invoices', href: '/patient/invoices', icon: Receipt },
    { title: 'Health Card', href: '/patient/health-card', icon: Shield },
    { title: 'Referrals', href: '/patient/referrals', icon: Share2 },
]);

const aiItems: NavItem[] = [
    {
        title: 'Symptom Checker',
        href: '/patient/ai/symptom-checker',
        icon: Bot,
    },
    {
        title: 'Prescription Explainer',
        href: '/patient/ai/prescription-explainer',
        icon: FileText,
    },
    { title: 'Health Risk', href: '/patient/ai/health-risk', icon: Shield },
    { title: 'Health Plan', href: '/patient/ai/health-plan', icon: Sprout },
];

function logout() {
    router.post('/logout/patient');
}
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link href="/patient/dashboard">
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
                                🏥
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
                                    >Patient Portal</span
                                >
                            </span>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="navItems" label="Patient Portal" />
            <NavMain :items="aiItems" label="AI Health Tools" />
        </SidebarContent>

        <SidebarFooter>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton as-child size="lg">
                        <Link href="/patient/profile">
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
                                        getInitials(hospital?.user?.name ?? 'P')
                                    }}
                                </AvatarFallback>
                            </Avatar>
                            <span
                                class="grid flex-1 text-left text-sm leading-tight"
                            >
                                <span class="truncate font-medium">{{
                                    hospital?.user?.name
                                }}</span>
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
