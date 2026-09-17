<script setup lang="ts">
import { Deferred, Head, Link } from '@inertiajs/vue3';
import {
    AlertTriangle,
    Dumbbell,
    Loader2,
    Moon,
    Sparkles,
    Sprout,
    Stethoscope,
    Target,
    Utensils,
} from '@lucide/vue';
import { ref } from 'vue';
import Reveal from '@/components/public/Reveal.vue';
import { Button } from '@/components/ui/button';
import { aiPost } from '@/lib/aiClient';

interface ExerciseDay {
    day: string;
    activity: string;
    duration: string;
    intensity: string;
    notes: string;
}
interface PlanResult {
    intro_message: string;
    diet_plan: {
        breakfast: string[];
        lunch: string[];
        dinner: string[];
        snacks: string[];
        foods_to_avoid: string[];
        hydration: string;
    };
    exercise_plan: ExerciseDay[];
    sleep_plan: {
        recommended_hours: string;
        bedtime: string;
        wake_time: string;
        sleep_tips: string[];
    };
    stress_management: string[];
    weekly_goals: string[];
    health_warnings: string[];
    disclaimer: string;
}

defineProps<{
    patient: {
        name: string;
        gender: string;
        age: number | null;
        blood_group: string | null;
        total_visits: number;
    };
    conditions: string[];
    apiStatus?: { connected: boolean; message?: string };
}>();

const loading = ref(false);
const error = ref('');
const result = ref<PlanResult | null>(null);

const intensityColor: Record<string, string> = {
    low: 'bg-brand-50 text-brand-700',
    moderate: 'bg-amber-50 text-amber-700',
    high: 'bg-red-50 text-red-700',
};

async function generate() {
    loading.value = true;
    error.value = '';
    const { ok, data } = await aiPost<PlanResult & { error?: string }>(
        '/patient/ai/health-plan/generate',
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
    <Head title="Personalized Health Plan" />

    <div class="flex items-start justify-between">
        <div>
            <h1 class="flex items-center gap-2 text-xl font-bold text-ink-900">
                <Sprout class="size-6 text-brand-500" /> Personalized Health
                Plan
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
        AI creates a weekly diet, exercise & lifestyle plan tailored to your
        health conditions
    </p>

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
        <div class="space-y-5">
            <div
                class="rounded-2xl border border-ink-100 bg-white p-5 shadow-sm"
            >
                <h2 class="mb-3 text-[13.5px] font-semibold text-ink-700">
                    👤 Your Profile
                </h2>
                <dl class="space-y-2 text-[13px]">
                    <div class="flex justify-between">
                        <dt class="text-ink-500">Name</dt>
                        <dd class="font-medium text-ink-800">
                            {{ patient.name }}
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-ink-500">Age</dt>
                        <dd class="font-medium text-ink-800">
                            {{ patient.age ?? '—' }} years
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-ink-500">Gender</dt>
                        <dd class="font-medium text-ink-800">
                            {{ patient.gender ?? '—' }}
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-ink-500">Blood</dt>
                        <dd class="font-mono-brand font-bold text-brand-600">
                            {{ patient.blood_group ?? '—' }}
                        </dd>
                    </div>
                </dl>
            </div>

            <div
                v-if="conditions.length"
                class="rounded-2xl border border-ink-100 bg-white p-5 shadow-sm"
            >
                <h2 class="mb-3 text-[13.5px] font-semibold text-ink-700">
                    📋 Known Conditions
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
                    🌱 Generate Plan
                </h2>
                <p class="mb-3 text-[12.5px] text-ink-500">
                    AI will create a personalized weekly plan with diet,
                    exercise, sleep & stress management advice — all tailored to
                    your conditions.
                </p>
                <Button
                    class="w-full gap-2 bg-gradient-to-r from-brand-500 to-sky-600 py-5 hover:from-brand-600 hover:to-sky-700"
                    :disabled="loading"
                    @click="generate"
                >
                    <Loader2 v-if="loading" class="size-4 animate-spin" />
                    <Sparkles v-else class="size-4" />
                    {{ loading ? 'Creating your plan…' : 'Generate My Plan' }}
                </Button>
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
                    <Sprout class="mb-3 size-14 opacity-15" />
                    <div class="text-[14px] font-semibold text-ink-600">
                        Your Weekly Plan
                    </div>
                    <p class="mt-1 text-[13px]">
                        Click
                        <strong class="text-ink-600">Generate My Plan</strong>
                        to get started
                    </p>
                </div>
            </template>

            <Reveal v-if="result" class="space-y-5">
                <div
                    class="rounded-2xl border border-brand-100 bg-brand-50/50 p-5 text-[13.5px] leading-relaxed text-ink-700"
                >
                    {{ result.intro_message }}
                </div>

                <div
                    class="rounded-2xl border border-ink-100 bg-white p-5 shadow-sm"
                >
                    <h2
                        class="mb-4 flex items-center gap-2 text-[14px] font-bold text-ink-900"
                    >
                        <Utensils class="size-[18px] text-brand-500" /> Diet
                        Plan
                    </h2>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div
                            v-for="(items, meal) in {
                                Breakfast: result.diet_plan.breakfast,
                                Lunch: result.diet_plan.lunch,
                                Dinner: result.diet_plan.dinner,
                                Snacks: result.diet_plan.snacks,
                            }"
                            :key="meal"
                        >
                            <h3
                                class="mb-1.5 text-[12.5px] font-semibold text-ink-700 capitalize"
                            >
                                {{ meal }}
                            </h3>
                            <ul class="space-y-1 text-[12.5px] text-ink-600">
                                <li v-for="i in items" :key="i">• {{ i }}</li>
                            </ul>
                        </div>
                    </div>
                    <div class="mt-4 rounded-lg bg-red-50/60 p-3">
                        <h3
                            class="mb-1 text-[12.5px] font-semibold text-red-700"
                        >
                            Foods to Avoid
                        </h3>
                        <ul class="space-y-1 text-[12.5px] text-red-600">
                            <li
                                v-for="f in result.diet_plan.foods_to_avoid"
                                :key="f"
                            >
                                • {{ f }}
                            </li>
                        </ul>
                    </div>
                    <p class="mt-3 text-[12.5px] text-sky-700">
                        💧 {{ result.diet_plan.hydration }}
                    </p>
                </div>

                <div
                    class="rounded-2xl border border-ink-100 bg-white p-5 shadow-sm"
                >
                    <h2
                        class="mb-4 flex items-center gap-2 text-[14px] font-bold text-ink-900"
                    >
                        <Dumbbell class="size-[18px] text-sky-500" /> Weekly
                        Exercise Plan
                    </h2>
                    <div
                        class="grid grid-cols-1 gap-2.5 sm:grid-cols-2 lg:grid-cols-3"
                    >
                        <div
                            v-for="d in result.exercise_plan"
                            :key="d.day"
                            class="rounded-xl border border-ink-100 bg-ink-50/30 p-3"
                        >
                            <div class="flex items-center justify-between">
                                <span
                                    class="text-[12.5px] font-bold text-ink-800"
                                    >{{ d.day }}</span
                                >
                                <span
                                    class="rounded-full px-2 py-0.5 text-[10.5px] font-semibold"
                                    :class="
                                        intensityColor[d.intensity] ??
                                        'bg-ink-100 text-ink-600'
                                    "
                                    >{{ d.intensity }}</span
                                >
                            </div>
                            <div
                                class="mt-1.5 text-[12.5px] font-semibold text-ink-900"
                            >
                                {{ d.activity }}
                            </div>
                            <div class="text-[11.5px] text-ink-400">
                                {{ d.duration }}
                            </div>
                            <p class="mt-1 text-[11.5px] text-ink-500">
                                {{ d.notes }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <div
                        class="rounded-2xl border border-ink-100 bg-white p-5 shadow-sm"
                    >
                        <h2
                            class="mb-3 flex items-center gap-2 text-[14px] font-bold text-ink-900"
                        >
                            <Moon class="size-[18px] text-purple-500" /> Sleep
                            Plan
                        </h2>
                        <p class="text-[13px] text-ink-700">
                            <strong>{{
                                result.sleep_plan.recommended_hours
                            }}</strong>
                        </p>
                        <p class="mt-1 text-[12.5px] text-ink-500">
                            Bedtime {{ result.sleep_plan.bedtime }} · Wake
                            {{ result.sleep_plan.wake_time }}
                        </p>
                        <ul class="mt-2 space-y-1 text-[12.5px] text-ink-600">
                            <li
                                v-for="t in result.sleep_plan.sleep_tips"
                                :key="t"
                            >
                                • {{ t }}
                            </li>
                        </ul>
                    </div>
                    <div
                        class="rounded-2xl border border-ink-100 bg-white p-5 shadow-sm"
                    >
                        <h2
                            class="mb-3 flex items-center gap-2 text-[14px] font-bold text-ink-900"
                        >
                            <Target class="size-[18px] text-amber-500" /> Weekly
                            Goals
                        </h2>
                        <ul class="space-y-1.5 text-[12.5px] text-ink-600">
                            <li v-for="g in result.weekly_goals" :key="g">
                                ☐ {{ g }}
                            </li>
                        </ul>
                    </div>
                </div>

                <div
                    class="rounded-2xl border border-ink-100 bg-white p-5 shadow-sm"
                >
                    <h2 class="mb-2 text-[14px] font-bold text-ink-900">
                        🧘 Stress Management
                    </h2>
                    <ul class="space-y-1 text-[12.5px] text-ink-600">
                        <li v-for="s in result.stress_management" :key="s">
                            • {{ s }}
                        </li>
                    </ul>
                </div>

                <div class="rounded-2xl border border-red-100 bg-red-50/60 p-5">
                    <h2
                        class="mb-1.5 flex items-center gap-1.5 text-[13.5px] font-semibold text-red-700"
                    >
                        <AlertTriangle class="size-4" /> Health Warnings
                    </h2>
                    <ul class="space-y-1 text-[12.5px] text-red-600">
                        <li v-for="w in result.health_warnings" :key="w">
                            • {{ w }}
                        </li>
                    </ul>
                </div>

                <p class="text-center text-[11.5px] text-ink-400 italic">
                    {{ result.disclaimer }}
                </p>
            </Reveal>
        </div>
    </div>
</template>
