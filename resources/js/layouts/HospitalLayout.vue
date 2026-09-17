<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Bell, LogOut, Menu, X } from '@lucide/vue';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { about, contact, home, login } from '@/routes';
import {
    aiInsights,
    appointments as adminAppointments,
    dashboard as adminDashboard,
    doctors as adminDoctors,
    patients as adminPatients,
    revenue,
    settings as adminSettings,
} from '@/routes/admin';
import {
    appointments as doctorAppointments,
    calendar,
    dashboard as doctorDashboard,
    earnings,
    notifications as doctorNotifications,
} from '@/routes/doctor';
import {
    admin as logoutAdmin,
    doctor as logoutDoctor,
    patient as logoutPatient,
} from '@/routes/logout';
import {
    appointments as patientAppointments,
    dashboard as patientDashboard,
    notifications as patientNotifications,
    search,
} from '@/routes/patient';
import { risk, symptomChecker } from '@/routes/patient/ai';
import { step1 } from '@/routes/patient/book';

type Role = 'patient' | 'doctor' | 'admin' | null;

interface PageProps {
    auth: { role: Role; user: { name?: string; full_name?: string } | null };
    hospital: { name: string; logo: string };
    notifCount: number;
    flash: { success?: string | null; error?: string | null };
    [key: string]: unknown;
}

const page = usePage<PageProps>();

const role = computed(() => page.props.auth.role);
const userName = computed(
    () => page.props.auth.user?.full_name ?? page.props.auth.user?.name ?? '',
);
const hospitalName = computed(() => page.props.hospital.name);
const hospitalLogo = computed(() => page.props.hospital.logo);
const notifCount = computed(() => page.props.notifCount);

const dashboardHref = computed(() => {
    if (role.value === 'patient') {
        return patientDashboard();
    }

    if (role.value === 'doctor') {
        return doctorDashboard();
    }

    if (role.value === 'admin') {
        return adminDashboard();
    }

    return home();
});

const navLinks = computed(() => {
    switch (role.value) {
        case 'patient':
            return [
                { href: patientDashboard(), label: 'Dashboard' },
                { href: step1(), label: '📅 Book' },
                { href: patientAppointments(), label: 'Appointments' },
                { href: search(), label: 'Find Doctor' },
                { href: symptomChecker(), label: '🤖 Symptom AI' },
                { href: risk(), label: '🛡️ Risk AI' },
            ];
        case 'doctor':
            return [
                { href: doctorDashboard(), label: 'Dashboard' },
                { href: doctorAppointments(), label: 'Appointments' },
                { href: calendar(), label: 'Calendar' },
                { href: earnings(), label: 'Earnings' },
            ];
        case 'admin':
            return [
                { href: adminDashboard(), label: 'Dashboard' },
                { href: adminDoctors(), label: 'Doctors' },
                { href: adminPatients(), label: 'Patients' },
                { href: adminAppointments(), label: 'Appointments' },
                { href: revenue(), label: 'Revenue' },
                { href: aiInsights(), label: '🧠 AI Insights' },
                { href: adminSettings(), label: 'Settings' },
            ];
        default:
            return [
                { href: home(), label: 'Home' },
                { href: about(), label: 'About' },
                { href: contact(), label: 'Contact' },
            ];
    }
});

const notifLink = computed(() => {
    if (role.value === 'patient') {
        return patientNotifications();
    }

    if (role.value === 'doctor') {
        return doctorNotifications();
    }

    return null;
});

const logoutHref = computed(() => {
    if (role.value === 'patient') {
        return logoutPatient();
    }

    if (role.value === 'doctor') {
        return logoutDoctor();
    }

    if (role.value === 'admin') {
        return logoutAdmin();
    }

    return null;
});

const mobileOpen = ref(false);
</script>

<template>
    <div class="flex min-h-screen flex-col bg-background">
        <header
            class="sticky top-0 z-40 border-b bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60"
        >
            <div class="mx-auto flex h-16 max-w-7xl items-center gap-4 px-4">
                <Link
                    :href="dashboardHref"
                    class="flex items-center gap-2 font-semibold"
                >
                    <img
                        v-if="hospitalLogo"
                        :src="`/${hospitalLogo}`"
                        :alt="hospitalName"
                        class="h-8 w-8 rounded-md object-contain"
                    />
                    <span v-else class="text-2xl">🏥</span>
                    <span class="hidden sm:inline">{{ hospitalName }}</span>
                </Link>

                <nav class="ml-4 hidden flex-1 items-center gap-1 md:flex">
                    <Link
                        v-for="link in navLinks"
                        :key="link.label"
                        :href="link.href"
                        class="rounded-md px-3 py-2 text-sm font-medium text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground"
                    >
                        {{ link.label }}
                    </Link>
                </nav>

                <div class="ml-auto flex items-center gap-2">
                    <template v-if="role">
                        <Link
                            v-if="notifLink"
                            :href="notifLink"
                            class="relative rounded-md p-2 text-muted-foreground hover:bg-accent hover:text-accent-foreground"
                        >
                            <Bell class="h-5 w-5" />
                            <span
                                v-if="notifCount > 0"
                                class="absolute -top-0.5 -right-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-destructive px-1 text-[10px] font-semibold text-white"
                            >
                                {{ notifCount > 9 ? '9+' : notifCount }}
                            </span>
                        </Link>

                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <Button
                                    variant="ghost"
                                    class="hidden gap-2 sm:flex"
                                >
                                    {{ userName }}
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end">
                                <DropdownMenuItem as-child>
                                    <Link
                                        v-if="logoutHref"
                                        :href="logoutHref"
                                        as="button"
                                        class="flex w-full items-center gap-2"
                                    >
                                        <LogOut class="h-4 w-4" /> Logout
                                    </Link>
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </template>
                    <Button v-else as-child size="sm">
                        <Link :href="login()">Login</Link>
                    </Button>

                    <button
                        class="rounded-md p-2 text-muted-foreground hover:bg-accent md:hidden"
                        @click="mobileOpen = !mobileOpen"
                    >
                        <Menu v-if="!mobileOpen" class="h-5 w-5" />
                        <X v-else class="h-5 w-5" />
                    </button>
                </div>
            </div>

            <nav v-if="mobileOpen" class="border-t px-4 py-2 md:hidden">
                <Link
                    v-for="link in navLinks"
                    :key="link.label"
                    :href="link.href"
                    class="block rounded-md px-3 py-2 text-sm font-medium text-muted-foreground hover:bg-accent hover:text-accent-foreground"
                    @click="mobileOpen = false"
                >
                    {{ link.label }}
                </Link>
            </nav>
        </header>

        <div
            v-if="page.props.flash.success"
            class="mx-auto mt-3 w-full max-w-7xl px-4"
        >
            <div
                class="rounded-md border border-green-200 bg-green-50 px-4 py-2.5 text-sm text-green-800 dark:border-green-900 dark:bg-green-950 dark:text-green-300"
            >
                {{ page.props.flash.success }}
            </div>
        </div>
        <div
            v-if="page.props.flash.error"
            class="mx-auto mt-3 w-full max-w-7xl px-4"
        >
            <div
                class="rounded-md border border-red-200 bg-red-50 px-4 py-2.5 text-sm text-red-800 dark:border-red-900 dark:bg-red-950 dark:text-red-300"
            >
                {{ page.props.flash.error }}
            </div>
        </div>

        <main class="mx-auto w-full max-w-7xl flex-1 px-4 py-6">
            <slot />
        </main>

        <footer class="border-t py-4 text-center text-sm text-muted-foreground">
            © {{ new Date().getFullYear() }} {{ hospitalName }} · Dhaka,
            Bangladesh ·
            <Link :href="contact()" class="text-primary hover:underline"
                >Contact Us</Link
            >
        </footer>
    </div>
</template>
