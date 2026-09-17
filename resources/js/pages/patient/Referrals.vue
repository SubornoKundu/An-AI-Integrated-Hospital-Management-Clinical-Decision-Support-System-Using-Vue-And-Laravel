<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Pagination from '@/components/patient/Pagination.vue';

interface Referral {
    id: number;
    reason: string | null;
    notes: string | null;
    status: string;
    from_doctor: string | null;
    to_category: { id: number; name: string; icon: string } | null;
    created_at: string;
}

defineProps<{
    referrals: {
        data: Referral[];
        links: { url: string | null; label: string; active: boolean }[];
    };
}>();

const statusStyle: Record<string, string> = {
    pending: 'bg-amber-50 text-amber-700 ring-1 ring-amber-200',
    booked: 'bg-brand-50 text-brand-700 ring-1 ring-brand-200',
    completed: 'bg-sky-50 text-sky-700 ring-1 ring-sky-200',
};
</script>

<template>
    <Head title="Referrals" />

    <h1 class="text-xl font-bold text-ink-900">🔗 Referrals</h1>

    <div v-if="referrals.data.length" class="space-y-3">
        <div
            v-for="r in referrals.data"
            :key="r.id"
            class="rounded-2xl border border-ink-100 bg-white p-5 shadow-sm"
        >
            <div class="flex items-start justify-between gap-3">
                <div class="flex items-center gap-3">
                    <span class="text-2xl">{{
                        r.to_category?.icon ?? '🔗'
                    }}</span>
                    <div>
                        <div class="text-[14.5px] font-semibold text-ink-900">
                            Referred to
                            {{ r.to_category?.name ?? 'Specialist' }}
                        </div>
                        <div class="text-[12.5px] text-ink-500">
                            From Dr. {{ r.from_doctor ?? '—' }} ·
                            {{ r.created_at }}
                        </div>
                    </div>
                </div>
                <span
                    class="shrink-0 rounded-full px-2.5 py-1 text-[11px] font-semibold capitalize"
                    :class="statusStyle[r.status] ?? 'bg-ink-100 text-ink-600'"
                    >{{ r.status }}</span
                >
            </div>
            <p
                v-if="r.reason"
                class="mt-3 rounded-lg bg-ink-50 p-3 text-[13px] text-ink-600"
            >
                <strong class="text-ink-800">Reason:</strong> {{ r.reason }}
            </p>
            <Link
                v-if="r.status === 'pending' && r.to_category"
                :href="`/patient/book/doctor?category_id=${r.to_category.id}`"
                class="mt-3 inline-block text-[13px] font-semibold text-brand-600 hover:underline"
                >Book this referral →</Link
            >
        </div>
        <Pagination :links="referrals.links" />
    </div>
    <div
        v-else
        class="rounded-2xl border border-ink-100 bg-white py-16 text-center text-ink-400 shadow-sm"
    >
        <div class="mb-2 text-4xl opacity-20">🔗</div>
        No referrals yet.
    </div>
</template>
