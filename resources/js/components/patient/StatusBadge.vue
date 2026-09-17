<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    status: string;
}>();

const config = computed(() => {
    switch (props.status) {
        case 'active':
            return {
                label: '● Active',
                class: 'bg-brand-50 text-brand-700 ring-1 ring-brand-200',
            };
        case 'completed':
            return {
                label: '✓ Completed',
                class: 'bg-sky-50 text-sky-700 ring-1 ring-sky-200',
            };
        case 'cancelled_by_patient':
            return {
                label: '✕ Cancelled',
                class: 'bg-red-50 text-red-600 ring-1 ring-red-200',
            };
        case 'cancelled_by_doctor':
            return {
                label: '✕ Cancelled by Doctor',
                class: 'bg-amber-50 text-amber-700 ring-1 ring-amber-200',
            };
        case 'cancelled_by_admin':
            return {
                label: '✕ Cancelled — Hospital Holiday',
                class: 'bg-amber-50 text-amber-700 ring-1 ring-amber-200',
            };
        default:
            return {
                label: props.status,
                class: 'bg-ink-100 text-ink-600 ring-1 ring-ink-200',
            };
    }
});
</script>

<template>
    <span
        class="inline-flex shrink-0 items-center rounded-full px-2.5 py-1 text-[11px] font-semibold whitespace-nowrap"
        :class="config.class"
    >
        {{ config.label }}
    </span>
</template>
