<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { TrendingDown, TrendingUp, Wallet } from '@lucide/vue';
import { computed, ref } from 'vue';

interface CompletedVisit {
    id: number;
    appointment_date: string;
    patient_name: string;
}

const props = defineProps<{
    completed: CompletedVisit[];
    total: number;
    month: string;
    chartLabels: string[];
    chartValues: number[];
    prevTotal: number;
}>();

const selectedMonth = ref(props.month);

function changeMonth() {
    router.get(
        '/doctor/earnings',
        { month: selectedMonth.value },
        { preserveState: true },
    );
}

const maxValue = computed(() => Math.max(...props.chartValues, 1));
const trend = computed(() => {
    if (props.prevTotal === 0) {
        return null;
    }

    return Math.round(((props.total - props.prevTotal) / props.prevTotal) * 100);
});
</script>

<template>
    <Head title="Earnings" />

    <div
        class="flex flex-col items-start justify-between gap-3 sm:flex-row sm:items-center"
    >
        <h1 class="flex items-center gap-2 text-xl font-bold text-ink-900">
            <Wallet class="size-5 text-brand-500" /> Earnings
        </h1>
        <input
            v-model="selectedMonth"
            type="month"
            class="rounded-lg border border-ink-200 px-3 py-2 text-[13px]"
            @change="changeMonth"
        />
    </div>

    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
        <div class="rounded-2xl border border-ink-100 bg-white p-4 shadow-sm">
            <div class="text-2xl font-bold text-brand-600">
                ৳{{ props.total.toLocaleString() }}
            </div>
            <div class="mt-1 text-[12.5px] text-ink-500">This Month</div>
        </div>
        <div class="rounded-2xl border border-ink-100 bg-white p-4 shadow-sm">
            <div class="text-2xl font-bold text-ink-900">
                {{ props.completed.length }}
            </div>
            <div class="mt-1 text-[12.5px] text-ink-500">Visits Completed</div>
        </div>
        <div
            class="col-span-2 rounded-2xl border border-ink-100 bg-white p-4 shadow-sm sm:col-span-1"
        >
            <div
                v-if="trend !== null"
                class="flex items-center gap-1 text-2xl font-bold"
                :class="trend >= 0 ? 'text-emerald-600' : 'text-red-500'"
            >
                <component :is="trend >= 0 ? TrendingUp : TrendingDown" class="size-5" />
                {{ Math.abs(trend) }}%
            </div>
            <div v-else class="text-2xl font-bold text-ink-300">—</div>
            <div class="mt-1 text-[12.5px] text-ink-500">vs Last Month</div>
        </div>
    </div>

    <div class="rounded-2xl border border-ink-100 bg-white p-4 shadow-sm">
        <h2 class="mb-4 text-[13.5px] font-bold text-ink-900">Daily Earnings</h2>
        <div class="flex h-40 items-end gap-1">
            <div
                v-for="(v, i) in props.chartValues"
                :key="i"
                class="group relative flex-1 rounded-t bg-brand-200 transition-colors hover:bg-brand-400"
                :style="{ height: `${(v / maxValue) * 100}%`, minHeight: v > 0 ? '3px' : '0' }"
            >
                <span
                    v-if="v > 0"
                    class="pointer-events-none absolute -top-6 left-1/2 -translate-x-1/2 rounded bg-ink-900 px-1.5 py-0.5 text-[10px] whitespace-nowrap text-white opacity-0 group-hover:opacity-100"
                    >৳{{ v.toLocaleString() }}</span
                >
            </div>
        </div>
        <div class="mt-1.5 flex gap-1 text-[9.5px] text-ink-400">
            <span
                v-for="(l, i) in props.chartLabels"
                :key="i"
                class="flex-1 text-center"
                >{{ i % 5 === 0 ? l : '' }}</span
            >
        </div>
    </div>

    <div class="rounded-2xl border border-ink-100 bg-white p-4 shadow-sm">
        <h2 class="mb-3 text-[13.5px] font-bold text-ink-900">
            Completed Visits
        </h2>
        <div v-if="props.completed.length" class="divide-y divide-ink-50">
            <div
                v-for="c in props.completed"
                :key="c.id"
                class="flex items-center justify-between py-2 text-[13px]"
            >
                <span class="text-ink-700">{{ c.patient_name }}</span>
                <span class="text-ink-400">{{ c.appointment_date }}</span>
            </div>
        </div>
        <p v-else class="py-6 text-center text-[13px] text-ink-400">
            No completed visits this month.
        </p>
    </div>
</template>
