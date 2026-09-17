<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { Camera, HeartPulse, Lock, Mail, Trash2, User } from '@lucide/vue';
import { ref, watch } from 'vue';
import PasswordInput from '@/components/PasswordInput.vue';
import Reveal from '@/components/public/Reveal.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const props = defineProps<{
    patient: {
        first_name: string;
        last_name: string;
        email: string;
        contact: string;
        gender: string;
        date_of_birth: string | null;
        blood_group: string | null;
        address: string | null;
        known_allergies: string | null;
        chronic_conditions: string | null;
        current_medications: string | null;
        family_medical_history: string | null;
        photo: string | null;
        has_custom_photo: boolean;
    };
}>();

const form = useForm<{
    first_name: string;
    last_name: string;
    contact: string;
    date_of_birth: string;
    blood_group: string;
    address: string;
    known_allergies: string;
    chronic_conditions: string;
    current_medications: string;
    family_medical_history: string;
    password: string;
    password_confirmation: string;
    photo: File | null;
}>({
    first_name: props.patient.first_name,
    last_name: props.patient.last_name,
    contact: props.patient.contact,
    date_of_birth: props.patient.date_of_birth ?? '',
    blood_group: props.patient.blood_group ?? '',
    address: props.patient.address ?? '',
    known_allergies: props.patient.known_allergies ?? '',
    chronic_conditions: props.patient.chronic_conditions ?? '',
    current_medications: props.patient.current_medications ?? '',
    family_medical_history: props.patient.family_medical_history ?? '',
    password: '',
    password_confirmation: '',
    photo: null,
});

const photoPreview = ref<string | null>(props.patient.photo);
const removingPhoto = ref(false);

// Keep the preview in sync with the server-confirmed photo — without this,
// photoPreview only ever gets set once at page load, so after saving (or
// removing) a photo the page could keep showing the stale image until a
// full browser refresh.
watch(
    () => props.patient.photo,
    (newPhoto) => {
        if (photoPreview.value?.startsWith('blob:')) {
            URL.revokeObjectURL(photoPreview.value);
        }

        photoPreview.value = newPhoto ?? null;
    },
);

function onPhotoChange(e: Event) {
    const file = (e.target as HTMLInputElement).files?.[0];

    if (!file) {
        return;
    }

    form.photo = file;

    if (photoPreview.value?.startsWith('blob:')) {
        URL.revokeObjectURL(photoPreview.value);
    }

    photoPreview.value = URL.createObjectURL(file);
}

function removePhoto() {
    if (
        !confirm(
            'Remove your profile photo? This will reset it to the default avatar.',
        )
    ) {
        return;
    }

    router.delete('/patient/profile/photo', {
        preserveScroll: true,
        onStart: () => (removingPhoto.value = true),
        onFinish: () => (removingPhoto.value = false),
        onSuccess: () => {
            form.photo = null;
        },
    });
}

function submit() {
    form.post('/patient/profile', {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            form.password = '';
            form.password_confirmation = '';
        },
    });
}
</script>

<template>
    <Head title="My Profile" />

    <h1 class="text-xl font-bold text-ink-900">👤 My Profile</h1>

    <Reveal
        class="max-w-2xl rounded-2xl border border-ink-100 bg-white p-6 shadow-sm"
    >
        <div class="mb-5 flex items-center gap-3 border-b border-ink-100 pb-5">
            <label class="group relative cursor-pointer">
                <div
                    class="flex size-14 items-center justify-center overflow-hidden rounded-full bg-brand-50 text-2xl font-bold text-brand-600 ring-1 ring-brand-100"
                >
                    <img
                        v-if="photoPreview"
                        :src="photoPreview"
                        alt="Profile photo"
                        class="h-full w-full object-cover"
                    />
                    <span v-else
                        >{{ patient.first_name[0]
                        }}{{ patient.last_name[0] }}</span
                    >
                </div>
                <div
                    class="absolute inset-0 flex items-center justify-center rounded-full bg-black/40 opacity-0 transition-opacity group-hover:opacity-100"
                >
                    <Camera class="size-4 text-white" />
                </div>
                <input
                    type="file"
                    accept="image/jpeg,image/png,image/webp"
                    class="hidden"
                    @change="onPhotoChange"
                />
            </label>
            <div>
                <div class="text-[15px] font-semibold text-ink-900">
                    {{ patient.first_name }} {{ patient.last_name }}
                </div>
                <div class="flex items-center gap-1 text-[13px] text-ink-500">
                    <Mail class="size-3.5" /> {{ patient.email }} ·
                    {{ patient.gender }}
                </div>
                <p class="mt-1 text-[11px] text-ink-400">
                    Click the photo to change it
                </p>
                <button
                    v-if="patient.has_custom_photo"
                    type="button"
                    :disabled="removingPhoto"
                    class="mt-1 flex items-center gap-1 text-[11px] font-medium text-destructive hover:underline disabled:opacity-50"
                    @click="removePhoto"
                >
                    <Trash2 class="size-3" />
                    {{ removingPhoto ? 'Removing…' : 'Remove photo' }}
                </button>
                <p v-if="form.errors.photo" class="mt-1 text-xs text-destructive">
                    {{ form.errors.photo }}
                </p>
            </div>
        </div>

        <form class="space-y-4" @submit.prevent="submit">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="space-y-1.5">
                    <Label class="gap-1.5"
                        ><User class="size-3.5 text-ink-300" /> First
                        Name</Label
                    >
                    <Input v-model="form.first_name" required />
                    <p
                        v-if="form.errors.first_name"
                        class="text-xs text-destructive"
                    >
                        {{ form.errors.first_name }}
                    </p>
                </div>
                <div class="space-y-1.5">
                    <Label>Last Name</Label>
                    <Input v-model="form.last_name" required />
                    <p
                        v-if="form.errors.last_name"
                        class="text-xs text-destructive"
                    >
                        {{ form.errors.last_name }}
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="space-y-1.5">
                    <Label>Phone</Label>
                    <Input v-model="form.contact" required />
                    <p
                        v-if="form.errors.contact"
                        class="text-xs text-destructive"
                    >
                        {{ form.errors.contact }}
                    </p>
                </div>
                <div class="space-y-1.5">
                    <Label>Date of Birth</Label>
                    <Input v-model="form.date_of_birth" type="date" />
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="space-y-1.5">
                    <Label>Blood Group</Label>
                    <select
                        v-model="form.blood_group"
                        class="h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm outline-none"
                    >
                        <option value="">Select</option>
                        <option
                            v-for="bg in [
                                'A+',
                                'A-',
                                'B+',
                                'B-',
                                'AB+',
                                'AB-',
                                'O+',
                                'O-',
                            ]"
                            :key="bg"
                            :value="bg"
                        >
                            {{ bg }}
                        </option>
                    </select>
                </div>
                <div class="space-y-1.5">
                    <Label>Address</Label>
                    <Input v-model="form.address" placeholder="Your address" />
                </div>
            </div>

            <div class="border-t border-ink-100 pt-4">
                <p
                    class="mb-1 flex items-center gap-1.5 text-[13px] font-semibold text-ink-600"
                >
                    <HeartPulse class="size-3.5" /> Medical History
                </p>
                <p class="mb-3 text-[11.5px] text-ink-400">
                    Sharing this helps your doctor and the AI symptom checker
                    give you better guidance — especially if you're new here.
                </p>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="space-y-1.5">
                        <Label>Known Allergies</Label>
                        <textarea
                            v-model="form.known_allergies"
                            rows="2"
                            placeholder="e.g. Penicillin, peanuts..."
                            class="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm outline-none focus-visible:border-brand-400"
                        />
                    </div>
                    <div class="space-y-1.5">
                        <Label>Chronic Conditions</Label>
                        <textarea
                            v-model="form.chronic_conditions"
                            rows="2"
                            placeholder="e.g. Diabetes, hypertension..."
                            class="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm outline-none focus-visible:border-brand-400"
                        />
                    </div>
                    <div class="space-y-1.5">
                        <Label>Current Medications</Label>
                        <textarea
                            v-model="form.current_medications"
                            rows="2"
                            placeholder="e.g. Metformin 500mg daily..."
                            class="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm outline-none focus-visible:border-brand-400"
                        />
                    </div>
                    <div class="space-y-1.5">
                        <Label>Family Medical History</Label>
                        <textarea
                            v-model="form.family_medical_history"
                            rows="2"
                            placeholder="e.g. Father — heart disease..."
                            class="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm outline-none focus-visible:border-brand-400"
                        />
                    </div>
                </div>
            </div>

            <div class="border-t border-ink-100 pt-4">
                <p
                    class="mb-3 flex items-center gap-1.5 text-[13px] font-semibold text-ink-600"
                >
                    <Lock class="size-3.5" /> Change Password (optional)
                </p>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="space-y-1.5">
                        <Label>New Password</Label>
                        <PasswordInput
                            v-model="form.password"
                            placeholder="Leave blank to keep current"
                        />
                        <p
                            v-if="form.errors.password"
                            class="text-xs text-destructive"
                        >
                            {{ form.errors.password }}
                        </p>
                    </div>
                    <div class="space-y-1.5">
                        <Label>Confirm Password</Label>
                        <PasswordInput v-model="form.password_confirmation" />
                    </div>
                </div>
            </div>

            <Button
                type="submit"
                class="bg-brand-600 hover:bg-brand-700"
                :disabled="form.processing"
            >
                Save Changes
            </Button>
        </form>
    </Reveal>
</template>
