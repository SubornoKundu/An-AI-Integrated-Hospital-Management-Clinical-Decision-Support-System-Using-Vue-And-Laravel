<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { Send } from '@lucide/vue';
import { nextTick, onMounted, onUnmounted, ref } from 'vue';

interface ChatMessage {
    id: number;
    sender_role: 'doctor' | 'patient';
    message: string;
    time: string;
}

const props = defineProps<{
    appointment: {
        id: number;
        appointment_date: string;
        slot_start: string | null;
        slot_end: string | null;
        doctor: {
            name: string;
            phone: string | null;
            category: string;
            icon: string;
            photo: string | null;
            is_online: boolean;
        } | null;
    };
    messages: ChatMessage[];
}>();

const form = useForm({ message: '' });
const chatBox = ref<HTMLDivElement | null>(null);

function scrollToBottom() {
    nextTick(() => {
        if (chatBox.value) {
            chatBox.value.scrollTop = chatBox.value.scrollHeight;
        }
    });
}

function send() {
    if (!form.message.trim()) {
        return;
    }

    form.post(`/patient/chat/${props.appointment.id}/send`, {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            router.reload({ only: ['messages'], onFinish: scrollToBottom });
        },
    });
}

onMounted(scrollToBottom);

// Same reasoning as the doctor side — no real-time delivery exists
// anywhere in this app, so without this a reply only shows up after a
// manual reload. Paused while typing so a draft can't get interrupted.
const pollInterval = setInterval(() => {
    if (form.message.trim()) {
        return;
    }

    router.reload({
        only: ['messages', 'appointment'],
        onFinish: scrollToBottom,
    });
}, 5000);

onUnmounted(() => clearInterval(pollInterval));
</script>

<template>
    <Head :title="`Chat — ${props.appointment.doctor?.name ?? 'Doctor'}`" />

    <div class="flex flex-wrap items-center justify-between gap-2">
        <div>
            <h1 class="text-xl font-bold text-ink-900">💬 Consultation Chat</h1>
            <p class="mt-1 text-[13px] text-ink-500">
                Appointment:
                <strong>{{ props.appointment.appointment_date }}</strong>
                <span v-if="props.appointment.slot_start">
                    · {{ props.appointment.slot_start.slice(0, 5) }} –
                    {{ props.appointment.slot_end?.slice(0, 5) }}</span
                >
            </p>
        </div>
        <Link
            href="/patient/appointments"
            class="rounded-full border border-ink-200 px-4 py-2 text-[12.5px] font-medium text-ink-600 hover:bg-ink-50"
            >← Back</Link
        >
    </div>

    <div
        class="mx-auto flex w-full max-w-2xl flex-1 flex-col overflow-hidden rounded-2xl border border-ink-100 bg-white shadow-sm"
    >
        <div class="flex items-center gap-3 border-b border-ink-100 p-4">
            <img
                v-if="props.appointment.doctor?.photo"
                :src="props.appointment.doctor.photo"
                :alt="props.appointment.doctor.name"
                class="size-10 rounded-full object-cover"
            />
            <div
                v-else
                class="flex size-10 items-center justify-center rounded-full bg-brand-50 text-lg"
            >
                {{ props.appointment.doctor?.icon ?? '🩺' }}
            </div>
            <div>
                <div class="text-[14px] font-semibold text-ink-900">
                    {{ props.appointment.doctor?.name ?? 'Doctor' }}
                </div>
                <div class="text-[12px] text-ink-500">
                    {{ props.appointment.doctor?.category ?? 'Specialist' }}
                    <span v-if="props.appointment.doctor?.phone">
                        · 📞 {{ props.appointment.doctor.phone }}</span
                    >
                </div>
            </div>
            <span
                class="ml-auto size-2.5 rounded-full"
                :class="
                    props.appointment.doctor?.is_online
                        ? 'bg-emerald-400'
                        : 'bg-ink-300'
                "
                :title="
                    props.appointment.doctor?.is_online ? 'Online' : 'Offline'
                "
            />
        </div>

        <div
            ref="chatBox"
            class="flex-1 space-y-3 overflow-y-auto p-4"
            style="min-height: 360px; max-height: 55vh"
        >
            <div
                v-for="m in props.messages"
                :key="m.id"
                class="flex items-end gap-2"
                :class="m.sender_role === 'patient' ? 'flex-row-reverse' : ''"
            >
                <img
                    v-if="
                        m.sender_role === 'doctor' &&
                        props.appointment.doctor?.photo
                    "
                    :src="props.appointment.doctor.photo"
                    :alt="props.appointment.doctor.name"
                    class="size-6 shrink-0 rounded-full object-cover"
                />
                <div
                    v-else-if="m.sender_role === 'doctor'"
                    class="flex size-6 shrink-0 items-center justify-center rounded-full bg-ink-100 text-[11px]"
                >
                    {{ props.appointment.doctor?.icon ?? '🩺' }}
                </div>
                <div :class="m.sender_role === 'patient' ? 'text-right' : ''">
                    <div
                        class="max-w-xs rounded-2xl px-3.5 py-2 text-[13px]"
                        :class="
                            m.sender_role === 'patient'
                                ? 'rounded-br-sm bg-gradient-to-r from-brand-500 to-sky-600 text-white'
                                : 'rounded-bl-sm bg-ink-100 text-ink-800'
                        "
                    >
                        {{ m.message }}
                    </div>
                    <div class="mt-0.5 text-[10.5px] text-ink-400">
                        {{ m.time }}
                        <span v-if="m.sender_role === 'patient'">✓✓</span>
                    </div>
                </div>
            </div>
            <div
                v-if="!props.messages.length"
                class="flex h-full flex-col items-center justify-center py-10 text-center text-ink-400"
            >
                <div class="mb-2 text-3xl">💬</div>
                <div class="font-semibold text-ink-500">No messages yet</div>
                <div class="text-[12.5px]">
                    Start the conversation with your doctor below.
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2 border-t border-ink-100 p-3">
            <input
                v-model="form.message"
                type="text"
                placeholder="Type your message… (Enter to send)"
                autocomplete="off"
                class="flex-1 rounded-full border border-ink-200 px-4 py-2.5 text-[13px] outline-none focus-visible:border-brand-400"
                @keydown.enter="send"
            />
            <button
                type="button"
                :disabled="!form.message.trim() || form.processing"
                class="flex size-10 shrink-0 items-center justify-center rounded-full bg-gradient-to-r from-brand-500 to-sky-600 text-white shadow-sm disabled:opacity-50"
                @click="send"
            >
                <Send class="size-4" />
            </button>
        </div>
    </div>
</template>
