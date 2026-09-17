<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Bell } from '@lucide/vue';
import Pagination from '@/components/patient/Pagination.vue';

interface NotificationItem {
    id: number;
    title: string;
    message: string;
    type: string;
    is_read: boolean;
    link: string | null;
    created_at: string;
}

defineProps<{
    items: {
        data: NotificationItem[];
        links: { url: string | null; label: string; active: boolean }[];
    };
}>();

const typeIcon: Record<string, string> = {
    booking: '📅',
    cancellation: '❌',
    chat: '💬',
    prescription: '💊',
    referral: '🔗',
};
</script>

<template>
    <Head title="Notifications" />

    <h1 class="text-xl font-bold text-ink-900">🔔 Notifications</h1>

    <div v-if="items.data.length" class="space-y-2.5">
        <component
            :is="n.link ? Link : 'div'"
            v-for="n in items.data"
            :key="n.id"
            :href="n.link ?? undefined"
            class="flex items-start gap-3 rounded-2xl border p-4 shadow-sm transition-colors"
            :class="
                n.is_read
                    ? 'border-ink-100 bg-white'
                    : 'border-brand-200 bg-brand-50/40'
            "
        >
            <span class="text-xl">{{ typeIcon[n.type] ?? '🔔' }}</span>
            <div class="min-w-0 flex-1">
                <div class="text-[14px] font-semibold text-ink-900">
                    {{ n.title }}
                </div>
                <div class="mt-0.5 text-[13px] text-ink-600">
                    {{ n.message }}
                </div>
                <div class="mt-1 text-[11.5px] text-ink-400">
                    {{ n.created_at }}
                </div>
            </div>
            <span
                v-if="!n.is_read"
                class="mt-1 size-2 shrink-0 rounded-full bg-brand-500"
            />
        </component>
        <Pagination :links="items.links" />
    </div>
    <div
        v-else
        class="rounded-2xl border border-ink-100 bg-white py-16 text-center text-ink-400 shadow-sm"
    >
        <Bell class="mx-auto mb-2 size-10 opacity-20" />
        No notifications yet.
    </div>
</template>
