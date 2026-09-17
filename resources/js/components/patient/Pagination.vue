<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

interface PageLink {
    url: string | null;
    label: string;
    active: boolean;
}

defineProps<{
    links: PageLink[];
}>();
</script>

<template>
    <nav
        v-if="links.length > 3"
        class="mt-5 flex flex-wrap items-center justify-center gap-1"
    >
        <template v-for="(link, i) in links" :key="i">
            <span
                v-if="!link.url"
                class="rounded-lg px-3 py-1.5 text-[13px] text-ink-300"
                v-html="link.label"
            />
            <Link
                v-else
                :href="link.url"
                preserve-scroll
                class="rounded-lg px-3 py-1.5 text-[13px] font-medium transition-colors"
                :class="
                    link.active
                        ? 'bg-brand-600 text-white'
                        : 'text-ink-600 hover:bg-ink-100'
                "
            >
                <span v-html="link.label" />
            </Link>
        </template>
    </nav>
</template>
