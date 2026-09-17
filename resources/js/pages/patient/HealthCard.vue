<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Droplet, Phone, User } from '@lucide/vue';
import Reveal from '@/components/public/Reveal.vue';

defineProps<{
    patient: {
        name: string;
        id: number;
        gender: string;
        blood_group: string | null;
        date_of_birth: string | null;
        age: number | null;
        contact: string;
        email: string;
    };
    lastVisit: { date: string; doctor: string | null } | null;
    totalVisits: number;
}>();
</script>

<template>
    <Head title="Health Card" />

    <h1 class="text-xl font-bold text-ink-900">🪪 Digital Health Card</h1>

    <Reveal class="mx-auto w-full max-w-md">
        <div
            class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-ink-900 via-ink-800 to-brand-900 p-6 text-white shadow-xl"
        >
            <div
                class="pointer-events-none absolute -top-16 -right-16 size-56 animate-blob rounded-full bg-brand-500/20 blur-3xl"
            />
            <div class="relative flex items-center justify-between">
                <span
                    class="text-[11px] font-semibold tracking-[0.2em] text-white/60 uppercase"
                    >MediCare Hospital</span
                >
                <span class="text-lg">🏥</span>
            </div>
            <div class="relative mt-6 flex items-center gap-4">
                <div
                    class="flex size-16 items-center justify-center rounded-2xl bg-white/10 text-2xl font-bold ring-1 ring-white/20"
                >
                    {{ patient.name.charAt(0) }}
                </div>
                <div>
                    <div class="text-lg font-bold">{{ patient.name }}</div>
                    <div class="text-[12.5px] text-white/60">
                        Patient ID #{{ String(patient.id).padStart(6, '0') }}
                    </div>
                </div>
            </div>

            <div
                class="relative mt-6 grid grid-cols-2 gap-4 border-t border-white/10 pt-5 text-[13px]"
            >
                <div>
                    <div class="text-white/50">Gender</div>
                    <div class="font-medium">{{ patient.gender }}</div>
                </div>
                <div>
                    <div class="text-white/50">Age</div>
                    <div class="font-medium">{{ patient.age ?? '—' }}</div>
                </div>
                <div>
                    <div class="flex items-center gap-1 text-white/50">
                        <Droplet class="size-3" /> Blood Group
                    </div>
                    <div
                        class="font-mono-brand text-base font-bold text-brand-300"
                    >
                        {{ patient.blood_group ?? '—' }}
                    </div>
                </div>
                <div>
                    <div class="flex items-center gap-1 text-white/50">
                        <Phone class="size-3" /> Contact
                    </div>
                    <div class="font-medium">{{ patient.contact }}</div>
                </div>
            </div>
        </div>
    </Reveal>

    <div class="mx-auto grid w-full max-w-md grid-cols-2 gap-4">
        <Reveal
            :delay="100"
            class="rounded-2xl border border-ink-100 bg-white p-4 text-center shadow-sm"
        >
            <div class="font-mono-brand text-2xl font-bold text-brand-600">
                {{ totalVisits }}
            </div>
            <div class="mt-1 text-[12px] text-ink-500">Completed Visits</div>
        </Reveal>
        <Reveal
            :delay="150"
            class="rounded-2xl border border-ink-100 bg-white p-4 text-center shadow-sm"
        >
            <div
                v-if="lastVisit"
                class="text-[13px] font-semibold text-ink-900"
            >
                {{ lastVisit.date }}
            </div>
            <div v-else class="text-[13px] text-ink-400">No visits yet</div>
            <div class="mt-1 text-[12px] text-ink-500">
                Last Visit<span v-if="lastVisit?.doctor">
                    · Dr. {{ lastVisit.doctor }}</span
                >
            </div>
        </Reveal>
    </div>

    <p
        class="mx-auto flex max-w-md items-center gap-1.5 text-center text-[12px] text-ink-400"
    >
        <User class="size-3.5 shrink-0" /> {{ patient.email }}
    </p>
</template>
