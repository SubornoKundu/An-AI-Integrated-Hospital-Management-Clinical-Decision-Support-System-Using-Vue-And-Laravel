<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Wallet } from '@lucide/vue';
import { ref } from 'vue';

interface RevenueRecord {
    id: number;
    appointment_date: string | null;
    patient_name: string;
    doctor_name: string;
    doctor_category: string;
    fee: number;
}
interface DoctorBreakdown {
    doctor: string;
    count: number;
    revenue: number;
}

const props = defineProps<{
    records: RevenueRecord[];
    totalRevenue: number;
    byDoctor: DoctorBreakdown[];
    month: string;
}>();

const selectedMonth = ref(props.month);

function changeMonth() {
    router.get('/admin/revenue', { month: selectedMonth.value }, { preserveState: true });
}
</script>

<template>
    <Head title="Revenue" />

    <div class="flex flex-col items-start justify-between gap-3 sm:flex-row sm:items-center">
        <h1 class="flex items-center gap-2 text-xl font-bold text-ink-900">
            <Wallet class="size-5 text-brand-500" /> Revenue
        </h1>
        <input v-model="selectedMonth" type="month" class="rounded-lg border border-ink-200 px-3 py-2 text-[13px]" @change="changeMonth" />
    </div>

    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
        <div class="rounded-2xl border border-ink-100 bg-white p-4 shadow-sm">
            <div class="text-2xl font-bold text-brand-600">৳{{ props.totalRevenue.toLocaleString() }}</div>
            <div class="mt-1 text-[12.5px] text-ink-500">Total This Month</div>
        </div>
        <div class="rounded-2xl border border-ink-100 bg-white p-4 shadow-sm">
            <div class="text-2xl font-bold text-ink-900">{{ props.records.length }}</div>
            <div class="mt-1 text-[12.5px] text-ink-500">Completed Visits</div>
        </div>
        <div class="col-span-2 rounded-2xl border border-ink-100 bg-white p-4 shadow-sm sm:col-span-1">
            <div class="text-2xl font-bold text-ink-900">{{ props.byDoctor.length }}</div>
            <div class="mt-1 text-[12.5px] text-ink-500">Doctors Earning</div>
        </div>
    </div>

    <div class="rounded-2xl border border-ink-100 bg-white p-4 shadow-sm">
        <h2 class="mb-3 text-[13.5px] font-bold text-ink-900">Revenue by Doctor</h2>
        <div v-if="props.byDoctor.length" class="space-y-2">
            <div v-for="d in props.byDoctor" :key="d.doctor" class="flex items-center justify-between rounded-xl border border-ink-50 px-3 py-2.5">
                <div>
                    <div class="text-[13px] font-medium text-ink-800">{{ d.doctor }}</div>
                    <div class="text-[11px] text-ink-400">{{ d.count }} visits</div>
                </div>
                <div class="text-[13px] font-semibold text-brand-600">৳{{ d.revenue.toLocaleString() }}</div>
            </div>
        </div>
        <p v-else class="py-6 text-center text-[13px] text-ink-400">No revenue recorded for this month.</p>
    </div>

    <div class="overflow-x-auto rounded-2xl border border-ink-100 bg-white shadow-sm">
        <h2 class="border-b border-ink-100 px-4 py-3 text-[13.5px] font-bold text-ink-900">All Transactions</h2>
        <table class="w-full text-left text-[13px]">
            <thead class="border-b border-ink-100 bg-ink-25 text-[11.5px] text-ink-500">
                <tr>
                    <th class="px-4 py-2.5 font-medium">Date</th>
                    <th class="px-4 py-2.5 font-medium">Patient</th>
                    <th class="px-4 py-2.5 font-medium">Doctor</th>
                    <th class="px-4 py-2.5 font-medium text-right">Fee</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-ink-50">
                <tr v-for="r in props.records" :key="r.id">
                    <td class="px-4 py-2.5 text-ink-500">{{ r.appointment_date }}</td>
                    <td class="px-4 py-2.5 text-ink-700">{{ r.patient_name }}</td>
                    <td class="px-4 py-2.5 text-ink-600">{{ r.doctor_name }} <span class="text-[11px] text-ink-400">· {{ r.doctor_category }}</span></td>
                    <td class="px-4 py-2.5 text-right font-medium text-ink-800">৳{{ r.fee.toLocaleString() }}</td>
                </tr>
                <tr v-if="!props.records.length">
                    <td colspan="4" class="px-4 py-10 text-center text-ink-400">No transactions this month.</td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
