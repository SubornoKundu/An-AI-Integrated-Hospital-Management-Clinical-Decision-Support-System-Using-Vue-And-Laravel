<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Share2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import Pagination from '@/components/patient/Pagination.vue';

interface ReferralItem {
    id: number;
    created_at: string;
    reason: string;
    patient_name: string;
    to_category: string | null;
}

interface Patient {
    id: number;
    name: string;
}

interface AppointmentOption {
    id: number;
    label: string;
}

interface PageLink {
    url: string | null;
    label: string;
    active: boolean;
}

const props = defineProps<{
    referrals: { data: ReferralItem[]; links: PageLink[] };
    categories: { id: number; name: string }[];
    patients: Patient[];
    patientAppointments: Record<string, AppointmentOption[]>;
}>();

const showForm = ref(false);
const form = useForm({
    patient_id: '',
    to_category_id: '',
    appointment_id: '',
    reason: '',
    notes: '',
});

const availableAppointments = computed(
    () => props.patientAppointments[form.patient_id] ?? [],
);

function submit() {
    form.post('/doctor/referrals', {
        preserveScroll: true,
        onSuccess: () => {
            showForm.value = false;
            form.reset();
        },
    });
}
</script>

<template>
    <Head title="Referrals" />

    <div class="flex items-center justify-between">
        <h1 class="flex items-center gap-2 text-xl font-bold text-ink-900">
            <Share2 class="size-5 text-brand-500" /> Referrals
        </h1>
        <button
            type="button"
            class="rounded-full bg-gradient-to-r from-brand-500 to-sky-600 px-4 py-2 text-[12.5px] font-semibold text-white shadow-sm"
            @click="showForm = !showForm"
        >
            {{ showForm ? 'Close' : '+ New Referral' }}
        </button>
    </div>

    <div
        v-if="showForm"
        class="rounded-2xl border border-brand-200 bg-white p-4 shadow-sm"
    >
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <select
                v-model="form.patient_id"
                class="rounded-lg border border-ink-200 px-3 py-2 text-[13px]"
            >
                <option value="">Select patient…</option>
                <option v-for="p in props.patients" :key="p.id" :value="p.id">
                    {{ p.name }}
                </option>
            </select>
            <p v-if="form.errors.patient_id" class="text-[11.5px] text-red-500 sm:col-span-2">
                {{ form.errors.patient_id }}
            </p>
            <select
                v-model="form.appointment_id"
                :disabled="!form.patient_id"
                class="rounded-lg border border-ink-200 px-3 py-2 text-[13px] disabled:opacity-50"
            >
                <option value="">Related appointment (optional)</option>
                <option
                    v-for="a in availableAppointments"
                    :key="a.id"
                    :value="a.id"
                >
                    {{ a.label }}
                </option>
            </select>
            <select
                v-model="form.to_category_id"
                class="rounded-lg border border-ink-200 px-3 py-2 text-[13px] sm:col-span-2"
            >
                <option value="">Refer to specialty…</option>
                <option v-for="c in props.categories" :key="c.id" :value="c.id">
                    {{ c.name }}
                </option>
            </select>
            <textarea
                v-model="form.reason"
                rows="2"
                placeholder="Reason for referral *"
                class="rounded-lg border border-ink-200 px-3 py-2 text-[13px] sm:col-span-2"
            />
            <p v-if="form.errors.reason" class="text-[11.5px] text-red-500 sm:col-span-2">
                {{ form.errors.reason }}
            </p>
            <textarea
                v-model="form.notes"
                rows="2"
                placeholder="Additional notes (optional)"
                class="rounded-lg border border-ink-200 px-3 py-2 text-[13px] sm:col-span-2"
            />
        </div>
        <button
            type="button"
            :disabled="!form.patient_id || !form.reason.trim() || form.processing"
            class="mt-3 rounded-full bg-gradient-to-r from-brand-500 to-sky-600 px-4 py-2 text-[12.5px] font-semibold text-white disabled:opacity-50"
            @click="submit"
        >
            Send Referral
        </button>
    </div>

    <div v-if="props.referrals.data.length" class="space-y-2.5">
        <div
            v-for="r in props.referrals.data"
            :key="r.id"
            class="rounded-2xl border border-ink-100 bg-white p-4 shadow-sm"
        >
            <div class="flex items-center justify-between">
                <span class="text-[13.5px] font-semibold text-ink-900">{{
                    r.patient_name
                }}</span>
                <span class="text-[11.5px] text-ink-400">{{
                    r.created_at
                }}</span>
            </div>
            <div class="mt-1 text-[12.5px] text-ink-600">{{ r.reason }}</div>
            <span
                v-if="r.to_category"
                class="mt-1.5 inline-block rounded-full bg-brand-50 px-2.5 py-0.5 text-[11px] font-medium text-brand-600"
                >→ {{ r.to_category }}</span
            >
        </div>
        <Pagination :links="props.referrals.links" />
    </div>
    <div
        v-else
        class="rounded-2xl border border-ink-100 bg-white py-16 text-center text-ink-400 shadow-sm"
    >
        <Share2 class="mx-auto mb-2 size-10 opacity-20" />
        No referrals sent yet.
    </div>
</template>
