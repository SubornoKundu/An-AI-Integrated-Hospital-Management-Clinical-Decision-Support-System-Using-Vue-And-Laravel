<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    Activity,
    AlertTriangle,
    Bot,
    CheckCircle2,
    FileEdit,
    Loader2,
    ShieldCheck,
    Sparkles,
    UserRoundPlus,
    XCircle,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { aiPost } from '@/lib/aiClient';

interface Template {
    id: number;
    template_name: string;
    diagnosis: string;
    medicines: string;
    instructions: string | null;
}

interface PastVisit {
    id: number;
    appointment_date: string;
    category: string;
    diagnosis: string;
    medicines: string;
}

const props = defineProps<{
    appointment: {
        id: number;
        appointment_date: string;
        patient: {
            id: number;
            full_name: string;
            gender: string | null;
            age: number | null;
            blood_group: string | null;
            contact: string | null;
            photo: string | null;
        } | null;
    };
    prescription: {
        diagnosis: string;
        medicines: string;
        instructions: string | null;
        follow_up_date: string | null;
    } | null;
    vital: {
        bp_systolic: number | null;
        bp_diastolic: number | null;
        pulse_rate: number | null;
        temperature_celsius: number | null;
        spo2: number | null;
    } | null;
    templates: Template[];
    pastPrescriptions: PastVisit[];
    categories: { id: number; name: string }[];
}>();

const form = useForm({
    diagnosis: props.prescription?.diagnosis ?? '',
    medicines: props.prescription?.medicines ?? '',
    instructions: props.prescription?.instructions ?? '',
    follow_up_date: props.prescription?.follow_up_date ?? '',
    bp_systolic: props.vital?.bp_systolic ?? '',
    bp_diastolic: props.vital?.bp_diastolic ?? '',
    pulse_rate: props.vital?.pulse_rate ?? '',
    temperature_celsius: props.vital?.temperature_celsius ?? '',
    spo2: props.vital?.spo2 ?? '',
    safety_check_override: null as string | null,
});

// Rough visual flags only — not a diagnosis, just a nudge to double-check
// a number that's outside a normal resting range.
function isAbnormal(field: 'bp' | 'pulse' | 'temp' | 'spo2'): boolean {
    if (field === 'bp') {
        const sys = Number(form.bp_systolic);
        const dia = Number(form.bp_diastolic);

        return (
            (!!form.bp_systolic && (sys >= 140 || sys < 90)) ||
            (!!form.bp_diastolic && (dia >= 90 || dia < 60))
        );
    }

    if (field === 'pulse') {
        const p = Number(form.pulse_rate);

        return !!form.pulse_rate && (p > 100 || p < 60);
    }

    if (field === 'temp') {
        const t = Number(form.temperature_celsius);

        return !!form.temperature_celsius && (t >= 37.5 || t < 35);
    }

    const s = Number(form.spo2);

    return !!form.spo2 && s < 95;
}

function applyTemplate(t: Template) {
    form.diagnosis = t.diagnosis;
    form.medicines = t.medicines;
    form.instructions = t.instructions ?? '';
}

// ── AI Patient Briefing ─────────────────────────────────────────
interface SummaryResult {
    patient_snapshot: string;
    chronic_conditions: string[];
    red_flags: string[];
    consultation_focus: string[];
    clinical_note: string;
    error?: string;
}

const summaryState = ref<'idle' | 'loading' | 'done' | 'error'>('idle');
const summary = ref<SummaryResult | null>(null);
const summaryError = ref('');

async function generateSummary() {
    summaryState.value = 'loading';
    summaryError.value = '';

    const { ok, data } = await aiPost<SummaryResult>(
        `/doctor/prescription/${props.appointment.id}/ai-summary`,
    );

    if (!ok || data.error) {
        summaryError.value = data.error ?? 'Connection failed.';
        summaryState.value = 'error';

        return;
    }

    summary.value = data;
    summaryState.value = 'done';
}

// ── AI Safety Check ──────────────────────────────────────────────
interface SafetyResult {
    verdict_summary: string;
    critical_issues: string[];
    warnings: string[];
    info: string[];
    safe_checks: string[];
    recommendations: string[];
    error?: string;
}

const modalOpen = ref(false);
watch(modalOpen, (open) => {
    if (!open) {
        showReferForm.value = false;
    }
});
const safetyState = ref<'loading' | 'done' | 'error'>('loading');
const safety = ref<SafetyResult | null>(null);
const safetyError = ref('');

async function runSafetyCheck() {
    if (!form.diagnosis.trim() || !form.medicines.trim()) {
        return;
    }

    modalOpen.value = true;
    safetyState.value = 'loading';
    safety.value = null;

    const { ok, data } = await aiPost<SafetyResult>(
        `/doctor/prescription/${props.appointment.id}/safety-check`,
        { diagnosis: form.diagnosis, medicines: form.medicines },
    );

    if (!ok || data.error) {
        safetyError.value =
            data.error ?? 'Connection failed. You may proceed to save.';
        safetyState.value = 'error';

        return;
    }

    safety.value = data;
    safetyState.value = 'done';
}

// A snapshot of exactly what was flagged, only when there was something to
// flag — this is what gets stored on the prescription if the doctor
// chooses to override rather than what triggers the button to change.
const safetyOverrideNote = computed(() => {
    if (!safety.value) {
        return null;
    }

    const flagged = [...safety.value.critical_issues, ...safety.value.warnings];

    return flagged.length
        ? `AI safety check flagged (doctor proceeded anyway): ${flagged.join('; ')}`
        : null;
});

function save() {
    form.safety_check_override = safetyOverrideNote.value;
    modalOpen.value = false;
    form.post(`/doctor/prescription/${props.appointment.id}`);
}

// ── Refer Instead ────────────────────────────────────────────────
const showReferForm = ref(false);
const referCategoryId = ref<number | ''>('');
const referReason = ref('');
const referring = ref(false);

function openReferForm() {
    const flagged = safety.value
        ? [...safety.value.critical_issues, ...safety.value.warnings]
        : [];
    referReason.value = flagged.length
        ? `AI prescription safety check flagged: ${flagged.join('; ')}`
        : '';
    showReferForm.value = true;
}

function submitReferral() {
    if (!referReason.value.trim() || !props.appointment.patient) {
        return;
    }

    referring.value = true;
    router.post(
        '/doctor/referrals',
        {
            patient_id: props.appointment.patient.id,
            appointment_id: props.appointment.id,
            to_category_id: referCategoryId.value || null,
            reason: referReason.value,
        },
        {
            onSuccess: () => router.visit('/doctor/appointments'),
            onFinish: () => (referring.value = false),
        },
    );
}
</script>

<template>
    <Head title="Write Prescription" />

    <div class="flex items-center justify-between">
        <div>
            <h1 class="flex items-center gap-2 text-xl font-bold text-ink-900">
                <FileEdit class="size-5 text-brand-500" /> Write Prescription
            </h1>
            <p class="mt-1 text-[13.5px] text-ink-500">
                {{ props.appointment.patient?.full_name ?? '—' }} ·
                {{ props.appointment.appointment_date }}
            </p>
        </div>
        <Link
            href="/doctor/appointments"
            class="rounded-full border border-ink-200 px-4 py-2 text-[12.5px] font-medium text-ink-600 hover:bg-ink-50"
            >← Back</Link
        >
    </div>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-[1fr_1.6fr]">
        <div class="space-y-4">
            <div
                class="rounded-2xl border border-ink-100 bg-white p-4 shadow-sm"
            >
                <h2 class="mb-3 text-[13.5px] font-bold text-ink-900">
                    Patient Info
                </h2>
                <div class="mb-3 flex items-center gap-3">
                    <img
                        v-if="props.appointment.patient?.photo"
                        :src="props.appointment.patient.photo"
                        :alt="props.appointment.patient.full_name"
                        class="size-12 shrink-0 rounded-full object-cover"
                    />
                    <div
                        v-else
                        class="flex size-12 shrink-0 items-center justify-center rounded-full bg-brand-50 text-base font-semibold text-brand-600"
                    >
                        {{
                            (props.appointment.patient?.full_name ?? '?')
                                .charAt(0)
                                .toUpperCase()
                        }}
                    </div>
                    <div class="min-w-0 font-semibold text-ink-900">
                        {{ props.appointment.patient?.full_name ?? '—' }}
                    </div>
                </div>
                <dl class="space-y-2 text-[13px]">
                    <div class="flex justify-between">
                        <dt class="text-ink-500">Name</dt>
                        <dd class="font-medium text-ink-800">
                            {{ props.appointment.patient?.full_name ?? '—' }}
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-ink-500">Gender</dt>
                        <dd class="font-medium text-ink-800">
                            {{ props.appointment.patient?.gender ?? '—' }}
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-ink-500">Age</dt>
                        <dd class="font-medium text-ink-800">
                            {{ props.appointment.patient?.age ?? '—' }} yrs
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-ink-500">Blood</dt>
                        <dd class="font-medium text-red-500">
                            {{ props.appointment.patient?.blood_group ?? '—' }}
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-ink-500">Phone</dt>
                        <dd class="font-medium text-ink-800">
                            {{ props.appointment.patient?.contact ?? '—' }}
                        </dd>
                    </div>
                </dl>
            </div>

            <div
                class="rounded-2xl border border-brand-200 bg-white p-4 shadow-sm"
            >
                <div class="mb-2 flex items-center justify-between">
                    <h2
                        class="flex items-center gap-1.5 text-[13.5px] font-bold text-brand-600"
                    >
                        <Bot class="size-4" /> AI Patient Briefing
                    </h2>
                    <button
                        type="button"
                        :disabled="summaryState === 'loading'"
                        class="rounded-full border border-brand-200 px-3 py-1.5 text-[12px] font-medium text-brand-600 hover:bg-brand-50 disabled:opacity-50"
                        @click="generateSummary"
                    >
                        {{
                            summaryState === 'done'
                                ? 'Refresh'
                                : 'Generate Summary'
                        }}
                    </button>
                </div>

                <p
                    v-if="summaryState === 'idle'"
                    class="text-[12.5px] leading-relaxed text-ink-500"
                >
                    AI will analyze this patient's full medical history and
                    provide clinical insights to help you today.
                </p>
                <div
                    v-else-if="summaryState === 'loading'"
                    class="flex items-center justify-center gap-2 py-4 text-[12.5px] text-ink-500"
                >
                    <Loader2 class="size-4 animate-spin" /> Analyzing history…
                </div>
                <p
                    v-else-if="summaryState === 'error'"
                    class="text-[12.5px] text-red-600"
                >
                    ⚠️ {{ summaryError }}
                </p>
                <div v-else-if="summary" class="space-y-2.5 text-[12.5px]">
                    <p class="border-b border-ink-100 pb-2.5 text-ink-700">
                        {{ summary.patient_snapshot }}
                    </p>
                    <div v-if="summary.chronic_conditions?.length">
                        <div
                            class="mb-1 text-[10.5px] font-bold tracking-wide text-ink-400 uppercase"
                        >
                            Chronic Conditions
                        </div>
                        <span
                            v-for="(c, i) in summary.chronic_conditions"
                            :key="i"
                            class="mr-1.5 mb-1 inline-block rounded-full bg-red-50 px-2 py-0.5 text-[11.5px] text-red-600"
                            >{{ c }}</span
                        >
                    </div>
                    <div v-if="summary.red_flags?.length">
                        <div
                            class="mb-1 text-[10.5px] font-bold tracking-wide text-amber-500 uppercase"
                        >
                            ⚠️ Red Flags
                        </div>
                        <ul class="list-disc space-y-0.5 pl-4 text-amber-600">
                            <li v-for="(f, i) in summary.red_flags" :key="i">
                                {{ f }}
                            </li>
                        </ul>
                    </div>
                    <div v-if="summary.consultation_focus?.length">
                        <div
                            class="mb-1 text-[10.5px] font-bold tracking-wide text-brand-500 uppercase"
                        >
                            Focus Today
                        </div>
                        <ul class="list-disc space-y-0.5 pl-4 text-ink-600">
                            <li
                                v-for="(f, i) in summary.consultation_focus"
                                :key="i"
                            >
                                {{ f }}
                            </li>
                        </ul>
                    </div>
                    <p
                        v-if="summary.clinical_note"
                        class="rounded-md border-l-2 border-brand-400 bg-brand-50/60 p-2 text-[11.5px] text-ink-500"
                    >
                        {{ summary.clinical_note }}
                    </p>
                </div>
            </div>

            <div
                v-if="props.templates.length"
                class="rounded-2xl border border-ink-100 bg-white p-4 shadow-sm"
            >
                <h2 class="mb-2 text-[13.5px] font-bold text-ink-900">
                    📝 Quick Templates
                </h2>
                <div class="flex flex-col gap-1.5">
                    <button
                        v-for="t in props.templates"
                        :key="t.id"
                        type="button"
                        class="rounded-lg border border-ink-100 px-3 py-2 text-left text-[12.5px] text-ink-600 hover:border-brand-200 hover:bg-brand-50"
                        @click="applyTemplate(t)"
                    >
                        {{ t.template_name }}
                    </button>
                </div>
            </div>

            <div
                v-if="props.pastPrescriptions.length"
                class="rounded-2xl border border-ink-100 bg-white p-4 shadow-sm"
            >
                <h2 class="mb-2 text-[13.5px] font-bold text-ink-900">
                    🗂 Patient's Past Visits
                </h2>
                <div
                    v-for="p in props.pastPrescriptions"
                    :key="p.id"
                    class="border-b border-ink-50 py-2 text-[12.5px] last:border-0"
                >
                    <div class="flex justify-between">
                        <span class="font-semibold text-brand-600">{{
                            p.category
                        }}</span>
                        <span class="text-ink-400">{{
                            p.appointment_date
                        }}</span>
                    </div>
                    <div class="mt-0.5 text-ink-600">
                        <strong>Dx:</strong> {{ p.diagnosis }}
                    </div>
                    <div class="text-ink-400">
                        <strong>Rx:</strong> {{ p.medicines }}
                    </div>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-ink-100 bg-white p-4 shadow-sm">
            <div class="space-y-4">
                <div>
                    <label
                        class="mb-2 flex items-center gap-1.5 text-[12.5px] font-medium text-ink-700"
                    >
                        <Activity class="size-3.5 text-ink-400" /> Vitals
                        <span class="font-normal text-ink-400">(optional)</span>
                    </label>
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                        <div>
                            <label class="mb-1 block text-[11px] text-ink-500"
                                >BP (mmHg)</label
                            >
                            <div class="flex items-center gap-1">
                                <input
                                    v-model="form.bp_systolic"
                                    type="number"
                                    placeholder="120"
                                    class="w-full rounded-lg border px-2 py-1.5 text-[13px] outline-none focus-visible:border-brand-400"
                                    :class="
                                        isAbnormal('bp')
                                            ? 'border-red-300 bg-red-50 text-red-700'
                                            : 'border-ink-200'
                                    "
                                />
                                <span class="text-ink-300">/</span>
                                <input
                                    v-model="form.bp_diastolic"
                                    type="number"
                                    placeholder="80"
                                    class="w-full rounded-lg border px-2 py-1.5 text-[13px] outline-none focus-visible:border-brand-400"
                                    :class="
                                        isAbnormal('bp')
                                            ? 'border-red-300 bg-red-50 text-red-700'
                                            : 'border-ink-200'
                                    "
                                />
                            </div>
                        </div>
                        <div>
                            <label class="mb-1 block text-[11px] text-ink-500"
                                >Pulse (bpm)</label
                            >
                            <input
                                v-model="form.pulse_rate"
                                type="number"
                                placeholder="72"
                                class="w-full rounded-lg border px-2 py-1.5 text-[13px] outline-none focus-visible:border-brand-400"
                                :class="
                                    isAbnormal('pulse')
                                        ? 'border-red-300 bg-red-50 text-red-700'
                                        : 'border-ink-200'
                                "
                            />
                        </div>
                        <div>
                            <label class="mb-1 block text-[11px] text-ink-500"
                                >Temp (°C)</label
                            >
                            <input
                                v-model="form.temperature_celsius"
                                type="number"
                                step="0.1"
                                placeholder="36.8"
                                class="w-full rounded-lg border px-2 py-1.5 text-[13px] outline-none focus-visible:border-brand-400"
                                :class="
                                    isAbnormal('temp')
                                        ? 'border-red-300 bg-red-50 text-red-700'
                                        : 'border-ink-200'
                                "
                            />
                        </div>
                        <div>
                            <label class="mb-1 block text-[11px] text-ink-500"
                                >SpO2 (%)</label
                            >
                            <input
                                v-model="form.spo2"
                                type="number"
                                placeholder="98"
                                class="w-full rounded-lg border px-2 py-1.5 text-[13px] outline-none focus-visible:border-brand-400"
                                :class="
                                    isAbnormal('spo2')
                                        ? 'border-red-300 bg-red-50 text-red-700'
                                        : 'border-ink-200'
                                "
                            />
                        </div>
                    </div>
                </div>
                <div>
                    <label
                        class="mb-1 block text-[12.5px] font-medium text-ink-700"
                        >Diagnosis *</label
                    >
                    <textarea
                        v-model="form.diagnosis"
                        rows="3"
                        required
                        placeholder="Primary diagnosis..."
                        class="w-full rounded-lg border border-ink-200 px-3 py-2 text-[13px] outline-none focus-visible:border-brand-400"
                    />
                    <p
                        v-if="form.errors.diagnosis"
                        class="mt-1 text-[11.5px] text-red-500"
                    >
                        {{ form.errors.diagnosis }}
                    </p>
                </div>
                <div>
                    <label
                        class="mb-1 block text-[12.5px] font-medium text-ink-700"
                        >Medicines *</label
                    >
                    <textarea
                        v-model="form.medicines"
                        rows="6"
                        required
                        placeholder="Medicine — Dose — Frequency — Duration&#10;e.g: Paracetamol 500mg — 1 tab — 3x/day — 5 days"
                        class="w-full rounded-lg border border-ink-200 px-3 py-2 text-[13px] outline-none focus-visible:border-brand-400"
                    />
                    <p
                        v-if="form.errors.medicines"
                        class="mt-1 text-[11.5px] text-red-500"
                    >
                        {{ form.errors.medicines }}
                    </p>
                </div>
                <div>
                    <label
                        class="mb-1 block text-[12.5px] font-medium text-ink-700"
                        >Instructions / Advice</label
                    >
                    <textarea
                        v-model="form.instructions"
                        rows="3"
                        placeholder="Rest, diet, follow-up..."
                        class="w-full rounded-lg border border-ink-200 px-3 py-2 text-[13px] outline-none focus-visible:border-brand-400"
                    />
                </div>
                <div>
                    <label
                        class="mb-1 block text-[12.5px] font-medium text-ink-700"
                        >Follow-up Date</label
                    >
                    <input
                        v-model="form.follow_up_date"
                        type="date"
                        class="rounded-lg border border-ink-200 px-3 py-2 text-[13px]"
                    />
                    <p
                        v-if="form.errors.follow_up_date"
                        class="mt-1 text-[11.5px] text-red-500"
                    >
                        {{ form.errors.follow_up_date }}
                    </p>
                </div>
                <div class="flex gap-2">
                    <button
                        type="button"
                        :disabled="
                            !form.diagnosis.trim() || !form.medicines.trim()
                        "
                        class="flex items-center gap-1.5 rounded-full bg-gradient-to-r from-brand-500 to-sky-600 px-4 py-2.5 text-[13px] font-semibold text-white shadow-sm hover:from-brand-600 hover:to-sky-700 disabled:opacity-50"
                        @click="runSafetyCheck"
                    >
                        <ShieldCheck class="size-4" /> Check & Save
                    </button>
                    <Link
                        href="/doctor/appointments"
                        class="rounded-full border border-ink-200 px-4 py-2.5 text-[13px] font-medium text-ink-600 hover:bg-ink-50"
                        >Cancel</Link
                    >
                </div>
            </div>
        </div>
    </div>

    <!-- AI Safety Check Modal -->
    <div
        v-if="modalOpen"
        class="fixed inset-0 z-50 flex items-center justify-center bg-ink-900/50 p-4 backdrop-blur-sm"
        @click.self="modalOpen = false"
    >
        <div
            class="w-full max-w-lg overflow-hidden rounded-2xl bg-white shadow-2xl"
        >
            <div
                class="flex items-center gap-3 bg-gradient-to-r from-brand-50 to-sky-50 p-5"
            >
                <ShieldCheck class="size-6 text-brand-600" />
                <div>
                    <div class="text-[14.5px] font-bold text-ink-900">
                        AI Prescription Safety Check
                    </div>
                    <div class="text-[12px] text-ink-500">
                        Analyzing for drug interactions, contraindications &amp;
                        dosage…
                    </div>
                </div>
            </div>

            <div v-if="safetyState === 'loading'" class="py-10 text-center">
                <Loader2
                    class="mx-auto mb-3 size-8 animate-spin text-brand-500"
                />
                <div class="font-semibold text-ink-800">
                    Checking prescription safety…
                </div>
                <div class="mt-1 text-[12.5px] text-ink-500">
                    AI is analyzing medicines, dosage &amp; patient history
                </div>
            </div>

            <div v-else-if="safetyState === 'error'" class="p-5 text-[13px]">
                <div
                    class="flex items-start gap-2 rounded-xl bg-amber-50 p-3 text-amber-700"
                >
                    <AlertTriangle class="mt-0.5 size-4 shrink-0" />
                    <div>
                        <strong>Connection failed</strong><br />
                        {{ safetyError }}
                    </div>
                </div>
            </div>

            <div
                v-else-if="safety"
                class="max-h-[60vh] overflow-y-auto p-5 text-[13px]"
            >
                <div
                    class="mb-4 flex items-start gap-2.5 rounded-xl p-3.5"
                    :class="
                        safety.critical_issues.length
                            ? 'bg-red-50 ring-1 ring-red-200'
                            : safety.warnings.length
                              ? 'bg-amber-50 ring-1 ring-amber-200'
                              : 'bg-emerald-50 ring-1 ring-emerald-200'
                    "
                >
                    <component
                        :is="
                            safety.critical_issues.length
                                ? XCircle
                                : safety.warnings.length
                                  ? AlertTriangle
                                  : CheckCircle2
                        "
                        class="mt-0.5 size-5 shrink-0"
                        :class="
                            safety.critical_issues.length
                                ? 'text-red-600'
                                : safety.warnings.length
                                  ? 'text-amber-600'
                                  : 'text-emerald-600'
                        "
                    />
                    <div>
                        <div class="font-bold text-ink-900">
                            {{
                                safety.critical_issues.length
                                    ? 'Critical Issues Found'
                                    : safety.warnings.length
                                      ? 'Warnings Found'
                                      : 'Prescription Looks Safe'
                            }}
                        </div>
                        <div class="mt-0.5 text-[12.5px] text-ink-600">
                            {{ safety.verdict_summary }}
                        </div>
                    </div>
                </div>

                <div
                    v-if="
                        safety.critical_issues.length ||
                        safety.warnings.length ||
                        safety.info.length
                    "
                    class="mb-4"
                >
                    <div
                        class="mb-2 text-[11px] font-bold tracking-wide text-ink-400 uppercase"
                    >
                        ⚠️ Issues Found
                    </div>
                    <div class="space-y-1.5">
                        <div
                            v-for="(i, idx) in safety.critical_issues"
                            :key="'c' + idx"
                            class="flex gap-2 rounded-lg bg-red-50 p-2 text-red-700"
                        >
                            🚨 <span>{{ i }}</span>
                        </div>
                        <div
                            v-for="(w, idx) in safety.warnings"
                            :key="'w' + idx"
                            class="flex gap-2 rounded-lg bg-amber-50 p-2 text-amber-700"
                        >
                            ⚠️ <span>{{ w }}</span>
                        </div>
                        <div
                            v-for="(n, idx) in safety.info"
                            :key="'n' + idx"
                            class="flex gap-2 rounded-lg bg-ink-50 p-2 text-ink-600"
                        >
                            ℹ️ <span>{{ n }}</span>
                        </div>
                    </div>
                </div>

                <div v-if="safety.safe_checks.length" class="mb-4">
                    <div
                        class="mb-2 text-[11px] font-bold tracking-wide text-ink-400 uppercase"
                    >
                        ✅ Checks Passed
                    </div>
                    <div class="space-y-1">
                        <div
                            v-for="(s, idx) in safety.safe_checks"
                            :key="idx"
                            class="flex gap-2 text-emerald-700"
                        >
                            ✓ <span>{{ s }}</span>
                        </div>
                    </div>
                </div>

                <div v-if="safety.recommendations.length">
                    <div
                        class="mb-2 text-[11px] font-bold tracking-wide text-ink-400 uppercase"
                    >
                        💡 Recommendations
                    </div>
                    <div class="space-y-1">
                        <div
                            v-for="(r, idx) in safety.recommendations"
                            :key="idx"
                            class="flex gap-2 text-ink-600"
                        >
                            💡 <span>{{ r }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div
                v-if="showReferForm"
                class="space-y-3 border-t border-ink-100 p-4"
            >
                <p class="text-[12.5px] font-semibold text-ink-700">
                    Refer to another doctor instead
                </p>
                <div>
                    <label class="mb-1 block text-[11px] text-ink-500"
                        >Specialty (optional)</label
                    >
                    <select
                        v-model="referCategoryId"
                        class="w-full rounded-lg border border-ink-200 bg-transparent px-2 py-1.5 text-[13px] outline-none focus-visible:border-brand-400"
                    >
                        <option value="">No preference</option>
                        <option
                            v-for="c in props.categories"
                            :key="c.id"
                            :value="c.id"
                        >
                            {{ c.name }}
                        </option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-[11px] text-ink-500"
                        >Reason *</label
                    >
                    <textarea
                        v-model="referReason"
                        rows="3"
                        class="w-full rounded-lg border border-ink-200 bg-transparent px-3 py-2 text-[13px] outline-none focus-visible:border-brand-400"
                    />
                </div>
                <div class="flex justify-end gap-2">
                    <button
                        type="button"
                        class="rounded-lg border border-ink-200 px-4 py-2 text-[12.5px] font-semibold text-ink-600 hover:bg-ink-50"
                        @click="showReferForm = false"
                    >
                        ← Back
                    </button>
                    <button
                        type="button"
                        :disabled="referring || !referReason.trim()"
                        class="flex items-center gap-1.5 rounded-lg bg-gradient-to-r from-brand-500 to-sky-600 px-4 py-2 text-[12.5px] font-semibold text-white shadow-sm disabled:opacity-50"
                        @click="submitReferral"
                    >
                        <Loader2
                            v-if="referring"
                            class="size-3.5 animate-spin"
                        />
                        <UserRoundPlus v-else class="size-3.5" />
                        Confirm Referral
                    </button>
                </div>
            </div>

            <div
                v-else
                class="flex justify-end gap-2 border-t border-ink-100 p-4"
            >
                <button
                    type="button"
                    class="rounded-lg border border-ink-200 px-4 py-2 text-[12.5px] font-semibold text-ink-600 hover:bg-ink-50"
                    @click="modalOpen = false"
                >
                    ← Edit Prescription
                </button>
                <button
                    v-if="
                        safety &&
                        (safety.critical_issues.length ||
                            safety.warnings.length)
                    "
                    type="button"
                    class="flex items-center gap-1.5 rounded-lg border border-ink-200 px-4 py-2 text-[12.5px] font-semibold text-ink-600 hover:bg-ink-50"
                    @click="openReferForm"
                >
                    <UserRoundPlus class="size-3.5" /> Refer Instead
                </button>
                <button
                    type="button"
                    :disabled="safetyState === 'loading'"
                    class="flex items-center gap-1.5 rounded-lg px-4 py-2 text-[12.5px] font-semibold text-white shadow-sm disabled:opacity-50"
                    :class="
                        safety?.critical_issues.length
                            ? 'bg-gradient-to-r from-red-500 to-red-600'
                            : 'bg-gradient-to-r from-brand-500 to-sky-600'
                    "
                    @click="save"
                >
                    <Sparkles class="size-3.5" />
                    {{
                        safety?.critical_issues.length
                            ? 'Override & Save Anyway'
                            : 'Save Prescription →'
                    }}
                </button>
            </div>
        </div>
    </div>
</template>
