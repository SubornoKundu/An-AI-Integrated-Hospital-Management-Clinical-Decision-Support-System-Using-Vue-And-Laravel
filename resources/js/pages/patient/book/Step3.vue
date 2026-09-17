<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import BookingProgress from '@/components/patient/BookingProgress.vue';
import { Button } from '@/components/ui/button';

interface Slot {
    start: string;
    end: string;
    booked: boolean;
    past?: boolean;
}

const props = defineProps<{
    doctor: {
        id: number;
        name: string;
        bio: string | null;
        phone: string | null;
        category_id: number;
        category: string;
        icon: string;
        photo: string | null;
        consultancy_fee: number;
    };
    date: string;
    slots: Slot[] | { holiday: true };
    slotMinutes: number;
}>();

const selectedDate = ref(props.date);

function changeDate() {
    router.get(
        '/patient/book/slot',
        { doctor_id: props.doctor.id, date: selectedDate.value },
        { preserveState: true, preserveScroll: true },
    );
}

const isHoliday = computed(() => !Array.isArray(props.slots));
const slotList = computed<Slot[]>(() =>
    Array.isArray(props.slots) ? props.slots : [],
);
const availableCount = computed(
    () => slotList.value.filter((s) => !s.booked).length,
);
const unavailableCount = computed(
    () => slotList.value.filter((s) => s.booked).length,
);
const pastCount = computed(
    () => slotList.value.filter((s) => s.past).length,
);

const selected = ref<Slot | null>(null);

const form = useForm({
    doctor_id: props.doctor.id,
    appointment_date: props.date,
    slot_start: '',
    slot_end: '',
});

function pick(slot: Slot) {
    selected.value = slot;
    form.slot_start = slot.start;
    form.slot_end = slot.end;
}

// props.date changes when changeDate() reloads Step3 with a new date, but
// useForm() only sets its initial values once — without this, the form
// would keep submitting whatever date the page first loaded with,
// regardless of what date is actually shown/selected. Also clear any
// slot picked under the previous date, since it's no longer valid.
watch(
    () => props.date,
    (newDate) => {
        form.appointment_date = newDate;
        selected.value = null;
        form.slot_start = '';
        form.slot_end = '';
    },
);

function confirmBooking() {
    form.post('/patient/book/confirm');
}

function fmtTaka(n: number) {
    return new Intl.NumberFormat('en-BD').format(Math.round(n));
}

const dateLabel = computed(() =>
    new Date(props.date + 'T00:00:00').toLocaleDateString('en-US', {
        weekday: 'long',
        day: '2-digit',
        month: 'long',
        year: 'numeric',
    }),
);

// NOT toISOString().slice(0, 10) — that formats in UTC, which during
// Bangladesh's midnight-to-6am window is still 'yesterday' in UTC terms,
// wrongly allowing a past date to be picked here (only to be rejected
// later by the server's correctly-timezoned validation). Local date parts
// avoid that mismatch entirely.
const now = new Date();
const todayStr = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;
</script>

<template>
    <Head title="Choose Date & Slot" />

    <div class="flex items-start justify-between">
        <div>
            <h1 class="text-xl font-bold text-ink-900">📅 Book Appointment</h1>
            <p class="mt-0.5 text-sm text-ink-500">
                Step 3 of 3 — Pick Date & Time
            </p>
        </div>
        <Link
            :href="`/patient/book/doctor?category_id=${doctor.category_id}`"
            class="rounded-full border border-ink-200 px-4 py-2 text-sm font-medium text-ink-600 hover:bg-ink-50"
            >← Back</Link
        >
    </div>

    <BookingProgress :step="3" />

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-[1fr_1.6fr]">
        <!-- Doctor info -->
        <div
            class="h-fit rounded-2xl border border-ink-100 bg-white p-5 shadow-sm"
        >
            <h2 class="mb-3 text-sm font-semibold text-ink-700">
                Doctor Details
            </h2>
            <div
                class="mb-4 flex items-center gap-3 border-b border-ink-100 pb-4"
            >
                <img
                    v-if="doctor.photo"
                    :src="doctor.photo"
                    :alt="doctor.name"
                    class="size-12 rounded-xl object-cover ring-1 ring-brand-100"
                />
                <div
                    v-else
                    class="flex size-12 items-center justify-center rounded-xl bg-brand-50 text-xl ring-1 ring-brand-100"
                >
                    {{ doctor.icon }}
                </div>
                <div>
                    <div class="font-semibold text-ink-900">
                        {{ doctor.name }}
                    </div>
                    <div class="text-[12.5px] text-ink-500">
                        {{ doctor.category }}
                    </div>
                </div>
            </div>
            <dl class="space-y-2 text-[13.5px]">
                <div class="flex justify-between">
                    <dt class="text-ink-500">Consultancy Fee</dt>
                    <dd class="font-mono-brand font-semibold text-brand-600">
                        ৳{{ fmtTaka(doctor.consultancy_fee) }}
                    </dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-ink-500">Phone</dt>
                    <dd class="text-ink-800">{{ doctor.phone ?? '—' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-ink-500">Slot Duration</dt>
                    <dd class="text-ink-800">{{ slotMinutes }} minutes</dd>
                </div>
            </dl>
            <p
                v-if="doctor.bio"
                class="mt-3 rounded-lg bg-ink-50 p-3 text-[12.5px] leading-relaxed text-ink-500"
            >
                {{ doctor.bio }}
            </p>

            <div class="mt-4 space-y-1.5">
                <label class="text-[13px] font-medium text-ink-600"
                    >Select Date</label
                >
                <input
                    v-model="selectedDate"
                    type="date"
                    :min="todayStr"
                    class="h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                    @change="changeDate"
                />
            </div>
        </div>

        <!-- Slots -->
        <div class="rounded-2xl border border-ink-100 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-semibold text-ink-700">
                Available Time Slots
            </h2>
            <p class="mt-1 mb-4 text-[12.5px] text-ink-400">{{ dateLabel }}</p>

            <div
                v-if="isHoliday"
                class="rounded-xl border border-red-100 bg-red-50 p-4 text-[13.5px] text-red-600"
            >
                🏖 Hospital holiday — no appointments on this date. Please select
                another date.
            </div>
            <div
                v-else-if="!slotList.length"
                class="py-12 text-center text-ink-400"
            >
                <div class="mb-2 text-3xl opacity-20">📅</div>
                Doctor has no schedule on this day.<br />
                Please select a different date.
            </div>
            <template v-else>
                <p class="mb-4 text-[12.5px] text-ink-400">
                    <span class="font-semibold text-brand-600"
                        >{{ availableCount }} available</span
                    >
                    · {{ unavailableCount }} unavailable<template
                        v-if="pastCount"
                    >
                        ({{ pastCount }} already passed)</template
                    >
                </p>

                <div class="mb-5 flex flex-wrap gap-2">
                    <button
                        v-for="slot in slotList"
                        :key="slot.start"
                        type="button"
                        class="rounded-lg px-3 py-2 font-mono text-[13px] transition-all focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-500"
                        :class="
                            slot.booked
                                ? 'cursor-not-allowed bg-ink-100 text-ink-300 opacity-60'
                                : selected?.start === slot.start
                                  ? 'scale-105 bg-brand-600 text-white shadow-sm shadow-brand-600/30'
                                  : 'cursor-pointer border border-ink-200 text-ink-700 hover:border-brand-400 hover:text-brand-600'
                        "
                        :disabled="slot.booked"
                        :title="
                            slot.past
                                ? 'This time has already passed today'
                                : slot.booked
                                  ? 'Already booked'
                                  : undefined
                        "
                        @click="pick(slot)"
                    >
                        {{ slot.start }}
                    </button>
                </div>

                <div
                    v-if="selected"
                    class="rounded-xl border border-brand-100 bg-brand-50/60 p-4"
                >
                    <div
                        class="mb-3 space-y-1 text-[13.5px] leading-relaxed text-ink-700"
                    >
                        <div class="font-semibold text-ink-900">
                            Booking Summary
                        </div>
                        <div>
                            Doctor: <strong>{{ doctor.name }}</strong>
                        </div>
                        <div>
                            Date: <strong>{{ dateLabel }}</strong>
                        </div>
                        <div>
                            Time:
                            <strong
                                >{{ selected.start }} –
                                {{ selected.end }}</strong
                            >
                        </div>
                        <div>
                            Fee:
                            <strong class="font-mono-brand text-brand-600"
                                >৳{{ fmtTaka(doctor.consultancy_fee) }}</strong
                            >
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <Button
                            class="bg-brand-600 hover:bg-brand-700"
                            :disabled="form.processing"
                            @click="confirmBooking"
                        >
                            ✅ Confirm Booking
                        </Button>
                        <Button variant="outline" @click="selected = null"
                            >Change Slot</Button
                        >
                    </div>
                </div>
            </template>
        </div>
    </div>
</template>
