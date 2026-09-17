<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Pencil, Plus, Search, Trash2, Calendar } from '@lucide/vue';
import { onBeforeUnmount, ref, watch } from 'vue';

interface DoctorRow {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    category: string;
    icon: string;
    photo: string | null;
    consultancy_fee: number;
    is_active: boolean;
}

interface DoctorSuggestion {
    id: number;
    name: string;
    category: string;
    icon: string;
    photo: string | null;
}

const props = defineProps<{
    doctors: {
        data: DoctorRow[];
        links: { url: string | null; label: string; active: boolean }[];
        from: number | null;
        to: number | null;
        total: number;
    };
    categories: { id: number; name: string; icon: string }[];
    filters: { search?: string; category?: string };
}>();

const search = ref(props.filters.search ?? '');
const category = ref(props.filters.category ?? '');

function applyFilters() {
    showDropdown.value = false;
    router.get(
        '/admin/doctors',
        { search: search.value || undefined, category: category.value || undefined },
        { preserveState: true, replace: true },
    );
}

// ── Live name suggestions as you type (same pattern as patient/Search.vue,
// hitting the /admin/doctors/suggest endpoint that already existed but
// wasn't wired up to this page yet) ──
const suggestions = ref<DoctorSuggestion[]>([]);
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
        let result: DoctorSuggestion[] = [];

        try {
            const res = await fetch(`/admin/doctors/suggest?q=${encodeURIComponent(val)}`);
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

function pickSuggestion(s: DoctorSuggestion) {
    search.value = s.name;
    showDropdown.value = false;
    applyFilters();
}

function deleteDoctor(doctor: DoctorRow) {
    if (confirm(`Delete Dr. ${doctor.name}? This cannot be undone.`)) {
        router.delete(`/admin/doctors/${doctor.id}`);
    }
}
</script>

<template>
    <Head title="Manage Doctors" />

    <div class="flex flex-col items-start justify-between gap-3 sm:flex-row sm:items-center">
        <div>
            <h1 class="text-xl font-bold text-ink-900">Doctors</h1>
            <p class="mt-1 text-[13px] text-ink-500">
                {{ props.doctors.total }} doctor{{ props.doctors.total === 1 ? '' : 's' }} registered.
            </p>
        </div>
        <Link
            href="/admin/doctors/create"
            class="flex items-center gap-1.5 rounded-full bg-gradient-to-r from-brand-500 to-sky-600 px-4 py-2.5 text-[13px] font-semibold text-white shadow-sm"
        >
            <Plus class="size-4" /> Add Doctor
        </Link>
    </div>

    <div class="flex flex-col gap-2 sm:flex-row">
        <div class="relative flex-1">
            <Search class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-ink-300" />
            <input
                v-model="search"
                type="text"
                placeholder="Search by name…"
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
                    <span v-else class="flex size-8 shrink-0 items-center justify-center rounded-full bg-brand-50 text-sm">{{ s.icon }}</span>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-[13px] font-semibold text-ink-900">Dr. {{ s.name }}</span>
                        <span class="block text-[11.5px] text-ink-400">{{ s.category }}</span>
                    </span>
                </button>
            </div>
        </div>
        <select
            v-model="category"
            class="rounded-lg border border-ink-200 px-3 py-2.5 text-[13px]"
            @change="applyFilters"
        >
            <option value="">All Categories</option>
            <option v-for="c in props.categories" :key="c.id" :value="c.id">
                {{ c.icon }} {{ c.name }}
            </option>
        </select>
        <button
            class="rounded-lg border border-ink-200 px-4 py-2.5 text-[13px] font-medium text-ink-600 hover:bg-ink-50"
            @click="applyFilters"
        >
            Search
        </button>
    </div>

    <div class="overflow-x-auto rounded-2xl border border-ink-100 bg-white shadow-sm">
        <table class="w-full text-left text-[13px]">
            <thead class="border-b border-ink-100 bg-ink-25 text-[11.5px] text-ink-500">
                <tr>
                    <th class="px-4 py-3 font-medium">Doctor</th>
                    <th class="px-4 py-3 font-medium">Category</th>
                    <th class="px-4 py-3 font-medium">Contact</th>
                    <th class="px-4 py-3 font-medium">Fee</th>
                    <th class="px-4 py-3 font-medium">Status</th>
                    <th class="px-4 py-3 font-medium text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-ink-50">
                <tr v-for="d in props.doctors.data" :key="d.id">
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2.5">
                            <img v-if="d.photo" :src="d.photo" :alt="d.name" class="size-8 rounded-full object-cover" />
                            <span v-else class="flex size-8 items-center justify-center rounded-full bg-brand-50 text-sm">{{ d.icon }}</span>
                            <span class="font-medium text-ink-800">Dr. {{ d.name }}</span>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-ink-600">{{ d.category }}</td>
                    <td class="px-4 py-3 text-ink-500">
                        <div>{{ d.email }}</div>
                        <div class="text-[11.5px] text-ink-400">{{ d.phone || '—' }}</div>
                    </td>
                    <td class="px-4 py-3 text-ink-700">৳{{ d.consultancy_fee.toLocaleString() }}</td>
                    <td class="px-4 py-3">
                        <span
                            class="rounded-full px-2 py-0.5 text-[11px] font-medium"
                            :class="d.is_active ? 'bg-emerald-50 text-emerald-600' : 'bg-ink-100 text-ink-500'"
                            >{{ d.is_active ? 'Active' : 'Inactive' }}</span
                        >
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center justify-end gap-1.5">
                            <Link
                                :href="`/admin/doctors/${d.id}/schedule`"
                                class="rounded-lg p-1.5 text-ink-400 hover:bg-ink-50 hover:text-brand-600"
                                title="Schedule"
                                ><Calendar class="size-4"
                            /></Link>
                            <Link
                                :href="`/admin/doctors/${d.id}/edit`"
                                class="rounded-lg p-1.5 text-ink-400 hover:bg-ink-50 hover:text-brand-600"
                                title="Edit"
                                ><Pencil class="size-4"
                            /></Link>
                            <button
                                class="rounded-lg p-1.5 text-ink-400 hover:bg-red-50 hover:text-red-500"
                                title="Delete"
                                @click="deleteDoctor(d)"
                            >
                                <Trash2 class="size-4" />
                            </button>
                        </div>
                    </td>
                </tr>
                <tr v-if="!props.doctors.data.length">
                    <td colspan="6" class="px-4 py-10 text-center text-ink-400">
                        No doctors found.
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div v-if="props.doctors.links.length > 3" class="flex flex-wrap items-center justify-between gap-2">
        <p class="text-[12.5px] text-ink-500">
            Showing {{ props.doctors.from ?? 0 }}–{{ props.doctors.to ?? 0 }} of {{ props.doctors.total }}
        </p>
        <div class="flex flex-wrap gap-1">
            <Link
                v-for="(link, i) in props.doctors.links"
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
