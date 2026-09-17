<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Phone, Star } from '@lucide/vue';
import BookingProgress from '@/components/patient/BookingProgress.vue';
import Reveal from '@/components/public/Reveal.vue';

defineProps<{
    category: { id: number; name: string; icon: string };
    doctors: {
        id: number;
        name: string;
        bio: string | null;
        phone: string | null;
        photo: string | null;
        consultancy_fee: number;
        avg_rating: number;
        rating_count: number;
    }[];
}>();

function fmtTaka(n: number) {
    return new Intl.NumberFormat('en-BD').format(Math.round(n));
}
</script>

<template>
    <Head title="Choose Doctor" />

    <div class="flex items-start justify-between">
        <div>
            <h1 class="text-xl font-bold text-ink-900">📅 Book Appointment</h1>
            <p class="mt-0.5 text-sm text-ink-500">
                Step 2 of 3 — Choose your Doctor ·
                <span class="text-brand-600"
                    >{{ category.icon }} {{ category.name }}</span
                >
            </p>
        </div>
        <Link
            href="/patient/book"
            class="rounded-full border border-ink-200 px-4 py-2 text-sm font-medium text-ink-600 hover:bg-ink-50"
            >← Back</Link
        >
    </div>

    <BookingProgress :step="2" />

    <div
        class="rounded-2xl border border-ink-100 bg-white p-5 shadow-sm sm:p-6"
    >
        <h2 class="mb-4 text-sm font-semibold text-ink-700">
            {{ category.icon }} {{ category.name }} Specialists
        </h2>

        <div v-if="doctors.length" class="space-y-3">
            <Reveal
                v-for="(d, i) in doctors"
                :key="d.id"
                :delay="i * 50"
                class="flex flex-col items-start gap-3 rounded-2xl border border-ink-100 bg-ink-50/30 p-4 transition-colors hover:border-brand-200 sm:flex-row sm:items-center"
            >
                <img
                    v-if="d.photo"
                    :src="d.photo"
                    :alt="d.name"
                    class="size-12 shrink-0 rounded-xl object-cover ring-1 ring-brand-100"
                />
                <div
                    v-else
                    class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-xl ring-1 ring-brand-100"
                >
                    {{ category.icon }}
                </div>
                <div class="min-w-0 flex-1">
                    <div class="text-[15px] font-semibold text-ink-900">
                        {{ d.name }}
                    </div>
                    <p
                        v-if="d.bio"
                        class="mt-0.5 line-clamp-2 text-[13px] text-ink-500"
                    >
                        {{ d.bio }}
                    </p>
                    <div
                        class="mt-1.5 flex flex-wrap items-center gap-3 text-[12.5px]"
                    >
                        <span
                            class="font-mono-brand font-semibold text-brand-600"
                            >৳{{ fmtTaka(d.consultancy_fee) }} / visit</span
                        >
                        <span
                            v-if="d.phone"
                            class="flex items-center gap-1 text-ink-400"
                            ><Phone class="size-3" /> {{ d.phone }}</span
                        >
                        <span
                            v-if="d.avg_rating > 0"
                            class="flex items-center gap-1 text-amber-500"
                        >
                            <Star class="size-3 fill-amber-400" />
                            {{ d.avg_rating }} ({{ d.rating_count }})
                        </span>
                        <span v-else class="text-ink-400">No reviews yet</span>
                    </div>
                </div>
                <Link
                    :href="`/patient/book/slot?doctor_id=${d.id}`"
                    class="w-full shrink-0 rounded-full bg-brand-600 px-5 py-2 text-center text-sm font-semibold text-white shadow-sm shadow-brand-600/25 transition-colors hover:bg-brand-700 sm:w-auto"
                >
                    Book →
                </Link>
            </Reveal>
        </div>
        <div v-else class="py-16 text-center text-ink-400">
            <div class="mb-2 text-4xl opacity-20">👨‍⚕️</div>
            No doctors available in this specialty right now.<br />
            <Link
                href="/patient/book"
                class="mt-2 inline-block text-sm text-brand-600 hover:underline"
                >← Try another specialty</Link
            >
        </div>
    </div>
</template>
