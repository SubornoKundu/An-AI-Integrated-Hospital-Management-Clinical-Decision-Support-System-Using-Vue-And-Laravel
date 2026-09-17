<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Check, Mail } from '@lucide/vue';

interface ContactRow {
    id: number;
    name: string;
    email: string;
    subject: string | null;
    message: string;
    is_resolved: boolean;
    created_at: string;
}

const props = defineProps<{
    contacts: {
        data: ContactRow[];
        links: { url: string | null; label: string; active: boolean }[];
        from: number | null;
        to: number | null;
        total: number;
    };
}>();

function resolve(c: ContactRow) {
    router.post(`/admin/feedback/${c.id}/resolve`, {}, { preserveScroll: true });
}
</script>

<template>
    <Head title="Feedback & Messages" />

    <div>
        <h1 class="flex items-center gap-2 text-xl font-bold text-ink-900">
            <Mail class="size-5 text-brand-500" /> Feedback &amp; Messages
        </h1>
        <p class="mt-1 text-[13px] text-ink-500">{{ props.contacts.total }} message{{ props.contacts.total === 1 ? '' : 's' }} from the contact form.</p>
    </div>

    <div class="space-y-3">
        <div v-for="c in props.contacts.data" :key="c.id" class="rounded-2xl border border-ink-100 bg-white p-4 shadow-sm">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <div class="text-[13.5px] font-semibold text-ink-800">{{ c.name }}</div>
                    <div class="text-[11.5px] text-ink-400">{{ c.email }} · {{ c.created_at }}</div>
                </div>
                <span
                    class="shrink-0 rounded-full px-2.5 py-1 text-[11px] font-medium"
                    :class="c.is_resolved ? 'bg-emerald-50 text-emerald-600' : 'bg-amber-50 text-amber-600'"
                    >{{ c.is_resolved ? 'Resolved' : 'Pending' }}</span
                >
            </div>
            <div v-if="c.subject" class="mt-2 text-[12.5px] font-medium text-ink-700">{{ c.subject }}</div>
            <p class="mt-1 text-[13px] whitespace-pre-line text-ink-600">{{ c.message }}</p>
            <div v-if="!c.is_resolved" class="mt-3 border-t border-ink-50 pt-3">
                <button class="flex items-center gap-1.5 rounded-full border border-emerald-200 px-3 py-1.5 text-[12px] font-medium text-emerald-600 hover:bg-emerald-50" @click="resolve(c)">
                    <Check class="size-3.5" /> Mark Resolved
                </button>
            </div>
        </div>
        <p v-if="!props.contacts.data.length" class="py-10 text-center text-[13px] text-ink-400">No messages yet.</p>
    </div>

    <div v-if="props.contacts.links.length > 3" class="flex flex-wrap items-center justify-between gap-2">
        <p class="text-[12.5px] text-ink-500">Showing {{ props.contacts.from ?? 0 }}–{{ props.contacts.to ?? 0 }} of {{ props.contacts.total }}</p>
        <div class="flex flex-wrap gap-1">
            <Link
                v-for="(link, i) in props.contacts.links"
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
