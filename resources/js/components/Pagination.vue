<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

defineProps<{
    links: PaginationLink[];
}>();
</script>

<template>
    <div v-if="links.length > 3" class="flex flex-wrap items-center gap-1">
        <template v-for="(link, i) in links" :key="i">
            <span
                v-if="!link.url"
                class="rounded-md px-3 py-1.5 text-sm text-muted-foreground/40"
                v-html="link.label"
            />
            <Link
                v-else
                :href="link.url"
                preserve-scroll
                class="rounded-md px-3 py-1.5 text-sm transition-colors"
                :class="
                    link.active
                        ? 'bg-primary text-primary-foreground'
                        : 'text-muted-foreground hover:bg-accent'
                "
            >
                <span v-html="link.label" />
            </Link>
        </template>
    </div>
</template>
