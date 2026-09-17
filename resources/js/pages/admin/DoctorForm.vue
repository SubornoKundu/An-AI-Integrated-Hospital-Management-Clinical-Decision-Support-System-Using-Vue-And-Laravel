<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Save } from '@lucide/vue';
import { computed } from 'vue';
import PasswordInput from '@/components/PasswordInput.vue';

interface DoctorData {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    category_id: number | null;
    consultancy_fee: number;
    bio: string | null;
    gender: string | null;
    is_active: boolean;
    photo: string | null;
}

const props = defineProps<{
    categories: { id: number; name: string; icon: string }[];
    doctor: DoctorData | null;
}>();

const isEdit = computed(() => props.doctor !== null);

const form = useForm({
    name: props.doctor?.name ?? '',
    email: props.doctor?.email ?? '',
    password: '',
    category_id: props.doctor?.category_id ?? '',
    consultancy_fee: props.doctor?.consultancy_fee ?? 500,
    bio: props.doctor?.bio ?? '',
    phone: props.doctor?.phone ?? '',
    gender: props.doctor?.gender ?? '',
    is_active: props.doctor?.is_active ?? true,
});

function submit() {
    if (isEdit.value) {
        form.put(`/admin/doctors/${props.doctor!.id}`);
    } else {
        form.post('/admin/doctors');
    }
}
</script>

<template>
    <Head :title="isEdit ? 'Edit Doctor' : 'Add Doctor'" />

    <div class="flex items-center gap-3">
        <Link
            href="/admin/doctors"
            class="rounded-full border border-ink-200 p-2 text-ink-500 hover:bg-ink-50"
            ><ArrowLeft class="size-4"
        /></Link>
        <img
            v-if="isEdit && props.doctor?.photo"
            :src="props.doctor.photo"
            :alt="props.doctor.name"
            class="size-12 rounded-full object-cover"
        />
        <div>
            <h1 class="text-xl font-bold text-ink-900">
                {{ isEdit ? 'Edit Doctor' : 'Add New Doctor' }}
            </h1>
            <p class="mt-0.5 text-[13px] text-ink-500">
                {{ isEdit ? `Updating Dr. ${props.doctor?.name}` : 'Create a new doctor account.' }}
            </p>
        </div>
    </div>

    <form
        class="max-w-2xl space-y-4 rounded-2xl border border-ink-100 bg-white p-5 shadow-sm"
        @submit.prevent="submit"
    >
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="mb-1 block text-[12.5px] font-medium text-ink-700">Full Name *</label>
                <input v-model="form.name" type="text" required class="w-full rounded-lg border border-ink-200 px-3 py-2 text-[13px]" />
                <p v-if="form.errors.name" class="mt-1 text-[11.5px] text-red-500">{{ form.errors.name }}</p>
            </div>
            <div>
                <label class="mb-1 block text-[12.5px] font-medium text-ink-700">Email *</label>
                <input v-model="form.email" type="email" required class="w-full rounded-lg border border-ink-200 px-3 py-2 text-[13px]" />
                <p v-if="form.errors.email" class="mt-1 text-[11.5px] text-red-500">{{ form.errors.email }}</p>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="mb-1 block text-[12.5px] font-medium text-ink-700">
                    {{ isEdit ? 'New Password' : 'Password *' }}
                </label>
                <PasswordInput
                    v-model="form.password"
                    :required="!isEdit"
                    :placeholder="isEdit ? 'Leave blank to keep current' : ''"
                    autocomplete="new-password"
                    class="w-full rounded-lg border border-ink-200 px-3 py-2 text-[13px]"
                />
                <p v-if="form.errors.password" class="mt-1 text-[11.5px] text-red-500">{{ form.errors.password }}</p>
            </div>
            <div>
                <label class="mb-1 block text-[12.5px] font-medium text-ink-700">Phone</label>
                <input v-model="form.phone" type="text" placeholder="01700-000000" class="w-full rounded-lg border border-ink-200 px-3 py-2 text-[13px]" />
                <p v-if="form.errors.phone" class="mt-1 text-[11.5px] text-red-500">{{ form.errors.phone }}</p>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="mb-1 block text-[12.5px] font-medium text-ink-700">Category</label>
                <select v-model="form.category_id" class="w-full rounded-lg border border-ink-200 px-3 py-2 text-[13px]">
                    <option value="">— Select —</option>
                    <option v-for="c in props.categories" :key="c.id" :value="c.id">{{ c.icon }} {{ c.name }}</option>
                </select>
                <p v-if="form.errors.category_id" class="mt-1 text-[11.5px] text-red-500">{{ form.errors.category_id }}</p>
            </div>
            <div>
                <label class="mb-1 block text-[12.5px] font-medium text-ink-700">Consultancy Fee (৳) *</label>
                <input v-model.number="form.consultancy_fee" type="number" min="0" step="1" required class="w-full rounded-lg border border-ink-200 px-3 py-2 text-[13px]" />
                <p v-if="form.errors.consultancy_fee" class="mt-1 text-[11.5px] text-red-500">{{ form.errors.consultancy_fee }}</p>
            </div>
        </div>

        <div>
            <label class="mb-1 block text-[12.5px] font-medium text-ink-700">Gender</label>
            <select v-model="form.gender" class="w-full max-w-xs rounded-lg border border-ink-200 px-3 py-2 text-[13px]">
                <option value="">— Not specified —</option>
                <option value="Male">Male</option>
                <option value="Female">Female</option>
                <option value="Other">Other</option>
            </select>
            <p class="mt-1 text-[11px] text-ink-400">
                Used to style the placeholder avatar until a real photo is uploaded.
            </p>
        </div>

        <div>
            <label class="mb-1 block text-[12.5px] font-medium text-ink-700">Bio</label>
            <textarea v-model="form.bio" rows="3" maxlength="400" placeholder="Qualifications, experience, areas of expertise…" class="w-full rounded-lg border border-ink-200 px-3 py-2 text-[13px]" />
            <p v-if="form.errors.bio" class="mt-1 text-[11.5px] text-red-500">{{ form.errors.bio }}</p>
        </div>

        <label v-if="isEdit" class="flex items-center gap-2 text-[13px] text-ink-700">
            <input v-model="form.is_active" type="checkbox" class="size-4 rounded border-ink-300" />
            Active (doctor can log in and receive bookings)
        </label>

        <div class="flex items-center gap-2 border-t border-ink-100 pt-4">
            <button
                type="submit"
                :disabled="form.processing"
                class="flex items-center gap-1.5 rounded-full bg-gradient-to-r from-brand-500 to-sky-600 px-5 py-2.5 text-[13px] font-semibold text-white shadow-sm disabled:opacity-50"
            >
                <Save class="size-4" /> {{ isEdit ? 'Save Changes' : 'Create Doctor' }}
            </button>
            <Link href="/admin/doctors" class="rounded-full border border-ink-200 px-5 py-2.5 text-[13px] font-medium text-ink-600 hover:bg-ink-50">
                Cancel
            </Link>
        </div>
    </form>
</template>
