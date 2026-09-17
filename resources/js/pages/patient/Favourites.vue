<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Heart, Star } from '@lucide/vue';

defineProps<{
    doctors: {
        id: number;
        name: string;
        category: string;
        icon: string;
        photo: string | null;
        consultancy_fee: number;
        avg_rating: number;
        rating_count: number;
    }[];
}>();

function unfavourite(doctorId: number) {
    router.post(
        `/patient/favourites/${doctorId}`,
        {},
        { preserveScroll: true },
    );
}

function fmtTaka(n: number) {
    return new Intl.NumberFormat('en-BD').format(Math.round(n));
}
</script>

<template>
    <Head title="Favourites" />

    <h1 class="text-xl font-bold text-ink-900">⭐ Favourite Doctors</h1>

    <div
        v-if="doctors.length"
        class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3"
    >
        <div
            v-for="d in doctors"
            :key="d.id"
            class="rounded-2xl border border-ink-100 bg-white p-5 shadow-sm transition-shadow hover:shadow-md"
        >
            <div class="flex items-start justify-between">
                <div class="flex items-center gap-3">
                    <img
                        v-if="d.photo"
                        :src="d.photo"
                        :alt="d.name"
                        class="size-11 rounded-xl object-cover ring-1 ring-brand-100"
                    />
                    <div
                        v-else
                        class="flex size-11 items-center justify-center rounded-xl bg-brand-50 text-xl ring-1 ring-brand-100"
                    >
                        {{ d.icon }}
                    </div>
                    <div>
                        <div class="text-[15px] font-semibold text-ink-900">
                            {{ d.name }}
                        </div>
                        <div class="text-[12.5px] text-ink-500">
                            {{ d.category }}
                        </div>
                    </div>
                </div>
                <button
                    class="text-red-400 hover:text-red-600"
                    @click="unfavourite(d.id)"
                >
                    <Heart class="size-5 fill-current" />
                </button>
            </div>
            <div class="mt-4 flex items-center justify-between text-[13px]">
                <span
                    v-if="d.avg_rating > 0"
                    class="flex items-center gap-1 text-amber-500"
                >
                    <Star class="size-3.5 fill-amber-400" />
                    {{ d.avg_rating }} ({{ d.rating_count }})
                </span>
                <span v-else class="text-ink-400">No reviews yet</span>
                <span class="font-mono-brand font-bold text-brand-600"
                    >৳{{ fmtTaka(d.consultancy_fee) }}</span
                >
            </div>
            <Link
                :href="`/patient/book/slot?doctor_id=${d.id}`"
                class="mt-4 block rounded-full bg-brand-600 py-2 text-center text-sm font-semibold text-white hover:bg-brand-700"
                >Book Appointment →</Link
            >
        </div>
    </div>
    <div
        v-else
        class="rounded-2xl border border-ink-100 bg-white py-16 text-center text-ink-400 shadow-sm"
    >
        <div class="mb-2 text-4xl opacity-20">⭐</div>
        No favourite doctors yet.
        <Link
            href="/patient/search"
            class="mt-2 block text-sm font-medium text-brand-600 hover:underline"
            >Find a doctor →</Link
        >
    </div>
</template>
