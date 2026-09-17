<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { FileText, Receipt, Sparkles } from '@lucide/vue';
import Pagination from '@/components/patient/Pagination.vue';

interface AppointmentCard {
    id: number;
    appointment_date_label: string | null;
    doctor: { name: string; category: string; icon: string; photo: string | null } | null;
    has_prescription: boolean;
}

defineProps<{
    records: {
        data: AppointmentCard[];
        links: { url: string | null; label: string; active: boolean }[];
    };
}>();
</script>

<template>
    <Head title="Medical History" />

    <h1 class="text-xl font-bold text-ink-900">📜 Medical History</h1>

    <div v-if="records.data.length" class="space-y-3">
        <div
            v-for="r in records.data"
            :key="r.id"
            class="flex flex-col items-start gap-3 rounded-2xl border border-ink-100 bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between"
        >
            <div class="flex items-center gap-3">
                <img
                    v-if="r.doctor?.photo"
                    :src="r.doctor.photo"
                    :alt="r.doctor.name"
                    class="size-11 rounded-xl object-cover ring-1 ring-sky-100"
                />
                <div
                    v-else
                    class="flex size-11 items-center justify-center rounded-xl bg-sky-50 text-lg ring-1 ring-sky-100"
                >
                    {{ r.doctor?.icon }}
                </div>
                <div>
                    <div class="text-[14.5px] font-semibold text-ink-900">
                        {{ r.doctor?.name }}
                    </div>
                    <div class="text-[12.5px] text-ink-500">
                        {{ r.doctor?.category }} ·
                        {{ r.appointment_date_label }}
                    </div>
                </div>
            </div>
            <div class="flex gap-2">
                <a
                    :href="`/patient/appointments/${r.id}/invoice`"
                    class="flex items-center gap-1 rounded-full border border-ink-200 px-3 py-1.5 text-[12px] font-medium text-ink-600 hover:bg-ink-50"
                    ><Receipt class="size-3.5" /> Invoice</a
                >
                <a
                    v-if="r.has_prescription"
                    :href="`/patient/prescription/${r.id}/download`"
                    class="flex items-center gap-1 rounded-full border border-sky-200 px-3 py-1.5 text-[12px] font-medium text-sky-600 hover:bg-sky-50"
                    ><FileText class="size-3.5" /> Prescription</a
                >
                <Link
                    v-if="r.has_prescription"
                    :href="`/patient/ai/prescription/${r.id}/explain`"
                    class="flex items-center gap-1 rounded-full border border-purple-200 px-3 py-1.5 text-[12px] font-medium text-purple-600 hover:bg-purple-50"
                    ><Sparkles class="size-3.5" /> Explain with AI</Link
                >
            </div>
        </div>
        <Pagination :links="records.links" />
    </div>
    <div
        v-else
        class="rounded-2xl border border-ink-100 bg-white py-16 text-center text-ink-400 shadow-sm"
    >
        <div class="mb-2 text-4xl opacity-20">📜</div>
        No completed visits yet.
        <Link
            href="/patient/book"
            class="mt-2 block text-sm font-medium text-brand-600 hover:underline"
            >Book your first appointment →</Link
        >
    </div>
</template>
