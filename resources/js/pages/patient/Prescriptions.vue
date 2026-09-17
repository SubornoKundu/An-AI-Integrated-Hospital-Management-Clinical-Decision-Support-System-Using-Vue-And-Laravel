<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { FileText, Pill } from '@lucide/vue';
import Reveal from '@/components/public/Reveal.vue';

interface AppointmentItem {
    id: number;
    appointment_date: string;
    diagnosis: string;
    doctor: { name: string; category: string; icon: string; photo: string | null };
}

defineProps<{
    appointments: AppointmentItem[];
}>();
</script>

<template>
    <Head title="Prescriptions" />

    <h1 class="flex items-center gap-2 text-xl font-bold text-ink-900">
        <Pill class="size-6 text-brand-500" /> Prescriptions
    </h1>

    <div v-if="appointments.length" class="mt-4 space-y-3">
        <Reveal
            v-for="(a, i) in appointments"
            :key="a.id"
            :delay="i * 40"
            class="flex flex-col items-start gap-3 rounded-2xl border border-ink-100 bg-white p-4 shadow-sm transition-shadow hover:shadow-md sm:flex-row sm:items-center sm:justify-between"
        >
            <div class="flex items-center gap-3">
                <img
                    v-if="a.doctor.photo"
                    :src="a.doctor.photo"
                    :alt="a.doctor.name"
                    class="size-11 shrink-0 rounded-xl object-cover ring-1 ring-brand-100"
                />
                <div
                    v-else
                    class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-lg ring-1 ring-brand-100"
                >
                    {{ a.doctor.icon }}
                </div>
                <div>
                    <div class="text-[14.5px] font-semibold text-ink-900">
                        {{ a.doctor.name }}
                    </div>
                    <div class="text-[12.5px] text-ink-500">
                        {{ a.doctor.category }} · {{ a.appointment_date }}
                    </div>
                    <div class="mt-0.5 text-[12.5px] text-ink-600">
                        {{ a.diagnosis }}
                    </div>
                </div>
            </div>
            <a
                :href="`/patient/prescription/${a.id}/download`"
                target="_blank"
                rel="noopener noreferrer"
                class="flex shrink-0 items-center gap-1.5 rounded-full bg-gradient-to-r from-brand-500 to-sky-600 px-4 py-2 text-[12.5px] font-semibold text-white shadow-sm hover:from-brand-600 hover:to-sky-700"
            >
                <FileText class="size-3.5" /> View Prescription
            </a>
        </Reveal>
    </div>
    <div
        v-else
        class="mt-4 rounded-2xl border border-ink-100 bg-white py-16 text-center text-ink-400 shadow-sm"
    >
        <Pill class="mx-auto mb-2 size-10 opacity-20" />
        No prescriptions yet. Once a doctor prescribes medicine after a visit,
        it'll show up here.
    </div>
</template>
