<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    Bed,
    Calendar,
    ClipboardList,
    Clock,
    Stethoscope,
    Users,
    Wallet,
} from '@lucide/vue';
import { computed } from 'vue';
import StatusBadge from '@/components/patient/StatusBadge.vue';

interface TopDoctor {
    id: number;
    name: string;
    revenue: number;
    appointments: number;
}

interface RecentAppointment {
    id: number;
    status: string;
    appointment_date: string | null;
    patient_name: string;
    doctor_name: string;
    doctor_icon: string;
}

const props = defineProps<{
    stats: {
        categories: number;
        doctors: number;
        patients: number;
        appointments: number;
        revenue: number;
        today: number;
        pending: number;
        available_beds: number;
    };
    topDoctors: TopDoctor[];
    recentAppointments: RecentAppointment[];
    monthlyRevenue: { month: string; revenue: number }[];
}>();

const maxRevenue = computed(() =>
    Math.max(...props.monthlyRevenue.map((m) => m.revenue), 1),
);

const cards = computed(() => [
    { label: 'Total Doctors', value: props.stats.doctors, icon: Stethoscope, color: 'text-brand-600 bg-brand-50' },
    { label: 'Total Patients', value: props.stats.patients, icon: Users, color: 'text-sky-600 bg-sky-50' },
    { label: 'Appointments', value: props.stats.appointments, icon: ClipboardList, color: 'text-violet-600 bg-violet-50' },
    { label: "Today's Appointments", value: props.stats.today, icon: Clock, color: 'text-amber-600 bg-amber-50' },
    { label: 'Total Revenue', value: `৳${props.stats.revenue.toLocaleString()}`, icon: Wallet, color: 'text-emerald-600 bg-emerald-50' },
    { label: 'Available Beds', value: props.stats.available_beds, icon: Bed, color: 'text-rose-600 bg-rose-50' },
]);
</script>

<template>
    <Head title="Admin Dashboard" />

    <div>
        <h1 class="text-xl font-bold text-ink-900">Dashboard</h1>
        <p class="mt-1 text-[13px] text-ink-500">
            Hospital overview and performance at a glance.
        </p>
    </div>

    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
        <div
            v-for="c in cards"
            :key="c.label"
            class="rounded-2xl border border-ink-100 bg-white p-4 shadow-sm"
        >
            <div
                class="mb-2 flex size-8 items-center justify-center rounded-lg"
                :class="c.color"
            >
                <component :is="c.icon" class="size-4" />
            </div>
            <div class="text-lg font-bold text-ink-900">{{ c.value }}</div>
            <div class="mt-0.5 text-[11.5px] text-ink-500">{{ c.label }}</div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <div
            class="rounded-2xl border border-ink-100 bg-white p-4 shadow-sm lg:col-span-2"
        >
            <h2 class="mb-4 text-[13.5px] font-bold text-ink-900">
                Revenue — Last 6 Months
            </h2>
            <div class="flex h-44 items-end gap-3">
                <div
                    v-for="m in props.monthlyRevenue"
                    :key="m.month"
                    class="group relative flex flex-1 flex-col items-center"
                >
                    <span
                        class="pointer-events-none mb-1 rounded bg-ink-900 px-1.5 py-0.5 text-[10px] whitespace-nowrap text-white opacity-0 group-hover:opacity-100"
                        >৳{{ m.revenue.toLocaleString() }}</span
                    >
                    <div
                        class="w-full rounded-t bg-gradient-to-t from-brand-500 to-sky-400 transition-colors group-hover:from-brand-600 group-hover:to-sky-500"
                        :style="{
                            height: `${(m.revenue / maxRevenue) * 100}%`,
                            minHeight: m.revenue > 0 ? '4px' : '0',
                        }"
                    />
                    <span class="mt-1.5 text-[10.5px] text-ink-400">{{
                        m.month
                    }}</span>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-ink-100 bg-white p-4 shadow-sm">
            <h2 class="mb-3 text-[13.5px] font-bold text-ink-900">
                Top Doctors
            </h2>
            <div v-if="props.topDoctors.length" class="space-y-3">
                <div
                    v-for="(d, i) in props.topDoctors"
                    :key="d.id"
                    class="flex items-center gap-2.5"
                >
                    <span
                        class="flex size-6 shrink-0 items-center justify-center rounded-full bg-ink-100 text-[11px] font-bold text-ink-600"
                        >{{ i + 1 }}</span
                    >
                    <div class="min-w-0 flex-1">
                        <div class="truncate text-[12.5px] font-medium text-ink-800">
                            {{ d.name }}
                        </div>
                        <div class="text-[11px] text-ink-400">
                            {{ d.appointments }} visits
                        </div>
                    </div>
                    <div class="text-[12.5px] font-semibold text-brand-600">
                        ৳{{ d.revenue.toLocaleString() }}
                    </div>
                </div>
            </div>
            <p v-else class="py-6 text-center text-[13px] text-ink-400">
                No completed appointments yet.
            </p>
        </div>
    </div>

    <div class="rounded-2xl border border-ink-100 bg-white p-4 shadow-sm">
        <div class="mb-3 flex items-center justify-between">
            <h2 class="text-[13.5px] font-bold text-ink-900">
                Recent Appointments
            </h2>
            <Link
                href="/admin/appointments"
                class="text-[12px] font-medium text-brand-600 hover:underline"
                >View all →</Link
            >
        </div>
        <div v-if="props.recentAppointments.length" class="divide-y divide-ink-50">
            <div
                v-for="a in props.recentAppointments"
                :key="a.id"
                class="flex items-center gap-3 py-2.5"
            >
                <span
                    class="flex size-8 shrink-0 items-center justify-center rounded-full bg-brand-50 text-sm"
                    >{{ a.doctor_icon }}</span
                >
                <div class="min-w-0 flex-1">
                    <div class="truncate text-[13px] font-medium text-ink-800">
                        {{ a.patient_name }}
                        <span class="text-ink-400">→</span>
                        {{ a.doctor_name }}
                    </div>
                    <div class="flex items-center gap-1 text-[11px] text-ink-400">
                        <Calendar class="size-3" /> {{ a.appointment_date }}
                    </div>
                </div>
                <StatusBadge :status="a.status" />
            </div>
        </div>
        <p v-else class="py-6 text-center text-[13px] text-ink-400">
            No appointments yet.
        </p>
    </div>
</template>
