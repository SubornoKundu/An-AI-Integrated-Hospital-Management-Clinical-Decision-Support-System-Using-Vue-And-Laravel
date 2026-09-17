<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import {
    Bell,
    Calendar,
    ClipboardList,
    DollarSign,
    LayoutDashboard,
    LogOut,
    Menu,
    Search,
    Settings,
    Shield,
    Sparkles,
    Stethoscope,
    Users,
} from '@lucide/vue';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';

const page = usePage();
const hospital = computed(() => page.props.hospital);
const role = computed(() => hospital.value?.role ?? null);

const scrolled = ref(false);
function onScroll() {
    scrolled.value = window.scrollY > 12;
}
onMounted(() => {
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
});
onUnmounted(() => window.removeEventListener('scroll', onScroll));

const mobileOpen = ref(false);

type NavItem = { label: string; href: string; icon?: unknown; ai?: boolean };

const guestLinks: NavItem[] = [
    { label: 'Home', href: '/' },
    { label: 'About', href: '/about' },
    { label: 'Contact', href: '/contact' },
];

const patientLinks: NavItem[] = [
    { label: 'Dashboard', href: '/patient/dashboard', icon: LayoutDashboard },
    { label: 'Book', href: '/patient/book', icon: Calendar },
    {
        label: 'Appointments',
        href: '/patient/appointments',
        icon: ClipboardList,
    },
    { label: 'Find Doctor', href: '/patient/search', icon: Search },
    {
        label: 'Symptom AI',
        href: '/patient/ai/symptom-checker',
        icon: Sparkles,
        ai: true,
    },
    {
        label: 'Risk AI',
        href: '/patient/ai/health-risk',
        icon: Shield,
        ai: true,
    },
];

const doctorLinks: NavItem[] = [
    { label: 'Dashboard', href: '/doctor/dashboard', icon: LayoutDashboard },
    {
        label: 'Appointments',
        href: '/doctor/appointments',
        icon: ClipboardList,
    },
    { label: 'Calendar', href: '/doctor/calendar', icon: Calendar },
    { label: 'Earnings', href: '/doctor/earnings', icon: DollarSign },
];

const adminLinks: NavItem[] = [
    { label: 'Dashboard', href: '/admin/dashboard', icon: LayoutDashboard },
    { label: 'Doctors', href: '/admin/doctors', icon: Stethoscope },
    { label: 'Patients', href: '/admin/patients', icon: Users },
    { label: 'Appointments', href: '/admin/appointments', icon: ClipboardList },
    { label: 'Revenue', href: '/admin/revenue', icon: DollarSign },
    {
        label: 'AI Insights',
        href: '/admin/ai-insights',
        icon: Sparkles,
        ai: true,
    },
    { label: 'Settings', href: '/admin/settings', icon: Settings },
];

const navLinks = computed<NavItem[]>(() => {
    switch (role.value) {
        case 'patient':
            return patientLinks;
        case 'doctor':
            return doctorLinks;
        case 'admin':
            return adminLinks;
        default:
            return guestLinks;
    }
});

const homeHref = computed(() => {
    if (role.value) {
        return `/${role.value}/dashboard`;
    }

    return '/';
});

const notifHref = computed(() => {
    if (role.value === 'patient') {
        return '/patient/notifications';
    }

    if (role.value === 'doctor') {
        return '/doctor/notifications';
    }

    return '#';
});

function logout() {
    mobileOpen.value = false;

    if (role.value) {
        router.post(`/logout/${role.value}`);
    }
}
</script>

<template>
    <header
        class="fixed inset-x-0 top-0 z-50 transition-all duration-300"
        :class="
            scrolled
                ? 'bg-white/85 shadow-[0_1px_0_0_rgba(16,31,61,0.06),0_8px_24px_-12px_rgba(16,31,61,0.15)] backdrop-blur-md'
                : 'bg-transparent'
        "
    >
        <div
            class="mx-auto flex h-16 max-w-7xl items-center gap-4 px-4 sm:px-6 lg:px-8"
        >
            <!-- Brand -->
            <Link
                :href="homeHref"
                class="flex shrink-0 items-center gap-2.5 font-heading font-bold text-ink-900"
            >
                <span
                    v-if="hospital?.logo"
                    class="flex h-9 w-9 items-center justify-center overflow-hidden rounded-lg bg-white ring-1 ring-ink-200"
                >
                    <img
                        :src="hospital.logo"
                        :alt="hospital.name"
                        class="h-full w-full object-contain"
                    />
                </span>
                <span
                    v-else
                    class="flex h-9 w-9 items-center justify-center rounded-lg bg-gradient-to-br from-brand-500 to-sky-600 text-lg shadow-sm shadow-brand-500/30"
                >
                    🏥
                </span>
                <span class="hidden text-[17px] tracking-tight sm:inline">{{
                    hospital?.name ?? 'MediCare Hospital'
                }}</span>
            </Link>

            <!-- Desktop nav -->
            <nav class="ml-4 hidden flex-1 items-center gap-1 lg:flex">
                <Link
                    v-for="item in navLinks"
                    :key="item.href"
                    :href="item.href"
                    class="group relative flex items-center gap-1.5 rounded-full px-3.5 py-2 text-[14px] font-medium transition-colors"
                    :class="
                        item.ai
                            ? 'text-brand-700 hover:bg-brand-50'
                            : 'text-ink-600 hover:bg-ink-50 hover:text-ink-900'
                    "
                >
                    <component
                        :is="item.icon"
                        v-if="item.icon"
                        class="size-[15px]"
                    />
                    {{ item.label }}
                </Link>
            </nav>

            <div class="ml-auto flex items-center gap-2">
                <template v-if="role">
                    <Link
                        v-if="notifHref !== '#'"
                        :href="notifHref"
                        class="relative hidden size-9 items-center justify-center rounded-full text-ink-500 transition-colors hover:bg-ink-50 hover:text-ink-900 sm:inline-flex"
                        aria-label="Notifications"
                    >
                        <Bell class="size-[18px]" />
                        <span
                            v-if="hospital?.unreadNotifications"
                            class="absolute -top-0.5 -right-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-brand-500 px-1 text-[10px] font-semibold text-white"
                        >
                            {{
                                hospital.unreadNotifications > 9
                                    ? '9+'
                                    : hospital.unreadNotifications
                            }}
                        </span>
                    </Link>
                    <span
                        class="hidden rounded-full bg-ink-50 px-3 py-1.5 text-[13px] font-medium text-ink-700 md:inline-block"
                    >
                        {{ hospital?.user?.name }}
                    </span>
                    <Button
                        variant="ghost"
                        size="sm"
                        class="hidden gap-1.5 sm:inline-flex"
                        @click="logout"
                    >
                        <LogOut class="size-4" /> Logout
                    </Button>
                </template>
                <template v-else>
                    <Button
                        as-child
                        variant="ghost"
                        size="sm"
                        class="hidden sm:inline-flex"
                    >
                        <Link href="/login">Login</Link>
                    </Button>
                    <Button
                        as-child
                        size="sm"
                        class="hidden bg-brand-600 shadow-sm shadow-brand-600/25 hover:bg-brand-700 sm:inline-flex"
                    >
                        <Link href="/register">Get Started</Link>
                    </Button>
                </template>

                <button
                    class="inline-flex size-9 items-center justify-center rounded-full text-ink-700 transition-colors hover:bg-ink-50 lg:hidden"
                    aria-label="Open menu"
                    @click="mobileOpen = true"
                >
                    <Menu class="size-5" />
                </button>
            </div>
        </div>
    </header>

    <!-- Mobile menu -->
    <Sheet v-model:open="mobileOpen">
        <SheetContent side="right" class="w-[85vw] max-w-sm p-0">
            <SheetHeader class="border-b border-ink-100 px-5 py-4 text-left">
                <SheetTitle class="font-heading">{{
                    hospital?.name ?? 'MediCare Hospital'
                }}</SheetTitle>
            </SheetHeader>
            <nav class="flex flex-col gap-1 p-3">
                <Link
                    v-for="item in navLinks"
                    :key="item.href"
                    :href="item.href"
                    class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-[15px] font-medium transition-colors hover:bg-ink-50"
                    :class="item.ai ? 'text-brand-700' : 'text-ink-700'"
                    @click="mobileOpen = false"
                >
                    <component
                        :is="item.icon"
                        v-if="item.icon"
                        class="size-[18px]"
                    />
                    {{ item.label }}
                </Link>
            </nav>
            <div class="mt-auto border-t border-ink-100 p-4">
                <template v-if="role">
                    <Button
                        variant="outline"
                        class="w-full gap-2"
                        @click="logout"
                    >
                        <LogOut class="size-4" /> Logout
                    </Button>
                </template>
                <template v-else>
                    <div class="flex flex-col gap-2">
                        <Button as-child variant="outline">
                            <Link href="/login" @click="mobileOpen = false"
                                >Login</Link
                            >
                        </Button>
                        <Button
                            as-child
                            class="bg-brand-600 hover:bg-brand-700"
                        >
                            <Link href="/register" @click="mobileOpen = false"
                                >Get Started</Link
                            >
                        </Button>
                    </div>
                </template>
            </div>
        </SheetContent>
    </Sheet>
</template>
