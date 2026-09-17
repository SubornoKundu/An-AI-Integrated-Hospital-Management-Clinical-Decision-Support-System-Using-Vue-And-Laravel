<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { CalendarOff, Plus, Trash2 } from '@lucide/vue';

interface HolidayRow {
    id: number;
    holiday_date: string;
    holiday_date_label: string;
    name: string;
}

const props = defineProps<{ holidays: HolidayRow[] }>();

const form = useForm({ holiday_date: '', name: '' });

function submit() {
    form.post('/admin/holidays', {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
}

function deleteHoliday(h: HolidayRow) {
    if (confirm(`Remove holiday "${h.name}"?`)) {
        router.delete(`/admin/holidays/${h.id}`, { preserveScroll: true });
    }
}
</script>

<template>
    <Head title="Hospital Holidays" />

    <div>
        <h1 class="flex items-center gap-2 text-xl font-bold text-ink-900">
            <CalendarOff class="size-5 text-brand-500" /> Holidays
        </h1>
        <p class="mt-1 text-[13px] text-ink-500">
            Dates the hospital is closed — doctors won't be bookable on these days.
        </p>
    </div>

    <form class="flex flex-wrap items-end gap-3 rounded-2xl border border-ink-100 bg-white p-4 shadow-sm" @submit.prevent="submit">
        <div>
            <label class="mb-1 block text-[12px] font-medium text-ink-700">Date</label>
            <input v-model="form.holiday_date" type="date" required class="rounded-lg border border-ink-200 px-3 py-2 text-[13px]" />
        </div>
        <div class="flex-1">
            <label class="mb-1 block text-[12px] font-medium text-ink-700">Holiday Name</label>
            <input v-model="form.name" type="text" placeholder="e.g. Eid-ul-Fitr" required class="w-full rounded-lg border border-ink-200 px-3 py-2 text-[13px]" />
        </div>
        <button type="submit" :disabled="form.processing" class="flex items-center gap-1.5 rounded-full bg-gradient-to-r from-brand-500 to-sky-600 px-4 py-2.5 text-[13px] font-semibold text-white shadow-sm disabled:opacity-50">
            <Plus class="size-4" /> Add
        </button>
        <p v-if="form.errors.holiday_date" class="w-full text-[11.5px] text-red-500">{{ form.errors.holiday_date }}</p>
    </form>

    <div class="overflow-hidden rounded-2xl border border-ink-100 bg-white shadow-sm">
        <div v-if="props.holidays.length" class="divide-y divide-ink-50">
            <div v-for="h in props.holidays" :key="h.id" class="flex items-center justify-between px-4 py-3">
                <div>
                    <div class="text-[13px] font-medium text-ink-800">{{ h.name }}</div>
                    <div class="text-[11.5px] text-ink-400">{{ h.holiday_date_label }}</div>
                </div>
                <button class="rounded-lg p-1.5 text-ink-400 hover:bg-red-50 hover:text-red-500" @click="deleteHoliday(h)"><Trash2 class="size-4" /></button>
            </div>
        </div>
        <p v-else class="py-10 text-center text-[13px] text-ink-400">No holidays configured.</p>
    </div>
</template>
