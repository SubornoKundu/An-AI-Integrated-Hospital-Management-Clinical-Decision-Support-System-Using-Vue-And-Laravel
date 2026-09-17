<script setup lang="ts">
import { Deferred, Head, Link } from '@inertiajs/vue3';
import {
    Activity,
    AlertTriangle,
    Bot,
    ChevronDown,
    ChevronUp,
    Loader2,
    Mic,
    ScrollText,
    Sparkles,
    Square,
} from '@lucide/vue';
import { onBeforeUnmount, ref } from 'vue';
import Reveal from '@/components/public/Reveal.vue';
import { Button } from '@/components/ui/button';
import { aiPost, aiPostForm } from '@/lib/aiClient';

interface Category {
    id: number;
    name: string;
    icon: string;
}
interface ApiStatus {
    connected: boolean;
    message?: string;
}
interface AnalysisResult {
    urgency: 'low' | 'medium' | 'high' | 'emergency';
    urgency_note: string;
    recommended_specialty: string;
    specialty_reason: string;
    possible_conditions: string[];
    immediate_advice: string[];
    warning_signs: string[];
    disclaimer: string;
    category_id: number | null;
    category_icon: string;
}

defineProps<{
    categories: Category[];
    apiStatus?: ApiStatus;
}>();

const symptoms = ref('');
const loading = ref(false);
const error = ref('');
const result = ref<AnalysisResult | null>(null);

// How long symptoms have lasted and whether they're changing — this is
// usually the first thing a doctor asks, and it can swing the urgency call
// a lot, so it's kept visible rather than tucked into the optional section
// below. Still skippable — if left unset, it's simply not mentioned to the AI.
const durationOptions = [
    'Just started (today)',
    '2-3 days',
    'About a week',
    '2-4 weeks',
    'A month or more',
];
const trendOptions = ['Getting worse', 'About the same', 'Getting better'];
const symptomDuration = ref('');
const symptomTrend = ref('');

// ── Optional "for this check only" context ──────────────────────────
// Neither of these is saved anywhere (not the vitals table, not the
// profile). If filled in, each overrides what would otherwise be pulled
// from stored data — just for this one analysis.
const showMoreContext = ref(false);
const manualBpSystolic = ref('');
const manualBpDiastolic = ref('');
const manualPulse = ref('');
const manualTemp = ref('');
const manualSpo2 = ref('');
const additionalHistory = ref('');

function isManualVitalAbnormal(
    field: 'bp' | 'pulse' | 'temp' | 'spo2',
): boolean {
    if (field === 'bp') {
        const sys = Number(manualBpSystolic.value);
        const dia = Number(manualBpDiastolic.value);

        return (
            (!!manualBpSystolic.value && (sys >= 140 || sys < 90)) ||
            (!!manualBpDiastolic.value && (dia >= 90 || dia < 60))
        );
    }

    if (field === 'pulse') {
        const p = Number(manualPulse.value);

        return !!manualPulse.value && (p > 100 || p < 60);
    }

    if (field === 'temp') {
        const t = Number(manualTemp.value);

        return !!manualTemp.value && (t >= 37.5 || t < 35);
    }

    const s = Number(manualSpo2.value);

    return !!manualSpo2.value && s < 95;
}

const quickTags = [
    'Headache',
    'Fever',
    'Chest Pain',
    'Shortness of breath',
    'Abdominal pain',
    'Back pain',
    'Joint pain',
    'Skin rash',
    'Eye problem',
    'Ear pain',
    'Cough',
    'Fatigue',
    'Dizziness',
    'Nausea',
    'Vomiting',
];

function addTag(tag: string) {
    symptoms.value = symptoms.value ? `${symptoms.value}, ${tag}` : tag;
}

function clearForm() {
    symptoms.value = '';
    result.value = null;
    error.value = '';
    symptomDuration.value = '';
    symptomTrend.value = '';
    manualBpSystolic.value = '';
    manualBpDiastolic.value = '';
    manualPulse.value = '';
    manualTemp.value = '';
    manualSpo2.value = '';
    additionalHistory.value = '';
}

// ── Voice input ──────────────────────────────────────────────────────
const isRecording = ref(false);
const isTranscribing = ref(false);
const voiceError = ref('');
let mediaRecorder: MediaRecorder | null = null;
let audioChunks: Blob[] = [];
let activeStream: MediaStream | null = null;

async function toggleRecording() {
    if (isRecording.value) {
        mediaRecorder?.stop();
        isRecording.value = false;

        return;
    }

    voiceError.value = '';

    if (!navigator.mediaDevices?.getUserMedia) {
        voiceError.value = window.isSecureContext
            ? 'Voice input is not supported in this browser.'
            : 'Voice input needs a secure connection (HTTPS). This page is loaded over plain HTTP — enable HTTPS for this site (e.g. run `herd secure` if using Herd) and reload.';

        return;
    }

    try {
        activeStream = await navigator.mediaDevices.getUserMedia({
            audio: true,
        });
    } catch (err) {
        if (err instanceof DOMException && err.name === 'NotAllowedError') {
            voiceError.value =
                'Microphone access denied. Please allow microphone permission for this site in your browser and try again.';
        } else if (
            err instanceof DOMException &&
            err.name === 'NotFoundError'
        ) {
            voiceError.value =
                'No microphone found. Please connect a microphone and try again.';
        } else {
            voiceError.value =
                'Could not access the microphone. Please check your browser and system permissions.';
        }

        return;
    }

    audioChunks = [];
    mediaRecorder = new MediaRecorder(activeStream);
    mediaRecorder.ondataavailable = (e) => {
        if (e.data.size > 0) {
            audioChunks.push(e.data);
        }
    };
    mediaRecorder.onstop = async () => {
        activeStream?.getTracks().forEach((t) => t.stop());
        const blob = new Blob(audioChunks, { type: 'audio/webm' });
        await transcribeAudio(blob);
    };
    mediaRecorder.start();
    isRecording.value = true;
}

async function transcribeAudio(blob: Blob) {
    isTranscribing.value = true;
    voiceError.value = '';
    const form = new FormData();
    form.append('audio', blob, 'recording.webm');
    const { ok, data } = await aiPostForm<{ text?: string; error?: string }>(
        '/patient/ai/symptom-checker/voice',
        form,
    );
    isTranscribing.value = false;

    if (!ok || data.error || !data.text) {
        voiceError.value =
            data.error ?? 'Could not process the recording. Please try again.';

        return;
    }

    symptoms.value = symptoms.value
        ? `${symptoms.value} ${data.text}`
        : data.text;
}

onBeforeUnmount(() => {
    activeStream?.getTracks().forEach((t) => t.stop());
});

const urgencyStyle: Record<string, { badge: string; ring: string }> = {
    low: {
        badge: 'bg-brand-50 text-brand-700 ring-brand-200',
        ring: 'border-brand-200',
    },
    medium: {
        badge: 'bg-amber-50 text-amber-700 ring-amber-200',
        ring: 'border-amber-200',
    },
    high: {
        badge: 'bg-orange-50 text-orange-700 ring-orange-200',
        ring: 'border-orange-200',
    },
    emergency: {
        badge: 'bg-red-50 text-red-700 ring-red-200',
        ring: 'border-red-200',
    },
};

async function analyze() {
    if (symptoms.value.trim().length < 10) {
        error.value =
            'Please describe your symptoms in at least 10 characters.';

        return;
    }

    loading.value = true;
    error.value = '';
    result.value = null;

    const { ok, data } = await aiPost<AnalysisResult & { error?: string }>(
        '/patient/ai/symptom-checker/analyze',
        {
            symptoms: symptoms.value,
            duration: symptomDuration.value,
            trend: symptomTrend.value,
            bp_systolic: manualBpSystolic.value,
            bp_diastolic: manualBpDiastolic.value,
            pulse_rate: manualPulse.value,
            temperature_celsius: manualTemp.value,
            spo2: manualSpo2.value,
            additional_history: additionalHistory.value,
        },
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
    <Head title="AI Symptom Checker" />

    <div class="flex items-start justify-between">
        <div>
            <h1 class="flex items-center gap-2 text-xl font-bold text-ink-900">
                <Bot class="size-6 text-purple-500" /> AI Symptom Checker
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
            href="/patient/dashboard"
            class="rounded-full border border-ink-200 px-4 py-2 text-sm font-medium text-ink-600 hover:bg-ink-50"
            >← Dashboard</Link
        >
    </div>

    <p class="text-[13.5px] text-ink-500">
        Describe your symptoms — AI will recommend the right specialist
    </p>

    <div
        class="flex items-start gap-2.5 rounded-xl border border-amber-100 bg-amber-50/60 p-4 text-[13px] text-amber-800"
    >
        <AlertTriangle class="mt-0.5 size-4 shrink-0" />
        <p>
            <strong>Medical Disclaimer:</strong> This AI tool is for guidance
            only and does not replace professional medical advice. Always
            consult a qualified doctor for diagnosis and treatment.
        </p>
    </div>

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
        <div class="rounded-2xl border border-ink-100 bg-white p-5 shadow-sm">
            <h2
                class="mb-3 flex items-center gap-2 text-sm font-semibold text-ink-700"
            >
                <ScrollText class="size-4" /> Describe Your Symptoms
            </h2>
            <label class="mb-1.5 block text-[13px] font-medium text-ink-600"
                >Your Symptoms *</label
            >
            <div class="relative">
                <textarea
                    v-model="symptoms"
                    rows="6"
                    placeholder="Describe your symptoms in detail, or tap the mic to speak in Bengali or English. For example:

I have been experiencing severe headache for 3 days, along with fever (38°C) and stiff neck. The headache is worst in the morning and I feel nauseous."
                    class="w-full rounded-md border border-input bg-transparent px-3 py-2 pr-12 text-sm leading-relaxed outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                />
                <button
                    type="button"
                    :title="
                        isRecording
                            ? 'Stop recording'
                            : 'Speak your symptoms (any language)'
                    "
                    class="absolute top-2 right-2 flex size-8 items-center justify-center rounded-full transition-colors"
                    :class="
                        isRecording
                            ? 'animate-pulse bg-red-500 text-white'
                            : 'bg-ink-100 text-ink-500 hover:bg-brand-50 hover:text-brand-600'
                    "
                    :disabled="isTranscribing"
                    @click="toggleRecording"
                >
                    <Loader2
                        v-if="isTranscribing"
                        class="size-4 animate-spin"
                    />
                    <Square
                        v-else-if="isRecording"
                        class="size-3.5 fill-current"
                    />
                    <Mic v-else class="size-4" />
                </button>
            </div>
            <p v-if="voiceError" class="mt-1.5 text-[12px] text-destructive">
                ⚠ {{ voiceError }}
            </p>
            <p
                v-else-if="isRecording"
                class="mt-1.5 text-[12px] font-medium text-red-500"
            >
                🔴 Listening… tap again to stop
            </p>
            <p
                v-else-if="isTranscribing"
                class="mt-1.5 text-[12px] text-ink-400"
            >
                Transcribing your voice…
            </p>
            <p v-else class="mt-1.5 text-[12px] text-ink-400">
                💡 More detail = better recommendation. Min 10 characters. Tap
                🎙️ to speak in Bengali or English — speak slowly and clearly, a
                little close to the mic, for the most accurate result.
            </p>

            <p class="mt-4 mb-2 text-[12.5px] font-medium text-ink-500">
                Quick Tags (click to add)
            </p>
            <div class="flex flex-wrap gap-2">
                <button
                    v-for="tag in quickTags"
                    :key="tag"
                    type="button"
                    class="rounded-full border border-ink-200 px-3 py-1.5 text-[12.5px] font-medium text-ink-600 transition-colors hover:border-brand-300 hover:bg-brand-50 hover:text-brand-700"
                    @click="addTag(tag)"
                >
                    {{ tag }}
                </button>
            </div>

            <div class="mt-4">
                <p class="mb-2 text-[12.5px] font-medium text-ink-500">
                    How long has this been going on?
                    <span class="font-normal text-ink-400">(optional)</span>
                </p>
                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="opt in durationOptions"
                        :key="opt"
                        type="button"
                        class="rounded-full border px-3 py-1.5 text-[12.5px] font-medium transition-colors"
                        :class="
                            symptomDuration === opt
                                ? 'border-brand-400 bg-brand-50 text-brand-700'
                                : 'border-ink-200 text-ink-600 hover:border-brand-300 hover:bg-brand-50 hover:text-brand-700'
                        "
                        @click="
                            symptomDuration = symptomDuration === opt ? '' : opt
                        "
                    >
                        {{ opt }}
                    </button>
                </div>
            </div>

            <div class="mt-4">
                <p class="mb-2 text-[12.5px] font-medium text-ink-500">
                    Is it getting worse?
                    <span class="font-normal text-ink-400">(optional)</span>
                </p>
                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="opt in trendOptions"
                        :key="opt"
                        type="button"
                        class="rounded-full border px-3 py-1.5 text-[12.5px] font-medium transition-colors"
                        :class="
                            symptomTrend === opt
                                ? 'border-brand-400 bg-brand-50 text-brand-700'
                                : 'border-ink-200 text-ink-600 hover:border-brand-300 hover:bg-brand-50 hover:text-brand-700'
                        "
                        @click="symptomTrend = symptomTrend === opt ? '' : opt"
                    >
                        {{ opt }}
                    </button>
                </div>
            </div>

            <div class="mt-4 border-t border-ink-100 pt-4">
                <button
                    type="button"
                    class="flex w-full items-center justify-between text-[12.5px] font-medium text-ink-600 hover:text-brand-600"
                    @click="showMoreContext = !showMoreContext"
                >
                    <span class="flex items-center gap-1.5">
                        <Activity class="size-3.5" /> Add recent vitals or extra
                        history
                        <span class="font-normal text-ink-400">(optional)</span>
                    </span>
                    <ChevronUp
                        v-if="showMoreContext"
                        class="size-4 text-ink-400"
                    />
                    <ChevronDown v-else class="size-4 text-ink-400" />
                </button>

                <div v-if="showMoreContext" class="mt-3 space-y-3">
                    <div>
                        <label
                            class="mb-1.5 block text-[12px] font-medium text-ink-600"
                            >Latest vitals, if you know them</label
                        >
                        <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                            <div>
                                <label
                                    class="mb-1 block text-[11px] text-ink-500"
                                    >BP (mmHg)</label
                                >
                                <div class="flex items-center gap-1">
                                    <input
                                        v-model="manualBpSystolic"
                                        type="number"
                                        placeholder="120"
                                        class="w-full rounded-md border px-2 py-1.5 text-[13px] outline-none focus-visible:border-brand-400"
                                        :class="
                                            isManualVitalAbnormal('bp')
                                                ? 'border-red-300 bg-red-50 text-red-700'
                                                : 'border-ink-200'
                                        "
                                    />
                                    <span class="text-ink-300">/</span>
                                    <input
                                        v-model="manualBpDiastolic"
                                        type="number"
                                        placeholder="80"
                                        class="w-full rounded-md border px-2 py-1.5 text-[13px] outline-none focus-visible:border-brand-400"
                                        :class="
                                            isManualVitalAbnormal('bp')
                                                ? 'border-red-300 bg-red-50 text-red-700'
                                                : 'border-ink-200'
                                        "
                                    />
                                </div>
                            </div>
                            <div>
                                <label
                                    class="mb-1 block text-[11px] text-ink-500"
                                    >Pulse (bpm)</label
                                >
                                <input
                                    v-model="manualPulse"
                                    type="number"
                                    placeholder="72"
                                    class="w-full rounded-md border px-2 py-1.5 text-[13px] outline-none focus-visible:border-brand-400"
                                    :class="
                                        isManualVitalAbnormal('pulse')
                                            ? 'border-red-300 bg-red-50 text-red-700'
                                            : 'border-ink-200'
                                    "
                                />
                            </div>
                            <div>
                                <label
                                    class="mb-1 block text-[11px] text-ink-500"
                                    >Temp (°C)</label
                                >
                                <input
                                    v-model="manualTemp"
                                    type="number"
                                    step="0.1"
                                    placeholder="36.8"
                                    class="w-full rounded-md border px-2 py-1.5 text-[13px] outline-none focus-visible:border-brand-400"
                                    :class="
                                        isManualVitalAbnormal('temp')
                                            ? 'border-red-300 bg-red-50 text-red-700'
                                            : 'border-ink-200'
                                    "
                                />
                            </div>
                            <div>
                                <label
                                    class="mb-1 block text-[11px] text-ink-500"
                                    >SpO2 (%)</label
                                >
                                <input
                                    v-model="manualSpo2"
                                    type="number"
                                    placeholder="98"
                                    class="w-full rounded-md border px-2 py-1.5 text-[13px] outline-none focus-visible:border-brand-400"
                                    :class="
                                        isManualVitalAbnormal('spo2')
                                            ? 'border-red-300 bg-red-50 text-red-700'
                                            : 'border-ink-200'
                                    "
                                />
                            </div>
                        </div>
                    </div>
                    <div>
                        <label
                            class="mb-1.5 block text-[12px] font-medium text-ink-600"
                            >Anything else relevant right now?</label
                        >
                        <textarea
                            v-model="additionalHistory"
                            rows="2"
                            placeholder="e.g. had dengue 2 years ago, allergic to aspirin..."
                            class="w-full rounded-md border border-ink-200 bg-transparent px-3 py-2 text-[13px] outline-none focus-visible:border-brand-400"
                        />
                        <p class="mt-1 text-[11px] text-ink-400">
                            Used for this check only — added alongside your
                            saved profile history for this analysis, nothing
                            here gets saved anywhere.
                        </p>
                    </div>
                </div>
            </div>

            <div class="mt-5 flex gap-2">
                <Button
                    class="flex-1 gap-2 bg-gradient-to-r from-purple-500 to-brand-600 py-5 hover:from-purple-600 hover:to-brand-700"
                    :disabled="loading"
                    @click="analyze"
                >
                    <Loader2 v-if="loading" class="size-4 animate-spin" />
                    <Sparkles v-else class="size-4" />
                    {{ loading ? 'Analyzing…' : 'Analyze Symptoms' }}
                </Button>
                <Button variant="outline" @click="clearForm">Clear</Button>
            </div>
            <p v-if="error" class="mt-2 text-[13px] text-destructive">
                ⚠ {{ error }}
            </p>
        </div>

        <div class="rounded-2xl border border-ink-100 bg-white p-5 shadow-sm">
            <template v-if="!result">
                <div
                    class="flex h-full min-h-[280px] flex-col items-center justify-center text-center text-ink-400"
                >
                    <Bot class="mb-3 size-12 opacity-20" />
                    <div class="text-[14px] font-semibold text-ink-600">
                        AI Analysis Result
                    </div>
                    <p class="mt-1 text-[13px]">
                        Describe your symptoms on the left and click
                        <strong class="text-ink-600">Analyze Symptoms</strong>
                    </p>
                </div>
            </template>
            <Reveal v-else class="space-y-4">
                <div
                    class="rounded-xl border p-4"
                    :class="
                        urgencyStyle[result.urgency]?.ring ?? 'border-ink-200'
                    "
                >
                    <span
                        class="inline-block rounded-full px-2.5 py-1 text-[11px] font-bold tracking-wide uppercase ring-1"
                        :class="
                            urgencyStyle[result.urgency]?.badge ??
                            'bg-ink-100 text-ink-600'
                        "
                        >{{ result.urgency }} urgency</span
                    >
                    <p class="mt-2 text-[13.5px] leading-relaxed text-ink-700">
                        {{ result.urgency_note }}
                    </p>
                </div>

                <div class="rounded-xl bg-brand-50/60 p-4">
                    <div class="flex items-center gap-2.5">
                        <span class="text-xl">{{ result.category_icon }}</span>
                        <span class="text-[15px] font-bold text-ink-900">{{
                            result.recommended_specialty
                        }}</span>
                    </div>
                    <p class="mt-1.5 text-[13px] text-ink-600">
                        {{ result.specialty_reason }}
                    </p>
                    <Link
                        v-if="result.category_id"
                        :href="`/patient/book/doctor?category_id=${result.category_id}`"
                        class="mt-3 inline-block rounded-full bg-brand-600 px-4 py-2 text-[13px] font-semibold text-white hover:bg-brand-700"
                        >Book with {{ result.recommended_specialty }} →</Link
                    >
                </div>

                <div>
                    <h3 class="mb-1.5 text-[13px] font-semibold text-ink-700">
                        Possible Conditions
                    </h3>
                    <ul class="space-y-1 text-[13px] text-ink-600">
                        <li v-for="c in result.possible_conditions" :key="c">
                            • {{ c }}
                        </li>
                    </ul>
                </div>

                <div>
                    <h3 class="mb-1.5 text-[13px] font-semibold text-ink-700">
                        Immediate Advice
                    </h3>
                    <ul class="space-y-1 text-[13px] text-ink-600">
                        <li v-for="a in result.immediate_advice" :key="a">
                            ✓ {{ a }}
                        </li>
                    </ul>
                </div>

                <div
                    v-if="result.warning_signs?.length"
                    class="rounded-xl border border-red-100 bg-red-50/60 p-4"
                >
                    <h3 class="mb-1.5 text-[13px] font-semibold text-red-700">
                        ⚠ Seek Emergency Care If:
                    </h3>
                    <ul class="space-y-1 text-[13px] text-red-600">
                        <li v-for="w in result.warning_signs" :key="w">
                            • {{ w }}
                        </li>
                    </ul>
                </div>

                <p class="text-[11.5px] text-ink-400 italic">
                    {{ result.disclaimer }}
                </p>
            </Reveal>
        </div>
    </div>
</template>
