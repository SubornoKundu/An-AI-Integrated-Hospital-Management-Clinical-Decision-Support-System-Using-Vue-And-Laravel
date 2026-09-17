<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    Bot,
    Calendar,
    CalendarClock,
    CheckCircle2,
    ClipboardList,
    FileText,
    Search,
    Sparkles,
} from '@lucide/vue';
import StatusBadge from '@/components/patient/StatusBadge.vue';
import Reveal from '@/components/public/Reveal.vue';

interface AppointmentCard {
    id: number;
    status: string;
    appointment_date_label: string | null;
    slot_start: string;
    slot_end: string;
    doctor: {
        id: number;
        name: string;
        category: string;
        icon: string;
        photo: string | null;
        consultancy_fee: number;
    } | null;
    has_prescription: boolean;
}

defineProps<{
    patient: { id: number; name: string; first_name: string };
    stats: { total: number; active: number; completed: number };
    upcoming: AppointmentCard[];
    recent: AppointmentCard[];
    pendingFollowUps: {
        prescription_id: number;
        doctor_name: string;
        diagnosis: string;
        follow_up_date: string;
        is_overdue: boolean;
    }[];
}>();

const quickActions = [
    {
        label: 'Book Appointment',
        href: '/patient/book',
        icon: Calendar,
        tone: 'brand',
    },
    {
        label: 'Find a Doctor',
        href: '/patient/search',
        icon: Search,
        tone: 'sky',
    },
    {
        label: 'Symptom Checker',
        href: '/patient/ai/symptom-checker',
        icon: Bot,
        tone: 'purple',
    },
    {
        label: 'My Appointments',
        href: '/patient/appointments',
        icon: ClipboardList,
        tone: 'ink',
    },
];

const toneClasses: Record<string, string> = {
    brand: 'from-brand-50 to-brand-100/40 text-brand-600 group-hover:border-brand-300',
    sky: 'from-sky-50 to-sky-100/40 text-sky-600 group-hover:border-sky-300',
    purple: 'from-purple-50 to-purple-100/40 text-purple-600 group-hover:border-purple-300',
    ink: 'from-ink-50 to-ink-100/40 text-ink-600 group-hover:border-ink-300',
};
</script>

<template>
    <Head title="Dashboard" />

    <Reveal>
        <h1 class="text-2xl font-bold text-ink-900">
            Welcome back, {{ patient.first_name }} 👋
        </h1>
        <p class="mt-1 text-sm text-ink-500">
            Here's what's happening with your health today.
        </p>
    </Reveal>

    <Reveal :delay="60" class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-2xl border border-ink-100 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span
                    class="text-xs font-semibold tracking-wide text-ink-400 uppercase"
                    >Total Visits</span
                >
                <ClipboardList class="size-4 text-ink-300" />
            </div>
            <div class="mt-2 font-mono-brand text-3xl font-bold text-ink-900">
                {{ stats.total }}
            </div>
        </div>
        <div
            class="rounded-2xl border border-brand-100 bg-brand-50/50 p-5 shadow-sm"
        >
            <div class="flex items-center justify-between">
                <span
                    class="text-xs font-semibold tracking-wide text-brand-600 uppercase"
                    >Active</span
                >
                <Calendar class="size-4 text-brand-400" />
            </div>
            <div class="mt-2 font-mono-brand text-3xl font-bold text-brand-700">
                {{ stats.active }}
            </div>
        </div>
        <div
            class="rounded-2xl border border-sky-100 bg-sky-50/50 p-5 shadow-sm"
        >
            <div class="flex items-center justify-between">
                <span
                    class="text-xs font-semibold tracking-wide text-sky-600 uppercase"
                    >Completed</span
                >
                <CheckCircle2 class="size-4 text-sky-400" />
            </div>
            <div class="mt-2 font-mono-brand text-3xl font-bold text-sky-700">
                {{ stats.completed }}
            </div>
        </div>
    </Reveal>

    <Reveal
        v-if="pendingFollowUps.length"
        :delay="100"
        class="rounded-2xl border border-amber-100 bg-amber-50/50 p-5 shadow-sm"
    >
        <h2
            class="mb-3 flex items-center gap-1.5 text-sm font-semibold text-amber-800"
        >
            <CalendarClock class="size-4" /> Follow-ups Due
        </h2>
        <div class="space-y-2.5">
            <div
                v-for="f in pendingFollowUps"
                :key="f.prescription_id"
                class="flex items-center justify-between gap-3 rounded-xl border bg-white p-3"
                :class="f.is_overdue ? 'border-red-200' : 'border-amber-200'"
            >
                <div class="min-w-0 flex-1">
                    <div class="text-[13.5px] font-semibold text-ink-900">
                        Dr. {{ f.doctor_name }}
                        <span class="font-normal text-ink-500"
                            >— {{ f.diagnosis }}</span
                        >
                    </div>
                    <div
                        class="text-[12px] font-medium"
                        :class="
                            f.is_overdue ? 'text-red-600' : 'text-amber-700'
                        "
                    >
                        {{ f.is_overdue ? 'Overdue since' : 'Due' }}
                        {{ f.follow_up_date }}
                    </div>
                </div>
                <Link
                    href="/patient/book"
                    class="shrink-0 rounded-full bg-brand-600 px-3 py-1.5 text-[11.5px] font-semibold text-white hover:bg-brand-700"
                    >Book →</Link
                >
            </div>
        </div>
    </Reveal>

    <Reveal :delay="120">
        <h2 class="mb-3 text-sm font-semibold text-ink-700">Quick Actions</h2>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            <Link
                v-for="a in quickActions"
                :key="a.label"
                :href="a.href"
                class="group flex flex-col items-center gap-2.5 rounded-2xl border border-ink-100 bg-gradient-to-br p-5 text-center shadow-sm transition-all hover:-translate-y-1 hover:shadow-md"
                :class="toneClasses[a.tone]"
            >
                <component
                    :is="a.icon"
                    class="size-6 transition-transform group-hover:scale-110"
                />
                <span
                    class="text-[12.5px] leading-tight font-semibold text-ink-800"
                    >{{ a.label }}</span
                >
            </Link>
        </div>
    </Reveal>

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
        <Reveal
            :delay="160"
            class="rounded-2xl border border-ink-100 bg-white p-5 shadow-sm"
        >
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-ink-700">
                    Upcoming Appointments
                </h2>
                <Link
                    href="/patient/appointments"
                    class="text-xs font-semibold text-brand-600 hover:underline"
                    >View all →</Link
                >
            </div>
            <div v-if="upcoming.length" class="space-y-3">
                <div
                    v-for="a in upcoming"
                    :key="a.id"
                    class="flex items-center gap-3 rounded-xl border border-ink-100 p-3 transition-colors hover:bg-ink-50/60"
                >
                    <img
                        v-if="a.doctor?.photo"
                        :src="a.doctor.photo"
                        :alt="a.doctor.name"
                        class="size-10 shrink-0 rounded-lg object-cover"
                    />
                    <div
                        v-else
                        class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-lg"
                    >
                        {{ a.doctor?.icon }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <div
                            class="truncate text-[13.5px] font-semibold text-ink-900"
                        >
                            {{ a.doctor?.name }}
                        </div>
                        <div class="text-[12px] text-ink-500">
                            {{ a.appointment_date_label }} · {{ a.slot_start }}
                        </div>
                    </div>
                    <StatusBadge :status="a.status" />
                </div>
            </div>
            <p v-else class="py-8 text-center text-sm text-ink-400">
                No upcoming appointments.
                <Link
                    href="/patient/book"
                    class="font-medium text-brand-600 hover:underline"
                    >Book one →</Link
                >
            </p>
        </Reveal>

        <Reveal
            :delay="200"
            class="rounded-2xl border border-ink-100 bg-white p-5 shadow-sm"
        >
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-ink-700">
                    Recent Activity
                </h2>
                <Link
                    href="/patient/history"
                    class="text-xs font-semibold text-brand-600 hover:underline"
                    >Full history →</Link
                >
            </div>
            <div v-if="recent.length" class="space-y-3">
                <div
                    v-for="a in recent.slice(0, 5)"
                    :key="a.id"
                    class="flex items-center gap-3 rounded-xl border border-ink-100 p-3 transition-colors hover:bg-ink-50/60"
                >
                    <img
                        v-if="a.doctor?.photo"
                        :src="a.doctor.photo"
                        :alt="a.doctor.name"
                        class="size-10 shrink-0 rounded-lg object-cover"
                    />
                    <div
                        v-else
                        class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-ink-50 text-lg"
                    >
                        {{ a.doctor?.icon }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <div
                            class="truncate text-[13.5px] font-semibold text-ink-900"
                        >
                            {{ a.doctor?.name }}
                        </div>
                        <div class="text-[12px] text-ink-500">
                            {{ a.appointment_date_label }}
                        </div>
                    </div>
                    <Link
                        v-if="a.has_prescription"
                        :href="`/patient/prescription/${a.id}/download`"
                        class="flex items-center gap-1 text-[11px] font-semibold text-sky-600 hover:underline"
                    >
                        <FileText class="size-3" /> Rx
                    </Link>
                    <StatusBadge v-else :status="a.status" />
                </div>
            </div>
            <p v-else class="py-8 text-center text-sm text-ink-400">
                No visit history yet.
            </p>
        </Reveal>
    </div>

    <Reveal
        :delay="240"
        class="flex items-center gap-4 rounded-2xl border border-purple-100 bg-gradient-to-r from-purple-50/60 to-brand-50/60 p-5"
    >
        <Sparkles class="size-8 shrink-0 text-purple-500" />
        <div class="min-w-0 flex-1">
            <div class="text-[14px] font-semibold text-ink-900">
                Not feeling well?
            </div>
            <div class="text-[12.5px] text-ink-500">
                Try our AI Symptom Checker to get instant specialist
                recommendations.
            </div>
        </div>
        <Link
            href="/patient/ai/symptom-checker"
            class="shrink-0 rounded-full bg-purple-600 px-4 py-2 text-[12.5px] font-semibold text-white shadow-sm transition-colors hover:bg-purple-700"
            >Try Now →</Link
        >
    </Reveal>
</template>
