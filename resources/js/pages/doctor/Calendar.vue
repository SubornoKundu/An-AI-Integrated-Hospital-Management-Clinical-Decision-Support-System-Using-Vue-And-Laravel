<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { CalendarDays, CalendarX, Trash2 } from '@lucide/vue';
import { computed } from 'vue';

interface UpcomingEvent {
    title: string;
    start: string;
    end: string;
    color: string;
}

interface BlockedDate {
    id: number;
    blocked_date: string;
    reason: string | null;
}

const props = defineProps<{
    appointments: UpcomingEvent[];
    blockedDates: BlockedDate[];
}>();

const groupedByDate = computed(() => {
    const groups: Record<string, UpcomingEvent[]> = {};

    for (const ev of props.appointments) {
        const date = ev.start.split('T')[0];

        (groups[date] ??= []).push(ev);
    }

    return groups;
});

const form = useForm({
    blocked_date: '',
    reason: '',
});

function blockDate() {
    form.post('/doctor/blocked-dates', {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
}

function unblockDate(id: number) {
    if (!confirm('Unblock this date? Patients will be able to book again.')) {
        return;
    }

    router.delete(`/doctor/blocked-dates/${id}`, { preserveScroll: true });
}
</script>

<template>
    <Head title="Calendar" />

    <h1 class="flex items-center gap-2 text-xl font-bold text-ink-900">
        <CalendarDays class="size-5 text-brand-500" /> Appointment Calendar
    </h1>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-[1.5fr_1fr]">
        <div class="rounded-2xl border border-ink-100 bg-white p-4 shadow-sm">
            <h2 class="mb-3 text-[13.5px] font-bold text-ink-900">
                Upcoming Schedule
            </h2>
            <div v-if="Object.keys(groupedByDate).length" class="space-y-4">
                <div v-for="(events, date) in groupedByDate" :key="date">
                    <div class="mb-1.5 text-[12px] font-semibold text-brand-600">
                        {{
                            new Date(date as string).toLocaleDateString(
                                'en-GB',
                                {
                                    weekday: 'long',
                                    day: '2-digit',
                                    month: 'short',
                                },
                            )
                        }}
                    </div>
                    <div class="space-y-1.5">
                        <div
                            v-for="(ev, i) in events"
                            :key="i"
                            class="rounded-lg border-l-2 border-emerald-400 bg-ink-50/60 px-3 py-1.5 text-[12.5px] text-ink-700"
                        >
                            {{ ev.title }}
                        </div>
                    </div>
                </div>
            </div>
            <p v-else class="py-10 text-center text-[13px] text-ink-400">
                No upcoming appointments scheduled.
            </p>
        </div>

        <div class="space-y-4">
            <div class="rounded-2xl border border-ink-100 bg-white p-4 shadow-sm">
                <h2
                    class="mb-3 flex items-center gap-1.5 text-[13.5px] font-bold text-ink-900"
                >
                    <CalendarX class="size-4 text-red-500" /> Block a Date
                </h2>
                <div class="space-y-2.5">
                    <input
                        v-model="form.blocked_date"
                        type="date"
                        class="w-full rounded-lg border border-ink-200 px-3 py-2 text-[13px]"
                    />
                    <p v-if="form.errors.blocked_date" class="text-[11.5px] text-red-500">
                        {{ form.errors.blocked_date }}
                    </p>
                    <input
                        v-model="form.reason"
                        type="text"
                        placeholder="Reason (optional)"
                        class="w-full rounded-lg border border-ink-200 px-3 py-2 text-[13px]"
                    />
                    <p v-if="form.errors.reason" class="text-[11.5px] text-red-500">
                        {{ form.errors.reason }}
                    </p>
                    <button
                        type="button"
                        :disabled="!form.blocked_date || form.processing"
                        class="w-full rounded-full bg-gradient-to-r from-red-500 to-red-600 px-4 py-2 text-[12.5px] font-semibold text-white disabled:opacity-50"
                        @click="blockDate"
                    >
                        Block This Date
                    </button>
                </div>
            </div>

            <div class="rounded-2xl border border-ink-100 bg-white p-4 shadow-sm">
                <h2 class="mb-2 text-[13.5px] font-bold text-ink-900">
                    Blocked Dates
                </h2>
                <div v-if="props.blockedDates.length" class="space-y-1.5">
                    <div
                        v-for="b in props.blockedDates"
                        :key="b.id"
                        class="flex items-center justify-between rounded-lg bg-red-50 px-3 py-2 text-[12.5px]"
                    >
                        <div>
                            <div class="font-medium text-red-700">
                                {{ b.blocked_date }}
                            </div>
                            <div v-if="b.reason" class="text-[11.5px] text-red-500">
                                {{ b.reason }}
                            </div>
                        </div>
                        <button
                            type="button"
                            class="rounded-full p-1.5 text-red-400 hover:bg-red-100 hover:text-red-600"
                            @click="unblockDate(b.id)"
                        >
                            <Trash2 class="size-3.5" />
                        </button>
                    </div>
                </div>
                <p v-else class="py-4 text-center text-[12.5px] text-ink-400">
                    No blocked dates.
                </p>
            </div>
        </div>
    </div>
</template>
