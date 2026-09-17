<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { Bed, BedDouble, LogOut, Plus, TrendingUp, Trash2, Users } from '@lucide/vue';
import { ref } from 'vue';

interface Ward {
    id: number;
    name: string;
    type: string;
    floor: string | null;
    building: string | null;
    beds_count: number;
    available_count: number;
    occupied_count: number;
}
interface Admission {
    id: number;
    patient_name: string;
    patient_blood_group: string | null;
    bed_number: string;
    ward_name: string;
    doctor_name: string;
    admission_date: string;
    admission_date_raw: string;
    days_admitted: number;
    daily_charge: number;
}

const props = defineProps<{
    wards: Ward[];
    totalBeds: number;
    occupiedBeds: number;
    availableBeds: number;
    activeAdmissions: Admission[];
    bedRevenue: number;
    availableBedsList: { id: number; label: string }[];
    patientsList: { id: number; name: string }[];
    doctorsList: { id: number; name: string }[];
}>();

const wardTypes = ['general', 'icu', 'ccu', 'cabin', 'emergency', 'pediatric', 'maternity'];
const bedTypes = ['standard', 'deluxe', 'icu', 'cabin'];

// ── Add Ward ──────────────────────────────────────────────────
const showWardForm = ref(false);
const wardForm = useForm({ name: '', type: 'general', floor: '', building: '' });
function submitWard() {
    wardForm.post('/admin/beds/ward', {
        preserveScroll: true,
        onSuccess: () => {
            wardForm.reset();
            showWardForm.value = false;
        },
    });
}
function deleteWard(w: Ward) {
    if (confirm(`Delete ward "${w.name}"?`)) {
        router.delete(`/admin/beds/ward/${w.id}`, { preserveScroll: true });
    }
}

// ── Add Bed ───────────────────────────────────────────────────
const showBedForm = ref(false);
const bedForm = useForm({ ward_id: '', bed_number: '', bed_type: 'standard', daily_charge: 1000, notes: '' });
function submitBed() {
    bedForm.post('/admin/beds/bed', {
        preserveScroll: true,
        onSuccess: () => {
            bedForm.reset();
            showBedForm.value = false;
        },
    });
}

// ── Admit Patient ────────────────────────────────────────────
const showAdmitForm = ref(false);
const admitForm = useForm({
    bed_id: '',
    patient_id: '',
    doctor_id: '',
    admission_date: new Date().toISOString().slice(0, 10),
    diagnosis: '',
    notes: '',
});
function submitAdmit() {
    admitForm.post('/admin/beds/admit', {
        preserveScroll: true,
        onSuccess: () => {
            admitForm.reset();
            showAdmitForm.value = false;
        },
    });
}

// ── Discharge ─────────────────────────────────────────────────
const dischargingId = ref<number | null>(null);
const dischargeForm = useForm({ discharge_date: new Date().toISOString().slice(0, 10) });
function startDischarge(a: Admission) {
    dischargingId.value = a.id;
    dischargeForm.discharge_date = new Date().toISOString().slice(0, 10);
}
function submitDischarge(id: number) {
    dischargeForm.put(`/admin/beds/admissions/${id}/discharge`, {
        preserveScroll: true,
        onSuccess: () => (dischargingId.value = null),
    });
}
</script>

<template>
    <Head title="Bed Management" />

    <div class="flex flex-col items-start justify-between gap-3 sm:flex-row sm:items-center">
        <div>
            <h1 class="flex items-center gap-2 text-xl font-bold text-ink-900">
                <BedDouble class="size-5 text-brand-500" /> Bed Management
            </h1>
            <p class="mt-1 text-[13px] text-ink-500">Wards, beds, and patient admissions.</p>
        </div>
        <div class="flex gap-2">
            <button class="flex items-center gap-1.5 rounded-full border border-ink-200 px-3.5 py-2 text-[12.5px] font-medium text-ink-600 hover:bg-ink-50" @click="showWardForm = !showWardForm">
                <Plus class="size-3.5" /> Add Ward
            </button>
            <button class="flex items-center gap-1.5 rounded-full border border-ink-200 px-3.5 py-2 text-[12.5px] font-medium text-ink-600 hover:bg-ink-50" @click="showBedForm = !showBedForm">
                <Plus class="size-3.5" /> Add Bed
            </button>
            <button class="flex items-center gap-1.5 rounded-full bg-gradient-to-r from-brand-500 to-sky-600 px-4 py-2 text-[12.5px] font-semibold text-white shadow-sm" @click="showAdmitForm = !showAdmitForm">
                <Plus class="size-3.5" /> Admit Patient
            </button>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
        <div class="rounded-2xl border border-ink-100 bg-white p-4 shadow-sm">
            <div class="text-2xl font-bold text-ink-900">{{ props.totalBeds }}</div>
            <div class="mt-1 text-[12px] text-ink-500">Total Beds</div>
        </div>
        <div class="rounded-2xl border border-ink-100 bg-white p-4 shadow-sm">
            <div class="text-2xl font-bold text-emerald-600">{{ props.availableBeds }}</div>
            <div class="mt-1 text-[12px] text-ink-500">Available</div>
        </div>
        <div class="rounded-2xl border border-ink-100 bg-white p-4 shadow-sm">
            <div class="text-2xl font-bold text-red-500">{{ props.occupiedBeds }}</div>
            <div class="mt-1 text-[12px] text-ink-500">Occupied</div>
        </div>
        <div class="rounded-2xl border border-ink-100 bg-white p-4 shadow-sm">
            <div class="flex items-center gap-1 text-2xl font-bold text-brand-600"><TrendingUp class="size-4" /> ৳{{ props.bedRevenue.toLocaleString() }}</div>
            <div class="mt-1 text-[12px] text-ink-500">Bed Revenue</div>
        </div>
    </div>

    <!-- Add Ward inline form -->
    <form v-if="showWardForm" class="grid gap-3 rounded-2xl border border-ink-100 bg-white p-4 shadow-sm sm:grid-cols-4" @submit.prevent="submitWard">
        <input v-model="wardForm.name" type="text" placeholder="Ward name (e.g. General Ward A)" required class="rounded-lg border border-ink-200 px-3 py-2 text-[13px] sm:col-span-2" />
        <select v-model="wardForm.type" class="rounded-lg border border-ink-200 px-3 py-2 text-[13px]">
            <option v-for="t in wardTypes" :key="t" :value="t">{{ t.toUpperCase() }}</option>
        </select>
        <input v-model="wardForm.floor" type="text" placeholder="Floor" class="rounded-lg border border-ink-200 px-3 py-2 text-[13px]" />
        <button type="submit" :disabled="wardForm.processing" class="rounded-full bg-brand-500 px-4 py-2 text-[12.5px] font-semibold text-white sm:col-span-4">Create Ward</button>
    </form>

    <!-- Add Bed inline form -->
    <form v-if="showBedForm" class="grid gap-3 rounded-2xl border border-ink-100 bg-white p-4 shadow-sm sm:grid-cols-4" @submit.prevent="submitBed">
        <select v-model="bedForm.ward_id" required class="rounded-lg border border-ink-200 px-3 py-2 text-[13px]">
            <option value="">— Select Ward —</option>
            <option v-for="w in props.wards" :key="w.id" :value="w.id">{{ w.name }} ({{ w.type.toUpperCase() }})</option>
        </select>
        <input v-model="bedForm.bed_number" type="text" placeholder="Bed number (e.g. A-101)" required class="rounded-lg border border-ink-200 px-3 py-2 text-[13px]" />
        <select v-model="bedForm.bed_type" class="rounded-lg border border-ink-200 px-3 py-2 text-[13px]">
            <option v-for="t in bedTypes" :key="t" :value="t">{{ t }}</option>
        </select>
        <input v-model.number="bedForm.daily_charge" type="number" min="0" placeholder="Daily charge (৳)" required class="rounded-lg border border-ink-200 px-3 py-2 text-[13px]" />
        <p v-if="wardForm.errors.name || bedForm.errors.ward_id" class="text-[11.5px] text-red-500 sm:col-span-4">{{ bedForm.errors.ward_id || bedForm.errors.bed_number }}</p>
        <button type="submit" :disabled="bedForm.processing" class="rounded-full bg-brand-500 px-4 py-2 text-[12.5px] font-semibold text-white sm:col-span-4">Add Bed</button>
    </form>

    <!-- Admit Patient inline form -->
    <form v-if="showAdmitForm" class="grid gap-3 rounded-2xl border border-brand-100 bg-brand-50/30 p-4 shadow-sm sm:grid-cols-3" @submit.prevent="submitAdmit">
        <select v-model="admitForm.patient_id" required class="rounded-lg border border-ink-200 px-3 py-2 text-[13px]">
            <option value="">— Select Patient —</option>
            <option v-for="p in props.patientsList" :key="p.id" :value="p.id">{{ p.name }}</option>
        </select>
        <select v-model="admitForm.doctor_id" required class="rounded-lg border border-ink-200 px-3 py-2 text-[13px]">
            <option value="">— Attending Doctor —</option>
            <option v-for="d in props.doctorsList" :key="d.id" :value="d.id">Dr. {{ d.name }}</option>
        </select>
        <select v-model="admitForm.bed_id" required class="rounded-lg border border-ink-200 px-3 py-2 text-[13px]">
            <option value="">— Available Bed —</option>
            <option v-for="b in props.availableBedsList" :key="b.id" :value="b.id">{{ b.label }}</option>
        </select>
        <input v-model="admitForm.admission_date" type="date" required class="rounded-lg border border-ink-200 px-3 py-2 text-[13px]" />
        <input v-model="admitForm.diagnosis" type="text" placeholder="Diagnosis (optional)" class="rounded-lg border border-ink-200 px-3 py-2 text-[13px] sm:col-span-2" />
        <p v-if="Object.keys(admitForm.errors).length" class="text-[11.5px] text-red-500 sm:col-span-3">{{ Object.values(admitForm.errors)[0] }}</p>
        <button type="submit" :disabled="admitForm.processing" class="rounded-full bg-gradient-to-r from-brand-500 to-sky-600 px-4 py-2 text-[12.5px] font-semibold text-white sm:col-span-3">Confirm Admission</button>
    </form>

    <div>
        <h2 class="mb-3 text-[13.5px] font-bold text-ink-900">Wards Overview</h2>
        <div v-if="props.wards.length" class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <div v-for="w in props.wards" :key="w.id" class="rounded-2xl border border-ink-100 bg-white p-4 shadow-sm">
                <div class="flex items-start justify-between">
                    <div>
                        <h3 class="text-[13.5px] font-bold text-ink-900">{{ w.name }}</h3>
                        <div class="mt-1 flex items-center gap-1.5">
                            <span class="rounded-full bg-ink-100 px-2 py-0.5 text-[10.5px] font-medium text-ink-600">{{ w.type.toUpperCase() }}</span>
                            <span v-if="w.floor" class="text-[11px] text-ink-400">Floor {{ w.floor }}</span>
                        </div>
                    </div>
                    <button class="rounded-lg p-1 text-ink-300 hover:bg-red-50 hover:text-red-500" @click="deleteWard(w)"><Trash2 class="size-3.5" /></button>
                </div>
                <div class="mt-3 grid grid-cols-3 gap-2 text-center">
                    <div>
                        <div class="text-[15px] font-bold text-ink-900">{{ w.beds_count }}</div>
                        <div class="text-[10.5px] text-ink-400">Total</div>
                    </div>
                    <div>
                        <div class="text-[15px] font-bold text-emerald-600">{{ w.available_count }}</div>
                        <div class="text-[10.5px] text-ink-400">Free</div>
                    </div>
                    <div>
                        <div class="text-[15px] font-bold text-red-500">{{ w.occupied_count }}</div>
                        <div class="text-[10.5px] text-ink-400">Occupied</div>
                    </div>
                </div>
                <div v-if="w.beds_count > 0" class="mt-3">
                    <div class="h-1.5 overflow-hidden rounded-full bg-ink-100">
                        <div class="h-full rounded-full bg-red-400" :style="{ width: `${Math.round((w.occupied_count / w.beds_count) * 100)}%` }" />
                    </div>
                    <div class="mt-1 text-[10.5px] text-ink-400">{{ Math.round((w.occupied_count / w.beds_count) * 100) }}% occupied</div>
                </div>
            </div>
        </div>
        <div v-else class="rounded-2xl border border-dashed border-ink-200 py-10 text-center text-[13px] text-ink-400">
            No wards yet. Click "Add Ward" to get started.
        </div>
    </div>

    <div>
        <h2 class="mb-3 text-[13.5px] font-bold text-ink-900">
            Current Admissions ({{ props.activeAdmissions.length }})
        </h2>
        <div v-if="props.activeAdmissions.length" class="space-y-2">
            <div v-for="a in props.activeAdmissions" :key="a.id" class="rounded-2xl border border-ink-100 bg-white p-4 shadow-sm">
                <div v-if="dischargingId !== a.id" class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <span class="flex size-9 items-center justify-center rounded-full bg-brand-50 text-brand-600"><Users class="size-4" /></span>
                        <div>
                            <div class="text-[13px] font-semibold text-ink-800">
                                {{ a.patient_name }}
                                <span v-if="a.patient_blood_group" class="ml-1 rounded-full bg-red-50 px-1.5 py-0.5 text-[10px] text-red-500">{{ a.patient_blood_group }}</span>
                            </div>
                            <div class="text-[11.5px] text-ink-400">
                                Bed {{ a.bed_number }} · {{ a.ward_name }} · {{ a.doctor_name }} · {{ a.days_admitted }} day{{ a.days_admitted === 1 ? '' : 's' }}
                            </div>
                        </div>
                    </div>
                    <button class="flex items-center gap-1.5 rounded-full border border-emerald-200 px-3 py-1.5 text-[12px] font-medium text-emerald-600 hover:bg-emerald-50" @click="startDischarge(a)">
                        <LogOut class="size-3.5" /> Discharge
                    </button>
                </div>

                <form v-else class="flex flex-wrap items-end gap-3" @submit.prevent="submitDischarge(a.id)">
                    <div>
                        <div class="text-[12.5px] font-medium text-ink-700">Discharge {{ a.patient_name }}</div>
                        <div class="text-[11px] text-ink-400">Admitted {{ a.admission_date }} · ৳{{ a.daily_charge }}/day</div>
                    </div>
                    <input v-model="dischargeForm.discharge_date" type="date" :min="a.admission_date_raw" required class="rounded-lg border border-ink-200 px-3 py-2 text-[13px]" />
                    <button type="submit" :disabled="dischargeForm.processing" class="rounded-full bg-emerald-500 px-4 py-2 text-[12.5px] font-semibold text-white">Confirm</button>
                    <button type="button" class="rounded-full border border-ink-200 px-4 py-2 text-[12.5px] text-ink-600" @click="dischargingId = null">Cancel</button>
                    <p v-if="dischargeForm.errors.discharge_date" class="w-full text-[11.5px] text-red-500">{{ dischargeForm.errors.discharge_date }}</p>
                </form>
            </div>
        </div>
        <div v-else class="rounded-2xl border border-dashed border-ink-200 py-10 text-center text-[13px] text-ink-400">
            <Bed class="mx-auto mb-2 size-6 text-ink-200" /> No patients currently admitted.
        </div>
    </div>
</template>
