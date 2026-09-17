<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Calendar,
    CalendarClock,
    CalendarDays,
    ClipboardList,
    FileEdit,
    MessageCircle,
    Pill,
    Share2,
} from '@lucide/vue';
import { computed } from 'vue';
import StatusBadge from '@/components/patient/StatusBadge.vue';

interface TodayAppointment {
    id: number;
    slot_start: string;
    slot_end: string;
    status: string;
    patient: {
        full_name: string;
        contact: string | null;
        gender: string | null;
        age: number | null;
        blood_group: string | null;
        photo: string | null;
    } | null;
}

const props = defineProps<{
    doctor: {
        name: string;
        category: string;
        consultancy_fee: number;
    };
    todayAppointments: TodayAppointment[];
    stats: {
        today: number;
        upcoming: number;
        completed: number;
        earnings: number;
    };
    pendingFollowUps: {
        prescription_id: number;
        patient_name: string;
        diagnosis: string;
        follow_up_date: string;
        is_overdue: boolean;
    }[];
}>();

const greeting = computed(() => {
    const hour = new Date().getHours();

    return hour < 12
        ? 'Good Morning'
        : hour < 17
          ? 'Good Afternoon'
          : 'Good Evening';
});

const todayLabel = computed(() =>
    new Date().toLocaleDateString('en-GB', {
        weekday: 'long',
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    }),
);

function cancelAppointment(id: number) {
    if (!confirm('Cancel this appointment?')) {
        return;
    }

    router.post(
        `/doctor/appointments/${id}/cancel`,
        {},
        { preserveScroll: true },
    );
}
</script>

<template>
    <Head title="Doctor Dashboard" />

    <div
        class="flex flex-col items-start justify-between gap-3 sm:flex-row sm:items-center"
    >
        <div>
            <h1 class="text-xl font-bold text-ink-900">Doctor Dashboard</h1>
            <p class="mt-1 text-[13.5px] text-ink-500">
                {{ greeting }},
                <span class="font-semibold text-ink-800"
                    >Dr. {{ props.doctor.name }}</span
                >
                <span class="mx-1.5">·</span>
                <span class="text-brand-600">{{ props.doctor.category }}</span>
                <span class="mx-1.5">·</span>
                <span class="font-mono text-ink-500"
                    >৳{{
                        props.doctor.consultancy_fee.toLocaleString()
                    }}/visit</span
                >
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <Link
                href="/doctor/appointments"
                class="flex items-center gap-1.5 rounded-full border border-ink-200 px-3.5 py-2 text-[12.5px] font-medium text-ink-600 hover:bg-ink-50"
                ><ClipboardList class="size-3.5" /> Appointments</Link
            >
            <Link
                href="/doctor/calendar"
                class="flex items-center gap-1.5 rounded-full border border-ink-200 px-3.5 py-2 text-[12.5px] font-medium text-ink-600 hover:bg-ink-50"
                ><CalendarDays class="size-3.5" /> Calendar</Link
            >
            <Link
                href="/doctor/templates"
                class="flex items-center gap-1.5 rounded-full border border-ink-200 px-3.5 py-2 text-[12.5px] font-medium text-ink-600 hover:bg-ink-50"
                ><FileEdit class="size-3.5" /> Templates</Link
            >
            <Link
                href="/doctor/referrals"
                class="flex items-center gap-1.5 rounded-full border border-ink-200 px-3.5 py-2 text-[12.5px] font-medium text-ink-600 hover:bg-ink-50"
                ><Share2 class="size-3.5" /> Referrals</Link
            >
        </div>
    </div>

    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
        <div class="rounded-2xl border border-ink-100 bg-white p-4 shadow-sm">
            <div class="text-2xl font-bold text-brand-600">
                {{ props.stats.today }}
            </div>
            <div class="mt-1 text-[12.5px] text-ink-500">Today's Patients</div>
        </div>
        <div class="rounded-2xl border border-ink-100 bg-white p-4 shadow-sm">
            <div class="text-2xl font-bold text-ink-900">
                {{ props.stats.upcoming }}
            </div>
            <div class="mt-1 text-[12.5px] text-ink-500">Upcoming</div>
        </div>
        <div class="rounded-2xl border border-ink-100 bg-white p-4 shadow-sm">
            <div class="text-2xl font-bold text-brand-600">
                {{ props.stats.completed }}
            </div>
            <div class="mt-1 text-[12.5px] text-ink-500">Completed</div>
        </div>
        <div class="rounded-2xl border border-ink-100 bg-white p-4 shadow-sm">
            <div class="text-xl font-bold text-ink-900">
                ৳{{ props.stats.earnings.toLocaleString() }}
            </div>
            <div class="mt-1 text-[12.5px] text-ink-500">Earnings</div>
        </div>
    </div>

    <div
        v-if="pendingFollowUps.length"
        class="rounded-2xl border border-amber-100 bg-amber-50/50 p-4 shadow-sm sm:p-5"
    >
        <h2
            class="mb-3 flex items-center gap-1.5 text-[15px] font-bold text-amber-800"
        >
            <CalendarClock class="size-4.5" /> Patients Due for Follow-up
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
                        {{ f.patient_name }}
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
            </div>
        </div>
    </div>

    <div
        class="rounded-2xl border border-ink-100 bg-white p-4 shadow-sm sm:p-5"
    >
        <h2 class="flex items-center gap-2 text-[15px] font-bold text-ink-900">
            <Calendar class="size-4.5 text-brand-500" /> Today's Schedule —
            {{ todayLabel }}
        </h2>

        <div
            v-if="!props.todayAppointments.length"
            class="py-14 text-center text-ink-400"
        >
            <Calendar class="mx-auto mb-2 size-10 opacity-20" />
            No appointments today. Rest well, Doctor!
        </div>

        <div v-else class="mt-4 overflow-x-auto">
            <table class="w-full text-left text-[13px]">
                <thead>
                    <tr class="border-b border-ink-100 text-ink-500">
                        <th class="pr-3 pb-2 font-medium">#</th>
                        <th class="pr-3 pb-2 font-medium">Patient</th>
                        <th class="pr-3 pb-2 font-medium">Age / Gender</th>
                        <th class="pr-3 pb-2 font-medium">Time</th>
                        <th class="pr-3 pb-2 font-medium">Status</th>
                        <th class="pb-2 font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="(a, i) in props.todayAppointments"
                        :key="a.id"
                        class="border-b border-ink-50 last:border-0"
                    >
                        <td class="py-2.5 pr-3 text-ink-400">{{ i + 1 }}</td>
                        <td class="py-2.5 pr-3">
                            <div class="flex items-center gap-2.5">
                                <img
                                    v-if="a.patient?.photo"
                                    :src="a.patient.photo"
                                    :alt="a.patient.full_name"
                                    class="size-8 shrink-0 rounded-full object-cover"
                                />
                                <div
                                    v-else
                                    class="flex size-8 shrink-0 items-center justify-center rounded-full bg-brand-50 text-[12px] font-semibold text-brand-600"
                                >
                                    {{
                                        (a.patient?.full_name ?? '?')
                                            .charAt(0)
                                            .toUpperCase()
                                    }}
                                </div>
                                <div>
                                    <div class="font-semibold text-ink-900">
                                        {{ a.patient?.full_name ?? '—' }}
                                    </div>
                                    <div class="text-[11.5px] text-ink-400">
                                        {{ a.patient?.contact ?? '—' }}
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="py-2.5 pr-3 text-ink-600">
                            {{ a.patient?.gender ?? '—'
                            }}<span v-if="a.patient?.age">
                                , {{ a.patient.age }} yrs</span
                            >
                            <span
                                v-if="a.patient?.blood_group"
                                class="ml-1 font-medium text-red-500"
                                >· {{ a.patient.blood_group }}</span
                            >
                        </td>
                        <td
                            class="py-2.5 pr-3 font-mono text-[12.5px] whitespace-nowrap text-ink-600"
                        >
                            {{ a.slot_start.slice(0, 5) }} –
                            {{ a.slot_end.slice(0, 5) }}
                        </td>
                        <td class="py-2.5 pr-3">
                            <StatusBadge :status="a.status" />
                        </td>
                        <td class="py-2.5">
                            <div class="flex flex-wrap gap-1.5">
                                <Link
                                    :href="`/doctor/prescription/${a.id}`"
                                    class="flex items-center gap-1 rounded-full bg-gradient-to-r from-brand-500 to-sky-600 px-3 py-1.5 text-[11.5px] font-semibold text-white shadow-sm hover:from-brand-600 hover:to-sky-700"
                                    ><Pill class="size-3" /> Write Rx</Link
                                >
                                <Link
                                    :href="`/doctor/chat/${a.id}`"
                                    class="flex items-center gap-1 rounded-full border border-ink-200 px-3 py-1.5 text-[11.5px] font-medium text-ink-600 hover:bg-ink-50"
                                    ><MessageCircle class="size-3" /> Chat</Link
                                >
                                <button
                                    type="button"
                                    class="flex items-center gap-1 rounded-full border border-red-200 px-3 py-1.5 text-[11.5px] font-medium text-red-600 hover:bg-red-50"
                                    @click="cancelAppointment(a.id)"
                                >
                                    Cancel
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
