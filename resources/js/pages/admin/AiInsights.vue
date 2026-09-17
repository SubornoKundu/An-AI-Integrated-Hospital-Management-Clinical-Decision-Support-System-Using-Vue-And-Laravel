<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import {
    AlertTriangle,
    Lightbulb,
    ListChecks,
    RefreshCw,
    Sparkles,
    TrendingUp,
    Wrench,
} from '@lucide/vue';
import { ref } from 'vue';
import { aiPost } from '@/lib/aiClient';

interface InsightItem {
    title: string;
    detail: string;
    action: string;
}
interface InsightsData {
    executive_summary: string;
    critical_alerts: InsightItem[];
    revenue_insights: InsightItem[];
    operational_insights: InsightItem[];
    growth_opportunities: InsightItem[];
    quick_wins: string[];
}

const loading = ref(false);
const error = ref<string | null>(null);
const insights = ref<InsightsData | null>(null);

async function generate() {
    loading.value = true;
    error.value = null;

    const { ok, data } = await aiPost<InsightsData & { error?: string }>(
        '/admin/ai-insights/generate',
    );

    if (ok && !data.error) {
        insights.value = data;
    } else {
        error.value = data.error ?? 'Something went wrong. Please try again.';
    }

    loading.value = false;
}

const sections = [
    { key: 'critical_alerts' as const, title: 'Critical Alerts', icon: AlertTriangle, color: 'text-red-500 bg-red-50' },
    { key: 'revenue_insights' as const, title: 'Revenue Insights', icon: TrendingUp, color: 'text-emerald-600 bg-emerald-50' },
    { key: 'operational_insights' as const, title: 'Operational Insights', icon: Wrench, color: 'text-brand-600 bg-brand-50' },
    { key: 'growth_opportunities' as const, title: 'Growth Opportunities', icon: Lightbulb, color: 'text-amber-600 bg-amber-50' },
];
</script>

<template>
    <Head title="AI Hospital Insights" />

    <div class="flex flex-col items-start justify-between gap-3 sm:flex-row sm:items-center">
        <div>
            <h1 class="flex items-center gap-2 text-xl font-bold text-ink-900">
                <Sparkles class="size-5 text-brand-500" /> AI Hospital Insights
            </h1>
            <p class="mt-1 text-[13px] text-ink-500">
                AI-generated analysis of your hospital's operations, revenue, and growth opportunities.
            </p>
        </div>
        <button
            :disabled="loading"
            class="flex items-center gap-1.5 rounded-full bg-gradient-to-r from-brand-500 to-sky-600 px-4 py-2.5 text-[13px] font-semibold text-white shadow-sm disabled:opacity-50"
            @click="generate"
        >
            <RefreshCw class="size-4" :class="loading ? 'animate-spin' : ''" />
            {{ loading ? 'Analyzing…' : insights ? 'Regenerate' : 'Generate Insights' }}
        </button>
    </div>

    <div v-if="error" class="rounded-2xl border border-red-200 bg-red-50 p-4 text-[13px] text-red-600">
        {{ error }}
    </div>

    <div v-else-if="!insights && !loading" class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-ink-200 py-16 text-center">
        <Sparkles class="mb-3 size-8 text-ink-200" />
        <p class="text-[13.5px] font-medium text-ink-600">No insights generated yet</p>
        <p class="mt-1 text-[12.5px] text-ink-400">Click "Generate Insights" to analyze your hospital's data.</p>
    </div>

    <div v-else-if="loading" class="flex flex-col items-center justify-center py-16">
        <RefreshCw class="mb-3 size-8 animate-spin text-brand-400" />
        <p class="text-[13px] text-ink-500">Analyzing hospital data — this can take a few seconds…</p>
    </div>

    <template v-else-if="insights">
        <div class="rounded-2xl border border-brand-100 bg-gradient-to-br from-brand-50 to-sky-50 p-4">
            <h2 class="mb-1.5 text-[12.5px] font-bold text-brand-700">Executive Summary</h2>
            <p class="text-[13.5px] leading-relaxed text-ink-700">{{ insights.executive_summary }}</p>
        </div>

        <div v-for="s in sections" :key="s.key">
            <template v-if="insights[s.key]?.length">
                <h2 class="mb-2 flex items-center gap-1.5 text-[13.5px] font-bold text-ink-900">
                    <span class="flex size-6 items-center justify-center rounded-lg" :class="s.color">
                        <component :is="s.icon" class="size-3.5" />
                    </span>
                    {{ s.title }}
                </h2>
                <div class="mb-4 space-y-2">
                    <div v-for="(item, i) in insights[s.key]" :key="i" class="rounded-xl border border-ink-100 bg-white p-3.5 shadow-sm">
                        <div class="text-[13px] font-semibold text-ink-800">{{ item.title }}</div>
                        <p class="mt-1 text-[12.5px] text-ink-600">{{ item.detail }}</p>
                        <p class="mt-1.5 text-[12px] font-medium text-brand-600">→ {{ item.action }}</p>
                    </div>
                </div>
            </template>
        </div>

        <div v-if="insights.quick_wins?.length" class="rounded-2xl border border-ink-100 bg-white p-4 shadow-sm">
            <h2 class="mb-2.5 flex items-center gap-1.5 text-[13.5px] font-bold text-ink-900">
                <ListChecks class="size-4 text-emerald-500" /> Quick Wins
            </h2>
            <ul class="space-y-1.5">
                <li v-for="(w, i) in insights.quick_wins" :key="i" class="flex items-start gap-2 text-[12.5px] text-ink-600">
                    <span class="mt-1.5 size-1.5 shrink-0 rounded-full bg-emerald-400" />
                    {{ w }}
                </li>
            </ul>
        </div>
    </template>
</template>
