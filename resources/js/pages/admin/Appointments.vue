<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { FileText } from '@lucide/vue';
import { ref } from 'vue';
import StatusBadge from '@/components/patient/StatusBadge.vue';

interface AppointmentRow {
    id: number;
    status: string;
    appointment_date: string | null;
    slot_start: string | null;
    slot_end: string | null;
    patient_name: string;
    patient_photo: string | null;
    doctor_name: string;
    doctor_category: string;
    doctor_photo: string | null;
    has_prescription: boolean;
}

const props = defineProps<{
    appointments: {
        data: AppointmentRow[];
        links: { url: string | null; label: string; active: boolean }[];
        from: number | null;
        to: number | null;
        total: number;
    };
    doctors: { id: number; name: string }[];
    filters: { date?: string; status?: string; doctor?: string };
}>();

const date = ref(props.filters.date ?? '');
const status = ref(props.filters.status ?? '');
const doctor = ref(props.filters.doctor ?? '');

function applyFilters() {
    router.get(
        '/admin/appointments',
        { date: date.value || undefined, status: status.value || undefined, doctor: doctor.value || undefined },
        { preserveState: true, replace: true },
    );
}
</script>

<template>
    <Head title="All Appointments" />

    <div>
        <h1 class="text-xl font-bold text-ink-900">Appointments</h1>
        <p class="mt-1 text-[13px] text-ink-500">{{ props.appointments.total }} total appointments.</p>
    </div>

    <div class="flex flex-wrap gap-2">
        <input v-model="date" type="date" class="rounded-lg border border-ink-200 px-3 py-2 text-[13px]" @change="applyFilters" />
        <select v-model="status" class="rounded-lg border border-ink-200 px-3 py-2 text-[13px]" @change="applyFilters">
            <option value="">All Status</option>
            <option value="active">Active</option>
            <option value="completed">Completed</option>
            <option value="cancelled">Cancelled</option>
        </select>
        <select v-model="doctor" class="rounded-lg border border-ink-200 px-3 py-2 text-[13px]" @change="applyFilters">
            <option value="">All Doctors</option>
            <option v-for="d in props.doctors" :key="d.id" :value="d.id">Dr. {{ d.name }}</option>
        </select>
    </div>

    <div class="overflow-x-auto rounded-2xl border border-ink-100 bg-white shadow-sm">
        <table class="w-full text-left text-[13px]">
            <thead class="border-b border-ink-100 bg-ink-25 text-[11.5px] text-ink-500">
                <tr>
                    <th class="px-4 py-3 font-medium">Patient</th>
                    <th class="px-4 py-3 font-medium">Doctor</th>
                    <th class="px-4 py-3 font-medium">Date &amp; Time</th>
                    <th class="px-4 py-3 font-medium">Status</th>
                    <th class="px-4 py-3 font-medium text-right">Rx</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-ink-50">
                <tr v-for="a in props.appointments.data" :key="a.id">
                    <td class="px-4 py-3 font-medium text-ink-800">
                        <div class="flex items-center gap-2">
                            <img v-if="a.patient_photo" :src="a.patient_photo" :alt="a.patient_name" class="size-7 rounded-full object-cover" />
                            <span v-else class="flex size-7 items-center justify-center rounded-full bg-brand-50 text-[11px] font-semibold text-brand-600">{{ a.patient_name.charAt(0).toUpperCase() }}</span>
                            <span>{{ a.patient_name }}</span>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-ink-600">
                        <div class="flex items-center gap-2">
                            <img v-if="a.doctor_photo" :src="a.doctor_photo" :alt="a.doctor_name" class="size-7 rounded-full object-cover" />
                            <div>
                                <div>{{ a.doctor_name }}</div>
                                <div class="text-[11.5px] text-ink-400">{{ a.doctor_category }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-ink-500">
                        <div>{{ a.appointment_date }}</div>
                        <div v-if="a.slot_start" class="text-[11.5px] text-ink-400">{{ a.slot_start }}–{{ a.slot_end }}</div>
                    </td>
                    <td class="px-4 py-3"><StatusBadge :status="a.status" /></td>
                    <td class="px-4 py-3 text-right">
                        <FileText v-if="a.has_prescription" class="ml-auto size-4 text-emerald-500" />
                        <span v-else class="text-ink-300">—</span>
                    </td>
                </tr>
                <tr v-if="!props.appointments.data.length">
                    <td colspan="5" class="px-4 py-10 text-center text-ink-400">No appointments match these filters.</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div v-if="props.appointments.links.length > 3" class="flex flex-wrap items-center justify-between gap-2">
        <p class="text-[12.5px] text-ink-500">Showing {{ props.appointments.from ?? 0 }}–{{ props.appointments.to ?? 0 }} of {{ props.appointments.total }}</p>
        <div class="flex flex-wrap gap-1">
            <Link
                v-for="(link, i) in props.appointments.links"
                :key="i"
                :href="link.url ?? '#'"
                class="rounded-lg px-3 py-1.5 text-[12.5px]"
                :class="[link.active ? 'bg-brand-500 text-white' : 'text-ink-500 hover:bg-ink-50', !link.url ? 'pointer-events-none opacity-40' : '']"
            >
                <span v-html="link.label" />
            </Link>
        </div>
    </div>
</template>
