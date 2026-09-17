<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { CalendarDays, MessageCircle, Pill, Search } from '@lucide/vue';
import { reactive, ref } from 'vue';
import Pagination from '@/components/patient/Pagination.vue';
import StatusBadge from '@/components/patient/StatusBadge.vue';

interface AppointmentItem {
    id: number;
    appointment_date: string;
    slot_start: string;
    slot_end: string;
    status: string;
    has_prescription: boolean;
    patient: {
        full_name: string;
        gender: string | null;
        age: number | null;
        blood_group: string | null;
        photo: string | null;
    } | null;
}

interface PageLink {
    url: string | null;
    label: string;
    active: boolean;
}

const props = defineProps<{
    appointments: {
        data: AppointmentItem[];
        links: PageLink[];
        from: number | null;
    };
    filters: {
        date: string | null;
        status: string | null;
    };
}>();

const filterState = reactive({
    date: props.filters.date ?? '',
    status: props.filters.status ?? '',
});

const visitNotes = reactive<Record<number, string>>({});

function applyFilters() {
    router.get(
        '/doctor/appointments',
        { date: filterState.date || undefined, status: filterState.status || undefined },
        { preserveState: true, preserveScroll: true },
    );
}

function resetFilters() {
    filterState.date = '';
    filterState.status = '';
    router.get('/doctor/appointments');
}

const actioningId = ref<number | null>(null);

function completeAppointment(id: number) {
    if (!confirm('Mark as completed?')) {
        return;
    }

    actioningId.value = id;
    router.post(
        `/doctor/appointments/${id}/complete`,
        { visit_notes: visitNotes[id] ?? '' },
        { preserveScroll: true, onFinish: () => {
 actioningId.value = null; 
} },
    );
}

function cancelAppointment(id: number) {
    if (!confirm('Cancel this appointment?')) {
        return;
    }

    actioningId.value = id;
    router.post(`/doctor/appointments/${id}/cancel`, {}, {
        preserveScroll: true,
        onFinish: () => {
 actioningId.value = null; 
},
    });
}
</script>

<template>
    <Head title="My Appointments" />

    <div>
        <h1 class="text-xl font-bold text-ink-900">My Appointments</h1>
        <p class="mt-1 text-[13.5px] text-ink-500">
            Manage your scheduled and completed patient appointments
        </p>
    </div>

    <div
        class="flex flex-wrap items-center gap-2 rounded-2xl border border-ink-100 bg-white p-3 shadow-sm"
    >
        <input
            v-model="filterState.date"
            type="date"
            class="rounded-lg border border-ink-200 px-3 py-2 text-[13px] text-ink-700"
        />
        <select
            v-model="filterState.status"
            class="rounded-lg border border-ink-200 px-3 py-2 text-[13px] text-ink-700"
        >
            <option value="">All Status</option>
            <option value="active">Active</option>
            <option value="completed">Completed</option>
            <option value="cancelled_by_doctor">Cancelled by Me</option>
            <option value="cancelled_by_patient">Cancelled by Patient</option>
            <option value="cancelled_by_admin">Cancelled — Holiday</option>
        </select>
        <button
            type="button"
            class="flex items-center gap-1.5 rounded-full bg-gradient-to-r from-brand-500 to-sky-600 px-4 py-2 text-[12.5px] font-semibold text-white shadow-sm"
            @click="applyFilters"
        >
            <Search class="size-3.5" /> Filter
        </button>
        <button
            type="button"
            class="rounded-full border border-ink-200 px-4 py-2 text-[12.5px] font-medium text-ink-600 hover:bg-ink-50"
            @click="resetFilters"
        >
            Reset
        </button>
    </div>

    <div v-if="props.appointments.data.length" class="space-y-3">
        <div
            v-for="a in props.appointments.data"
            :key="a.id"
            class="rounded-2xl border border-ink-100 bg-white p-4 shadow-sm"
        >
            <div
                class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="flex items-center gap-3">
                    <img
                        v-if="a.patient?.photo"
                        :src="a.patient.photo"
                        :alt="a.patient.full_name"
                        class="size-10 shrink-0 rounded-full object-cover"
                    />
                    <div
                        v-else
                        class="flex size-10 shrink-0 items-center justify-center rounded-full bg-brand-50 text-[13px] font-semibold text-brand-600"
                    >
                        {{ (a.patient?.full_name ?? '?').charAt(0).toUpperCase() }}
                    </div>
                    <div>
                        <div class="text-[14.5px] font-semibold text-ink-900">
                            {{ a.patient?.full_name ?? '—' }}
                        </div>
                        <div class="text-[12px] text-ink-500">
                            {{ a.patient?.gender ?? '' }}
                            <span v-if="a.patient?.age"
                                >· {{ a.patient.age }} yrs</span
                            >
                            <span
                                v-if="a.patient?.blood_group"
                                class="font-medium text-red-500"
                                >· {{ a.patient.blood_group }}</span
                            >
                        </div>
                        <div class="mt-1 flex items-center gap-2 text-[12px] text-ink-500">
                            <CalendarDays class="size-3" />
                            {{ a.appointment_date }}
                            <span class="font-mono"
                                >{{ a.slot_start.slice(0, 5) }} –
                                {{ a.slot_end.slice(0, 5) }}</span
                            >
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <StatusBadge :status="a.status" />
                    <span
                        v-if="a.has_prescription"
                        class="text-[12px] font-semibold text-brand-600"
                        >✓ Rx Done</span
                    >
                </div>
            </div>

            <div
                v-if="a.status === 'active'"
                class="mt-3 flex flex-col gap-2 border-t border-ink-50 pt-3 sm:flex-row sm:items-center"
            >
                <Link
                    :href="`/doctor/prescription/${a.id}`"
                    class="flex items-center justify-center gap-1 rounded-full bg-gradient-to-r from-brand-500 to-sky-600 px-3.5 py-2 text-[12.5px] font-semibold text-white shadow-sm hover:from-brand-600 hover:to-sky-700"
                    ><Pill class="size-3.5" /> Write Rx</Link
                >
                <Link
                    :href="`/doctor/chat/${a.id}`"
                    class="flex items-center justify-center gap-1 rounded-full border border-ink-200 px-3.5 py-2 text-[12.5px] font-medium text-ink-600 hover:bg-ink-50"
                    ><MessageCircle class="size-3.5" /> Chat</Link
                >
                <input
                    v-model="visitNotes[a.id]"
                    type="text"
                    placeholder="Visit notes (optional)"
                    class="flex-1 rounded-full border border-ink-200 px-3.5 py-2 text-[12.5px] text-ink-700"
                />
                <button
                    type="button"
                    class="rounded-full border border-brand-200 px-3.5 py-2 text-[12.5px] font-medium text-brand-600 hover:bg-brand-50 disabled:cursor-not-allowed disabled:opacity-50"
                    :disabled="actioningId === a.id"
                    @click="completeAppointment(a.id)"
                >
                    {{ actioningId === a.id ? 'Saving…' : '✓ Complete' }}
                </button>
                <button
                    type="button"
                    class="rounded-full border border-red-200 px-3.5 py-2 text-[12.5px] font-medium text-red-600 hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-50"
                    :disabled="actioningId === a.id"
                    @click="cancelAppointment(a.id)"
                >
                    {{ actioningId === a.id ? 'Cancelling…' : '✕ Cancel' }}
                </button>
            </div>
            <div
                v-else-if="a.status === 'completed'"
                class="mt-3 flex gap-2 border-t border-ink-50 pt-3"
            >
                <Link
                    :href="`/doctor/prescription/${a.id}`"
                    class="flex flex-1 items-center justify-center gap-1 rounded-full border border-ink-200 px-3.5 py-2 text-[12.5px] font-medium text-ink-600 hover:bg-ink-50"
                    ><Pill class="size-3.5" /> View Rx</Link
                >
                <Link
                    :href="`/doctor/chat/${a.id}`"
                    class="flex flex-1 items-center justify-center gap-1 rounded-full border border-ink-200 px-3.5 py-2 text-[12.5px] font-medium text-ink-600 hover:bg-ink-50"
                    ><MessageCircle class="size-3.5" /> Chat</Link
                >
            </div>
        </div>
        <Pagination :links="props.appointments.links" />
    </div>
    <div
        v-else
        class="rounded-2xl border border-ink-100 bg-white py-16 text-center text-ink-400 shadow-sm"
    >
        <CalendarDays class="mx-auto mb-2 size-10 opacity-20" />
        No appointments found.
    </div>
</template>
