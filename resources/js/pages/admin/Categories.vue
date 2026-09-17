<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2, X } from '@lucide/vue';
import { ref } from 'vue';

interface CategoryRow {
    id: number;
    name: string;
    icon: string;
    description: string | null;
    is_active: boolean;
    doctors_count: number;
}

const props = defineProps<{
    categories: CategoryRow[];
    editCategory: CategoryRow | null;
}>();

const showCreate = ref(false);
const editingId = ref<number | null>(props.editCategory?.id ?? null);

const createForm = useForm({ name: '', icon: '🩺', description: '' });
const editForm = useForm({
    name: props.editCategory?.name ?? '',
    icon: props.editCategory?.icon ?? '🩺',
    description: props.editCategory?.description ?? '',
    is_active: props.editCategory?.is_active ?? true,
});

function submitCreate() {
    createForm.post('/admin/categories', {
        preserveScroll: true,
        onSuccess: () => {
            createForm.reset();
            showCreate.value = false;
        },
    });
}

function startEdit(c: CategoryRow) {
    editingId.value = c.id;
    editForm.name = c.name;
    editForm.icon = c.icon;
    editForm.description = c.description ?? '';
    editForm.is_active = c.is_active;
}

function submitEdit(id: number) {
    editForm.put(`/admin/categories/${id}`, { preserveScroll: true });
}

function deleteCategory(c: CategoryRow) {
    if (c.doctors_count > 0) {
        return;
    }

    if (confirm(`Delete category "${c.name}"?`)) {
        router.delete(`/admin/categories/${c.id}`, { preserveScroll: true });
    }
}
</script>

<template>
    <Head title="Doctor Categories" />

    <div
        class="flex flex-col items-start justify-between gap-3 sm:flex-row sm:items-center"
    >
        <div>
            <h1 class="text-xl font-bold text-ink-900">Categories</h1>
            <p class="mt-1 text-[13px] text-ink-500">
                {{ props.categories.length }} specialty categories.
            </p>
        </div>
        <button
            class="flex items-center gap-1.5 rounded-full bg-gradient-to-r from-brand-500 to-sky-600 px-4 py-2.5 text-[13px] font-semibold text-white shadow-sm"
            @click="showCreate = !showCreate"
        >
            <Plus class="size-4" /> Add Category
        </button>
    </div>

    <form
        v-if="showCreate"
        class="flex flex-wrap items-start gap-3 rounded-2xl border border-ink-100 bg-white p-4 shadow-sm"
        @submit.prevent="submitCreate"
    >
        <input
            v-model="createForm.icon"
            type="text"
            maxlength="4"
            placeholder="🩺"
            class="w-16 rounded-lg border border-ink-200 px-3 py-2 text-center text-[15px]"
        />
        <input
            v-model="createForm.name"
            type="text"
            placeholder="Category name"
            required
            class="flex-1 rounded-lg border border-ink-200 px-3 py-2 text-[13px]"
        />
        <input
            v-model="createForm.description"
            type="text"
            placeholder="Short description (optional)"
            class="flex-[2] rounded-lg border border-ink-200 px-3 py-2 text-[13px]"
        />
        <button
            type="submit"
            :disabled="createForm.processing"
            class="rounded-full bg-brand-500 px-4 py-2 text-[12.5px] font-semibold text-white disabled:opacity-50"
        >
            Add
        </button>
        <p
            v-if="createForm.errors.name"
            class="w-full text-[11.5px] text-red-500"
        >
            {{ createForm.errors.name }}
        </p>
    </form>

    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
        <div
            v-for="c in props.categories"
            :key="c.id"
            class="rounded-2xl border border-ink-100 bg-white p-4 shadow-sm"
        >
            <div v-if="editingId !== c.id">
                <div class="flex items-start justify-between">
                    <div class="flex items-center gap-2.5">
                        <span
                            class="flex size-9 items-center justify-center rounded-full bg-brand-50 text-lg"
                            >{{ c.icon }}</span
                        >
                        <div>
                            <div
                                class="text-[13.5px] font-semibold text-ink-800"
                            >
                                {{ c.name }}
                            </div>
                            <div class="text-[11px] text-ink-400">
                                {{ c.doctors_count }} doctor{{
                                    c.doctors_count === 1 ? '' : 's'
                                }}
                            </div>
                        </div>
                    </div>
                    <span
                        class="rounded-full px-2 py-0.5 text-[10.5px] font-medium"
                        :class="
                            c.is_active
                                ? 'bg-emerald-50 text-emerald-600'
                                : 'bg-ink-100 text-ink-500'
                        "
                    >
                        {{ c.is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>
                <p v-if="c.description" class="mt-2 text-[12px] text-ink-500">
                    {{ c.description }}
                </p>
                <div
                    class="mt-3 flex items-center gap-1.5 border-t border-ink-50 pt-3"
                >
                    <button
                        class="rounded-lg p-1.5 text-ink-400 hover:bg-ink-50 hover:text-brand-600"
                        @click="startEdit(c)"
                    >
                        <Pencil class="size-4" />
                    </button>
                    <button
                        class="rounded-lg p-1.5"
                        :class="
                            c.doctors_count > 0
                                ? 'cursor-not-allowed text-ink-200'
                                : 'text-ink-400 hover:bg-red-50 hover:text-red-500'
                        "
                        :title="
                            c.doctors_count > 0
                                ? 'Cannot delete — doctors assigned'
                                : 'Delete'
                        "
                        @click="deleteCategory(c)"
                    >
                        <Trash2 class="size-4" />
                    </button>
                </div>
            </div>

            <form v-else class="space-y-2.5" @submit.prevent="submitEdit(c.id)">
                <div class="flex items-center justify-between">
                    <span class="text-[11.5px] font-semibold text-ink-600"
                        >Editing</span
                    >
                    <button
                        type="button"
                        class="text-ink-400 hover:text-ink-600"
                        @click="editingId = null"
                    >
                        <X class="size-4" />
                    </button>
                </div>
                <div class="flex gap-2">
                    <input
                        v-model="editForm.icon"
                        type="text"
                        maxlength="4"
                        class="w-14 rounded-lg border border-ink-200 px-2 py-1.5 text-center text-[14px]"
                    />
                    <input
                        v-model="editForm.name"
                        type="text"
                        required
                        class="flex-1 rounded-lg border border-ink-200 px-2 py-1.5 text-[13px]"
                    />
                </div>
                <input
                    v-model="editForm.description"
                    type="text"
                    placeholder="Description"
                    class="w-full rounded-lg border border-ink-200 px-2 py-1.5 text-[12.5px]"
                />
                <label
                    class="flex items-center gap-1.5 text-[12px] text-ink-600"
                >
                    <input
                        v-model="editForm.is_active"
                        type="checkbox"
                        class="size-3.5 rounded border-ink-300"
                    />
                    Active
                </label>
                <button
                    type="submit"
                    :disabled="editForm.processing"
                    class="w-full rounded-full bg-brand-500 py-1.5 text-[12px] font-semibold text-white disabled:opacity-50"
                >
                    Save
                </button>
            </form>
        </div>
    </div>
</template>
