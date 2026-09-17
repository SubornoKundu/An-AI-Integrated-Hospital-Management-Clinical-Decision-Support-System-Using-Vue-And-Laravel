<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import BookingProgress from '@/components/patient/BookingProgress.vue';
import Reveal from '@/components/public/Reveal.vue';

defineProps<{
    categories: {
        id: number;
        name: string;
        icon: string;
        doctors_count: number;
    }[];
}>();
</script>

<template>
    <Head title="Book Appointment" />

    <div class="flex items-start justify-between">
        <div>
            <h1 class="text-xl font-bold text-ink-900">📅 Book Appointment</h1>
            <p class="mt-0.5 text-sm text-ink-500">
                Step 1 of 3 — Choose a Specialty
            </p>
        </div>
        <Link
            href="/patient/dashboard"
            class="rounded-full border border-ink-200 px-4 py-2 text-sm font-medium text-ink-600 hover:bg-ink-50"
            >← Dashboard</Link
        >
    </div>

    <BookingProgress :step="1" />

    <div
        class="rounded-2xl border border-ink-100 bg-white p-5 shadow-sm sm:p-6"
    >
        <h2 class="mb-4 text-sm font-semibold text-ink-700">
            Choose a Medical Specialty
        </h2>
        <div
            class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5"
        >
            <Reveal
                v-for="(cat, i) in categories"
                :key="cat.id"
                :delay="i * 30"
            >
                <Link
                    :href="`/patient/book/doctor?category_id=${cat.id}`"
                    class="group flex flex-col items-center gap-2 rounded-2xl border border-ink-100 bg-ink-50/40 px-3 py-5 text-center transition-all hover:-translate-y-1 hover:border-brand-300 hover:bg-brand-50/50 hover:shadow-md"
                >
                    <span
                        class="text-2xl transition-transform group-hover:scale-110"
                        >{{ cat.icon }}</span
                    >
                    <span
                        class="text-[13px] leading-tight font-semibold text-ink-800"
                        >{{ cat.name }}</span
                    >
                    <span class="text-[11px] text-ink-400"
                        >{{ cat.doctors_count }} doctor{{
                            cat.doctors_count !== 1 ? 's' : ''
                        }}</span
                    >
                </Link>
            </Reveal>
        </div>
    </div>
</template>
