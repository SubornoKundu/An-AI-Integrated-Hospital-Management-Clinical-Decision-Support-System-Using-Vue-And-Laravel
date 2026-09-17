<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Search } from '@lucide/vue';
import { onBeforeUnmount, ref, watch } from 'vue';

interface PatientRow {
    id: number;
    full_name: string;
    email: string;
    contact: string | null;
    gender: string | null;
    blood_group: string | null;
    photo: string | null;
}

interface PatientSuggestion {
    id: number;
    name: string;
    email: string;
    phone: string;
    photo: string | null;
}

const props = defineProps<{
    patients: {
        data: PatientRow[];
        links: { url: string | null; label: string; active: boolean }[];
        from: number | null;
        to: number | null;
        total: number;
    };
    filters: { search?: string };
}>();

const search = ref(props.filters.search ?? '');

function applyFilters() {
    showDropdown.value = false;
    router.get('/admin/patients', { search: search.value || undefined }, { preserveState: true, replace: true });
}

// ── Live name suggestions as you type (same pattern as patient/Search.vue
// and admin/Doctors.vue, hitting the /admin/patients/suggest endpoint that
// already existed but wasn't wired up to this page yet) ──
const suggestions = ref<PatientSuggestion[]>([]);
const showDropdown = ref(false);
let debounceTimer: ReturnType<typeof setTimeout> | undefined;
let requestSeq = 0;

watch(search, (val) => {
    clearTimeout(debounceTimer);

    if (!val.trim()) {
        suggestions.value = [];
        showDropdown.value = false;

        return;
    }

    debounceTimer = setTimeout(async () => {
        const seq = ++requestSeq;
        let result: PatientSuggestion[] = [];

        try {
            const res = await fetch(`/admin/patients/suggest?q=${encodeURIComponent(val)}`);
            result = res.ok ? await res.json() : [];
        } catch {
            result = [];
        }

        // A newer keystroke may have started another request while this one
        // was in flight — only apply this result if it's still current.
        if (seq === requestSeq) {
            suggestions.value = result;
            showDropdown.value = suggestions.value.length > 0;
        }
    }, 180);
});

onBeforeUnmount(() => clearTimeout(debounceTimer));

function hideDropdownSoon() {
    // Delay closing so a click on a suggestion (mousedown) still
    // registers before the dropdown disappears on blur.
    setTimeout(() => (showDropdown.value = false), 150);
}

function pickSuggestion(s: PatientSuggestion) {
    search.value = s.name;
    showDropdown.value = false;
    applyFilters();
}
</script>

<template>
    <Head title="Manage Patients" />

    <div>
        <h1 class="text-xl font-bold text-ink-900">Patients</h1>
        <p class="mt-1 text-[13px] text-ink-500">
            {{ props.patients.total }} patient{{ props.patients.total === 1 ? '' : 's' }} registered.
        </p>
    </div>

    <div class="relative max-w-md">
        <Search class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-ink-300" />
        <input
            v-model="search"
            type="text"
            placeholder="Search by name or email…"
            aria-label="Search patients by name or email"
            autocomplete="off"
            class="w-full rounded-lg border border-ink-200 py-2.5 pr-3 pl-9 text-[13px]"
            @keyup.enter="applyFilters"
            @focus="showDropdown = suggestions.length > 0"
            @blur="hideDropdownSoon"
        />
        <div
            v-if="showDropdown"
            class="absolute top-[calc(100%+4px)] right-0 left-0 z-30 max-h-72 overflow-y-auto rounded-xl border border-ink-100 bg-white shadow-lg"
        >
            <button
                v-for="s in suggestions"
                :key="s.id"
                type="button"
                class="flex w-full items-center gap-2.5 border-b border-ink-50 px-3.5 py-2.5 text-left last:border-0 hover:bg-ink-50"
                @mousedown.prevent="pickSuggestion(s)"
            >
                <img v-if="s.photo" :src="s.photo" :alt="s.name" class="size-8 shrink-0 rounded-full object-cover" />
                <span v-else class="flex size-8 shrink-0 items-center justify-center rounded-full bg-brand-50 text-[13px] font-semibold text-brand-600">{{ s.name.charAt(0).toUpperCase() }}</span>
                <span class="min-w-0 flex-1">
                    <span class="block truncate text-[13px] font-semibold text-ink-900">{{ s.name }}</span>
                    <span class="block truncate text-[11.5px] text-ink-400">{{ s.email }}{{ s.phone ? ' · ' + s.phone : '' }}</span>
                </span>
            </button>
        </div>
    </div>

    <div class="overflow-x-auto rounded-2xl border border-ink-100 bg-white shadow-sm">
        <table class="w-full text-left text-[13px]">
            <thead class="border-b border-ink-100 bg-ink-25 text-[11.5px] text-ink-500">
                <tr>
                    <th class="px-4 py-3 font-medium">Patient</th>
                    <th class="px-4 py-3 font-medium">Contact</th>
                    <th class="px-4 py-3 font-medium">Gender</th>
                    <th class="px-4 py-3 font-medium">Blood Group</th>
                    <th class="px-4 py-3 font-medium text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-ink-50">
                <tr v-for="p in props.patients.data" :key="p.id">
                    <td class="px-4 py-3 font-medium text-ink-800">
                        <div class="flex items-center gap-2.5">
                            <img v-if="p.photo" :src="p.photo" :alt="p.full_name" class="size-8 rounded-full object-cover" />
                            <span v-else class="flex size-8 items-center justify-center rounded-full bg-brand-50 text-[12px] font-semibold text-brand-600">{{ p.full_name.charAt(0).toUpperCase() }}</span>
                            <span>{{ p.full_name }}</span>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-ink-500">
                        <div>{{ p.email }}</div>
                        <div class="text-[11.5px] text-ink-400">{{ p.contact || '—' }}</div>
                    </td>
                    <td class="px-4 py-3 text-ink-600">{{ p.gender || '—' }}</td>
                    <td class="px-4 py-3">
                        <span v-if="p.blood_group" class="rounded-full bg-red-50 px-2 py-0.5 text-[11px] font-medium text-red-600">{{ p.blood_group }}</span>
                        <span v-else class="text-ink-300">—</span>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <Link :href="`/admin/patients/${p.id}`" class="text-[12.5px] font-medium text-brand-600 hover:underline">View →</Link>
                    </td>
                </tr>
                <tr v-if="!props.patients.data.length">
                    <td colspan="5" class="px-4 py-10 text-center text-ink-400">No patients found.</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div v-if="props.patients.links.length > 3" class="flex flex-wrap items-center justify-between gap-2">
        <p class="text-[12.5px] text-ink-500">
            Showing {{ props.patients.from ?? 0 }}–{{ props.patients.to ?? 0 }} of {{ props.patients.total }}
        </p>
        <div class="flex flex-wrap gap-1">
            <Link
                v-for="(link, i) in props.patients.links"
                :key="i"
                :href="link.url ?? '#'"
                class="rounded-lg px-3 py-1.5 text-[12.5px]"
                :class="[
                    link.active ? 'bg-brand-500 text-white' : 'text-ink-500 hover:bg-ink-50',
                    !link.url ? 'pointer-events-none opacity-40' : '',
                ]"
            >
                <span v-html="link.label" />
            </Link>
        </div>
    </div>
</template>
