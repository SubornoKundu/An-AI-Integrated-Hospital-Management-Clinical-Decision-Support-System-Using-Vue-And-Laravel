<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, House, TriangleAlert } from '@lucide/vue';
import { computed } from 'vue';

const props = defineProps<{ status: number }>();

function goBack() {
    router.visit(document.referrer || '/', { replace: true });
}

const content = computed(() => {
    const map: Record<number, { title: string; message: string }> = {
        403: {
            title: "You don't have access to this page",
            message: 'This might be the wrong account for what you were trying to do, or a link that has expired.',
        },
        404: {
            title: "This page doesn't exist",
            message: "The link might be old, or the page may have moved. Double-check the address and try again.",
        },
        419: {
            title: 'Your session expired',
            message: 'This usually happens after a period of inactivity. Please go back and try again.',
        },
        429: {
            title: "You've made too many requests",
            message: 'Please wait a moment before trying again.',
        },
        500: {
            title: 'Something went wrong on our end',
            message: "This isn't something you did — our team has been notified. Please try again shortly.",
        },
        503: {
            title: 'MediCare is temporarily unavailable',
            message: "We're down for scheduled maintenance. Please check back in a few minutes.",
        },
    };

    return map[props.status] ?? {
        title: 'Something went wrong',
        message: 'Please try again, or head back to the homepage.',
    };
});
</script>

<template>
    <Head :title="`${status} — ${content.title}`" />

    <div class="flex min-h-screen flex-col items-center justify-center bg-white px-6 font-heading antialiased">
        <div class="flex max-w-md flex-col items-center text-center">
            <div class="flex size-16 items-center justify-center rounded-full bg-brand-50 text-brand-600">
                <TriangleAlert class="size-7" />
            </div>

            <p class="mt-6 text-sm font-semibold tracking-wide text-ink-400 uppercase">Error {{ status }}</p>
            <h1 class="mt-2 text-2xl font-bold text-ink-900">{{ content.title }}</h1>
            <p class="mt-3 text-[15px] leading-relaxed text-ink-500">{{ content.message }}</p>

            <div class="mt-8 flex flex-col items-center gap-3 sm:flex-row">
                <Link
                    href="/"
                    class="group inline-flex items-center gap-2 rounded-full bg-brand-600 px-6 py-3 text-[15px] font-semibold text-white shadow-lg shadow-brand-600/30 transition-all hover:-translate-y-0.5 hover:bg-brand-700 hover:shadow-xl hover:shadow-brand-600/40"
                >
                    <House class="size-[18px]" />
                    Back to Home
                </Link>
                <button
                    type="button"
                    class="inline-flex items-center gap-2 rounded-full border border-ink-200 bg-white/70 px-6 py-3 text-[15px] font-semibold text-ink-700 backdrop-blur-sm transition-colors hover:bg-white"
                    @click="goBack"
                >
                    <ArrowLeft class="size-4" />
                    Go Back
                </button>
            </div>
        </div>
    </div>
</template>
