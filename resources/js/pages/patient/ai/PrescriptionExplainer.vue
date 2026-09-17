<script setup lang="ts">
import { Deferred, Head, Link } from '@inertiajs/vue3';
import {
    AlertTriangle,
    CalendarClock,
    Loader2,
    Pill,
    ShieldCheck,
    Sparkles,
    Utensils,
} from '@lucide/vue';
import { ref } from 'vue';
import Reveal from '@/components/public/Reveal.vue';
import { Button } from '@/components/ui/button';
import { aiPost } from '@/lib/aiClient';

interface Medicine {
    name: string;
    purpose: string;
    how_to_take: string;
    side_effects: string;
    important_notes: string;
}
interface Explanation {
    diagnosis_explained: string;
    medicines: Medicine[];
    general_instructions: string[];
    diet_advice: string[];
    recovery_timeline: string;
    when_to_seek_help: string[];
    follow_up_importance: string;
    safety_note: string | null;
    disclaimer: string;
}

const props = defineProps<{
    appointment: {
        id: number;
        appointment_date: string;
        diagnosis: string;
        medicines: string;
        doctor: { name: string; category: string; icon: string };
    };
    apiStatus?: { connected: boolean; message?: string };
}>();

const loading = ref(false);
const error = ref('');
const result = ref<Explanation | null>(null);

async function explain() {
    loading.value = true;
    error.value = '';
    const { ok, data } = await aiPost<Explanation & { error?: string }>(
        `/patient/ai/prescription/${props.appointment.id}/explain`,
    );
    loading.value = false;

    if (!ok || data.error) {
        error.value = data.error ?? 'Something went wrong. Please try again.';

        return;
    }

    result.value = data;
}
</script>

<template>
    <Head title="Prescription Explainer" />

    <div class="flex items-start justify-between">
        <div>
            <h1 class="flex items-center gap-2 text-xl font-bold text-ink-900">
                <Pill class="size-6 text-brand-500" /> Prescription Explainer
            </h1>
            <Deferred data="apiStatus">
                <template #fallback>
                    <span
                        class="mt-2 inline-flex items-center gap-1.5 rounded-full bg-ink-50 px-3 py-1 text-[12.5px] font-medium text-ink-400"
                    >
                        <span
                            class="size-1.5 animate-pulse rounded-full bg-ink-300"
                        />
                        Checking AI status…
                    </span>
                </template>
                <span
                    class="mt-2 inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-[12.5px] font-medium"
                    :class="
                        apiStatus?.connected
                            ? 'bg-brand-50 text-brand-700'
                            : 'bg-red-50 text-red-600'
                    "
                >
                    <span
                        class="size-1.5 rounded-full"
                        :class="
                            apiStatus?.connected ? 'bg-brand-500' : 'bg-red-500'
                        "
                    />
                    {{
                        apiStatus?.connected
                            ? 'AI Connected — Powered by Groq AI'
                            : (apiStatus?.message ?? 'AI Not Connected')
                    }}
                </span>
            </Deferred>
        </div>
        <Link
            href="/patient/appointments"
            class="rounded-full border border-ink-200 px-4 py-2 text-sm font-medium text-ink-600 hover:bg-ink-50"
            >← Appointments</Link
        >
    </div>

    <div class="rounded-2xl border border-ink-100 bg-white p-5 shadow-sm">
        <div class="flex items-center gap-3 border-b border-ink-100 pb-4">
            <span
                class="flex size-11 items-center justify-center rounded-xl bg-brand-50 text-lg ring-1 ring-brand-100"
                >{{ appointment.doctor.icon }}</span
            >
            <div>
                <div class="font-semibold text-ink-900">
                    {{ appointment.doctor.name }}
                </div>
                <div class="text-[12.5px] text-ink-500">
                    {{ appointment.doctor.category }} ·
                    {{ appointment.appointment_date }}
                </div>
            </div>
        </div>
        <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <div
                    class="text-[11px] font-bold tracking-wide text-ink-400 uppercase"
                >
                    Diagnosis
                </div>
                <p class="mt-1 text-[13.5px] text-ink-800">
                    {{ appointment.diagnosis }}
                </p>
            </div>
            <div>
                <div
                    class="text-[11px] font-bold tracking-wide text-ink-400 uppercase"
                >
                    Medicines Prescribed
                </div>
                <p class="mt-1 text-[13.5px] whitespace-pre-line text-ink-800">
                    {{ appointment.medicines }}
                </p>
            </div>
        </div>

        <Button
            class="mt-5 gap-2 bg-gradient-to-r from-brand-500 to-sky-600 py-5 hover:from-brand-600 hover:to-sky-700"
            :disabled="loading"
            @click="explain"
        >
            <Loader2 v-if="loading" class="size-4 animate-spin" />
            <Sparkles v-else class="size-4" />
            {{ loading ? 'Explaining…' : 'Explain This Prescription with AI' }}
        </Button>
        <p v-if="error" class="mt-2 text-[13px] text-destructive">
            ⚠ {{ error }}
        </p>
    </div>

    <Reveal v-if="result" class="space-y-5">
        <div
            v-if="result.safety_note"
            class="rounded-2xl border border-emerald-100 bg-emerald-50/60 p-5"
        >
            <h3
                class="mb-1.5 flex items-center gap-1.5 text-[13.5px] font-semibold text-emerald-800"
            >
                <ShieldCheck class="size-4" /> Reviewed by Your Doctor
            </h3>
            <p class="text-[13.5px] text-emerald-700">
                {{ result.safety_note }}
            </p>
        </div>

        <div class="rounded-2xl border border-brand-100 bg-brand-50/50 p-5">
            <h2 class="mb-2 text-[14px] font-bold text-ink-900">
                What does this mean?
            </h2>
            <p class="text-[13.5px] leading-relaxed text-ink-700">
                {{ result.diagnosis_explained }}
            </p>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div
                v-for="med in result.medicines"
                :key="med.name"
                class="rounded-2xl border border-ink-100 bg-white p-5 shadow-sm"
            >
                <div class="mb-2 flex items-center gap-2">
                    <Pill class="size-4 text-brand-500" />
                    <h3 class="text-[14.5px] font-bold text-ink-900">
                        {{ med.name }}
                    </h3>
                </div>
                <dl class="space-y-2 text-[13px]">
                    <div>
                        <dt class="font-semibold text-ink-500">Purpose</dt>
                        <dd class="text-ink-700">{{ med.purpose }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-ink-500">How to take</dt>
                        <dd class="text-ink-700">{{ med.how_to_take }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-ink-500">Side effects</dt>
                        <dd class="text-ink-700">{{ med.side_effects }}</dd>
                    </div>
                    <div
                        v-if="med.important_notes"
                        class="rounded-lg bg-amber-50 p-2 text-amber-700"
                    >
                        ⚠ {{ med.important_notes }}
                    </div>
                </dl>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div
                class="rounded-2xl border border-ink-100 bg-white p-5 shadow-sm"
            >
                <h3
                    class="mb-2 flex items-center gap-1.5 text-[13.5px] font-semibold text-ink-800"
                >
                    ✓ General Instructions
                </h3>
                <ul class="space-y-1 text-[13px] text-ink-600">
                    <li v-for="i in result.general_instructions" :key="i">
                        • {{ i }}
                    </li>
                </ul>
            </div>
            <div
                class="rounded-2xl border border-ink-100 bg-white p-5 shadow-sm"
            >
                <h3
                    class="mb-2 flex items-center gap-1.5 text-[13.5px] font-semibold text-ink-800"
                >
                    <Utensils class="size-4 text-brand-500" /> Diet Advice
                </h3>
                <ul class="space-y-1 text-[13px] text-ink-600">
                    <li v-for="d in result.diet_advice" :key="d">• {{ d }}</li>
                </ul>
            </div>
        </div>

        <div class="rounded-2xl border border-sky-100 bg-sky-50/50 p-5">
            <h3
                class="mb-1.5 flex items-center gap-1.5 text-[13.5px] font-semibold text-sky-800"
            >
                <CalendarClock class="size-4" /> Recovery Timeline
            </h3>
            <p class="text-[13.5px] text-ink-700">
                {{ result.recovery_timeline }}
            </p>
            <p class="mt-2 text-[13px] text-ink-600">
                {{ result.follow_up_importance }}
            </p>
        </div>

        <div class="rounded-2xl border border-red-100 bg-red-50/60 p-5">
            <h3
                class="mb-1.5 flex items-center gap-1.5 text-[13.5px] font-semibold text-red-700"
            >
                <AlertTriangle class="size-4" /> When to Seek Help Immediately
            </h3>
            <ul class="space-y-1 text-[13px] text-red-600">
                <li v-for="w in result.when_to_seek_help" :key="w">
                    • {{ w }}
                </li>
            </ul>
        </div>

        <p class="text-center text-[11.5px] text-ink-400 italic">
            {{ result.disclaimer }}
        </p>
    </Reveal>
</template>
