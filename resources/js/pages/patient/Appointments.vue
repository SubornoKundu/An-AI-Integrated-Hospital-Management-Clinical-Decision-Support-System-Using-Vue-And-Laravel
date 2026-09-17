<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { FileText, MessageCircle, Receipt, Sparkles, Star, X } from '@lucide/vue';
import { ref } from 'vue';
import Pagination from '@/components/patient/Pagination.vue';
import StatusBadge from '@/components/patient/StatusBadge.vue';
import { Button } from '@/components/ui/button';

interface AppointmentCard {
    id: number;
    status: string;
    appointment_date_label: string | null;
    slot_start: string;
    slot_end: string;
    doctor: {
        id: number;
        name: string;
        category: string;
        icon: string;
        photo: string | null;
        consultancy_fee: number;
    } | null;
    has_prescription: boolean;
    rating: { rating: number; review: string | null } | null;
}
interface Paginated<T> {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
}

const props = defineProps<{
    appointments: Paginated<AppointmentCard>;
    doctors: { id: number; name: string }[];
    filters: { status?: string; doctor?: string };
}>();

const statusFilter = ref(props.filters.status ?? '');
const doctorFilter = ref(props.filters.doctor ?? '');

function applyFilters() {
    router.get(
        '/patient/appointments',
        {
            status: statusFilter.value || undefined,
            doctor: doctorFilter.value || undefined,
        },
        { preserveState: true, preserveScroll: true },
    );
}

const cancellingId = ref<number | null>(null);

function cancelAppointment(id: number) {
    if (!confirm('Cancel this appointment?')) {
        return;
    }

    cancellingId.value = id;
    router.post(
        `/patient/appointments/${id}/cancel`,
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                cancellingId.value = null;
            },
        },
    );
}

const ratingOpenId = ref<number | null>(null);
const ratingForm = useForm({ rating: 5, review: '' });

function openRating(id: number) {
    ratingOpenId.value = id;
    ratingForm.reset();
}

function submitRating(id: number) {
    ratingForm.post(`/patient/appointments/${id}/rate`, {
        preserveScroll: true,
        onSuccess: () => (ratingOpenId.value = null),
    });
}
</script>

<template>
    <Head title="My Appointments" />

    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-xl font-bold text-ink-900">My Appointments</h1>
    </div>

    <div
        class="flex flex-wrap gap-2 rounded-2xl border border-ink-100 bg-white p-3 shadow-sm"
    >
        <select
            v-model="statusFilter"
            class="h-9 rounded-md border border-input bg-transparent px-3 text-sm outline-none"
            @change="applyFilters"
        >
            <option value="">All Status</option>
            <option value="active">Active</option>
            <option value="completed">Completed</option>
            <option value="cancelled_by_patient">Cancelled by me</option>
            <option value="cancelled_by_doctor">Cancelled by Doctor</option>
            <option value="cancelled_by_admin">Cancelled — Holiday</option>
        </select>
        <select
            v-model="doctorFilter"
            class="h-9 min-w-[160px] rounded-md border border-input bg-transparent px-3 text-sm outline-none"
            @change="applyFilters"
        >
            <option value="">All Doctors</option>
            <option v-for="d in doctors" :key="d.id" :value="d.id">
                {{ d.name }}
            </option>
        </select>
    </div>

    <div v-if="appointments.data.length" class="space-y-3">
        <div
            v-for="a in appointments.data"
            :key="a.id"
            class="rounded-2xl border border-ink-100 bg-white p-4 shadow-sm sm:p-5"
        >
            <div
                class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="flex items-center gap-3">
                    <img
                        v-if="a.doctor?.photo"
                        :src="a.doctor.photo"
                        :alt="a.doctor.name"
                        class="size-11 shrink-0 rounded-xl object-cover ring-1 ring-brand-100"
                    />
                    <div
                        v-else
                        class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-lg ring-1 ring-brand-100"
                    >
                        {{ a.doctor?.icon }}
                    </div>
                    <div>
                        <div class="font-semibold text-ink-900">
                            {{ a.doctor?.name }}
                        </div>
                        <div class="text-[12.5px] text-ink-500">
                            {{ a.doctor?.category }}
                        </div>
                        <div class="mt-0.5 text-[12.5px] text-ink-400">
                            {{ a.appointment_date_label }} ·
                            {{ a.slot_start }}–{{ a.slot_end }}
                        </div>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <StatusBadge :status="a.status" />
                    <a
                        :href="`/patient/chat/${a.id}`"
                        class="flex items-center gap-1 rounded-full border border-emerald-200 px-3 py-1.5 text-[12px] font-medium text-emerald-600 hover:bg-emerald-50"
                        ><MessageCircle class="size-3.5" /> Message</a
                    >
                    <a
                        :href="`/patient/appointments/${a.id}/invoice`"
                        class="flex items-center gap-1 rounded-full border border-ink-200 px-3 py-1.5 text-[12px] font-medium text-ink-600 hover:bg-ink-50"
                        ><Receipt class="size-3.5" /> Invoice</a
                    >
                    <a
                        v-if="a.has_prescription"
                        :href="`/patient/prescription/${a.id}/download`"
                        class="flex items-center gap-1 rounded-full border border-sky-200 px-3 py-1.5 text-[12px] font-medium text-sky-600 hover:bg-sky-50"
                        ><FileText class="size-3.5" /> Prescription</a
                    >
                    <Link
                        v-if="a.has_prescription"
                        :href="`/patient/ai/prescription/${a.id}/explain`"
                        class="flex items-center gap-1 rounded-full border border-purple-200 px-3 py-1.5 text-[12px] font-medium text-purple-600 hover:bg-purple-50"
                        ><Sparkles class="size-3.5" /> Explain with AI</Link
                    >
                    <button
                        v-if="a.status === 'active'"
                        class="flex items-center gap-1 rounded-full border border-red-200 px-3 py-1.5 text-[12px] font-medium text-red-600 hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-50"
                        :disabled="cancellingId === a.id"
                        @click="cancelAppointment(a.id)"
                    >
                        <X class="size-3.5" /> {{ cancellingId === a.id ? 'Cancelling…' : 'Cancel' }}
                    </button>
                    <button
                        v-if="a.status === 'completed' && !a.rating"
                        class="flex items-center gap-1 rounded-full border border-amber-200 px-3 py-1.5 text-[12px] font-medium text-amber-600 hover:bg-amber-50"
                        @click="openRating(a.id)"
                    >
                        <Star class="size-3.5" /> Rate
                    </button>
                    <span
                        v-else-if="a.rating"
                        class="flex items-center gap-1 text-[12px] font-medium text-amber-500"
                    >
                        <Star class="size-3.5 fill-amber-400" />
                        {{ a.rating.rating }}/5
                    </span>
                </div>
            </div>

            <div
                v-if="ratingOpenId === a.id"
                class="mt-4 rounded-xl border border-amber-100 bg-amber-50/50 p-4"
            >
                <div class="mb-2 flex gap-1">
                    <button
                        v-for="n in 5"
                        :key="n"
                        type="button"
                        @click="ratingForm.rating = n"
                    >
                        <Star
                            class="size-6 transition-colors"
                            :class="
                                n <= ratingForm.rating
                                    ? 'fill-amber-400 text-amber-400'
                                    : 'text-ink-200'
                            "
                        />
                    </button>
                </div>
                <textarea
                    v-model="ratingForm.review"
                    rows="2"
                    placeholder="Share your experience (optional)"
                    class="w-full rounded-md border border-input bg-white px-3 py-2 text-sm outline-none"
                />
                <p v-if="ratingForm.errors.review" class="mt-1 text-xs text-destructive">
                    {{ ratingForm.errors.review }}
                </p>
                <div class="mt-2 flex gap-2">
                    <Button
                        size="sm"
                        class="bg-amber-500 hover:bg-amber-600"
                        @click="submitRating(a.id)"
                        >Submit Rating</Button
                    >
                    <Button
                        size="sm"
                        variant="ghost"
                        @click="ratingOpenId = null"
                        >Cancel</Button
                    >
                </div>
            </div>
        </div>
        <Pagination :links="appointments.links" />
    </div>
    <div
        v-else
        class="rounded-2xl border border-ink-100 bg-white py-16 text-center text-ink-400 shadow-sm"
    >
        <div class="mb-2 text-4xl opacity-20">📋</div>
        No appointments found.
    </div>
</template>
