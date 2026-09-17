<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, KeyRound, Save } from '@lucide/vue';
import { ref } from 'vue';
import PasswordInput from '@/components/PasswordInput.vue';
import StatusBadge from '@/components/patient/StatusBadge.vue';

interface AppointmentRow {
    id: number;
    status: string;
    appointment_date: string | null;
    doctor_name: string;
    doctor_category: string;
    doctor_photo: string | null;
    has_prescription: boolean;
}

const props = defineProps<{
    patient: {
        id: number;
        first_name: string;
        last_name: string;
        full_name: string;
        email: string;
        contact: string | null;
        gender: string | null;
        blood_group: string | null;
        date_of_birth: string | null;
        address: string | null;
        photo: string | null;
    };
    appointments: AppointmentRow[];
}>();

const form = useForm({
    first_name: props.patient.first_name,
    last_name: props.patient.last_name,
    email: props.patient.email,
    contact: props.patient.contact ?? '',
    gender: props.patient.gender ?? '',
    blood_group: props.patient.blood_group ?? '',
    date_of_birth: props.patient.date_of_birth ?? '',
    address: props.patient.address ?? '',
});

function save() {
    form.put(`/admin/patients/${props.patient.id}/edit`);
}

const showResetPassword = ref(false);
const resetForm = useForm({ new_password: '', new_password_confirmation: '' });

function resetPassword() {
    resetForm.post(`/admin/patients/${props.patient.id}/reset-password`, {
        preserveScroll: true,
        onSuccess: () => {
            resetForm.reset();
            showResetPassword.value = false;
        },
    });
}
</script>

<template>
    <Head :title="`Patient — ${props.patient.full_name}`" />

    <div class="flex items-center gap-3">
        <Link href="/admin/patients" class="rounded-full border border-ink-200 p-2 text-ink-500 hover:bg-ink-50"><ArrowLeft class="size-4" /></Link>
        <img
            v-if="props.patient.photo"
            :src="props.patient.photo"
            :alt="props.patient.full_name"
            class="size-12 rounded-full object-cover"
        />
        <div
            v-else
            class="flex size-12 items-center justify-center rounded-full bg-brand-50 text-base font-semibold text-brand-600"
        >
            {{ props.patient.full_name.charAt(0).toUpperCase() }}
        </div>
        <div>
            <h1 class="text-xl font-bold text-ink-900">{{ props.patient.full_name }}</h1>
            <p class="mt-0.5 text-[13px] text-ink-500">{{ props.patient.email }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <form class="space-y-4 rounded-2xl border border-ink-100 bg-white p-5 shadow-sm lg:col-span-2" @submit.prevent="save">
            <h2 class="text-[13.5px] font-bold text-ink-900">Patient Details</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-[12.5px] font-medium text-ink-700">First Name *</label>
                    <input v-model="form.first_name" type="text" required class="w-full rounded-lg border border-ink-200 px-3 py-2 text-[13px]" />
                    <p v-if="form.errors.first_name" class="mt-1 text-[11.5px] text-red-500">{{ form.errors.first_name }}</p>
                </div>
                <div>
                    <label class="mb-1 block text-[12.5px] font-medium text-ink-700">Last Name *</label>
                    <input v-model="form.last_name" type="text" required class="w-full rounded-lg border border-ink-200 px-3 py-2 text-[13px]" />
                    <p v-if="form.errors.last_name" class="mt-1 text-[11.5px] text-red-500">{{ form.errors.last_name }}</p>
                </div>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-[12.5px] font-medium text-ink-700">Email *</label>
                    <input v-model="form.email" type="email" required class="w-full rounded-lg border border-ink-200 px-3 py-2 text-[13px]" />
                    <p v-if="form.errors.email" class="mt-1 text-[11.5px] text-red-500">{{ form.errors.email }}</p>
                </div>
                <div>
                    <label class="mb-1 block text-[12.5px] font-medium text-ink-700">Contact</label>
                    <input v-model="form.contact" type="text" class="w-full rounded-lg border border-ink-200 px-3 py-2 text-[13px]" />
                </div>
            </div>
            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <label class="mb-1 block text-[12.5px] font-medium text-ink-700">Gender</label>
                    <select v-model="form.gender" class="w-full rounded-lg border border-ink-200 px-3 py-2 text-[13px]">
                        <option value="">—</option>
                        <option>Male</option>
                        <option>Female</option>
                        <option>Other</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-[12.5px] font-medium text-ink-700">Blood Group</label>
                    <input v-model="form.blood_group" type="text" placeholder="e.g. O+" class="w-full rounded-lg border border-ink-200 px-3 py-2 text-[13px]" />
                </div>
                <div>
                    <label class="mb-1 block text-[12.5px] font-medium text-ink-700">Date of Birth</label>
                    <input v-model="form.date_of_birth" type="date" class="w-full rounded-lg border border-ink-200 px-3 py-2 text-[13px]" />
                </div>
            </div>
            <div>
                <label class="mb-1 block text-[12.5px] font-medium text-ink-700">Address</label>
                <textarea v-model="form.address" rows="2" class="w-full rounded-lg border border-ink-200 px-3 py-2 text-[13px]" />
            </div>
            <div class="flex items-center gap-2 border-t border-ink-100 pt-4">
                <button type="submit" :disabled="form.processing" class="flex items-center gap-1.5 rounded-full bg-gradient-to-r from-brand-500 to-sky-600 px-5 py-2.5 text-[13px] font-semibold text-white shadow-sm disabled:opacity-50">
                    <Save class="size-4" /> Save Changes
                </button>
                <button type="button" class="flex items-center gap-1.5 rounded-full border border-ink-200 px-5 py-2.5 text-[13px] font-medium text-ink-600 hover:bg-ink-50" @click="showResetPassword = !showResetPassword">
                    <KeyRound class="size-4" /> Reset Password
                </button>
            </div>

            <div v-if="showResetPassword" class="space-y-3 rounded-xl border border-amber-200 bg-amber-50/50 p-4">
                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-[12px] font-medium text-ink-700">New Password</label>
                        <PasswordInput v-model="resetForm.new_password" class="w-full rounded-lg border border-ink-200 px-3 py-2 text-[13px]" />
                    </div>
                    <div>
                        <label class="mb-1 block text-[12px] font-medium text-ink-700">Confirm Password</label>
                        <PasswordInput v-model="resetForm.new_password_confirmation" class="w-full rounded-lg border border-ink-200 px-3 py-2 text-[13px]" />
                    </div>
                </div>
                <p v-if="resetForm.errors.new_password" class="text-[11.5px] text-red-500">{{ resetForm.errors.new_password }}</p>
                <button type="button" :disabled="resetForm.processing" class="rounded-full bg-amber-500 px-4 py-2 text-[12.5px] font-semibold text-white disabled:opacity-50" @click="resetPassword">
                    Confirm Reset
                </button>
            </div>
        </form>

        <div class="rounded-2xl border border-ink-100 bg-white p-5 shadow-sm">
            <h2 class="mb-3 text-[13.5px] font-bold text-ink-900">Appointment History</h2>
            <div v-if="props.appointments.length" class="space-y-3">
                <div v-for="a in props.appointments" :key="a.id" class="rounded-xl border border-ink-100 p-3">
                    <div class="flex items-center justify-between">
                        <span class="flex items-center gap-2 text-[12.5px] font-medium text-ink-800">
                            <img v-if="a.doctor_photo" :src="a.doctor_photo" :alt="a.doctor_name" class="size-6 rounded-full object-cover" />
                            {{ a.doctor_name }}
                        </span>
                        <StatusBadge :status="a.status" />
                    </div>
                    <div class="mt-1 text-[11.5px] text-ink-400">{{ a.doctor_category }} · {{ a.appointment_date }}</div>
                    <div v-if="a.has_prescription" class="mt-1 text-[11px] text-emerald-600">📄 Prescription available</div>
                </div>
            </div>
            <p v-else class="py-6 text-center text-[13px] text-ink-400">No appointments yet.</p>
        </div>
    </div>
</template>
