<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { FileEdit, Plus, Trash2 } from '@lucide/vue';
import { ref } from 'vue';

interface TemplateItem {
    id: number;
    template_name: string;
    diagnosis: string;
    medicines: string;
    instructions: string | null;
}

defineProps<{
    templates: TemplateItem[];
}>();

const showForm = ref(false);
const form = useForm({
    id: null as number | null,
    template_name: '',
    diagnosis: '',
    medicines: '',
    instructions: '',
});

function newTemplate() {
    form.reset();
    showForm.value = true;
}

function editTemplate(t: TemplateItem) {
    form.id = t.id;
    form.template_name = t.template_name;
    form.diagnosis = t.diagnosis;
    form.medicines = t.medicines;
    form.instructions = t.instructions ?? '';
    showForm.value = true;
}

function save() {
    form.post('/doctor/templates', {
        preserveScroll: true,
        onSuccess: () => {
            showForm.value = false;
            form.reset();
        },
    });
}

function remove(id: number) {
    if (!confirm('Delete this template?')) {
        return;
    }

    router.delete(`/doctor/templates/${id}`, { preserveScroll: true });
}
</script>

<template>
    <Head title="Prescription Templates" />

    <div class="flex items-center justify-between">
        <h1 class="flex items-center gap-2 text-xl font-bold text-ink-900">
            <FileEdit class="size-5 text-brand-500" /> Prescription Templates
        </h1>
        <button
            type="button"
            class="flex items-center gap-1.5 rounded-full bg-gradient-to-r from-brand-500 to-sky-600 px-4 py-2 text-[12.5px] font-semibold text-white shadow-sm"
            @click="newTemplate"
        >
            <Plus class="size-3.5" /> New Template
        </button>
    </div>

    <div
        v-if="showForm"
        class="rounded-2xl border border-brand-200 bg-white p-4 shadow-sm"
    >
        <h2 class="mb-3 text-[13.5px] font-bold text-ink-900">
            {{ form.id ? 'Edit Template' : 'New Template' }}
        </h2>
        <div class="space-y-3">
            <input
                v-model="form.template_name"
                type="text"
                placeholder="Template name (e.g. Common Cold)"
                class="w-full rounded-lg border border-ink-200 px-3 py-2 text-[13px]"
            />
            <p v-if="form.errors.template_name" class="text-[11.5px] text-red-500">
                {{ form.errors.template_name }}
            </p>
            <textarea
                v-model="form.diagnosis"
                rows="2"
                placeholder="Diagnosis"
                class="w-full rounded-lg border border-ink-200 px-3 py-2 text-[13px]"
            />
            <p v-if="form.errors.diagnosis" class="text-[11.5px] text-red-500">
                {{ form.errors.diagnosis }}
            </p>
            <textarea
                v-model="form.medicines"
                rows="4"
                placeholder="Medicines"
                class="w-full rounded-lg border border-ink-200 px-3 py-2 text-[13px]"
            />
            <p v-if="form.errors.medicines" class="text-[11.5px] text-red-500">
                {{ form.errors.medicines }}
            </p>
            <textarea
                v-model="form.instructions"
                rows="2"
                placeholder="Instructions (optional)"
                class="w-full rounded-lg border border-ink-200 px-3 py-2 text-[13px]"
            />
            <div class="flex gap-2">
                <button
                    type="button"
                    :disabled="form.processing"
                    class="rounded-full bg-gradient-to-r from-brand-500 to-sky-600 px-4 py-2 text-[12.5px] font-semibold text-white disabled:opacity-50"
                    @click="save"
                >
                    Save Template
                </button>
                <button
                    type="button"
                    class="rounded-full border border-ink-200 px-4 py-2 text-[12.5px] font-medium text-ink-600"
                    @click="showForm = false"
                >
                    Cancel
                </button>
            </div>
        </div>
    </div>

    <div v-if="templates.length" class="space-y-3">
        <div
            v-for="t in templates"
            :key="t.id"
            class="rounded-2xl border border-ink-100 bg-white p-4 shadow-sm"
        >
            <div class="flex items-start justify-between gap-3">
                <div>
                    <div class="text-[14px] font-semibold text-ink-900">
                        {{ t.template_name }}
                    </div>
                    <div class="mt-1 text-[12.5px] text-ink-600">
                        <strong>Dx:</strong> {{ t.diagnosis }}
                    </div>
                    <div class="text-[12.5px] text-ink-500">
                        <strong>Rx:</strong> {{ t.medicines }}
                    </div>
                </div>
                <div class="flex shrink-0 gap-1.5">
                    <button
                        type="button"
                        class="rounded-full border border-ink-200 px-3 py-1.5 text-[12px] font-medium text-ink-600 hover:bg-ink-50"
                        @click="editTemplate(t)"
                    >
                        Edit
                    </button>
                    <button
                        type="button"
                        class="rounded-full border border-red-200 p-1.5 text-red-500 hover:bg-red-50"
                        @click="remove(t.id)"
                    >
                        <Trash2 class="size-3.5" />
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div
        v-else
        class="rounded-2xl border border-ink-100 bg-white py-16 text-center text-ink-400 shadow-sm"
    >
        <FileEdit class="mx-auto mb-2 size-10 opacity-20" />
        No templates yet. Create one to speed up prescription writing.
    </div>
</template>
