<script setup lang="ts">
defineProps<{
    step: 1 | 2 | 3;
}>();

const steps = [
    { n: 1, label: 'Specialty' },
    { n: 2, label: 'Doctor' },
    { n: 3, label: 'Date & Time' },
];
</script>

<template>
    <div class="mb-6 flex flex-wrap items-center gap-2">
        <template v-for="(s, i) in steps" :key="s.n">
            <span
                class="rounded-full px-3.5 py-1.5 text-[13px] font-semibold"
                :class="
                    s.n === step
                        ? 'bg-brand-600 text-white shadow-sm shadow-brand-600/25'
                        : s.n < step
                          ? 'bg-brand-50 text-brand-700'
                          : 'bg-ink-50 text-ink-400'
                "
            >
                {{ ['①', '②', '③'][i] }} {{ s.label }}
            </span>
            <span v-if="i < steps.length - 1" class="text-ink-300">──</span>
        </template>
    </div>
</template>
