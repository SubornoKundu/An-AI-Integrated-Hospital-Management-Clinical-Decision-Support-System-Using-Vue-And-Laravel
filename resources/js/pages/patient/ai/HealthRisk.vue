<script setup lang="ts">
import { Deferred, Head, Link } from '@inertiajs/vue3';
import { Loader2, Shield, Sparkles, Stethoscope } from '@lucide/vue';
import { computed, ref } from 'vue';
import Reveal from '@/components/public/Reveal.vue';
import { Button } from '@/components/ui/button';
import { aiPost } from '@/lib/aiClient';

interface RiskCategory {
    category: string;
    score: number;
    level: string;
    reason: string;
}
interface RiskResult {
    overall_risk_score: number;
    risk_level: 'low' | 'moderate' | 'high' | 'critical';
    risk_color: string;
    risk_summary: string;
    risk_categories: RiskCategory[];
    top_risk_factors: string[];
    protective_factors: string[];
    immediate_actions: string[];
    recommended_tests: string[];
    lifestyle_changes: string[];
    next_checkup_months: number;
    disclaimer: string;
}

defineProps<{
    patient: {
        name: string;
        gender: string;
        age: number | null;
        blood_group: string | null;
        total_visits: number;
        last_visit: string | null;
    };
    conditions: string[];
    hasProfile: boolean;
    apiStatus?: { connected: boolean; message?: string };
}>();

const loading = ref(false);
const error = ref('');
const result = ref<RiskResult | null>(null);

const levelColor: Record<string, string> = {
    low: '#00b389',
    moderate: '#d97706',
    high: '#ea580c',
    critical: '#dc2626',
};

const gaugeColor = computed(() =>
    result.value
        ? (levelColor[result.value.risk_level] ?? '#00b389')
        : '#e5e7eb',
);
const gaugeOffset = computed(() => {
    const score = result.value?.overall_risk_score ?? 0;
    const circumference = 2 * Math.PI * 54;

    return circumference - (score / 100) * circumference;
});

async function generate() {
    loading.value = true;
    error.value = '';
    const { ok, data } = await aiPost<RiskResult & { error?: string }>(
        '/patient/ai/health-risk/generate',
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
    <Head title="Health Risk Assessment" />

    <div class="flex items-start justify-between">
        <div>
            <h1 class="flex items-center gap-2 text-xl font-bold text-ink-900">
                <Shield class="size-6 text-sky-500" /> Automated Health Risk
                Assessment
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
        AI analyses your profile & medical history to calculate your personal
        health risk score
    </p>

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
        <div class="space-y-5">
            <div
                class="rounded-2xl border border-ink-100 bg-white p-5 shadow-sm"
            >
                <h2 class="mb-3 text-[13.5px] font-semibold text-ink-700">
                    👤 Your Health Profile
                </h2>
                <dl class="space-y-2 text-[13px]">
                    <div class="flex justify-between">
                        <dt class="text-ink-500">Name</dt>
                        <dd class="font-medium text-ink-800">
                            {{ patient.name }}
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-ink-500">Gender</dt>
                        <dd class="font-medium text-ink-800">
                            {{ patient.gender ?? '—' }}
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-ink-500">Age</dt>
                        <dd class="font-medium text-ink-800">
                            {{ patient.age ?? '—' }} years
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-ink-500">Blood Group</dt>
                        <dd class="font-mono-brand font-bold text-brand-600">
                            {{ patient.blood_group ?? '—' }}
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-ink-500">Total Visits</dt>
                        <dd class="font-medium text-ink-800">
                            {{ patient.total_visits }} completed visits
                        </dd>
                    </div>
                    <div v-if="patient.last_visit" class="flex justify-between">
                        <dt class="text-ink-500">Last Visit</dt>
                        <dd class="font-medium text-ink-800">
                            {{ patient.last_visit }}
                        </dd>
                    </div>
                </dl>
            </div>

            <div
                v-if="conditions.length"
                class="rounded-2xl border border-ink-100 bg-white p-5 shadow-sm"
            >
                <h2 class="mb-3 text-[13.5px] font-semibold text-ink-700">
                    📋 Conditions in History
                </h2>
                <ul class="space-y-2 text-[13px] text-ink-600">
                    <li
                        v-for="c in conditions"
                        :key="c"
                        class="flex items-center gap-2 border-b border-ink-50 py-1.5 last:border-0"
                    >
                        <Stethoscope class="size-3.5 shrink-0 text-brand-400" />
                        {{ c }}
                    </li>
                </ul>
            </div>

            <div
                class="rounded-2xl border border-ink-100 bg-white p-5 shadow-sm"
            >
                <h2 class="mb-2 text-[13.5px] font-semibold text-ink-700">
                    🧠 Generate Assessment
                </h2>
                <p class="mb-3 text-[12.5px] text-ink-500">
                    AI will analyze your full visit history to calculate a
                    personalized risk score across 5 health categories.
                </p>
                <Button
                    class="w-full gap-2 bg-gradient-to-r from-sky-500 to-brand-600 py-5 hover:from-sky-600 hover:to-brand-700"
                    :disabled="
                        loading || (!patient.total_visits && !hasProfile)
                    "
                    @click="generate"
                >
                    <Loader2 v-if="loading" class="size-4 animate-spin" />
                    <Sparkles v-else class="size-4" />
                    {{ loading ? 'Analyzing…' : 'Generate Risk Assessment' }}
                </Button>
                <p
                    v-if="!patient.total_visits && !hasProfile"
                    class="mt-2 text-[12px] text-ink-400"
                >
                    Complete at least one appointment, or add your medical
                    history in
                    <Link
                        href="/patient/profile"
                        class="text-brand-600 underline"
                        >Profile</Link
                    >, to generate an assessment.
                </p>
                <p
                    v-else-if="!hasProfile"
                    class="mt-2 text-[12px] text-ink-400"
                >
                    💡 Add allergies, chronic conditions, or family history in
                    your
                    <Link
                        href="/patient/profile"
                        class="text-brand-600 underline"
                        >Profile</Link
                    >
                    for a more complete assessment.
                </p>
                <p v-if="error" class="mt-2 text-[13px] text-destructive">
                    ⚠ {{ error }}
                </p>
            </div>
        </div>

        <div class="rounded-2xl border border-ink-100 bg-white p-6 shadow-sm">
            <template v-if="!result">
                <div
                    class="flex h-full min-h-[280px] flex-col items-center justify-center text-center text-ink-400"
                >
                    <Shield class="mb-3 size-14 opacity-15" />
                    <div class="text-[14px] font-semibold text-ink-600">
                        Health Risk Score
                    </div>
                    <p class="mt-1 text-[13px]">
                        Click
                        <strong class="text-ink-600"
                            >Generate Risk Assessment</strong
                        >
                        to get your personalized health report
                    </p>
                </div>
            </template>
            <Reveal v-else class="space-y-5">
                <div class="flex flex-col items-center">
                    <svg viewBox="0 0 120 120" class="size-40 -rotate-90">
                        <circle
                            cx="60"
                            cy="60"
                            r="54"
                            fill="none"
                            stroke="#eef3fb"
                            stroke-width="10"
                        />
                        <circle
                            cx="60"
                            cy="60"
                            r="54"
                            fill="none"
                            :stroke="gaugeColor"
                            stroke-width="10"
                            stroke-linecap="round"
                            :stroke-dasharray="2 * Math.PI * 54"
                            :stroke-dashoffset="gaugeOffset"
                            class="transition-all duration-1000 ease-out"
                        />
                    </svg>
                    <div class="-mt-28 flex flex-col items-center">
                        <span
                            class="font-mono-brand text-4xl font-extrabold text-ink-900"
                            >{{ result.overall_risk_score }}</span
                        >
                        <span
                            class="mt-1 rounded-full px-2.5 py-0.5 text-[11px] font-bold tracking-wide uppercase"
                            :style="{
                                backgroundColor: gaugeColor + '20',
                                color: gaugeColor,
                            }"
                            >{{ result.risk_level }} risk</span
                        >
                    </div>
                    <div class="mt-24" />
                </div>

                <p
                    class="rounded-xl bg-ink-50/60 p-4 text-center text-[13.5px] leading-relaxed text-ink-700"
                >
                    {{ result.risk_summary }}
                </p>

                <div class="space-y-2.5">
                    <div
                        v-for="cat in result.risk_categories"
                        :key="cat.category"
                    >
                        <div
                            class="mb-1 flex items-center justify-between text-[12.5px]"
                        >
                            <span class="font-medium text-ink-700">{{
                                cat.category
                            }}</span>
                            <span
                                class="font-mono-brand font-semibold text-ink-500"
                                >{{ cat.score }}</span
                            >
                        </div>
                        <div
                            class="h-1.5 overflow-hidden rounded-full bg-ink-100"
                        >
                            <div
                                class="h-full rounded-full transition-all duration-700"
                                :style="{
                                    width: cat.score + '%',
                                    backgroundColor:
                                        levelColor[cat.level] ?? '#00b389',
                                }"
                            />
                        </div>
                        <p class="mt-1 text-[11.5px] text-ink-400">
                            {{ cat.reason }}
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <h3
                            class="mb-1.5 text-[12.5px] font-semibold text-red-600"
                        >
                            Top Risk Factors
                        </h3>
                        <ul class="space-y-1 text-[12.5px] text-ink-600">
                            <li v-for="f in result.top_risk_factors" :key="f">
                                • {{ f }}
                            </li>
                        </ul>
                    </div>
                    <div>
                        <h3
                            class="mb-1.5 text-[12.5px] font-semibold text-brand-600"
                        >
                            Protective Factors
                        </h3>
                        <ul class="space-y-1 text-[12.5px] text-ink-600">
                            <li v-for="f in result.protective_factors" :key="f">
                                • {{ f }}
                            </li>
                        </ul>
                    </div>
                </div>

                <div>
                    <h3 class="mb-1.5 text-[12.5px] font-semibold text-ink-700">
                        Immediate Actions
                    </h3>
                    <ul class="space-y-1 text-[12.5px] text-ink-600">
                        <li v-for="a in result.immediate_actions" :key="a">
                            ✓ {{ a }}
                        </li>
                    </ul>
                </div>

                <div>
                    <h3 class="mb-1.5 text-[12.5px] font-semibold text-ink-700">
                        Recommended Tests
                    </h3>
                    <div class="flex flex-wrap gap-1.5">
                        <span
                            v-for="t in result.recommended_tests"
                            :key="t"
                            class="rounded-full bg-sky-50 px-2.5 py-1 text-[11.5px] font-medium text-sky-700"
                            >{{ t }}</span
                        >
                    </div>
                </div>

                <p class="text-[12.5px] text-ink-500">
                    📅 Recommended next checkup: in
                    <strong>{{ result.next_checkup_months }} months</strong>
                </p>
                <p class="text-center text-[11.5px] text-ink-400 italic">
                    {{ result.disclaimer }}
                </p>
            </Reveal>
        </div>
    </div>
</template>
