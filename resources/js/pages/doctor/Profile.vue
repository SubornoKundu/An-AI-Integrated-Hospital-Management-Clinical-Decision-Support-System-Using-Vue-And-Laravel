<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { Camera, Star, Trash2, User, Users } from '@lucide/vue';
import { ref, watch } from 'vue';
import PasswordInput from '@/components/PasswordInput.vue';

const props = defineProps<{
    doctor: {
        name: string;
        email: string;
        phone: string | null;
        bio: string | null;
        category: string;
        consultancy_fee: number;
        joined: string;
        total_patients: number;
        avg_rating: number;
        photo: string | null;
        has_custom_photo: boolean;
    };
}>();

const form = useForm<{
    name: string;
    phone: string;
    bio: string;
    password: string;
    password_confirmation: string;
    photo: File | null;
}>({
    name: props.doctor.name,
    phone: props.doctor.phone ?? '',
    bio: props.doctor.bio ?? '',
    password: '',
    password_confirmation: '',
    photo: null,
});

const photoPreview = ref<string | null>(props.doctor.photo);
const removingPhoto = ref(false);

watch(
    () => props.doctor.photo,
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

    router.delete('/doctor/profile/photo', {
        preserveScroll: true,
        onStart: () => (removingPhoto.value = true),
        onFinish: () => (removingPhoto.value = false),
        onSuccess: () => {
            form.photo = null;
        },
    });
}

function save() {
    form.post('/doctor/profile', {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            form.password = '';
            form.password_confirmation = '';
        },
    });
}

const initial = props.doctor.name
    .replace(/^Dr\.?\s*/i, '')
    .charAt(0)
    .toUpperCase();
</script>

<template>
    <Head title="My Profile" />

    <h1 class="flex items-center gap-2 text-xl font-bold text-ink-900">
        <User class="size-5 text-brand-500" /> My Profile
    </h1>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-[1fr_2fr]">
        <div
            class="rounded-2xl border border-ink-100 bg-white p-6 text-center shadow-sm"
        >
            <label
                class="group relative mx-auto mb-3 block size-20 cursor-pointer"
            >
                <div
                    class="flex size-20 items-center justify-center overflow-hidden rounded-full bg-gradient-to-br from-brand-500 to-sky-600 text-2xl font-bold text-white"
                >
                    <img
                        v-if="photoPreview"
                        :src="photoPreview"
                        alt="Profile photo"
                        class="h-full w-full object-cover"
                    />
                    <span v-else>{{ initial }}</span>
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
            <div class="mb-3 space-y-1">
                <p class="text-[11px] text-ink-400">Click photo to change</p>
                <button
                    v-if="doctor.has_custom_photo"
                    type="button"
                    :disabled="removingPhoto"
                    class="mx-auto flex items-center gap-1 text-[11px] font-medium text-red-500 hover:underline disabled:opacity-50"
                    @click="removePhoto"
                >
                    <Trash2 class="size-3" />
                    {{ removingPhoto ? 'Removing…' : 'Remove photo' }}
                </button>
                <p v-if="form.errors.photo" class="text-[11.5px] text-red-500">
                    {{ form.errors.photo }}
                </p>
            </div>
            <div class="text-[15px] font-bold text-ink-900">
                {{ props.doctor.name }}
            </div>
            <div class="mt-0.5 text-[12.5px] text-brand-600">
                {{ props.doctor.category }}
            </div>
            <div class="text-[12px] text-ink-400">{{ props.doctor.email }}</div>

            <div class="mt-4 space-y-2 text-left">
                <div
                    class="flex items-center gap-2.5 rounded-lg bg-ink-50 px-3 py-2"
                >
                    <span>📞</span>
                    <div>
                        <div
                            class="text-[10px] font-semibold tracking-wide text-ink-400 uppercase"
                        >
                            Phone
                        </div>
                        <div class="text-[12.5px] font-medium text-ink-800">
                            {{ props.doctor.phone ?? '—' }}
                        </div>
                    </div>
                </div>
                <div
                    class="flex items-center gap-2.5 rounded-lg bg-ink-50 px-3 py-2"
                >
                    <span>💰</span>
                    <div>
                        <div
                            class="text-[10px] font-semibold tracking-wide text-ink-400 uppercase"
                        >
                            Fee per visit
                        </div>
                        <div class="text-[12.5px] font-medium text-ink-800">
                            ৳{{ props.doctor.consultancy_fee.toLocaleString() }}
                        </div>
                    </div>
                </div>
                <div
                    class="flex items-center gap-2.5 rounded-lg bg-ink-50 px-3 py-2"
                >
                    <span>📅</span>
                    <div>
                        <div
                            class="text-[10px] font-semibold tracking-wide text-ink-400 uppercase"
                        >
                            Joined
                        </div>
                        <div class="text-[12.5px] font-medium text-ink-800">
                            {{ props.doctor.joined }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-4 grid grid-cols-2 gap-2">
                <div
                    class="rounded-xl border border-brand-100 bg-brand-50 py-3"
                >
                    <div
                        class="flex items-center justify-center gap-1 text-lg font-bold text-brand-600"
                    >
                        <Users class="size-4" />
                        {{ props.doctor.total_patients }}
                    </div>
                    <div class="text-[11px] text-ink-500">Patients</div>
                </div>
                <div class="rounded-xl border border-ink-100 bg-ink-50 py-3">
                    <div
                        class="flex items-center justify-center gap-1 text-lg font-bold text-amber-500"
                    >
                        <Star class="size-4 fill-amber-400" />
                        {{
                            props.doctor.avg_rating > 0
                                ? props.doctor.avg_rating
                                : '—'
                        }}
                    </div>
                    <div class="text-[11px] text-ink-500">Avg Rating</div>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-ink-100 bg-white p-5 shadow-sm">
            <h2 class="mb-4 text-[14px] font-bold text-ink-900">
                Edit Profile Information
            </h2>

            <div
                class="mb-3 border-b border-ink-100 pb-1.5 text-[11px] font-bold tracking-wide text-ink-400 uppercase"
            >
                Professional Information
            </div>
            <div class="space-y-3">
                <div>
                    <label
                        class="mb-1 block text-[12.5px] font-medium text-ink-700"
                        >Full Name *</label
                    >
                    <input
                        v-model="form.name"
                        type="text"
                        required
                        class="w-full rounded-lg border border-ink-200 px-3 py-2 text-[13px]"
                    />
                    <p
                        v-if="form.errors.name"
                        class="mt-1 text-[11.5px] text-red-500"
                    >
                        {{ form.errors.name }}
                    </p>
                </div>
                <div>
                    <label
                        class="mb-1 block text-[12.5px] font-medium text-ink-700"
                        >Phone Number</label
                    >
                    <input
                        v-model="form.phone"
                        type="text"
                        placeholder="01700-000000"
                        class="w-full rounded-lg border border-ink-200 px-3 py-2 text-[13px]"
                    />
                    <p
                        v-if="form.errors.phone"
                        class="mt-1 text-[11.5px] text-red-500"
                    >
                        {{ form.errors.phone }}
                    </p>
                </div>
                <div>
                    <label
                        class="mb-1 block text-[12.5px] font-medium text-ink-700"
                        >Professional Bio</label
                    >
                    <textarea
                        v-model="form.bio"
                        rows="4"
                        placeholder="Your qualifications, experience, and areas of expertise..."
                        class="w-full rounded-lg border border-ink-200 px-3 py-2 text-[13px]"
                    />
                    <p
                        v-if="form.errors.bio"
                        class="mt-1 text-[11.5px] text-red-500"
                    >
                        {{ form.errors.bio }}
                    </p>
                    <p class="mt-1 text-[11.5px] text-ink-400">
                        This will be visible to patients when booking
                        appointments.
                    </p>
                </div>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div>
                        <label
                            class="mb-1 block text-[12.5px] font-medium text-ink-700"
                            >Email (read-only)</label
                        >
                        <input
                            :value="props.doctor.email"
                            disabled
                            class="w-full cursor-not-allowed rounded-lg border border-ink-200 bg-ink-50 px-3 py-2 text-[13px] opacity-60"
                        />
                    </div>
                    <div>
                        <label
                            class="mb-1 block text-[12.5px] font-medium text-ink-700"
                            >Specialty (read-only)</label
                        >
                        <input
                            :value="props.doctor.category"
                            disabled
                            class="w-full cursor-not-allowed rounded-lg border border-ink-200 bg-ink-50 px-3 py-2 text-[13px] opacity-60"
                        />
                    </div>
                </div>
            </div>

            <div
                class="mt-5 mb-3 border-t border-ink-100 pt-4 text-[11px] font-bold tracking-wide text-ink-400 uppercase"
            >
                Change Password
                <span class="font-normal text-ink-400 normal-case"
                    >(leave blank to keep current)</span
                >
            </div>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div>
                    <label
                        class="mb-1 block text-[12.5px] font-medium text-ink-700"
                        >New Password</label
                    >
                    <PasswordInput
                        v-model="form.password"
                        placeholder="Minimum 6 characters"
                        autocomplete="new-password"
                        class="w-full rounded-lg border border-ink-200 px-3 py-2 text-[13px]"
                    />
                    <p
                        v-if="form.errors.password"
                        class="mt-1 text-[11.5px] text-red-500"
                    >
                        {{ form.errors.password }}
                    </p>
                </div>
                <div>
                    <label
                        class="mb-1 block text-[12.5px] font-medium text-ink-700"
                        >Confirm New Password</label
                    >
                    <PasswordInput
                        v-model="form.password_confirmation"
                        placeholder="Repeat new password"
                        autocomplete="new-password"
                        class="w-full rounded-lg border border-ink-200 px-3 py-2 text-[13px]"
                    />
                </div>
            </div>

            <div class="mt-5 flex gap-2">
                <button
                    type="button"
                    :disabled="form.processing"
                    class="rounded-full bg-gradient-to-r from-brand-500 to-sky-600 px-5 py-2.5 text-[13px] font-semibold text-white shadow-sm disabled:opacity-50"
                    @click="save"
                >
                    💾 Save Changes
                </button>
            </div>
        </div>
    </div>
</template>
