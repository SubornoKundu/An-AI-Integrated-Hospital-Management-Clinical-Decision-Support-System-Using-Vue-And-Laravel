<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { Download, FileText, Trash2, Upload } from '@lucide/vue';
import { ref } from 'vue';

interface Doc {
    id: number;
    file_name: string;
    doc_type: 'lab_report' | 'xray' | 'prescription' | 'other';
    notes: string | null;
    file_type: string | null;
    appointment_date: string | null;
    uploaded_at: string;
}

defineProps<{
    documents: Doc[];
}>();

const showForm = ref(false);

const form = useForm<{
    file: File | null;
    doc_type: string;
    notes: string;
}>({
    file: null,
    doc_type: 'lab_report',
    notes: '',
});

const docTypeLabels: Record<string, string> = {
    lab_report: 'Lab Report',
    xray: 'X-Ray / Scan',
    prescription: 'Prescription',
    other: 'Other',
};

function onFileChange(e: Event) {
    const target = e.target as HTMLInputElement;
    form.file = target.files?.[0] ?? null;
}

function submit() {
    form.post('/patient/documents', {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            showForm.value = false;
        },
    });
}

function destroy(id: number) {
    if (!confirm('Delete this document? This cannot be undone.')) {
return;
}

    router.delete(`/patient/documents/${id}`, { preserveScroll: true });
}
</script>

<template>
    <Head title="Health Documents" />

    <div class="flex flex-wrap items-center justify-between gap-2">
        <h1 class="text-xl font-bold text-ink-900">📁 Health Documents</h1>
        <button
            class="flex items-center gap-1.5 rounded-full bg-gradient-to-r from-brand-500 to-sky-600 px-4 py-2 text-[13px] font-semibold text-white shadow-sm"
            @click="showForm = !showForm"
        >
            <Upload class="size-4" />
            {{ showForm ? 'Cancel' : 'Upload Document' }}
        </button>
    </div>
    <p class="mt-1 text-[13px] text-ink-500">
        Keep lab reports, X-rays, and old prescriptions in one place — only
        you can see these.
    </p>

    <!-- Upload form -->
    <form
        v-if="showForm"
        class="mt-4 space-y-4 rounded-2xl border border-ink-100 bg-white p-5 shadow-sm"
        @submit.prevent="submit"
    >
        <div>
            <label class="mb-1.5 block text-[12.5px] font-medium text-ink-700"
                >File (PDF or image, max 10MB)</label
            >
            <input
                type="file"
                accept=".pdf,.jpg,.jpeg,.png,.webp"
                class="block w-full rounded-xl border border-ink-100 px-3 py-2 text-[13px] text-ink-700 file:mr-3 file:rounded-full file:border-0 file:bg-brand-50 file:px-3 file:py-1.5 file:text-[12.5px] file:font-medium file:text-brand-700"
                @change="onFileChange"
            />
            <p v-if="form.errors.file" class="mt-1 text-[12px] text-red-500">
                {{ form.errors.file }}
            </p>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label
                    class="mb-1.5 block text-[12.5px] font-medium text-ink-700"
                    >Document type</label
                >
                <select
                    v-model="form.doc_type"
                    class="w-full rounded-xl border border-ink-100 px-3 py-2 text-[13px] text-ink-700"
                >
                    <option value="lab_report">Lab Report</option>
                    <option value="xray">X-Ray / Scan</option>
                    <option value="prescription">Prescription</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div>
                <label
                    class="mb-1.5 block text-[12.5px] font-medium text-ink-700"
                    >Notes (optional)</label
                >
                <input
                    v-model="form.notes"
                    type="text"
                    maxlength="300"
                    placeholder="e.g. Blood test, Aug 2026"
                    class="w-full rounded-xl border border-ink-100 px-3 py-2 text-[13px] text-ink-700"
                />
            </div>
        </div>

        <button
            type="submit"
            :disabled="form.processing || !form.file"
            class="rounded-full bg-brand-600 px-5 py-2 text-[13px] font-semibold text-white hover:bg-brand-700 disabled:opacity-50"
        >
            {{ form.processing ? 'Uploading…' : 'Upload' }}
        </button>
    </form>

    <!-- Document list -->
    <div v-if="documents.length" class="mt-5 space-y-3">
        <div
            v-for="d in documents"
            :key="d.id"
            class="flex items-start justify-between gap-3 rounded-2xl border border-ink-100 bg-white p-4 shadow-sm"
        >
            <div class="flex items-start gap-3">
                <div
                    class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600"
                >
                    <FileText class="size-5" />
                </div>
                <div>
                    <div class="text-[14px] font-semibold text-ink-900">
                        {{ d.file_name }}
                    </div>
                    <div
                        class="mt-0.5 flex flex-wrap items-center gap-1.5 text-[12px] text-ink-500"
                    >
                        <span
                            class="rounded-full bg-ink-50 px-2 py-0.5 font-medium text-ink-600"
                            >{{ docTypeLabels[d.doc_type] }}</span
                        >
                        <span>· Uploaded {{ d.uploaded_at }}</span>
                        <span v-if="d.appointment_date"
                            >· Visit on {{ d.appointment_date }}</span
                        >
                    </div>
                    <p v-if="d.notes" class="mt-1 text-[12.5px] text-ink-500">
                        {{ d.notes }}
                    </p>
                </div>
            </div>
            <div class="flex shrink-0 items-center gap-1">
                <a
                    :href="`/patient/documents/${d.id}/download`"
                    class="rounded-full p-2 text-ink-400 hover:bg-ink-50 hover:text-brand-600"
                    title="Download"
                >
                    <Download class="size-4" />
                </a>
                <button
                    class="rounded-full p-2 text-ink-400 hover:bg-red-50 hover:text-red-500"
                    title="Delete"
                    @click="destroy(d.id)"
                >
                    <Trash2 class="size-4" />
                </button>
            </div>
        </div>
    </div>
    <div
        v-else
        class="mt-5 rounded-2xl border border-ink-100 bg-white py-16 text-center text-ink-400 shadow-sm"
    >
        <div class="mb-2 text-4xl opacity-20">📁</div>
        No documents uploaded yet.
    </div>
</template>
