<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Save, Trash2, Upload } from '@lucide/vue';
import { ref } from 'vue';

const props = defineProps<{
    settings: {
        hospital_name: string;
        hospital_address: string;
        hospital_phone: string;
        hospital_email: string;
        slot_duration: string;
        currency_symbol: string;
        hospital_logo: string | null;
    };
}>();

const form = useForm<{
    hospital_name: string;
    hospital_address: string;
    hospital_phone: string;
    hospital_email: string;
    slot_duration: string;
    currency_symbol: string;
    hospital_logo: File | null;
    remove_logo: string;
}>({
    hospital_name: props.settings.hospital_name,
    hospital_address: props.settings.hospital_address,
    hospital_phone: props.settings.hospital_phone,
    hospital_email: props.settings.hospital_email,
    slot_duration: props.settings.slot_duration,
    currency_symbol: props.settings.currency_symbol,
    hospital_logo: null,
    remove_logo: '0',
});

const logoPreview = ref<string | null>(props.settings.hospital_logo);

function onLogoChange(e: Event) {
    const file = (e.target as HTMLInputElement).files?.[0] ?? null;
    form.hospital_logo = file;
    form.remove_logo = '0';

    if (file) {
        logoPreview.value = URL.createObjectURL(file);
    }
}

function removeLogo() {
    form.hospital_logo = null;
    form.remove_logo = '1';
    logoPreview.value = null;
}

function submit() {
    form.post('/admin/settings', { forceFormData: true, preserveScroll: true });
}
</script>

<template>
    <Head title="Hospital Settings" />

    <div>
        <h1 class="text-xl font-bold text-ink-900">Settings</h1>
        <p class="mt-1 text-[13px] text-ink-500">
            Hospital branding and booking configuration.
        </p>
    </div>

    <form
        class="max-w-2xl space-y-5 rounded-2xl border border-ink-100 bg-white p-5 shadow-sm"
        @submit.prevent="submit"
    >
        <div>
            <label class="mb-2 block text-[12.5px] font-medium text-ink-700"
                >Hospital Logo</label
            >
            <div class="flex items-center gap-4">
                <div
                    class="bg-ink-25 flex size-16 items-center justify-center overflow-hidden rounded-xl border border-ink-200"
                >
                    <img
                        v-if="logoPreview"
                        :src="logoPreview"
                        alt="Logo"
                        class="size-full object-contain"
                    />
                    <span v-else class="text-[10px] text-ink-300">No logo</span>
                </div>
                <label
                    class="flex cursor-pointer items-center gap-1.5 rounded-full border border-ink-200 px-3 py-2 text-[12.5px] font-medium text-ink-600 hover:bg-ink-50"
                >
                    <Upload class="size-3.5" /> Upload
                    <input
                        type="file"
                        accept="image/*"
                        class="hidden"
                        @change="onLogoChange"
                    />
                </label>
                <button
                    v-if="logoPreview"
                    type="button"
                    class="flex items-center gap-1.5 rounded-full border border-red-200 px-3 py-2 text-[12.5px] font-medium text-red-500 hover:bg-red-50"
                    @click="removeLogo"
                >
                    <Trash2 class="size-3.5" /> Remove
                </button>
            </div>
            <p
                v-if="form.errors.hospital_logo"
                class="mt-1 text-[11.5px] text-red-500"
            >
                {{ form.errors.hospital_logo }}
            </p>
        </div>

        <div>
            <label class="mb-1 block text-[12.5px] font-medium text-ink-700"
                >Hospital Name *</label
            >
            <input
                v-model="form.hospital_name"
                type="text"
                required
                class="w-full rounded-lg border border-ink-200 px-3 py-2 text-[13px]"
            />
            <p
                v-if="form.errors.hospital_name"
                class="mt-1 text-[11.5px] text-red-500"
            >
                {{ form.errors.hospital_name }}
            </p>
        </div>

        <div>
            <label class="mb-1 block text-[12.5px] font-medium text-ink-700"
                >Address</label
            >
            <textarea
                v-model="form.hospital_address"
                rows="2"
                class="w-full rounded-lg border border-ink-200 px-3 py-2 text-[13px]"
            />
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="mb-1 block text-[12.5px] font-medium text-ink-700"
                    >Phone</label
                >
                <input
                    v-model="form.hospital_phone"
                    type="text"
                    class="w-full rounded-lg border border-ink-200 px-3 py-2 text-[13px]"
                />
            </div>
            <div>
                <label class="mb-1 block text-[12.5px] font-medium text-ink-700"
                    >Email</label
                >
                <input
                    v-model="form.hospital_email"
                    type="email"
                    class="w-full rounded-lg border border-ink-200 px-3 py-2 text-[13px]"
                />
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="mb-1 block text-[12.5px] font-medium text-ink-700"
                    >Slot Duration (minutes) *</label
                >
                <select
                    v-model="form.slot_duration"
                    class="w-full rounded-lg border border-ink-200 px-3 py-2 text-[13px]"
                >
                    <option value="10">10 minutes</option>
                    <option value="15">15 minutes</option>
                    <option value="20">20 minutes</option>
                    <option value="30">30 minutes</option>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-[12.5px] font-medium text-ink-700"
                    >Currency Symbol</label
                >
                <input
                    v-model="form.currency_symbol"
                    type="text"
                    maxlength="5"
                    class="w-full rounded-lg border border-ink-200 px-3 py-2 text-[13px]"
                />
            </div>
        </div>

        <div class="flex items-center gap-2 border-t border-ink-100 pt-4">
            <button
                type="submit"
                :disabled="form.processing"
                class="flex items-center gap-1.5 rounded-full bg-gradient-to-r from-brand-500 to-sky-600 px-5 py-2.5 text-[13px] font-semibold text-white shadow-sm disabled:opacity-50"
            >
                <Save class="size-4" /> Save Settings
            </button>
        </div>
    </form>
</template>
