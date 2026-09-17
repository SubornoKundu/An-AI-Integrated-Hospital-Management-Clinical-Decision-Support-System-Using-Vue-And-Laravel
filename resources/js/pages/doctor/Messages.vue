<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { MessageCircle } from '@lucide/vue';

interface Conversation {
    appointment_id: number;
    patient_name: string;
    patient_photo: string | null;
    appointment_date: string;
    last_message: string;
    last_message_time: string;
    last_sender: 'doctor' | 'patient';
    unread_count: number;
}

defineProps<{
    conversations: Conversation[];
}>();
</script>

<template>
    <Head title="Messages" />

    <h1 class="flex items-center gap-2 text-xl font-bold text-ink-900">
        <MessageCircle class="size-5 text-brand-500" /> Messages
    </h1>

    <div v-if="conversations.length" class="space-y-2.5">
        <Link
            v-for="c in conversations"
            :key="c.appointment_id"
            :href="`/doctor/chat/${c.appointment_id}`"
            class="flex items-center gap-3 rounded-2xl border bg-white p-4 shadow-sm transition-shadow hover:shadow-md"
            :class="
                c.unread_count
                    ? 'border-brand-200 bg-brand-50/30'
                    : 'border-ink-100'
            "
        >
            <img
                v-if="c.patient_photo"
                :src="c.patient_photo"
                :alt="c.patient_name"
                class="size-11 shrink-0 rounded-full object-cover"
            />
            <div
                v-else
                class="flex size-11 shrink-0 items-center justify-center rounded-full bg-brand-50 text-[15px] font-semibold text-brand-600"
            >
                {{ c.patient_name.charAt(0).toUpperCase() }}
            </div>
            <div class="min-w-0 flex-1">
                <div class="flex items-center justify-between gap-2">
                    <span class="truncate text-[14px] font-semibold text-ink-900">{{
                        c.patient_name
                    }}</span>
                    <span class="shrink-0 text-[11px] text-ink-400">{{
                        c.last_message_time
                    }}</span>
                </div>
                <p class="mt-0.5 truncate text-[12.5px] text-ink-500">
                    <span v-if="c.last_sender === 'doctor'" class="text-ink-400"
                        >You: </span
                    >{{ c.last_message }}
                </p>
                <span class="text-[11px] text-ink-400"
                    >Appointment: {{ c.appointment_date }}</span
                >
            </div>
            <span
                v-if="c.unread_count"
                class="flex size-5 shrink-0 items-center justify-center rounded-full bg-brand-500 text-[10.5px] font-bold text-white"
                >{{ c.unread_count }}</span
            >
        </Link>
    </div>
    <div
        v-else
        class="rounded-2xl border border-ink-100 bg-white py-16 text-center text-ink-400 shadow-sm"
    >
        <MessageCircle class="mx-auto mb-2 size-10 opacity-20" />
        No conversations yet. Messages from patients will show up here.
    </div>
</template>
