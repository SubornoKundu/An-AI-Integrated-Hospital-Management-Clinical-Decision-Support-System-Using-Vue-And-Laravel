<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Heart, Phone, Search as SearchIcon, Star } from '@lucide/vue';
import { onBeforeUnmount, ref, watch } from 'vue';

interface DoctorResult {
    id: number;
    name: string;
    bio: string | null;
    phone: string | null;
    category: string;
    icon: string;
    photo: string | null;
    consultancy_fee: number;
    avg_rating: number;
    rating_count: number;
}
interface Category {
    id: number;
    name: string;
    icon: string;
}
interface Suggestion {
    id: number;
    name: string;
    category: string;
    icon: string;
    photo: string | null;
    fee: string;
}

const props = defineProps<{
    doctors: DoctorResult[];
    categories: Category[];
    filters: { q?: string; category?: string };
}>();

const q = ref(props.filters.q ?? '');
const category = ref(props.filters.category ?? '');
const suggestions = ref<Suggestion[]>([]);
const showDropdown = ref(false);
let debounceTimer: ReturnType<typeof setTimeout> | undefined;
let requestSeq = 0;

watch(q, (val) => {
    clearTimeout(debounceTimer);

    if (!val.trim()) {
        suggestions.value = [];
        showDropdown.value = false;

        return;
    }

    debounceTimer = setTimeout(async () => {
        const seq = ++requestSeq;
        let result: Suggestion[] = [];

        try {
            const res = await fetch(
                `/patient/doctors/suggest?q=${encodeURIComponent(val)}`,
            );
            result = res.ok ? await res.json() : [];
        } catch {
            result = [];
        }

        // A newer keystroke may have started another request while this
        // one was in flight — only apply this result if it's still current.
        if (seq === requestSeq) {
            suggestions.value = result;
            showDropdown.value = suggestions.value.length > 0;
        }
    }, 180);
});

onBeforeUnmount(() => clearTimeout(debounceTimer));

function hideDropdownSoon() {
    setTimeout(() => (showDropdown.value = false), 150);
}

function submitSearch() {
    showDropdown.value = false;
    router.get(
        '/patient/search',
        { q: q.value || undefined, category: category.value || undefined },
        { preserveState: true },
    );
}

function pickSuggestion(s: Suggestion) {
    q.value = s.name;
    showDropdown.value = false;
    submitSearch();
}

function reset() {
    q.value = '';
    category.value = '';
    router.get('/patient/search');
}

function toggleFavourite(doctorId: number) {
    router.post(
        `/patient/favourites/${doctorId}`,
        {},
        { preserveScroll: true },
    );
}

function fmtTaka(n: number) {
    return new Intl.NumberFormat('en-BD').format(Math.round(n));
}

const hasSearched = props.filters.q || props.filters.category;
</script>

<template>
    <Head title="Find a Doctor" />

    <h1 class="text-xl font-bold text-ink-900">🔍 Find a Doctor</h1>

    <div class="rounded-2xl border border-ink-100 bg-white p-4 shadow-sm">
        <form class="flex flex-wrap gap-2" @submit.prevent="submitSearch">
            <div class="relative min-w-[220px] flex-1">
                <SearchIcon
                    class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-ink-300"
                />
                <input
                    v-model="q"
                    type="text"
                    placeholder="Search by doctor name or specialty..."
                    autocomplete="off"
                    class="h-10 w-full rounded-md border border-input bg-transparent py-2 pr-3 pl-9 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
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
                        class="flex w-full items-center gap-3 border-b border-ink-50 px-3.5 py-2.5 text-left last:border-0 hover:bg-ink-50"
                        @mousedown.prevent="pickSuggestion(s)"
                    >
                        <img
                            v-if="s.photo"
                            :src="s.photo"
                            :alt="s.name"
                            class="size-8 shrink-0 rounded-full object-cover"
                        />
                        <span v-else class="text-lg">{{ s.icon }}</span>
                        <span class="min-w-0 flex-1">
                            <span
                                class="block truncate text-[13.5px] font-semibold text-ink-900"
                                >{{ s.name }}</span
                            >
                            <span class="block text-[11.5px] text-ink-400">{{
                                s.category
                            }}</span>
                        </span>
                        <span
                            class="shrink-0 font-mono-brand text-[12px] text-brand-600"
                            >৳{{ s.fee }}</span
                        >
                    </button>
                </div>
            </div>
            <select
                v-model="category"
                class="h-10 rounded-md border border-input bg-transparent px-3 text-sm outline-none"
            >
                <option value="">All Specialties</option>
                <option v-for="c in categories" :key="c.id" :value="c.id">
                    {{ c.icon }} {{ c.name }}
                </option>
            </select>
            <button
                type="submit"
                class="rounded-md bg-brand-600 px-5 text-sm font-semibold text-white hover:bg-brand-700"
            >
                Search
            </button>
            <button
                type="button"
                class="rounded-md border border-ink-200 px-4 text-sm font-medium text-ink-600 hover:bg-ink-50"
                @click="reset"
            >
                Reset
            </button>
        </form>
    </div>

    <template v-if="hasSearched">
        <p class="text-[13px] text-ink-500">
            Found {{ doctors.length }} doctor{{
                doctors.length !== 1 ? 's' : ''
            }}
            <template v-if="filters.q"
                >for "<strong class="text-ink-800">{{ filters.q }}</strong
                >"</template
            >
        </p>
        <div
            v-if="doctors.length"
            class="grid grid-cols-1 gap-4 sm:grid-cols-2"
        >
            <div
                v-for="d in doctors"
                :key="d.id"
                class="flex items-start gap-3 rounded-2xl border border-ink-100 bg-white p-4 shadow-sm"
            >
                <img
                    v-if="d.photo"
                    :src="d.photo"
                    :alt="d.name"
                    class="size-11 shrink-0 rounded-xl object-cover ring-1 ring-brand-100"
                />
                <div
                    v-else
                    class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-lg ring-1 ring-brand-100"
                >
                    {{ d.icon }}
                </div>
                <div class="min-w-0 flex-1">
                    <div class="text-[14.5px] font-semibold text-ink-900">
                        {{ d.name }}
                    </div>
                    <div class="text-[12.5px] text-brand-600">
                        {{ d.category }}
                    </div>
                    <p
                        v-if="d.bio"
                        class="mt-1 line-clamp-2 text-[12.5px] text-ink-500"
                    >
                        {{ d.bio }}
                    </p>
                    <div
                        class="mt-2 flex flex-wrap items-center gap-3 text-[12px]"
                    >
                        <span
                            class="font-mono-brand font-semibold text-brand-600"
                            >৳{{ fmtTaka(d.consultancy_fee) }}</span
                        >
                        <span
                            v-if="d.phone"
                            class="flex items-center gap-1 text-ink-400"
                            ><Phone class="size-3" /> {{ d.phone }}</span
                        >
                        <span
                            v-if="d.avg_rating > 0"
                            class="flex items-center gap-1 text-amber-500"
                        >
                            <Star class="size-3 fill-amber-400" />
                            {{ d.avg_rating }} ({{ d.rating_count }})
                        </span>
                        <span v-else class="text-ink-400">No reviews yet</span>
                    </div>
                </div>
                <div class="flex shrink-0 flex-col gap-1.5">
                    <Link
                        :href="`/patient/book/slot?doctor_id=${d.id}`"
                        class="rounded-full bg-brand-600 px-4 py-1.5 text-center text-[12.5px] font-semibold text-white hover:bg-brand-700"
                        >Book →</Link
                    >
                    <button
                        class="flex items-center justify-center gap-1 rounded-full border border-ink-200 px-4 py-1.5 text-[12.5px] font-medium text-ink-600 hover:bg-ink-50"
                        @click="toggleFavourite(d.id)"
                    >
                        <Heart class="size-3.5" /> Save
                    </button>
                </div>
            </div>
        </div>
        <div
            v-else
            class="rounded-2xl border border-ink-100 bg-white py-16 text-center text-ink-400 shadow-sm"
        >
            <div class="mb-2 text-4xl opacity-20">🔍</div>
            No doctors found matching your search.
        </div>
    </template>

    <template v-else>
        <div class="rounded-2xl border border-ink-100 bg-white p-5 shadow-sm">
            <h2 class="mb-4 text-sm font-semibold text-ink-700">
                Browse by Specialty
            </h2>
            <div
                class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6"
            >
                <Link
                    v-for="c in categories"
                    :key="c.id"
                    :href="`/patient/search?category=${c.id}`"
                    class="flex flex-col items-center gap-2 rounded-2xl border border-ink-100 bg-ink-50/40 px-3 py-5 text-center transition-all hover:-translate-y-1 hover:border-brand-300 hover:bg-brand-50/50"
                >
                    <span class="text-2xl">{{ c.icon }}</span>
                    <span
                        class="text-[12.5px] leading-tight font-semibold text-ink-800"
                        >{{ c.name }}</span
                    >
                </Link>
            </div>
        </div>
    </template>
</template>
