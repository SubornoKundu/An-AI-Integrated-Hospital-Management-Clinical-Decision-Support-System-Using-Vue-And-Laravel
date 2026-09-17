<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { AlertTriangle, Camera, Save, Shield, Trash2 } from '@lucide/vue';
import { ref, watch } from 'vue';
import PasswordInput from '@/components/PasswordInput.vue';

interface AdminData {
    id: number;
    name: string;
    email: string;
    username: string;
    gender: string | null;
    photo: string | null;
    has_custom_photo: boolean;
}

const props = defineProps<{
    admin: AdminData | null;
    isStaleSession: boolean;
    fallbackName: string;
    usingEnvLogin: boolean;
}>();

const form = useForm<{
    name: string;
    email: string;
    gender: string;
    password: string;
    password_confirmation: string;
    photo: File | null;
}>({
    name: props.admin?.name ?? '',
    email: props.admin?.email ?? '',
    gender: props.admin?.gender ?? '',
    password: '',
    password_confirmation: '',
    photo: null,
});

const photoPreview = ref<string | null>(props.admin?.photo ?? null);
const removingPhoto = ref(false);

// Keep the preview in sync with the server-confirmed photo — without this,
// photoPreview only ever gets set once at page load, so after saving a new
// photo the page could keep showing the old one (or nothing) until a full
// browser refresh.
watch(
    () => props.admin?.photo,
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

    router.delete('/admin/profile/photo', {
        preserveScroll: true,
        onStart: () => (removingPhoto.value = true),
        onFinish: () => (removingPhoto.value = false),
        onSuccess: () => {
            form.photo = null;
        },
    });
}

function submit() {
    form.post('/admin/profile', {
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

    <h1 class="flex items-center gap-2 text-xl font-bold text-ink-900">
        <Shield class="size-5 text-brand-500" /> My Profile
    </h1>

    <div
        v-if="props.isStaleSession"
        class="max-w-2xl space-y-3 rounded-2xl border border-amber-200 bg-amber-50 p-5"
    >
        <div class="flex items-start gap-2.5">
            <AlertTriangle class="mt-0.5 size-5 shrink-0 text-amber-500" />
            <div>
                <h2 class="text-[14px] font-semibold text-amber-800">
                    One-time re-login needed
                </h2>
                <p class="mt-1 text-[13px] leading-relaxed text-amber-700">
                    You're signed in as <strong>{{ props.fallbackName }}</strong
                    >, but this session started before profile editing was
                    linked to the default login. Log out and back in once to
                    pick it up.
                </p>
            </div>
        </div>
    </div>

    <template v-else>
        <div
            v-if="props.usingEnvLogin"
            class="mb-1 flex max-w-2xl items-start gap-2.5 rounded-2xl border border-amber-200 bg-amber-50 p-4"
        >
            <AlertTriangle class="mt-0.5 size-4 shrink-0 text-amber-500" />
            <p class="text-[12.5px] leading-relaxed text-amber-700">
                You're signed in via the default
                <code class="rounded bg-amber-100 px-1 py-0.5 text-[11.5px]"
                    >.env</code
                >
                username/password. Everything below still saves normally — but
                for better security, set a proper password here, and update
                <code class="rounded bg-amber-100 px-1 py-0.5 text-[11.5px]"
                    >ADMIN_USERNAME</code
                >
                /
                <code class="rounded bg-amber-100 px-1 py-0.5 text-[11.5px]"
                    >ADMIN_PASSWORD</code
                >
                in your
                <code class="rounded bg-amber-100 px-1 py-0.5 text-[11.5px]"
                    >.env</code
                >
                to something only you know.
            </p>
        </div>

        <form
            class="max-w-2xl space-y-4 rounded-2xl border border-ink-100 bg-white p-6 shadow-sm"
            @submit.prevent="submit"
        >
            <div class="flex items-center gap-3 border-b border-ink-100 pb-5">
                <label class="group relative cursor-pointer">
                    <div
                        class="flex size-16 items-center justify-center overflow-hidden rounded-full bg-brand-50 text-2xl font-bold text-brand-600 ring-1 ring-brand-100"
                    >
                        <img
                            v-if="photoPreview"
                            :src="photoPreview"
                            alt="Profile photo"
                            class="h-full w-full object-cover"
                        />
                        <span v-else>{{
                            (props.admin?.name ?? 'A').charAt(0).toUpperCase()
                        }}</span>
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
                        {{ props.admin?.name }}
                    </div>
                    <div class="text-[12.5px] text-ink-500">
                        @{{ props.admin?.username }}
                    </div>
                    <p class="mt-1 text-[11px] text-ink-400">
                        Click the photo to change it
                    </p>
                    <button
                        v-if="props.admin?.has_custom_photo"
                        type="button"
                        :disabled="removingPhoto"
                        class="mt-1 flex items-center gap-1 text-[11px] font-medium text-red-500 hover:underline disabled:opacity-50"
                        @click="removePhoto"
                    >
                        <Trash2 class="size-3" />
                        {{ removingPhoto ? 'Removing…' : 'Remove photo' }}
                    </button>
                    <p
                        v-if="form.errors.photo"
                        class="mt-1 text-[11.5px] text-red-500"
                    >
                        {{ form.errors.photo }}
                    </p>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
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
                        >Email *</label
                    >
                    <input
                        v-model="form.email"
                        type="email"
                        required
                        class="w-full rounded-lg border border-ink-200 px-3 py-2 text-[13px]"
                    />
                    <p
                        v-if="form.errors.email"
                        class="mt-1 text-[11.5px] text-red-500"
                    >
                        {{ form.errors.email }}
                    </p>
                </div>
            </div>

            <div>
                <label class="mb-1 block text-[12.5px] font-medium text-ink-700"
                    >Gender</label
                >
                <select
                    v-model="form.gender"
                    class="w-full max-w-xs rounded-lg border border-ink-200 px-3 py-2 text-[13px]"
                >
                    <option value="">— Not specified —</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                    <option value="Other">Other</option>
                </select>
                <p class="mt-1 text-[11px] text-ink-400">
                    Used to style your placeholder avatar until you upload a
                    real photo.
                </p>
            </div>

            <div class="border-t border-ink-100 pt-4">
                <p
                    class="mb-3 text-[11px] font-bold tracking-wide text-ink-400 uppercase"
                >
                    Change Password
                    <span class="font-normal normal-case"
                        >(leave blank to keep current)</span
                    >
                </p>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label
                            class="mb-1 block text-[12.5px] font-medium text-ink-700"
                            >New Password</label
                        >
                        <PasswordInput
                            v-model="form.password"
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
                            autocomplete="new-password"
                            class="w-full rounded-lg border border-ink-200 px-3 py-2 text-[13px]"
                        />
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-2 border-t border-ink-100 pt-4">
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="flex items-center gap-1.5 rounded-full bg-gradient-to-r from-brand-500 to-sky-600 px-5 py-2.5 text-[13px] font-semibold text-white shadow-sm disabled:opacity-50"
                >
                    <Save class="size-4" /> Save Changes
                </button>
            </div>
        </form>
    </template>
</template>
