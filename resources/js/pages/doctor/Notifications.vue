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

interface PageLink {
    url: string | null;
    label: string;
    active: boolean;
}

defineProps<{
    items: {
        data: NotificationItem[];
        links: PageLink[];
    };
}>();

const typeIcon: Record<string, string> = {
    appointment: '📅',
    cancellation: '❌',
    prescription: '💊',
    general: '🔔',
};
</script>

<template>
    <Head title="Notifications" />

    <h1 class="flex items-center gap-2 text-xl font-bold text-ink-900">
        <Bell class="size-5 text-brand-500" /> Notifications
    </h1>

    <div v-if="items.data.length" class="mt-4 space-y-2">
        <component
            :is="n.link ? Link : 'div'"
            v-for="n in items.data"
            :key="n.id"
            :href="n.link ?? undefined"
            class="flex items-start gap-3 rounded-2xl border bg-white p-4 shadow-sm transition-shadow hover:shadow-md"
            :class="n.is_read ? 'border-ink-100' : 'border-brand-200 bg-brand-50/30'"
        >
            <span class="text-xl">{{ typeIcon[n.type] ?? '🔔' }}</span>
            <div class="flex-1">
                <div class="flex items-center gap-2">
                    <span class="text-[13.5px] font-semibold text-ink-900">{{
                        n.title
                    }}</span>
                    <span
                        v-if="!n.is_read"
                        class="size-1.5 rounded-full bg-brand-500"
                    />
                </div>
                <p class="mt-0.5 text-[12.5px] text-ink-600">
                    {{ n.message }}
                </p>
                <span class="mt-1 block text-[11px] text-ink-400">{{
                    n.created_at
                }}</span>
            </div>
        </component>
        <Pagination :links="items.links" />
    </div>
    <div
        v-else
        class="mt-4 rounded-2xl border border-ink-100 bg-white py-16 text-center text-ink-400 shadow-sm"
    >
        <Bell class="mx-auto mb-2 size-10 opacity-20" />
        No notifications yet.
    </div>
</template>
