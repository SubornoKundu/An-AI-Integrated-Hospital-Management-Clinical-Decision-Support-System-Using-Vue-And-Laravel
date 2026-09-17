<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, Ban, Coffee, Save } from '@lucide/vue';
import { reactive, ref } from 'vue';

interface ScheduleEntry {
    day_of_week: number;
    start_time: string;
    end_time: string;
}
interface BreakEntry {
    id: number;
    day_of_week: number;
    start_time: string;
    end_time: string;
}
interface BlockedDate {
    id: number;
    blocked_date: string;
    reason: string | null;
}

const props = defineProps<{
    doctor: {
        id: number;
        name: string;
        schedules: ScheduleEntry[];
        breaks: BreakEntry[];
        blockedDates: BlockedDate[];
    };
}>();

const days = [
    { num: 0, name: 'Sunday' },
    { num: 1, name: 'Monday' },
    { num: 2, name: 'Tuesday' },
    { num: 3, name: 'Wednesday' },
    { num: 4, name: 'Thursday' },
    { num: 5, name: 'Friday' },
    { num: 6, name: 'Saturday' },
];

const schedule = reactive(
    Object.fromEntries(
        days.map((d) => {
            const existing = props.doctor.schedules.find((s) => s.day_of_week === d.num);

            return [
                d.num,
                {
                    enabled: !!existing,
                    start: existing?.start_time ?? '09:00',
                    end: existing?.end_time ?? '17:00',
                },
            ];
        }),
    ),
);

const saving = ref(false);

function save() {
    saving.value = true;
    router.post(
        `/admin/doctors/${props.doctor.id}/schedule`,
        { schedules: schedule },
        { onFinish: () => (saving.value = false) },
    );
}

function breaksFor(day: number) {
    return props.doctor.breaks.filter((b) => b.day_of_week === day);
}
</script>

<template>
    <Head :title="`Schedule — Dr. ${props.doctor.name}`" />

    <div class="flex items-center gap-3">
        <Link
            href="/admin/doctors"
            class="rounded-full border border-ink-200 p-2 text-ink-500 hover:bg-ink-50"
            ><ArrowLeft class="size-4"
        /></Link>
        <div>
            <h1 class="text-xl font-bold text-ink-900">Weekly Schedule</h1>
            <p class="mt-0.5 text-[13px] text-ink-500">Dr. {{ props.doctor.name }}</p>
        </div>
    </div>

    <div class="rounded-2xl border border-ink-100 bg-white p-5 shadow-sm">
        <div class="space-y-2">
            <div
                v-for="d in days"
                :key="d.num"
                class="flex flex-wrap items-center gap-3 rounded-xl border border-ink-100 p-3"
                :class="schedule[d.num].enabled ? 'bg-brand-50/40' : ''"
            >
                <label class="flex w-28 shrink-0 items-center gap-2 text-[13px] font-medium text-ink-800">
                    <input v-model="schedule[d.num].enabled" type="checkbox" class="size-4 rounded border-ink-300" />
                    {{ d.name }}
                </label>

                <template v-if="schedule[d.num].enabled">
                    <input v-model="schedule[d.num].start" type="time" class="rounded-lg border border-ink-200 px-2.5 py-1.5 text-[12.5px]" />
                    <span class="text-ink-400">to</span>
                    <input v-model="schedule[d.num].end" type="time" class="rounded-lg border border-ink-200 px-2.5 py-1.5 text-[12.5px]" />

                    <span
                        v-for="b in breaksFor(d.num)"
                        :key="b.id"
                        class="flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-1 text-[11px] text-amber-700"
                    >
                        <Coffee class="size-3" /> Break {{ b.start_time }}–{{ b.end_time }}
                    </span>
                </template>
                <span v-else class="text-[12.5px] text-ink-400">Not available</span>
            </div>
        </div>

        <div class="mt-5 flex items-center gap-2 border-t border-ink-100 pt-4">
            <button
                :disabled="saving"
                class="flex items-center gap-1.5 rounded-full bg-gradient-to-r from-brand-500 to-sky-600 px-5 py-2.5 text-[13px] font-semibold text-white shadow-sm disabled:opacity-50"
                @click="save"
            >
                <Save class="size-4" /> Save Schedule
            </button>
        </div>
    </div>

    <div v-if="props.doctor.blockedDates.length" class="rounded-2xl border border-ink-100 bg-white p-5 shadow-sm">
        <h2 class="mb-3 flex items-center gap-1.5 text-[13.5px] font-bold text-ink-900">
            <Ban class="size-4 text-red-400" /> Blocked Dates
        </h2>
        <div class="flex flex-wrap gap-2">
            <span
                v-for="bd in props.doctor.blockedDates"
                :key="bd.id"
                class="rounded-full bg-red-50 px-3 py-1.5 text-[12px] text-red-600"
            >
                {{ bd.blocked_date }}<span v-if="bd.reason"> — {{ bd.reason }}</span>
            </span>
        </div>
    </div>
</template>
