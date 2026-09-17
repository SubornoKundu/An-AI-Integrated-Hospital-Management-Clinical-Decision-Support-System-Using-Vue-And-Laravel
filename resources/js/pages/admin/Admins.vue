<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { Pencil, Plus, Shield, Trash2, X } from '@lucide/vue';
import { ref } from 'vue';
import PasswordInput from '@/components/PasswordInput.vue';

interface AdminRow {
    id: number;
    name: string;
    email: string;
    username: string;
    is_active: boolean;
    created_at: string;
}

const props = defineProps<{ admins: AdminRow[] }>();

const showCreate = ref(false);
const editingId = ref<number | null>(null);

const createForm = useForm({ name: '', email: '', username: '', password: '', password_confirmation: '' });
const editForm = useForm({ name: '', email: '', username: '', password: '', password_confirmation: '' });

function submitCreate() {
    createForm.post('/admin/admins', {
        preserveScroll: true,
        onSuccess: () => {
            createForm.reset();
            showCreate.value = false;
        },
    });
}

function startEdit(a: AdminRow) {
    editingId.value = a.id;
    editForm.name = a.name;
    editForm.email = a.email;
    editForm.username = a.username;
    editForm.password = '';
    editForm.password_confirmation = '';
}

function submitEdit(id: number) {
    editForm.put(`/admin/admins/${id}`, {
        preserveScroll: true,
        onSuccess: () => (editingId.value = null),
    });
}

function deleteAdmin(a: AdminRow) {
    if (confirm(`Remove admin account "${a.name}"?`)) {
        router.delete(`/admin/admins/${a.id}`, { preserveScroll: true });
    }
}
</script>

<template>
    <Head title="Admin Accounts" />

    <div class="flex flex-col items-start justify-between gap-3 sm:flex-row sm:items-center">
        <div>
            <h1 class="text-xl font-bold text-ink-900">Admin Accounts</h1>
            <p class="mt-1 text-[13px] text-ink-500">{{ props.admins.length }} admin account{{ props.admins.length === 1 ? '' : 's' }}.</p>
        </div>
        <button
            class="flex items-center gap-1.5 rounded-full bg-gradient-to-r from-brand-500 to-sky-600 px-4 py-2.5 text-[13px] font-semibold text-white shadow-sm"
            @click="showCreate = !showCreate"
        >
            <Plus class="size-4" /> Add Admin
        </button>
    </div>

    <form v-if="showCreate" class="space-y-3 rounded-2xl border border-ink-100 bg-white p-4 shadow-sm" @submit.prevent="submitCreate">
        <div class="grid gap-3 sm:grid-cols-2">
            <input v-model="createForm.name" type="text" placeholder="Full name" required class="rounded-lg border border-ink-200 px-3 py-2 text-[13px]" />
            <input v-model="createForm.email" type="email" placeholder="Email" required class="rounded-lg border border-ink-200 px-3 py-2 text-[13px]" />
            <input v-model="createForm.username" type="text" placeholder="Username" required class="rounded-lg border border-ink-200 px-3 py-2 text-[13px]" />
            <PasswordInput v-model="createForm.password" placeholder="Password" required class="rounded-lg border border-ink-200 px-3 py-2 text-[13px]" />
            <PasswordInput v-model="createForm.password_confirmation" placeholder="Confirm password" required class="rounded-lg border border-ink-200 px-3 py-2 text-[13px]" />
        </div>
        <p v-if="Object.keys(createForm.errors).length" class="text-[11.5px] text-red-500">
            {{ Object.values(createForm.errors)[0] }}
        </p>
        <div class="flex gap-2">
            <button type="submit" :disabled="createForm.processing" class="rounded-full bg-brand-500 px-4 py-2 text-[12.5px] font-semibold text-white disabled:opacity-50">Create</button>
            <button type="button" class="rounded-full border border-ink-200 px-4 py-2 text-[12.5px] text-ink-600" @click="showCreate = false">Cancel</button>
        </div>
    </form>

    <div class="space-y-2">
        <div v-for="a in props.admins" :key="a.id" class="rounded-2xl border border-ink-100 bg-white p-4 shadow-sm">
            <div v-if="editingId !== a.id" class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <span class="flex size-9 items-center justify-center rounded-full bg-brand-50 text-brand-600"><Shield class="size-4" /></span>
                    <div>
                        <div class="text-[13.5px] font-medium text-ink-800">{{ a.name }}</div>
                        <div class="text-[11.5px] text-ink-400">{{ a.email }} · @{{ a.username }}</div>
                    </div>
                </div>
                <div class="flex items-center gap-1.5">
                    <button class="rounded-lg p-1.5 text-ink-400 hover:bg-ink-50 hover:text-brand-600" @click="startEdit(a)"><Pencil class="size-4" /></button>
                    <button class="rounded-lg p-1.5 text-ink-400 hover:bg-red-50 hover:text-red-500" @click="deleteAdmin(a)"><Trash2 class="size-4" /></button>
                </div>
            </div>

            <form v-else class="space-y-3" @submit.prevent="submitEdit(a.id)">
                <div class="flex items-center justify-between">
                    <h3 class="text-[12.5px] font-semibold text-ink-700">Editing {{ a.name }}</h3>
                    <button type="button" class="text-ink-400 hover:text-ink-600" @click="editingId = null"><X class="size-4" /></button>
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    <input v-model="editForm.name" type="text" placeholder="Full name" required class="rounded-lg border border-ink-200 px-3 py-2 text-[13px]" />
                    <input v-model="editForm.email" type="email" placeholder="Email" required class="rounded-lg border border-ink-200 px-3 py-2 text-[13px]" />
                    <input v-model="editForm.username" type="text" placeholder="Username" required class="rounded-lg border border-ink-200 px-3 py-2 text-[13px]" />
                    <PasswordInput v-model="editForm.password" placeholder="New password (optional)" class="rounded-lg border border-ink-200 px-3 py-2 text-[13px]" />
                    <PasswordInput v-model="editForm.password_confirmation" placeholder="Confirm new password" class="rounded-lg border border-ink-200 px-3 py-2 text-[13px]" />
                </div>
                <p v-if="Object.keys(editForm.errors).length" class="text-[11.5px] text-red-500">{{ Object.values(editForm.errors)[0] }}</p>
                <button type="submit" :disabled="editForm.processing" class="rounded-full bg-brand-500 px-4 py-2 text-[12.5px] font-semibold text-white disabled:opacity-50">Save</button>
            </form>
        </div>
        <p v-if="!props.admins.length" class="py-10 text-center text-[13px] text-ink-400">No admin accounts found.</p>
    </div>
</template>
